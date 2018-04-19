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

use \wm\WikipediaIt;
use \mw\Tokens;

/**
 * Handle a yearly category
 *
 * e.g. https://it.wikipedia.org/wiki/Categoria:Cancellazioni_-_2017
 */
class CategoryYear {

	/**
	 * Format of the generic title of a yearly category
	 *
	 * e.g. 'Categoria:Cancellazioni - 2018'
	 */
	const GENERIC_TITLE = 'Categoria:Cancellazioni - %d';

	/**
	 * Parent category title
	 */
	const MAIN_CATEGORY_TITLE = 'Categoria:Pagine in cancellazione per anno';

	/**
	 * Create the yearly category if it does not exist
	 *
	 * e.g. 'https://it.wikipedia.org/wiki/Categoria:Cancellazioni_-_2018'
	 *
	 * @param $year int e.g. 2018
	 */
	public static function createIfNotExists( $year ) {
		$categoryinfo = WikipediaIt::getInstance()->fetch( [
			'action' => 'query',
			'prop'   => 'categoryinfo',
			'titles' => self::title( $year ),
		] );
		foreach( $categoryinfo->query->pages as $pageid => $page ) {
			if( $pageid < 0 && isset( $page->missing ) ) {
				self::create( $year );
			}
		}
	}

	/**
	 * Create the yearly category
	 *
	 * e.g. 'https://it.wikipedia.org/wiki/Categoria:Cancellazioni_-_2018'
	 *
	 * @param $year int e.g. 2018
	 * @param $summary string
	 */
	private static function create( $year ) {
		$summary = sprintf(
			Template::get( 'CATEGORY_YEAR_SUMMARY' ),
			self::MAIN_CATEGORY_TITLE,
			$year
		);
		$text = sprintf(
			Template::get( 'CATEGORY_YEAR_CONTENT' ),
			self::MAIN_CATEGORY_TITLE,
			$year
		);
		$wit = WikipediaIt::getInstance();
		$args = [
			'action'  => 'edit',
			'title'   => self::title( $year ),
			'text'    => $text,
			'summary' => $summary,
			'bot'     => 1,
			'token'   => $wit->login()->getToken( Tokens::CSRF ),
		];
		$wit->post( $args );
	}

	/**
	 * Title of the category in the specified year
	 *
	 * @param $year int e.g. 2018
	 * @return string Category name e.g. 'Categoria:Cancellazioni - 2018'
	 */
	private static function title( $year ) {
		return sprintf( self::GENERIC_TITLE, $year );
	}
}
