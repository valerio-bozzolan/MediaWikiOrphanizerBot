#!/usr/bin/php
<?php
# Copyright (C) 2019 Valerio Bozzolan, Daimona Eaytoy
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace itwikidelbot;

// die on whatever error
set_error_handler( function( $errno, $errstr, $errfile, $errline ) {
	if( error_reporting() !== 0 ) {
		throw new \ErrorException( $errstr, 0, $errno, $errfile, $errline );
	}
} );

// do not expose from web
isset( $argv[ 0 ] ) or exit( 1 );

// autoload classes
require __DIR__ . '/includes/autoload.php';

// require config file
file_exists( $config_path = __DIR__ . '/config.php' )
	or die( "Please provide the configuration file at $config_path\n" );

require $config_path;

// how much titles at time requesting - this is a MediaWiki limit
define( 'MAX_TRANCHE_TITLES', 50 );

// classes used
use \cli\Input;
use \cli\Opts;
use \cli\ParamFlag;
use \cli\ParamValued;
use \cli\Log;
use \web\MediaWikis;
use \mw\Wikilink;
use \mw\Ns;
use \mw\API\ProtectedPageException;
use \regex\Generic as Regex;

// register available options
$opts = Opts::instance()->register( [
	// register arguments with a value
	new ParamValued( 'wiki',           null, 'Specify a wiki from its UID' ),
	new ParamValued( 'cfg',            null, 'Title of an on-wiki configuration page with JSON content model' ),
	new ParamValued( 'list',           null, 'Specify a pagename that should contain the wikilinks to be orphanized' ),
	new ParamValued( 'summary',        null, 'Edit summary' ),
	new ParamValued( 'list-summary',   null, 'Edit summary for editing the list' ),
	new ParamValued( 'done-text',      null, 'Replacement for the wikilink in the list' ),
	new ParamValued( 'ns',             null, 'Namespace whitelist' ),
	new ParamValued( 'delay',          null, 'Additional delay between each edit' ),
	new ParamValued( 'warmup',         null, 'Start only if the last edit on the list was done at least $warmup seconds ago' ),
	new ParamValued( 'cooldown',       null, 'End early when reaching this number of edits' ),
	new ParamValued( 'seealso',        null, 'Title of your local "See also" section' ),

	// register arguments without a value
	new ParamFlag(   'debug',          null, 'Increase verbosity' ),
	new ParamFlag(   'help',           'h',  'Show this message and quit' ),
	new ParamFlag(   'no-interaction', null, 'Do not confirm every change' ),
] );

// show help screen
if( $opts->getArg( 'help' ) ) {
	show_help();
}

// cli-only parameters
$NO_INTERACTION = $opts->getArg( 'no-interaction' );
$TITLE_SOURCE   = $opts->getArg( 'list', 'Utente:OrfanizzaBot/Wikilink da orfanizzare' );

Log::info( "start" );

// increase verbosity
if( $opts->getArg( 'debug' ) ) {
	Log::$DEBUG = true;
}

// wiki instance
$wiki = Mediawikis::findFromUid( $opts->getArg( 'wiki', 'itwiki' ) );

// load the wiki config
wiki_config();

// parameters available both from cli and on-wiki
$SUMMARY      = option( 'summary', "Bot TEST: orfanizzazione voci eliminate in seguito a [[WP:RPC|consenso cancellazione]]" );
$LIST_SUMMARY = option( 'list-summary', "Aggiornamento elenco" );
$DONE_TEXT    = option( 'done-text', "* [[Special:WhatLinksHere/$1]] - {{done}}" );
$NS           = option( 'ns' );
$WARMUP       = option( 'warmup', -1 );
$COOLDOWN     = option( 'cooldown', 1000 );
$DELAY        = option( 'delay', 0 );
$SEEALSO      = option( 'seealso', 'Voci correlate' );

// query titles to be orphanized alongside the last revision of the list
$responses =
	$wiki->createQuery( [
		'action'  => 'query',
		'titles'  => $TITLE_SOURCE,
		'prop'    => [
			'links',
			'revisions',
		],
		'rvslots' => 'main',
		'rvprop'  => 'timestamp',
	] );

