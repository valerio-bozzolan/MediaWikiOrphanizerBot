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

// report every error
error_reporting( E_STRICT );

// autoload classes and configuration
require __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'autoload.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

// how much titles at time requesting - this is a MediaWiki limit
define( 'MAX_TRANCHE_TITLES', 50 );

//$TITLE_SOURCE = 'Utente:.avgas/Wikilink da orfanizzare';
$TITLE_SOURCE = 'Utente:Valerio Bozzolan';

// classes used
use \cli\Log;
use \web\MediaWikis;

// wiki identifier
$wiki_uid = 'itwiki';

// wiki instance
$wiki = Mediawikis::findFromUid( $wiki_uid );

// query last revision
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
		Log::info( "Pages to be orphanized:" );
		foreach( $titles_to_be_orphanized as $title ) {
			Log::info( $title );
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
					'lhlimit'     => 500,
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
	$queries =
		$wiki->createQuery( [
			'action'  => 'query',
			'pageids' => $less_involved_pageids,
			'prop'    => 'revisions',
			'rvslots' => 'main',
			'rvprop'  => 'content',
		] );

	// for each revision query
	foreach( $queries as $query ) {

		// page wikitext raw
		$wikitext_raw = $page->revisions[ 0 ]->revision->slots->main->{ '*' };

		// wikitext object
		$wikitext = $wiki->createWikitext( $wikitext_raw );

		// orphanize these titles
		foreach( $involved_pagetitles as $title_complete ) {

			// spaces can be underscores
			$title_complete = str_replace( ' ', '[ _]', $title_complete );

			// the title can contain a namespace
			$ns = '';
			$parts = explode( ':', $title_complete, 2 );
			if( count( $parts ) === 2 ) {
				$ns    = $parts[ 0 ];
				$title = $parts[ 1 ];

				if( $ns ) {
					$ns .= "$ns:";
				}
			}

			// verify the namespace
			if( $ns ) {
				if( $ns_object = $wiki->findNamespace( $ns ) ) {
					$ns = $ns_object->getRegex();
				} else {
					// Oh, that was not a namespace but title part
					$title = "$ns:$title";
					$ns = '';
				}
			}

			// sanitize title and namespace
			$ns    = preg_quote( $ns );
			$title = preg_quote( $title );

			$wikitext->pregReplace(
				'/\[\[ *' . $title . ' *\]\]/'
			);
		}
	}

}
