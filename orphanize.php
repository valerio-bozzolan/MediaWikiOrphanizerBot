#!/usr/bin/php
<?php
# Copyright (C) 2020-2024 Valerio Bozzolan and contributors
# Copyright (C) 2019      Valerio Bozzolan, Daimona Eaytoy and contributors
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

namespace orphanizerbot;

// die on whatever error
// disabled since this may be problematic - sometime the script is stuck in unclear state.
#set_error_handler( function( $errno, $errstr, $errfile, $errline ) {
#	if( error_reporting() !== 0 ) {
#		throw new \ErrorException( $errstr, 0, $errno, $errfile, $errline );
#	}
#} );

// do not expose from web
isset( $argv[ 0 ] ) or exit( 1 );

// autoload classes
require __DIR__ . '/includes/autoload.php';

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
use \mw\API;
use \mw\API\ProtectedPageException;
use \mw\API\EditConflictException;
use \regex\Generic as Regex;

// register available options
$opts = Opts::instance()->register( [
	// register arguments with a value
	new ParamValued( 'wiki',               null, 'Specify a wiki from its UID' ),
	new ParamValued( 'cfg',                null, 'Title of an on-wiki configuration page with JSON content model' ),
	new ParamValued( 'list',               null, 'Specify a pagename that should contain the wikilinks to be orphanized' ),
	new ParamValued( 'summary',            null, 'Edit summary' ),
	new ParamValued( 'list-summary',       null, 'Edit summary for editing the list' ),
	new ParamValued( 'done-text',          null, 'Replacement for the wikilink in the list' ),
	new ParamValued( 'ns',                 null, 'Namespace allow-list (numeric values separated by pipe)' ),
	new ParamValued( 'delay',              null, 'Additional delay between each edit' ),
	new ParamValued( 'warmup',             null, 'Start only if the last edit on the list was done at least $warmup seconds ago' ),
	new ParamValued( 'cooldown',           null, 'End early when reaching this number of edits' ),
	new ParamValued( 'turbofresa',         null, 'If the list is older than this number of seconds a turbofresa will be spawned to clean the list' ),
	new ParamValued( 'turbofresa-text',    null, 'Text that will be saved to clean an old list' ),
	new ParamValued( 'turbofresa-summary', null, 'Edit summary to be used when cleaning an old list' ),
	new ParamValued( 'seealso',            null, 'Title of your local "See also" section' ),

	// register arguments without a value
	new ParamFlag(   'skip-permissions',   null, 'Execute the bot even if the list was last edited by a non-sysop (or by the bot itself)' ),
	new ParamFlag(   'debug',              null, 'Increase verbosity' ),
	new ParamFlag(   'help',               'h',  'Show this message and quit' ),
	new ParamFlag(   'no-interaction',     null, 'Do not confirm every change' ),
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
	bozmw_debug();
}

// wiki uid (from command line or from configuration file)
$wiki_uid = Config::instance()->get( 'wiki' );
$wiki_uid = $opts->getArg( 'wiki', $wiki_uid );
if( ! $wiki_uid ) {
	Log::error( "please choose the wiki! exit" );
	exit( 1 );
}

// wiki instance
$wiki = Mediawikis::findFromUid( $wiki_uid );

// try to load the wiki config
try {
	wiki_config();
} catch( \Exception $e ) {

	// I don't have any clue about this but sometime happen
	Log::error( sprintf(
		"failed reading wiki configuration: %s",
		$e->getMessage()
	) );

	exit( 1 );
}

// parameters available both from cli and on-wiki
$SUMMARY            = option( 'summary',      "Bot: pages orphanization" );
$LIST_SUMMARY       = option( 'list-summary', "Bot: orphanization list update" );
$DONE_TEXT          = option( 'done-text',    "* [[Special:WhatLinksHere/$1]] - {{done}}" );
$NS                 = option( 'ns' );
$WARMUP             = option( 'warmup', -1 );
$COOLDOWN           = option( 'cooldown', 1000 );
$DELAY              = option( 'delay', 0 );
$SEEALSO            = option( 'seealso', "See also" );
$TURBOFRESA         = option( 'turbofresa', 86400 );
$TURBOFRESA_TEXT    = option( 'turbofresa-text', "== List ==\n* ..." );
$TURBOFRESA_SUMMARY = option( 'turbofresa-summary', "Bot: list clean" );
$SKIP_PERMISSIONS   = option( 'skip-permissions' );

// hardcoded values (@TODO: consider an option)
$GROUP        = 'sysop';

// my username
// used to discover if we are the last user who edited something
$ME = explode( '@', API::$DEFAULT_USERNAME, 2 )[ 0 ];

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
		'rvprop'  => [
			'comment',   // the edit summary is used to detect if the list was already cleaned
			'user',      // the username     is used to detect if the last user is allowed
			'timestamp', // the timestamp    is used to check the age of the last edit
			'content',   // page content
		],
	] );