// collect links and take the last edit timestamp
$titles_to_be_orphanized = [];
Log::info( "reading $TITLE_SOURCE" );
foreach( $responses as $response ) {
	foreach( $response->query->pages as $page ) {

		// check if list is unexisting
		if( isset( $page->missing ) ) {
			Log::error( "missing list $TITLE_SOURCE" );
			exit( 1 );
		}

		// check warmup
		$timestamp = reset( $page->revisions )->timestamp;
		$timestamp = \DateTime::createFromFormat( \DateTime::ISO8601, $timestamp );
		$seconds = time() - $timestamp->getTimestamp();
		if( $seconds < $WARMUP ) {
			Log::info( "edited just $seconds seconds ago: quit until warmup $WARMUP" );
			exit( 1 );
		}

		// collect links (if any)
		if( isset( $page->links ) ) {
			foreach( $page->links as $link ) {
				// @TODO: does $link->title also contain the namespace itself? I think yes.
				$titles_to_be_orphanized[] = Ns::defaultCanonicalName( $link->ns ) . $link->title;
			}
		}
	}
}

// die if no links
if( ! $titles_to_be_orphanized ) {
	Log::info( 'empty list' );
	exit( 1 );
}

// keep a copy
$involved_pagetitles = $titles_to_be_orphanized;

// log titles
Log::info( 'read ' . count( $titles_to_be_orphanized ) . ' pages to be orphanized:' );
foreach( $titles_to_be_orphanized as $title ) {
	Log::info( " $title" );
}

// associative array of page IDs as key and a boolean as value containg pages to be orphanized
$involved_pageids = [];

// note that the API accepts a maximum trance of titles
while( $less_titles_to_be_orphanized = array_splice( $titles_to_be_orphanized, 0, MAX_TRANCHE_TITLES ) ) {

	// API arguments for the linkshere query
	$linksto_args = [
			'action'  => 'query',
			'titles'  => $less_titles_to_be_orphanized,
			'prop'    => 'linkshere',
			'lhprop'  => 'pageid',
			'lhshow'  => '!redirect',
			'lhlimit' => 300,
	];

	// limit to certain namespaces
	if( $NS !== null ) {
		$linksto_args[ 'lhnamespace' ] = implode( '|', $NS );
	}

	// cumulate the linkshere page ids
	Log::info( "requesting linkshere..." );
	$linksto = $wiki->createQuery( $linksto_args );
	foreach( $linksto as $response ) {
		foreach( $response->query->pages as $page ) {
			if( isset( $page->linkshere ) ) {
				foreach( $page->linkshere as $linkingpage ) {
					if ( $linkingpage->title !== $TITLE_SOURCE ) {
						$involved_pageids[] = (int) $linkingpage->pageid;
					}
				}
			}
		}
	}
}

// count of involved pages
Log::info( sprintf(
	"found %d pages containing the %d involved wlinks",
	count( $involved_pageids ),
	count( $involved_pagetitles )
) );

// number of edited pages
$edits = 0;

