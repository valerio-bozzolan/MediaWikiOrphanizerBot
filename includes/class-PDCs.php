<?php
# Copyright (C) 2018 Valerio Bozzolan
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

use DateTime;
use wm\WikipediaIt;
use mw\API\PageMatcher;

/**
 * Handle some PDCs
 */
class PDCs {

	/**
	 * Sort some PDCs by creation date
	 *
	 * @param $pdcs array
	 */
	public static function sortByCreationDate( & $pdcs ) {
		// sort PDCs by start date
		usort( $pdcs, function ( $a, $b ) {
			return $a->getCreationDate() > $b->getCreationDate();
		} );
	}

	/**
	 * Index some PDCs by their type
	 *
	 * @param $pdcs array
	 * @return array
	 */
	public static function indexByType( $pdcs ) {
		$pdcs_by_type = [];
		foreach( CategoryYearMonthDayTypes::all() as $Type ) {
			$pdcs_by_type[ $Type::PDC_TYPE ] = [];
		}
		foreach( $pdcs as $pdc ) {
			$type = $pdc->getType();
			$pdcs_by_type[ $type ][] = $pdc;
		}
		return $pdcs_by_type;
	}

	/**
	 * Discard all the PDCs that do not belong to a certain date
	 *
	 * @param $pdcs array
	 * @param $date DateTime
	 * @return array
	 */
	public static function filterByDate( $pdcs, DateTime $date ) {
		$y_m_d = $date->format( 'Y-m-d' );
		return array_filter( $pdcs, function ( $pdc ) use ( $y_m_d ) {
			return $pdc->getStartDate()->format( 'Y-m-d' ) === $y_m_d;
		} );
	}

	/**
	 * Discard all the PDCs that are not multiple
	 *
	 * @param $pdcs array
	 * @return $pdcs
	 */
	public static function filterNotMultiple( $pdcs ) {
		return array_filter( $pdcs, function ( $pdc ) {
			return ! $pdc->isMultiple();
		} );
	}

	/**
	 * Populate the PDC 'subject themes' field of some PDCs
	 *
	 * This method is intended to do less API requests as possible.
	 *
	 * @param $pdcs array
	 * @return array
	 * @see PDC::getSubjectThemes()
	 */
	public static function populateSubjectThemes( $pdcs ) {

		// only non multiple PDCs
		$pdcs = self::filterNotMultiple( $pdcs );

		// subject titles
		$titles = array_map( function ( $pdc ) {
			return $pdc->getTitleSubject();
		}, $pdcs );

		// API to retrieve the wikitext in the first section of all the pages
		$site = WikipediaIt::getInstance();
		$query = $site->createQuery( [
			'action'    => 'query',
			'titles'    => $titles,
			'prop'      => 'revisions',
			'rvprop'    => 'content',
			'rvsection' => '0',
		] );

		foreach( $query->getGenerator() as $response ) {
			( new PageMatcher( $response->query, $pdcs ) )->matchByTitle(
				// callback fired for every match between response pages and PDCs
				function ( $page, $pdc ) {
					if( isset( $page->revisions ) ) {
						$page_content = $page->revisions[ 0 ]->{ '*' };
						$pdc->setSubjectThemesScrapingSubjectWikitext( $page_content );
					}
				},
				// callback that must returns the PDC page title
				function ( $pdc ) {
					return $pdc->getTitleSubject();
				}
			);
		}
	}

}
