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
 * Handle a daily category that directly contains semplified ("semplificate") PDC pages
 * It contains other sub-categories.
 *
 * e.g. https://it.wikipedia.org/wiki/Categoria:Cancellazioni_del_19_febbraio_2018
 */
class CategoryYearMonthDay extends PageYearMonthDay {

	/**
	 * Template name
	 *
	 * @override CategoryTemplated::TEMPLATE_NAME
	 */
	const TEMPLATE_NAME = 'CATEGORY_DAY';

	/**
	 * Template arguments
	 *
	 * @override CategoryYearMonth::getTemplateArguments()
	 */
	public function getTemplateArguments() {
		return array_merge( [
				( new CategoryYearMonth( $this->getYear(), $this->getMonth() ) )
					->getTemplatedTitle()
			],
			parent::getTemplateArguments()
		);
	}

	/**
	 * Operate on every children page
	 */
	public function operateEveryChildrenPage() {

		// Categories for PDCs in order of importance (I think)
		$Categories = [
			CategoryYearMonthDayTypeVoting::class,
			CategoryYearMonthDayTypeProlonged::class,
			CategoryYearMonthDayTypeConsensual::class,
			CategoryYearMonthDayTypeOrdinary::class,
			CategoryYearMonthDayTypeSimple::class,
		];

		$pages_by_type = [];
		foreach( $Categories as $Category ) {
			$category = new $Category( $this->getYear(), $this->getMonth(), $this->getDay() );

			// fetch the pages in this category
			$pages = $category->fetchChildrenPages();
			if( count( $pages ) ) {
				$category->saveIfNotExists();
			}

			// sort by last update
			usort( $pages, function ( $a, $b ) {
				return $a->getDate() > $b->getDate();
			} );

			$pages_by_type[ $Category ] = $pages;
		}

//		PageYearMonthDayPDCsCount::createFromPagePDCs( $this, $pages_by_type )
//			->save();

		PageYearMonthDayPDCsLog::createFromPagePDCs( $this, $pages_by_type )
			->save();
	}

	/**
	 * Fetch children PDC pages from this category
	 *
	 * @return array
	 */
	protected function fetchChildrenPages() {
		$api = self::api()->setApiData( [
			'action' => 'query',

			// generator=categorymembers: get pages in category
			// gmtitle=:                  specify the category title
			// gmtype=page:               get sub-pages
			// gmsort=timestamp:          order by insertion date in that category (completly unuseful)
			// gmdir=asc:                 ascending order
			// https://it.wikipedia.org/w/api.php?action=help&modules=query%2Bcategorymembers
			// Note: Generator parameter names must be prefixed with a 'g'
			'generator'   => 'categorymembers',
			'gcmtitle'    => $this->getTitle(),
			'gcmnamespace' => 4, // Wikipedia namespace (not category)
//			'gcmtype'     => 'page', // Note: Ignored when cmsort=timestamp is set.
//			'gcmsort'     => 'timestamp',
//			'gcmdir'      => 'asc',

			// for each page load infos, categories and latest revision
			// https://it.wikipedia.org/w/api.php?action=help&modules=query%2Binfo
			// https://it.wikipedia.org/w/api.php?action=help&modules=query%2Bcategories
			// https://it.wikipedia.org/w/api.php?action=help&modules=query%2Brevisions
			// TODO: probably the revisions parameter is unuseful. Info already provides "touched" (sometimes ._.)
			'prop' => [ 'info' , 'categories', /* 'revisions' */ ],

			// inprop=protecion: list of the protection level of each page
			// https://it.wikipedia.org/w/api.php?action=help&modules=query%2Bcategories
			'inprop' => 'protection',

			// clprop=sortkey:   adds the sortkey and sortkey prefix for the category
			// https://it.wikipedia.org/w/api.php?action=help&modules=query%2Binfo
			'clprop' => 'sortkey',

			// timestamp of the latest revision
			// https://it.wikipedia.org/w/api.php?action=help&modules=query%2Brevisions
//			'rvprop' => 'timestamp',
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

		$all_pdc = [];
		foreach( $all as $page ) {
			$pdc = $this->createPDCFromRaw( $page );
			if( $pdc && $pdc->isValid() ) {
				$all_pdc[] = $pdc;
			}
		}
		return $all_pdc;
	}

	/**
	 * Create a PDF from raw (of this type)
	 *
	 * @param $pdc mixed
	 */
	public function createPDCFromRaw( $pdc ) {
		return PDC::createFromRaw( static::class, $pdc );
	}

}