// note that the API accepts a maximum tranche of IDs
while( $less_involved_pageids = array_splice( $involved_pageids, 0, MAX_TRANCHE_TITLES ) ) {

	// query last revision
	$responses =
		$wiki->createQuery( [
			'action'  => 'query',
			'pageids' => $less_involved_pageids,
			'prop'    => 'revisions',
			'rvslots' => 'main',
			'rvprop'  => [
				'content',
				'timestamp',
			],
		] );

	// for each response
	foreach( $responses as $response ) {

		// for each page
		foreach( $response->query->pages as $page ) {

			// avoid too many edits
			if( $edits > $COOLDOWN ) {
				Log::info( "reached cooldown: stop" );
				exit( 0 );
			}

			// page ID to be edited
			$pageid = $page->pageid;

			// does it have a revision?
			if( !isset( $page->revisions[ 0 ] ) ) {
				continue;
			}

			// the first revision
			$revision = $page->revisions[ 0 ];

			// timestamp of the revision useful to avoid edit conflicts
			$timestamp = $revision->timestamp;

			// wikitext from the main slot of this revision
			$wikitext_raw = $revision->slots->main->{ '*' };

			// create a Wikitext object
			$wikitext = $wiki->createWikitext( $wikitext_raw );

			// for each of the titles to be orphanized
			foreach( $involved_pagetitles as $involved_pagetitle ) {

				// parse the title being orphanized
				$title = $wiki->createTitleParsing( $involved_pagetitle );

				// a wikilink with and without alias
				$wikilink_simple = $wiki->createWikilink( $title, Wikilink::NO_ALIAS );
				$wikilink_alias  = $wiki->createWikilink( $title, Wikilink::WHATEVER_ALIAS );

				// replace simple links e.g. [[Hello]]
				$wikilink_regex_simple = $wikilink_simple->getRegex( [
					'title-group-name' => 'title',
				] );

				// replace links with alias e.g. [[Hello|whatever]]
				$wikilink_regex_alias = $wikilink_alias->getRegex( [
					'alias-group-name' => 'alias',
				] );

				// replace entry from "See also" section
				$wikilink_regex_clean = $wikilink_simple->getRegex();
				$wikilink_regex_clean = Regex::spaceBurger( $wikilink_regex_clean );
				$seealso = preg_quote( $SEEALSO );
				$seealso_regex =
					'/' .
						Regex::groupNamed( "\\n== *$seealso *== *((?!=).*\n)*",            'keep'   ) .
						Regex::groupNamed( "[ \\t]*\*[ \\t]*{$wikilink_regex_clean}.*\\n", 'wlink'  ) .
					'/';

				Log::debug( "regex simple wikilink:" );
				Log::debug( $wikilink_regex_simple );

				Log::debug( "regex wikilink aliased:" );
				Log::debug( $wikilink_regex_alias );

				Log::debug( "regex see also:" );
				Log::debug( $seealso_regex );

				// strip out the entry from «See also» section
				$wikitext->pregReplaceCallback( $seealso_regex, function ( $matches ) {
					return $matches[ 'keep' ];
				} );

				// convert '[[Hello]]' to 'Hello'
				$wikitext->pregReplaceCallback( "/$wikilink_regex_simple/", function ( $matches ) {
					// fix unwanted indentations
					$title = ltrim( $matches[ 'title' ], ':' );
					return trim(  $title );
				} );

				// convert '[[Hello|world]]' to 'world'
				$wikitext->pregReplaceCallback( "/$wikilink_regex_alias/", function ( $matches ) {
					// fix unwanted indentations
					return trim( $matches[ 'alias' ] );
				} );
			}
			// end loop titles to be orphanized

			// check for changes and save
			if( $wikitext->isChanged() ) {
				Log::info( "changes on page $pageid:" );
				foreach( $wikitext->getHumanUniqueSobstitutions() as $substitution ) {
					Log::info( "\t $substitution" );
				}

				if( $NO_INTERACTION || 'n' !== Input::yesNoQuestion( "confirm changes" ) ) {
					try {

						// the entire world absolutely needs this shitty ASCII animation - trust me
						if( $edits && $DELAY ) {
							Log::info( "delay $DELAY seconds", [ 'newline' => false ] );
							for( $i = 0; $i < $DELAY; $i++ ) {
								sleep( 1 );
								echo '.';
							}
							echo "\n";
						}

						// eventually login and save
						$wiki->login()->edit( [
							'pageid'        => $pageid,
							'text'          => $wikitext->getWikitext(),
							'summary'       => $SUMMARY,
							'basetimestamp' => $timestamp,
							'minor'         => 1,
							'bot'           => 1,
						] );

						$edits++;

					} catch( ProtectedPageException $e ) {
						Log::warn( "skip protected page $pageid" );
					}
				}
				// end confirmation

			}
			// end save

		}
		// end loop pages

	}
	// end loop responses

}
// end loop involved page IDs

// done! remove the link from the list
Log::info( "removing orphanized pages from list" );

// fetch a fresh version of the list
$response =
	$wiki->fetch( [
		'action'  => 'query',
		'titles'  => $TITLE_SOURCE,
		'prop'    => 'revisions',
		'rvprop'  => 'content',
		'rvslots' => 'main',
	] );

// content of the list
$content = reset( $response->query->pages )->revisions[0]->slots->main->{ '*' };
$wikitext = $wiki->createWikitext( $content );

// remove each entry from the list
foreach( $involved_pagetitles as $title_raw ) {

	$wlink = $wiki->createTitleParsing( $title_raw )
	              ->createWikilink( Wikilink::WHATEVER_ALIAS )
	              ->getRegex();

	// strip out the whole related line and replace with something else
	$from = "/.*$wlink.*/";

	// @todo In case done-text contains the full link to a page, and it has already been
	// replaced in a previous run, don't replace it again.
	$to = str_replace( '$1', $title_raw, $DONE_TEXT );

	$wikitext->pregReplace( $from, $to );
}

// update list
if( $wikitext->isChanged() ) {
	try {
		$wiki->login()->edit( [
			'title'   => $TITLE_SOURCE,
			'text'    => $wikitext->getWikitext(),
			'summary' => $LIST_SUMMARY,
			'bot'     => 1,
		] );
	} catch( ProtectedPageException $e ) {
		Log::warn( "can't update list because of protection" );
	}
}


Log::info( "end" );