// remember this to avoid edit conflicts
$list_timestamp = null;
$list_content = null;

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

		if( isset( $page->revisions ) ) {
			// there is just one revision
			$revision = reset( $page->revisions );

			// save list content
			$list_content = $revision->slots->main->{ '*' };

			// check warmup
			$list_timestamp = $revision->timestamp;
			$timestamp_datetime = \DateTime::createFromFormat( \DateTime::ISO8601, $list_timestamp );
			$seconds = time() - $timestamp_datetime->getTimestamp();
			if( $seconds < $WARMUP ) {
				Log::info( "list edited just $seconds seconds ago: quit until warmup $WARMUP" );
				exit( 1 );
			}

			// eventually clear list
			if( $seconds > $TURBOFRESA ) {
				if( $revision->comment === $TURBOFRESA_SUMMARY ) {
					Log::info( "list edited $seconds seconds ago. already cleared. quit" );
				} else {
					Log::info( "list edited $seconds seconds ago. spawning a turbofresa to clear the list. quit" );

					// TODO: dedicated customizable summary
					// TODO: customizable content
					$wiki->login()->edit( [
						'title'         => $TITLE_SOURCE,
						'summary'       => $TURBOFRESA_SUMMARY,
						'text'          => $TURBOFRESA_TEXT,
						'basetimestamp' => $list_timestamp,
						'bot'           => 1,
					] );

				}

				exit( 0 );
			}

			// check user
			$lastuser = $revision->user;
			$rights =
				$wiki->fetch( [
					'action'  => 'query',
					'list'    => 'users',
					'usprop'  => 'groups',
					'ususers' => $lastuser,
				] );

			// warn about that above user and eventually quit
			$lastuser_was = "$lastuser was the last editor: ";
			$groups = reset( $rights->query->users )->groups;
			if( in_array( $GROUP, $groups, true ) ) {
				Log::info( $lastuser_was . "a $GROUP. OK" );
			} else {
				// show a friendly message if it's just me
				$its_me = $wiki->isLogged() && $lastuser === $wiki->getUsername() || $lastuser === $ME;
				if( $its_me ) {
					Log::info( $lastuser_was . "It's-a me, Mario! quit" );
				} else {
					Log::error( $lastuser_was . "not a $GROUP. quit" );
				}

				if( $SKIP_PERMISSIONS ) {
					Log::warn( "skip list permission check because of 'skip-permissions' option enabled" );
				} else {
					// it's me? exit normally.
					exit( $its_me ? 0 : 1 );
				}
			}
		}

		// collect links (if any)
		if( isset( $page->links ) ) {
			foreach( $page->links as $link ) {
				$titles_to_be_orphanized[] = $link->title;
			}
		}
	}
}

// keep a copy
$involved_pagetitles = $titles_to_be_orphanized;

// log titles
if( $titles_to_be_orphanized ) {
	Log::info( 'found ' . count( $titles_to_be_orphanized ) . ' pages to be orphanized:' );
	foreach( $titles_to_be_orphanized as $title ) {
		Log::info( " $title" );
	}
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
			'lhprop'  => [
				'pageid',
				'title',
			],
			'lhshow'  => '!redirect',
			'lhlimit' => 300,
	];

	// limit to certain namespaces from command line
	if( $NS !== null ) {
		$linksto_args[ 'lhnamespace' ] = $NS;
	}

	// cumulate the linkshere page ids
	Log::info( "requesting linkshere..." );
	$linksto = $wiki->createQuery( $linksto_args );
	foreach( $linksto as $response ) {
		foreach( $response->query->pages as $page ) {
			if( isset( $page->linkshere ) ) {
				foreach( $page->linkshere as $linkingpage ) {
					if( $linkingpage->title !== $TITLE_SOURCE ) {
						$involved_pageids[] = (int) $linkingpage->pageid;
					}
				}
			}
		}
	}
}

// count of involved pages
if( $involved_pagetitles ) {
	Log::info( sprintf(
		"found %d pages containing the %d involved wlinks",
		count( $involved_pageids ),
		count( $involved_pagetitles )
	) );
}

