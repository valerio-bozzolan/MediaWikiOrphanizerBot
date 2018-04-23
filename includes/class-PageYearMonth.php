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
 * Abstraction of a page related to a month (in a certain year)
 */
abstract class PageYearMonth extends PageYear {

	/**
	 * Month
	 *
	 * @var int 1-12
	 */
	private $month;

	/**
	 * Constructor
	 *
	 * @param $year int
	 * @param $month int 1-12
	 * @see PageYear::__construct()
	 */
	public function __construct( $year, $month ) {
		$this->month = $month;
		parent::__construct( $year );
	}

	/**
	 * Get the month
	 *
	 * @return int 1-12
	 */
	public function getMonth() {
		return $this->month;
	}

	/**
	 * Template arguments
	 *
	 * @override PageYear::getTemplateArguments()
	 */
	public function getTemplateArguments() {
		return array_merge( parent::getTemplateArguments(), [
			$this->getMonth(),
			Months::number2name( $this->getMonth() - 1 ),
		] );
	}

}
