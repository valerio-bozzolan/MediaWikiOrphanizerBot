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
 * Handler of all the PDCs in this day of the year
 */
class CategoryYearMonthDayTypes extends CategoryYearMonthDay {

	/**
	 * Operate on every PDC type
	 */
	public function run() {

		// Categories for PDCs in order of importance (I think)
		$Categories = [
			CategoryYearMonthDayTypeVoting    ::class,
			CategoryYearMonthDayTypeProlonged ::class,
			CategoryYearMonthDayTypeConsensual::class,
			CategoryYearMonthDayTypeOrdinary  ::class,
			CategoryYearMonthDayTypeSimple    ::class,
		];

		$pdcs_by_type = [];
		foreach( $Categories as $Category ) {
			$category = new $Category( $this->getYear(), $this->getMonth(), $this->getDay() );

			$pdcs = $category->fetchPDCs();
			if( count( $pdcs ) ) {
				$category->saveIfNotExists();
			}

			// sort PDCs by last update
			usort( $pdcs, function ( $a, $b ) {
				return $a->getLasteditDate() > $b->getLasteditDate();
			} );

			$pdcs_by_type[ $Category::getPDCType() ] = $pdcs;
		}

		$pdcs_by_type = self::filterPDCs( $pdcs_by_type );

		PageYearMonthDayPDCsCount::createFromPagePDCs( $this->getDateTime(), $pages_by_type )
			->save();

		PageYearMonthDayPDCsLog::createFromPagePDCs( $this->getDateTime(), $pages_by_type )
			->save();
	}

	/**
	 * Make a bit of consistence in the specified PDCs
	 *
	 * @param $pdcs_by_type array
	 * @return array
	 * @TODO
	 */
	private static function filterPDCs( $pdcs_by_type ) {
		return $pdcs_by_type;
	}

}
