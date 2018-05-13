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
 * Abstraction of a page containing PDCs' log of the day
 */
class PageYearMonthDayPDCsCount extends PageYearMonthDayPDCs {

	/**
	 * Template name of this page
	 *
	 * @override CategoryTemplated::TEMPLATE_NAME
	 */
	const TEMPLATE_NAME = 'PAGE_COUNT';

	/**
	 * Get the template arguments:
	 * 1: page title
	 * 2: temperature
	 * 3: '1' if it's multiple
	 * 4: color associated to the PDC e.g. '#fff'
	 * 5: number of the row
	 * 6: PDC type e.g. 'consensuale'
	 * 7: duration e.g. 'un giorno'
	 * 8: title of the log page
	 * 9: goto action label e.g. 'pagina di discussione'
	 *
	 * @override PageTemplated::getTemplateArguments()
	 * @return array
	 */
	public function getTemplateArguments() {

		$sections = [];

		// runnings
		$i = 0;
		$entries = [];
		foreach( $this->getRunningPDCsByType() as $type => $pdcs ) {
			foreach( $pdcs as $pdc ) {
				$entries[] = $this->createPDCEntryContent( ++$i, $pdc );
			}
		}
		if( $entries ) {
			$sections[] = Template::get( self::TEMPLATE_NAME . '.RUNNING.section', [
				implode( "\n", $entries )
			] );
		}

		// endeds
		$i = 0;
		$entries = [];
		foreach( $this->getEndedPDCsByType() as $type => $pdcs ) {
			foreach( $pdcs as $pdc ) {
				$entries[] = $this->createPDCEntryContent( ++$i, $pdc );
			}
		}
		if( $entries ) {
			$sections[] = Template::get( self::TEMPLATE_NAME . '.ENDED.section', [
				implode( "\n", $entries )
			] );
		}

		$args = parent::getTemplateArguments();

		// Does it have content?
		if( $sections ) {
			$args[] = implode( "\n", $sections );
		} else  {
			$args[] = Template::get( self::TEMPLATE_NAME . '.empty' );
		}

		return $args;
	}

	/**
	 * Get the PDC entry content
	 *
	 * @param $pdc PDC
	 * @param $i Ordinal number passed from the count page
	 * @return string
	 */
	public function createPDCEntryContent( $i, PDC $pdc ) {

		$template_name = self::TEMPLATE_NAME;
		$template_name .= $pdc->isProtected()
			? '.ENDED.entry'
			: '.RUNNING.entry';

		return Template::get( $template_name, [
			$pdc->getTitleSubject(),
			$pdc->getTemperature(),
			$pdc->isMultiple(),
			'', // TODO: Ex color. Now unuseful.
			$i,
			$pdc->getHumanType(),
			$pdc->getHumanDuration(),
			$this->getTitle()
		] );

	}

}
