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

/**
 * PDCs handler
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
	 */
	public static function filterByDate( $pdcs, DateTime $date ) {
		$y_m_d = $date->format( 'Y-m-d' );
		return array_filter( $pdcs, function ( $pdc ) use ( $y_m_d ) {
			return $pdc->getStartDate()->format( 'Y-m-d' ) === $y_m_d;
		} );
	}

}
