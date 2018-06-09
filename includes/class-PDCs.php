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

		/*
	 	 * Pattern for the args in {{Cancellazione|arg=|arg2=}}
		 *
		 * This regex is a bit "repetitive" because PCRE does not support to
		 * match a group multiple time. It only support to match the entire regex
	 	 * multiple time.
		 */
		$SPACES = '[ \t\n]*';
		$ARG = '([a-zA-Z0-9.\- \/àèìòùÀÈÌÒÙ]+)';
		$PATTERN = '/' .
			'{{' . $SPACES . '[Cc]ancellazione' . $SPACES .
				'(?:' . // match without creating a group
					'\|' . $SPACES .
						'arg2?' . $SPACES .
							'=' . $SPACES .
								$ARG . $SPACES .
				')?' . $SPACES .
				'(?:' . // match without creating a group
					'\|' . $SPACES .
						'arg2?' . $SPACES .
							'=' . $SPACES .
								$ARG . $SPACES .
				')?' . $SPACES .
			'}}/';

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

			// all normalized PDC titles
			$normalizeds = [];
			if( isset( $response->query->normalized ) ) {
				foreach( $response->query->normalized as $fromto ) {
					$normalizeds[ $fromto->from ] = $fromto->to;
				}
			}

			foreach( $response->query->pages as $id => $page ) {

				// avoid unexisting pages
				if( ! isset( $page->revisions ) ) {
					continue;
				}

				// page content (only the section 0)
				$page_content = $page->revisions[ 0 ]->{ '*' };

				// find the matching PDC
				foreach( $pdcs as $i => $pdc ) {

					// normalized PDC title
					$title = $pdc->getTitleSubject();
					if( isset( $normalizeds[ $title ] ) ) {
						$title = $normalizeds[ $title ];
					}

					// does the normalized PDC title match the page title?
					if( $title === $page->title ) {

						// find the {{cancellazione|arg=|arg2=}}
						preg_match( $PATTERN, $page_content, $matches );
						for( $j = 1; $j < count( $matches ); $j++ ) {
							$pdc->addSubjectTheme( trim( $matches[ $j ] ) );
						}

						// do not fill again the themes of this PDC
						unset( $pdcs[ $i ] );

						// next page
						break;
					}
				}
			}
		}
	}
}
