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
 * Handle a yearly category
 *
 * e.g. https://it.wikipedia.org/wiki/Categoria:Cancellazioni_-_2017
 */
class CategoryYear extends CategoryTemplated {

	/**
	 * Year
	 *
	 * @var int
	 */
	private $year;

	/**
	 * The template name for a yearly category
	 *
	 * @override CategoryTemplated::TEMPLATE_NAME
	 */
	const TEMPLATE_NAME = 'CATEGORY_YEAR';

	/**
	 * Format of the generic title of a yearly category
	 *
	 * e.g. 'Categoria:Cancellazioni - 2018'
	 */
	const GENERIC_TITLE = 'Categoria:Cancellazioni - %d';

	/**
	 * Parent category title
	 */
	const PARENT_CATEGORY_TITLE = 'Categoria:Pagine in cancellazione per anno';

	/**
	 * Constructor
	 *
	 * @param $year int
	 */
	public function __construct( $year ) {
		parent::__construct( self::title( $year ) ) ;
		$this->year = $year;
	}

	/**
	 * Get the template arguments
	 *
	 * @override CategoryTemplated::getTemplateArguments()
	 * @return array
	 */
	protected function getTemplateArguments() {
		return [
			self::PARENT_CATEGORY_TITLE,
			$this->year,
		];
	}

	/**
	 * Title of the category in the specified year
	 *
	 * @param $year int e.g. 2018
	 * @return string Category name e.g. 'Categoria:Cancellazioni - 2018'
	 */
	public static function title( $year ) {
		return sprintf( self::GENERIC_TITLE, $year );
	}
}
