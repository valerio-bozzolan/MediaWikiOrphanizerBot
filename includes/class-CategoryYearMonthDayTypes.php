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

use \cli\Log;

/**
 * Handler of all the PDCs in this day of the year
 */
class CategoryYearMonthDayTypes extends CategoryYearMonthDay {

	/**
	 * PDC types
	 *
	 * Categories in order of importance (I think)
	 *
	 * @var array
	 */
	public static $TYPES = [
		CategoryYearMonthDayTypeVoting    ::class,
		CategoryYearMonthDayTypeProlonged ::class,
		CategoryYearMonthDayTypeConsensual::class,
		CategoryYearMonthDayTypeOrdinary  ::class,
		CategoryYearMonthDayTypeSimple    ::class,
	];

	/**
	 * Return the newer PDC, but setting the older start date.
	 *
	 * @param $pdc1 PDC
	 * @param $pdc2 PDC
	 * @return $pdc PDC
	 */
	public function mergePDCs( $pdc1, $pdc2 ) {
		if( $pdc2->getDurationDays() > $pdc1->getDurationDays() ) {
			return $pdc2->setStartDate( $pdc1->getStartDate() );
		}
		return $pdc1;
	}

	/**
	 * Operate on every PDC type
	 */
	public function run() {

		$pdcs_by_id = [];
		foreach( self::$TYPES as $Category ) {
			$category = new $Category( $this->getYear(), $this->getMonth(), $this->getDay() );

			$pdcs = $category->fetchPDCs();
			if( count( $pdcs ) ) {
				$category->saveIfNotExists();
			}

			// merge the same PDCs
			foreach( $pdcs as $pdc ) {
				$id = $pdc->getId();
				if( isset( $pdcs_by_id[ $id ] ) ) {
					$pdcs_by_id[ $id ] = self::mergePDCs( $pdcs_by_id[ $id ], $pdc );
				} else {
					$pdcs_by_id[ $id ] = $pdc;
				}
			}
		}

		// sort PDCs by start date
		usort( $pdcs_by_id, function ( $a, $b ) {
			return $a->getStartDate() > $b->getStartDate();
		} );

		foreach( $pdcs_by_id as $i => $pdc ) {
			if( $pdc->getDurationDays() > 7 ) {
				Log::info( sprintf(
					'the PDC %s is expired after %d days',
					$pdc->getTitle(),
					$pdc->getDurationDays()
				) );
			}
		}

		// index PDcs by their type
		$pdcs_by_type = [];
		foreach( $pdcs_by_id as $pdc ) {
			$type = $pdc->getType();
			if( ! isset( $pdcs_by_type[ $type ] ) ) {
				$pdcs_by_type[ $type ] = [];
			}
			$pdcs_by_type[ $type ][] = $pdc;
		}

		PageYearMonthDayPDCsCount::createFromDateTimePDCs( $this->getDateTime(), $pdcs_by_type )
			->save();

		PageYearMonthDayPDCsLog::createFromDateTimePDCs( $this->getDateTime(), $pdcs_by_type )
			->save();
	}

}
