<?php
# it.wiki deletion bot in PHP
# Copyright (C) 2013 Mauro742, MauroBot
# 	https://it.wikipedia.org/wiki/Utente:Mauro742
# 	https://it.wikipedia.org/wiki/Utente:MauroBot
# 	Originally under Creative Commons BY SA 3.0 International
# 	Utente:MauroBot/BotCancellazioni/dateFunctions.js
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
 * Singleton that handle months
 */
class Months {

	/**
	 * Months
	 *
	 * @var array
	 */
	private static $_months;

	/**
	 * Get the names of the months
	 *
	 * @return array Names of months
	 */
	public static function months() {
		if( ! self::$_months ) {
			self::$_months = [
				'gennaio',
				'febbraio',
				'marzo',
				'aprile',
				'maggio',
				'giugno',
				'luglio',
				'agosto',
				'settembre',
				'ottobre',
				'novembre',
				'dicembre'
			];
		}
		return self::$_months;
	}

	/**
	 * Get a month number from a month name
	 *
	 * @param $month_name string Month name e.g. 'gennaio'
	 * @return int Month number 0-11 e.g. 0
	 */
	public static function monthName2MonthNumber( $month_name ) {
		$i = array_search( $month_name, self::months(), true );
		if( false === $i ) {
			throw new \InvalidArgumentException( 'unexpected month name' );
		}
		return $i;
	}

	/**
	 * Get a month name from a month number
	 *
	 * @param $month_number int Month number 0-11 e.g. 0
	 * @return string Month name e.g. 'gennaio'
	 */
	public static function monthNumber2MonthName( $month_number ) {
		if( $month_number < 0 || $month_number > 11 ) {
			throw new \InvalidArgumentException( 'unexpected month number' );
		}
		$months = self::months();
		return $months[ $month_number ];
	}
}
