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
 * Handle a monthly category
 *
 * e.g. https://it.wikipedia.org/wiki/Categoria:Cancellazioni_-_aprile_2018
 */
class CategoryYearMonth extends CategoryTemplated {

	/**
	 * Template name of this category
	 *
	 * @override CategoryTemplated::TEMPLATE_NAME
	 */
	const TEMPLATE_NAME = 'CATEGORY_MONTH';

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
	 * Constructor
	 *
	 * @param $year int
	 * @param $month int 1-12
	 * @see CategoryTemplated::__construct()
	 */
	public function __construct( $year, $month ) {
		$this->year  = $year;
		$this->month = $month;
		parent::__construct();
	}

	/**
	 * Template arguments
	 *
	 * @override CategoryTemplated::getTemplateArguments()
	 */
	public function getTemplateArguments() {
		return [
			( new CategoryYear( $this->year ) )
				->getTemplatedTitle(),
			$this->year,
			$this->month,
			Months::number2name( $this->month - 1 ),
		];
	}
}
