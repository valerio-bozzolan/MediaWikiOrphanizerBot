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

// allowed options
$opts = getopt( 'h', [
	'wiki:',
	'list:',
	'summary:',
	'no-interaction',
	'debug',
	'help',
] );

// show help
if( isset( $opts[ 'h' ] ) || isset( $opts[ 'help' ] ) ) {
	echo "Welcome in your MediaWiki Orphanizer bot!\n\n"                      .
	     " Usage:   {$argv[0]} [OPTIONS]\n"                                   .
	     " Options: --wiki UID          Specify a wiki from it's UID.\n"      .
	     "          --list PAGENAME     Specify a pagename that should\n"     .
	     "                              contain the wikilinks to be\n"        .
	     "                              orphanized by this bot.\n"            .
	     "          --cfg PAGENAME      Read the config from the specified\n" .
	     "                              wikipage\n"                           .
	     "          --no-interaction    do not confirm every change\n"        .
	     "          --debug             increase verbosity\n"                 .
	     "          --help              Show this message and quit.\n"        .
	     " Example:\n"                                                        .
	     "          {$argv[0]} --wiki itwiki --list Wikipedia:PDC/Elenco\n\n" .
	     " Have fun! by Valerio Bozzolan\n"                                   ;
	exit( 1 );
}

// disable interaction
$NO_INTERACTION = isset( $opts[ 'no-interaction' ] );

// title source
$TITLE_SOURCE =
	isset( $opts[ 'list' ] )
	     ? $opts[ 'list' ]
	     : 'Utente:.avgas/Wikilink da orfanizzare';

// cfg page
$CFG_PAGE =
	isset( $opts[ 'cfg' ] )
	     ? $opts[ 'cfg' ]
	     : 'Utente:OrfanizzaBot/Configurazione';

// how much titles at time requesting - this is a MediaWiki limit
define( 'MAX_TRANCHE_TITLES', 50 );

// classes used
use \cli\Log;
use \cli\Input;
use \web\MediaWikis;
use \mw\Wikilink;
use \mw\Ns;
use \mw\API\ProtectedPageException;

// increase verbosity
if( isset( $opts[ 'debug' ] ) ) {
	Log::$DEBUG = true;
}

// wiki identifier
$wiki_uid =
	isset( $opts[ 'wiki' ] )
	     ? $opts[ 'wiki' ]
	     : 'itwiki';

// wiki instance
$wiki = Mediawikis::findFromUid( $wiki_uid );

// retrieve config page (that's a JSON page)
Log::info( "reading $CFG_PAGE" );
$cfgRevs =
	$wiki->fetch( [
		'action'  => 'query',
		'titles'  => $CFG_PAGE,
		'prop'    => 'revisions',
		'rvslots' => 'main',
		'rvprop'  => 'content',
	] );

$cfgRev = reset( $cfgRevs->query->pages )->revisions[0];
if ( $cfgRev->slots->main->contentmodel !== 'json' ) {
	Log::error( 'The cfg page must have JSON content model.' );
	exit( 1 );
}
$cfg = json_decode( $cfgRev->slots->main->{ '*' } );

// edit summary
$SUMMARY =
	isset( $cfg->summary )
	     ? $cfg->summary
	     : "Bot TEST: orfanizzazione voci eliminate in seguito a [[WP:RPC|consenso cancellazione]]";

// limit to a certain namespace (default is every namespace)
$NS =
	isset( $cfg->ns )
	     ? $cfg->ns
	     : null;

// number of seconds of pause after last edit to the list (default is immediately)
$WARMUP =
	isset( $cfg->warmup )
	     ? $cfg->warmup
	     : -1;

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

		// check warmup
		$timestamp = reset( $page->revisions )->timestamp;
		$timestamp = \DateTime::createFromFormat( \DateTime::ISO8601, $timestamp );
		$seconds = time() - $timestamp->getTimestamp();
		if( $seconds < $WARMUP ) {
			Log::info( "edited just $seconds seconds ago: quit until warmup $WARMUP" );
			exit( 1 );
		}

		// collect links
		foreach( $page->links as $link ) {
			$titles_to_be_orphanized[] = Ns::defaultCanonicalName( $link->ns ) . $link->title;
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
					$involved_pageids[] = (int) $linkingpage->pageid;
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

				// parse the orphanizing title
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

				Log::debug( "regex simple wikilink:" );
				Log::debug( $wikilink_regex_simple );

				Log::debug( "regex wikilink aliased:" );
				Log::debug( $wikilink_regex_alias );

				// convert '[[Hello]]' to 'Hello'
				$wikitext->pregReplaceCallback( "/$wikilink_regex_simple/", function ( $matches ) {
					return $matches[ 'title' ];
				} );

				// convert '[[Hello|world]]' to 'world'
				$wikitext->pregReplaceCallback( "/$wikilink_regex_alias/", function ( $matches ) {
					return $matches[ 'alias' ];
				} );
			}
			// end loop titles to be orphanized

			// check for changes and save
			if( $wikitext->isChanged() ) {
				Log::info( "changes on page $pageid:" );
				foreach( $wikitext->getHumanUniqueSobstitutions() as $sobstitution ) {
					Log::info( "\t $sobstitution" );
				}
				if( $NO_INTERACTION || 'n' !== Input::yesNoQuestion( "confirm changes" ) ) {
					try {

						// eventually login and save
						$wiki->login()->edit( [
							'pageid'    => $pageid,
							'text'      => $wikitext->getWikitext(),
							'summary'   => $SUMMARY,
							'timestamp' => $timestamp,
							'minor'     => 1,
							'bot'       => 1,
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