// List of pages that we have not changed, for example for an edit conflict.
// Better to re-process these later.
$incomplete_pagetitles = [];

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

			// array of pagetitles really involved by a real edit
			// this is used for the summary
			$involved_pagetitle_toucheds = [];

			// for each of the titles to be orphanized
			foreach( $involved_pagetitles as $involved_pagetitle ) {

				// check if this pagetitle was really involved
				$involved_pagetitle_touched = false;

				// parse the title being orphanized
				$title = $wiki->createTitleParsing( $involved_pagetitle );

				// if it's a category, remove it
				if( $title->getNs()->isCategory() ) {

					// try to remove the Category
					if( $wikitext->removeCategory( $title->getTitle() ) ) {

						// yeah! touched
						$involved_pagetitle_touched = true;
					}
				}

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

				// a non-capturing group to catch a single newline, or the end of the string
				$NEWLINE_OR_END = '(?:\n|$)';

				// replace entry from "See also" section
				$wikilink_regex_clean = $wikilink_simple->getRegex();
				$wikilink_regex_clean = Regex::spaceBurger( $wikilink_regex_clean );
				$seealso = preg_quote( $SEEALSO );
				$seealso_regex =
					'/' .
						Regex::groupNamed( "\\n== *$seealso *== *((?!=).*\\n)*",           'keep'  ) .
						Regex::groupNamed( "[ \\t]*\*[ \\t]*{$wikilink_regex_clean}.*$NEWLINE_OR_END", 'wlink' ) .
					'/';

				Log::debug( "regex see also:" );
				Log::debug( $seealso_regex );

				Log::debug( "regex simple wikilink:" );
				Log::debug( $wikilink_regex_simple );

				Log::debug( "regex wikilink aliased:" );
				Log::debug( $wikilink_regex_alias );

				// strip out the entry from «See also» section
				$wikitext->pregReplaceCallback( $seealso_regex, function ( $matches ) use ( & $involved_pagetitle_touched ) {

					// yeah! touched
					$involved_pagetitle_touched = true;

					return $matches[ 'keep' ];
				} );

				// convert '[[Hello]]' to 'Hello'
				$wikitext->pregReplaceCallback( "/$wikilink_regex_simple/", function ( $matches ) use ( & $involved_pagetitle_touched ) {

					// yeah! touched
					$involved_pagetitle_touched = true;

					// fix unwanted indentations
					$title = ltrim( $matches[ 'title' ], ':' );
					return trim( $title );
				} );

				// convert '[[Hello|world]]' to 'world'
				$wikitext->pregReplaceCallback( "/$wikilink_regex_alias/", function ( $matches ) use ( & $involved_pagetitle_touched ) {

					// yeah! touched
					$involved_pagetitle_touched = true;

					// fix unwanted indentations
					return trim( $matches[ 'alias' ] );
				} );

				// add this title to the list of touched titles (to build a cute summary)
				if( $involved_pagetitle_touched ) {
					$involved_pagetitle_toucheds[] = $title->getCompleteTitle();
				}
			}
			// end loop titles to be orphanized

			// check for changes and save
			if( $wikitext->isChanged() ) {

				// build a cute summary
				$summary = $SUMMARY;

				// append to the summary a list of deleted pages "-Foo -Bar -Etc"
				if( $involved_pagetitle_toucheds ) {
					$summary .= ':';
					foreach( $involved_pagetitle_toucheds as $involved_pagetitle_touched ) {
						$summary .= " -$involved_pagetitle_touched";
					}
				}

				Log::info( "changes on page $pageid:" );
				Log::info( "  summary: $summary" );

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
							'summary'       => $summary,
							'basetimestamp' => $timestamp,
							'minor'         => 1,
							'bot'           => 1,
						] );

						$edits++;

					} catch( ProtectedPageException $e ) {
						// A protected page cannot be processed by the bot. Just silently ignore.
						// Probably also a human being will ignore this case. Or, they will just
						// manually double-check the [[Special:WhatLinksHere]].
						Log::warn( sprintf(
							"skip protected page [[%s]]",
							$title->getCompleteTitle()
						) );
					} catch( EditConflictException $e ) {
						Log::warn( sprintf(
							"skip edit conflict on page [[%s]]",
							$title->getCompleteTitle()
						) );

						// Better to re-process this page soon.
						$incomplete_pagetitles[] = $involved_pagetitle;
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

// content of the list
$wikitext = $wiki->createWikitext( $list_content );

// remove each entry from the list if it was processed successfully
foreach( $involved_pagetitles as $title_raw ) {
	$is_page_still_needing_edit = in_array( $title_raw, $incomplete_pagetitles );
	if( !$is_page_still_needing_edit) {

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
}

// update list
if( $wikitext->isChanged() ) {
	Log::info( "removing orphanized pages from list" );

	try {
		$wiki->login()->edit( [
			'title'         => $TITLE_SOURCE,
			'text'          => $wikitext->getWikitext(),
			'summary'       => $LIST_SUMMARY,
			'basetimestamp' => $list_timestamp,
			'bot'           => 1,
		] );
	} catch( ProtectedPageException $e ) {
		Log::warn( "can't update list because of protection" );
	} catch( EditConflictException $e ) {
		Log::warn( "ARGHHHH! Is someone editing my list? MY PRECIOUSss LIST!?!? WHAAT?? I will find you, and I will rewrite your edit. Damn human beings... asd." );
	}
} else {
	Log::info( "nothing to be done" );
}

Log::info( "end" );
