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
	public function __construct( $year, $month, $day, $pdcs_by_type ) {
		$this->pdcsByType = $pdcs_by_type;
		parent::__construct( $year, $month, $day );
	}

	/**
	 * Static constructor from a PageYearMonthDay object
	 *
	 * @param $page PageYearMonthDay
	 * @param $pdcs PDCs by type
	 * @return self
	 */
	public static function createFromPagePDCs( PageYearMonthDay $page, $pdcs_by_type ) {
		return new static( $page->getYear(), $page->getMonth(), $page->getDay(), $pdcs_by_type );
	}

	/**
	 * Get the PDCs indexed by type
	 *
	 * @return array
	 */
	public function getPDCsByType() {
		return $this->pdcsByType;
	}

}
