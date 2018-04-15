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

use DateTime;

/**
 * Handle a human date
 */
class Date {

	/**
	 * Get a DateTime object from a human date
	 *
	 * @param $category_name Category name e.g. '4 aprile 2013'
	 * @return DateTime
	 */
	public static function human2object( $human_date ) {
		$parts = explode( ' ', $human_date );
		if( 3 === count( $parts ) ) {
			list( $d, $human_m, $y ) = $parts;
			$m = Months::name2number( $human_m ) + 1;
			return new DateTime( sprintf(
				// yyyy-mm-dd
				'%s-%02d-%02s',
				$y,
				$m,
				$d
			) );
		}
		throw new \InvalidArgumentException( 'wrong human date' );
	}

	/**
	 * Get a human date from a DateTime object
	 *
	 * @param $datetime DateTime
	 * @return string e.g. '4 aprile 2013'
	 */
	public static function object2human( DateTime $datetime ) {
		list( $y, $m, $d ) = explode( ' ', $datetime->format('Y m d') );
		$human_month = Months::number2name( $m - 1 );
		return "$d $human_month $y";
	}
}
