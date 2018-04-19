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
 * Handle a monthly category
 *
 * e.g. https://it.wikipedia.org/wiki/Categoria:Cancellazioni_-_aprile_2018
 */
class CategoryYearMonth {

	/**
	 * Format of the generic title of a yearly category
	 *
	 * e.g. 'Categoria:Cancellazioni - aprile 2017'
	 */
	const GENERIC_TITLE = 'Categoria:Cancellazioni - %2$s %1$d';

	/**
	 * Create the monthly category if it does not exist
	 *
	 * @param $year int e.g. 2018
	 * @param $month int e.g. 4
	 */
	public static function createIfNotExists( $year, $month ) {
		$categoryinfo = WikipediaIt::getInstance()->fetch( [
			'action' => 'query',
			'prop'   => 'categoryinfo',
			'titles' => self::title( $year, $month ),
		] );
		foreach( $categoryinfo->query->pages as $pageid => $page ) {
			if( $pageid < 0 && isset( $page->missing ) ) {
				self::create( $year, $month );
			}
		}
	}

	/**
	 * Create the monthly category
	 *
	 * @param $year int e.g. 2018
	 * @param $month int e.g. 4
	 */
	private static function create( $year, $month ) {
		$template_args = [
			CategoryYear::title( $year, $month ),
			$year,
			$month,
			Months::number2name( $month - 1 ),
		];
		$wit = WikipediaIt::getInstance();
		$args = [
			'action'  => 'edit',
			'title'   => self::title( $year, $month ),
			'text'    => Template::get( 'CATEGORY_MONTH_CONTENT', $template_args ),
			'summary' => Template::get( 'CATEGORY_MONTH_SUMMARY', $template_args ),
			'token'   => $wit->login()->getToken( Tokens::CSRF ),
			'bot'     => 1,
		];
		var_dump( $args ); exit;
		$wit->post( $args );
	}

	/**
	 * Title of the category in the specified month
	 *
	 * @param $year int e.g. 2018
	 * @return string Category name e.g. 'Categoria:Cancellazioni - 2018'
	 */
	private static function title( $year, $month ) {
		$human_month = Months::number2name( $month - 1 );
		return sprintf( self::GENERIC_TITLE, $year, $human_month );
	}
}
