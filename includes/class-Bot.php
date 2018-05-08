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
use DateInterval;

/**
 * The Bot class helps in running the bot
 */
class Bot {

	/**
	 * The date
	 *
	 * @var DateTime
	 */
	private $dateTime;

	/**
	 * A cache of already done dates.
	 * It's an array of booleans like [year][month] = true.
	 *
	 * @var array
	 */
	private $cache = [];

	/**
	 * Construct
	 *
	 * @param $date DateTime
	 */
	public function __construct( DateTime $date = null ) {
		if( ! $date ) {
			$date = new DateTime();
		}
		$this->setDate( $date );
	}

	/**
	 * Static construct
	 *
	 * @param $date string
	 */
	public static function createFromString( $date = 'now' ) {
		return new self( new DateTime( $date ) );
	}

	/**
	 * Static construct
	 *
	 * @param $y int Year
	 * @param $m int Month 1-12
	 * @param $d int Day 1-31
	 */
	public static function createFromYearMonthDay( $y, $m, $d ) {
		return new self( DateTime::createFromFormat( "Y m d", "$y $m $d") );
	}

	/**
	 * Get the date
	 *
	 * @return DateTime
	 */
	public function getDate() {
		return $this->dateTime;
	}

	/**
	 * Set the date
	 *
	 * @param $date DateTime
	 * @return self
	 */
	public function setDate( DateTime $date ) {
		$this->dateTime = $date;
	}

	/**
	 * Add a day
	 *
	 * @return self
	 */
	public function nextDay() {
		return $this->addDays( 1 );
	}

	/**
	 * Add a day
	 *
	 * @return self
	 */
	public function previousDay() {
		return $this->subDays( 1 );
	}

	/**
	 * Add a certain number of days
	 *
	 * @param $days int
	 * @return self
	 */
	public function addDays( $days ) {
		$this->getDate()->add( new DateInterval( sprintf(
			'P%dD',
			$days
		) ) );
		return $this;
	}

	/**
	 * Subtract a certain number of days
	 *
	 * @param $days int
	 * @return self
	 */
	public function subDays( $days ) {
		$this->getDate()->sub( new DateInterval( sprintf(
			'P%dD',
			$days
		) ) );
		return $this;
	}

	/**
	 * Run the bot at the internal date
	 *
	 * @TODO: do not repeat twice the same yearly and montly categories
	 * @return self
	 */
	public function run() {
		$date  = $this->getDate();
		$year  = $date->format( 'Y' );
		$month = $date->format( 'n' ); // 1-12
		$day   = $date->format( 'j' );

		// create the yearly category (once)
		$cache = & $this->cache;
		if( ! isset( $cache[ $year ] ) ) {
			$cache[ $year ] = [];
			( new CategoryYear( $year ) )
				->saveIfNotExists();
		}

		// create the monthly category (once)
		$cache = & $cache[ $year ];
		if( ! isset( $cache[ $month ] ) ) {
			$cache[ $month ] = true;
			( new CategoryYearMonth( $year, $month ) )
				->saveIfNotExists();
		}

		// create the daily category
		( new CategoryYearMonthDayTypes( $year, $month, $day ) )
			->saveIfNotExists()
			->run();

		return $this;
	}
}
