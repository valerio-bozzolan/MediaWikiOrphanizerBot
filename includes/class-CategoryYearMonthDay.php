<?php
# it.wiki deletion bot in PHP
# Copyright (C) 2013 Mauro742, MauroBot
# 	https://it.wikipedia.org/wiki/Utente:Mauro742
# 	https://it.wikipedia.org/wiki/Utente:MauroBot
# 	Originally under Creative Commons BY SA 3.0 International
# 	Utente:MauroBot/BotCancellazioni/category.js
# 	https://creativecommons.org/licenses/by-sa/3.0/
#   https://wikimediafoundation.org/wiki/Special:MyLanguage/Terms_of_Use/it
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
 * Handle a daily category
 *
 * e.g. https://it.wikipedia.org/wiki/Categoria:Cancellazioni_del_19_febbraio_2018
 */
class CategoryYearMonthDay extends CategoryTemplated {

	/**
	 * Template name
	 *
	 * @override CategoryTemplated::TEMPLATE_NAME
	 */
	const TEMPLATE_NAME = 'CATEGORY_DAY';

	/**
	 * Year
	 *
	 * @var int
	 */
	private $year;

	/**
	 * Month
	 *
	 * @var int 1-12
	 */
	private $month;

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
	 * @see CategoryTemplated::__construct()
	 */
	public function __construct( $year, $month, $day ) {
		$this->year  = $year;
		$this->month = $month;
		$this->day   = $day;
		parent::__construct();
	}

	/**
	 * Template arguments
	 *
	 * @override CategoryTemplated::getTemplateArguments()
	 */
	public function getTemplateArguments() {
		return [
			( new CategoryYearMonth( $this->year, $this->month ) )
				->getTemplatedTitle(),
			$this->year,
			$this->month,
			Months::number2name( $this->month - 1 ),
			$this->day,
		];
	}
}
