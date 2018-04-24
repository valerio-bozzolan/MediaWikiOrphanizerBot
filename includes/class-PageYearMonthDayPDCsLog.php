<?php
# it.wiki deletion bot in PHP
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
 * Abstraction of a page containing PDCs daily log
 *
 * e.g. https://it.wikipedia.org/wiki/Wikipedia:Pagine_da_cancellare/Conta/2017_aprile_20
 */
class PageYearMonthDayPDCsLog extends PageYearMonthDayPDCs {

	/**
	 * Template name of this page
	 *
	 * @override CategoryTemplated::TEMPLATE_NAME
	 */
	const TEMPLATE_NAME = 'PAGE_LOG';

	/**
	 * Get the template arguments
	 *
	 * @override PageTemplated::getTemplateArguments()
	 * @return array
	 */
	public function getTemplateArguments() {
		$args = parent::getTemplateArguments();

		$sections = [];
		foreach( $this->getPDCsByType() as $pdcs ) {
			if( $pdcs ) {
				$type = $pdcs[ 0 ]->getType();

				// call the entry template for each PDC
				$entries = [];
				foreach( $pdcs as $pdc ) {
					$entries[] = Template::get( self::TEMPLATE_NAME . '.entry', [
						$pdc->getTitle()
					] );
				}

				// call the section template for each PDC type
				$entries_txt = implode( "\n", $entries );
				$sections[] = Template::get( self::TEMPLATE_NAME . '.section', [
					$pdc->getType(),
					$entries_txt
				] );
			}
		}

		$args[] = implode( "\n", $sections );

		return $args;
	}

}
