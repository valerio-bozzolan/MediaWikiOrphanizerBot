<?php
# it.wiki deletion bot in PHP
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

/**
 * Abstraction of a page containing PDCs of a day
 */
abstract class PageYearMonthDayPDCs extends PageYearMonthDay {

	/**
	 * PDCs indexed by type
	 *
	 * @var array
	 */
	private $pdcsByType;

	/**
	 * Constructor
	 *
	 * @param $year int
	 * @param $month int 1-12
	 * @param $day int 1-31
	 * @param $pdcs_by_type array PDCs by type
	 * @see PageYearMonthDay::__construct()
	 */
	public function __construct( $year, $month, $day, $pdcs_by_type = [] ) {
		$this->pdcsByType = $pdcs_by_type;
		parent::__construct( $year, $month, $day );
	}

	/**
	 * Static constructor
	 *
	 * @param $datetime DateTime
	 * @param $pdcs_by_type PDCs by type
	 * @return self
	 */
	public static function createFromDateTimePDCs( $datetime, $pdcs_by_type = [] ) {
		return new static( $datetime->format('Y'), $datetime->format('m'), $datetime->format('d'), $pdcs_by_type );
	}

	/**
	 * Get all the PDCs
	 *
	 * @return array
	 */
	public function getPDCs() {
		$all = [];
		foreach( $this->pdcsByType as $pdcs ) {
			foreach( $pdcs as $pdc ) {
				$all[] = $pdc;
			}
		}
		return $all;
	}

	/**
	 * Get the PDCs indexed by type
	 *
	 * @return array
	 */
	public function getPDCsByType() {
		return $this->pdcsByType;
	}

	/**
	 * Get the running PDCs indexed by type
	 *
	 * @return array
	 */
	public function getRunningPDCsByType() {
		return $this->getPDCsByTypeFilteringRunning( true );
	}

	/**
	 * Get the ended PDCs indexed by type
	 *
	 * @return array
	 */
	public function getEndedPDCsByType() {
		return $this->getPDCsByTypeFilteringRunning( false );
	}

	/**
	 * Get the PDCs indexed by type, but only which of them are running (or not)
	 *
	 * @param $is_running bool
	 * @return array
	 */
	private function getPDCsByTypeFilteringRunning( $is_running ) {
		$pdcs_by_type = $this->getPDCsByType();
		foreach( $pdcs_by_type as $type => $pdcs ) {
			foreach( $pdcs as $i => $pdc ) {
				if( $pdc->isRunning() !== $is_running ) {
					unset( $pdcs_by_type[ $type ][ $i ] );
				}
			}
		}
		return $pdcs_by_type;
	}

}
