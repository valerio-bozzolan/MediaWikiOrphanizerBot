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
	 * The template name for a monthly category
	 *
	 * @override CategoryTemplated::TEMPLATE_NAME
	 */
	const TEMPLATE_NAME = 'CATEGORY_MONTH';

	/**
	 * Format of the generic title of a monthly category
	 *
	 * e.g. 'Categoria:Cancellazioni - aprile 2018'
	 */
	const GENERIC_TITLE = 'Categoria:Cancellazioni - %2$s %1$d';

	/**
	 * Constructor
	 *
	 * @param $year int
	 * @param $month int 1-12
	 */
	public function __construct( $year, $month ) {
		parent::__construct( self::title( $year, $month ) );
		$this->year = $year;
		$this->month = $month;
	}

	/**
	 * Get the template arguments
	 *
	 * @override CategoryTemplated::getTemplateArguments()
	 * @return array
	 */
	protected function getTemplateArguments() {
		return [
			CategoryYear::title( $this->year ),
			$this->year,
			$this->month,
			Months::number2name( $this->month - 1 ),
		];
	}

	/**
	 * Title of a monthly category
	 *
	 * @param $year int e.g. 2018
	 * @return string Category name e.g. 'Categoria:Cancellazioni - 2018'
	 */
	public static function title( $year, $month ) {
		$human_month = Months::number2name( $month - 1 );
		return sprintf( self::GENERIC_TITLE, $year, $human_month );
	}
}
