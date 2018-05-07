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
 * Abstraction of a daily category with a specified PDC type
 */
abstract class CategoryYearMonthDayType extends CategoryYearMonthDay {

	/**
	 * Template name of this category
	 *
	 * @override CategoryTemplated::TEMPLATE_NAME
	 */
	const TEMPLATE_NAME = 'CATEGORY_DAY_PDCTYPE';

	/**
	 * PDC type e.g. 'consensuale'
	 *
	 * To be overrided
	 */
	const PDC_TYPE = 'EXAMPLE';

	/**
	 * Get the PDC type
	 *
	 * @return string e.g. 'consensuale'
	 */
	public function getPDCType() {
		return static::PDC_TYPE;
	}

	/**
	 * Get template arguments
	 *
	 * @override CategoryTemplated::getTemplateArguments()
	 */
	public function getTemplateArguments() {
		$parent_arguments = parent::getTemplateArguments();
		$parent_arguments[] = $this->getPDCType();
		return $parent_arguments;
	}

	/**
	 * Create a PDF of this type (from raw)
	 *
	 * @param $pdc_raw mixed Raw PDC data
	 * @see PDC::createFromRaw()
	 */
	public function createPDCFromRaw( $pdc_raw ) {
		return PDC::createFromRaw( $this, $pdc_raw );
	}

	/**
	 * Fetch PDCs of this type
	 *
	 * @return array
	 */
	public function fetchPDCs() {
		$api = self::api()->createQuery( [
			'action' => 'query',

			// generator=categorymembers: get pages in category
			// gmtitle=:                  specify the category title
			// gmtype=page:               get sub-pages
			// gmsort=timestamp:          order by insertion date in that category
			// gmdir=asc:                 ascending order
			// https://it.wikipedia.org/w/api.php?action=help&modules=query%2Bcategorymembers
			// Note: Generator parameter names must be prefixed with a 'g'
			'generator'   => 'categorymembers',
			'gcmtitle'    => $this->getTitle(),
			'gcmtype'     => 'page', // Note: Ignored when cmsort=timestamp is set.
			'gcmlimit'    => 100,
			'gcmsort'     => 'timestamp',
			'gcmdir'      => 'asc',
//
			// for each page load infos, categories and latest revision
			// https://it.wikipedia.org/w/api.php?action=help&modules=query%2Binfo
			// https://it.wikipedia.org/w/api.php?action=help&modules=query%2Bcategories
			// https://it.wikipedia.org/w/api.php?action=help&modules=query%2Brevisions
			// Note: probably the revisions parameter is unuseful for last update date (already provided by "touched").
			// Note: revisions can be still useful to know the creation date
			'prop' => [ 'info' , 'categories'/*, 'revisions'*/ ],

			// inprop=protecion: list of the protection level of each page
			// https://it.wikipedia.org/w/api.php?action=help&modules=query%2Bcategories
			'inprop' => 'protection',

			// clprop=sortkey:   adds the sortkey and sortkey prefix for the category
			// clprop=timestamp: adds the timestamp of when the page was included
			// https://it.wikipedia.org/w/api.php?action=help&modules=query%2Binfo
			'clprop' => [ 'sortkey', 'timestamp' ],

			// rvprop=timestamp: timestamp of the revision
			// rvlimit=1: only 1 revision
			// rvdir=older: order by oldest
			// https://it.wikipedia.org/w/api.php?action=help&modules=query%2Brevisions
//			'rvprop'  => 'timestamp',
//			'rvdir'   => 'older',
//			'rvlimit' => 1,
		] );

		$all = [];
		while( $api->hasNext() ) {
			$next = $api->fetchNext();

			if( isset( $next->query->pages ) ) {
				foreach( $next->query->pages as $page ) {

					// multiple call will add more infos on the same page
					// https://www.mediawiki.org/wiki/API:Query#Generators_and_continuation
					// TODO: use batchcomplete for better memory usage
					// Note: pages are not directly saved with their pageid to preserve their order
					$pageid = $page->pageid;
					if( isset( $all[ $pageid ] ) ) {
						foreach( $page as $property => $value ) {
							$all[ $pageid ]->{$property} = $value;
						}
					} else {
						$all[ $pageid ] = $page;
					}
				}
			}
		}

		$pdcs = [];
		foreach( $all as $page ) {
			try {
				$pdcs[] = $this->createPDCFromRaw( $page );
			} catch( PDCExpiredException $e ) {
				Log::info( sprintf(
					"%s: %s",
					$page->title,
					$e->getMessage()
				) );
			} catch( PDCException $e ) {
				Log::warn( sprintf(
					"exception in PDC '%s': %s",
					$page->title,
					$e->getMessage()
				) );
			}
		}

		return $pdcs;
	}

}
