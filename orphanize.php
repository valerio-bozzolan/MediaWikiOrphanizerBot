#!/usr/bin/php
<?php
# Copyright (C) 2019 Valerio Bozzolan
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
	'help'
] );

// show help
if( isset( $opts[ 'h' ] ) || isset( $opts[ 'help' ] ) ) {
	echo "Welcome in your MediaWiki Orphanizer bot!\n\n"                      .
	     " Usage:   {$argv[0]} [OPTIONS]\n"                                   .
	     " Options: --wiki UID          Specify a wiki from it's UID.\n"      .
	     "          --list PAGENAME     Specify a pagename that should\n"     .
	     "                              contain the wikilinks to be\n"        .
	     "                              orphanized by this bot.\n"            .
	     "          --summary TEXT      Edit summary.\n"                      .
	     "          --help              Show this message and quit.\n"        .
	     " Example:\n"                                                        .
	     "          {$argv[0]} --wiki itwiki --list Wikipedia:PDC/Elenco\n\n" .
	     " Have fun! by Valerio Bozzolan\n"                                   ;
	exit( 1 );
}

// title source
$TITLE_SOURCE =
	isset( $opts[ 'list' ] )
	     ? $opts[ 'list' ]
	     : 'Utente:.avgas/Wikilink da orfanizzare';

// edit summary
$SUMMARY =
	isset( $opts[ 'summary' ] )
	     ? $opts[ 'summary' ]
	     : "Bot TEST: orfanizzazione voci eliminate in seguito a [[WP:RPC|consenso cancellazione]]";

// how much titles at time requesting - this is a MediaWiki limit
define( 'MAX_TRANCHE_TITLES', 50 );

// classes used
use \cli\Log;
use \cli\Input;
use \web\MediaWikis;
use \mw\Wikilink;

// wiki identifier
$wiki_uid =
	isset( $opts[ 'wiki' ] )
		? $opts[ 'wiki' ]
		: 'itwiki';

// wiki instance
$wiki = Mediawikis::findFromUid( $wiki_uid );

// query last revision
Log::info( "reading $TITLE_SOURCE" );
$revision =
	$wiki->fetch( [
		'action'  => 'query',
		'prop'    => 'revisions',
		'titles'  => $TITLE_SOURCE,
		'rvslots' => 'main',
		'rvprop'  => 'content',
		'rvlimit' => 1,
	] );

// array of page titles to be orphanized
$involved_pagetitles = [];

// associative array of page IDs as key and a boolean as value containg pages to be orphanized
$involved_pageids = [];

// for each page (well, just one)
foreach( $revision->query->pages as $sourcepage ) {

	// for each revision (well, just one)
	foreach( $sourcepage->revisions as $revision ) {

		// pure wikitext
		$wikitextRaw = $revision->slots->main->{ '*' };

		// wikitext object
		$wikitext = $wiki->createWikitext( $revision->slots->main->{ '*' } );

		// identify wikilinks
		$n = $wikitext->pregMatchAll( '~\[\[(.*?)\]\]~', $matches );
		if( $n === false ) {
			Log::error( 'wtf' );
			exit( 1 );
		}

		// collect these titles
		$titles_to_be_orphanized = [];
		for( $i = 0; $i < $n; $i++ ) {

			// can be both 'title' and 'title|alias'
			$wlink = $matches[ 1 ][ $i ];

			// just the page title
			$title = explode( '|', $wlink )[0];

			// normalize titles
			$title = str_replace( '_', ' ', ucfirst( $title ) );

			// append
			$titles_to_be_orphanized[] = $title;
		}

		// drop duplicates
		$titles_to_be_orphanized = array_unique( $titles_to_be_orphanized );

		// order
		sort( $titles_to_be_orphanized, SORT_STRING );

		// keep a copy
		$involved_pagetitles = $titles_to_be_orphanized;

		// log titles
		Log::info( "read $n pages to be orphanized:" );
		foreach( $titles_to_be_orphanized as $title ) {
			Log::info( " $title" );
		}

		// note that the API accepts a maximum trance of titles
		while( $less_titles_to_be_orphanized = array_splice( $titles_to_be_orphanized, 0, MAX_TRANCHE_TITLES ) ) {

			// for each of these titles, query linksto
			$linksto =
				$wiki->createQuery( [
					'action'      => 'query',
					'titles'      => $less_titles_to_be_orphanized,
					'prop'        => 'linkshere',
					'lhprop'      => 'pageid',
					'lhnamespace' => 0,
					'lhlimit'     => 300,
				] );

			// cumulate the linkshere page ids
			Log::info( "requesting linkshere..." );
			foreach( $linksto as $response ) {
				foreach( $response->query->pages as $page ) {
					if( isset( $page->linkshere ) ) {
						foreach( $page->linkshere as $linkingpage ) {
							$pageid = (int) $linkingpage->pageid;
							$involved_pageids[ $pageid ] = false;
						}
					}
				}
			}

		}
	}
}

// create a clean array or page ids
$involved_pageids = array_keys( $involved_pageids );

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
			if( isset( $page->revisions[ 0 ] ) ) {

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

					// sobstitute simple links e.g. [[Hello]]
					$wikilink_regex_simple = $wikilink_simple->getRegex( [
						'title-group-name' => 'title',
					] );

					// sobstitute links with alias e.g. [[Hello|whatever]]
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
					Log::info( "changes:" );
					foreach( $wikitext->getHumanUniqueSobstitutions() as $sobstitution ) {
						Log::info( "\t $sobstitution" );
					}
					if( 'n' !== Input::yesNoQuestion( "confirm changes" ) ) {
						$wiki->login()->edit( [
							'pageid'    => $pageid,
							'text'      => $wikitext->getWikitext(),
							'summary'   => $SUMMARY,
							'timestamp' => $timestamp,
							'minor'     => 1,
							'bot'       => 1,
						] );
					}
					// end confirmation

				}
				// end save

			}
			// end revision check

		}
		// end loop pages

	}
	// end loop responses

}
// end loop involved page IDs
