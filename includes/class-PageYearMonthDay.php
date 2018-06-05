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

use DateTime;

/**
 * Abstraction of a page related to a day (in a certain month of a certain year)
 */
abstract class PageYearMonthDay extends PageYearMonth {

	/**
	 * Day
	 *
	 * @var int 1-31
	 */
	private $day;

	/**
	 * Constructor
	 *
	 * @param $year int
	 * @param $month int 1-12
	 * @param $day int 1-31
	 * @see PageYearMonth::__construct()
	 */
	public function __construct( $year, $month, $day ) {
		$this->day = (int) $day;
		parent::__construct( $year, $month );
	}

	/**
	 * Static constructor
	 *
	 * @param $date_time DateTime
	 */
	public static function createFromDateTime( DateTime $datetime ) {
		return new static( $datetime->format('Y'), $datetime->format('m'), $datetime->format('d') );
	}

	/**
	 * Get the day
	 *
	 * @return int 1-13
	 */
	public function getDay() {
		return $this->day;
	}

	/**
	 * Get a DateTime object
	 *
	 * @return DateTime
	 */
	public function getDateTime() {
		return DateTime::createFromFormat( 'Y-m-d', sprintf(
			'%d-%d-%d',
			$this->getYear(),
			$this->getMonth(),
			$this->getDay()
		) );
	}

	/**
	 * Template arguments
	 *
	 * @override PageYearMonth::getTemplateArguments()
	 */
	public function getTemplateArguments() {
		return array_merge( parent::getTemplateArguments(), [
			$this->getDay()
		] );
	}

}
