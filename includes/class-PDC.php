<?php
# it.wiki deletion bot in PHP
# Copyright (C) 2013 Mauro742, MauroBot
# 	https://it.wikipedia.org/wiki/Utente:Mauro742
# 	https://it.wikipedia.org/wiki/Utente:MauroBot
# 	Originally under Creative Commons BY SA 3.0 International
#	https://it.wikipedia.org/wiki/Utente:MauroBot/BotCancellazioni/core.js
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
 * Handle a "Procedura di cancellazione"
 */
class PDC extends Page {

	/**
	 * Prefix of every PDC
	 */
	const PREFIX = 'Wikipedia:Pagine da cancellare/';

	/**
	 * Prefix of every multiple PDC
	 */
	const PREFIX_MULTIPLE = self::PREFIX . 'multiple/';

	/**
	 * PDC type
	 *
	 * @var class
	 */
	private $type;

	/**
	 * Page id
	 *
	 * @var int
	 */
	private $id;

	/**
	 * Page title with prefix
	 *
	 * @var string
	 */
	private $title;

	/**
	 * Page length in bytes
	 *
	 * @var int
	 */
	private $length;

	/**
	 * Page date
	 *
	 * @var date
	 */
	private $date;

	/**
	 * Constructor
	 *
	 * @param $id Page id
	 * @param $title string Page title
	 * @param $length Page length
	 * @param $date Page last update
	 * @see Page::__construct()
	 */
	public function __construct( $type, $id, $title, $length, $date ) {
		$this->type   = $type;
		$this->id     = $id;
		$this->title  = $title;
		$this->length = $length;
		$this->date   = $date;
		parent::__construct( $title );
	}

	/**
	 * Statical constructor
	 *
	 * @param $type string Specified PDC class name
	 * @param $page mixed|null
	 */
	public static function createFromRaw( $type, $page ) {
		if( ! isset( $page->touched, $page->pageid, $page->title, $page->length ) ) {
			throw new \Exception( 'invalid PDC' );
		}
		return new self(
			$type,
			$page->pageid,
			$page->title,
			$page->length,
			DateTime::createFromFormat( DateTime::ISO8601, $page->touched ) // e.g. 2018-04-22T14:07:49Z
		);
	}

	/**
	 * Get the page length
	 *
	 * @return int
	 */
	public function getLength() {
		return $this->length;
	}

	/**
	 * Get the latest update date
	 *
	 * @return DateTime
	 */
	public function getDate() {
		return $this->date;
	}

	/**
	 * Get the PDC type
	 *
	 * @return string e.g. 'consensuale'
	 */
	public function getType() {
		return $this->type::getPDCType();
	}

	/**
	 * Check if this is a valid PDC
	 *
	 * @return bool
	 */
	public function isValid() {
		return $this->titlehasPrefix( self::PREFIX );
	}

	/**
	 * Check if this PDC involves multiple PDCs
	 *
	 * @return bool
	 */
	public function isMultiple() {
		return $this->titleHasPrefix( self::PREFIX_MULTIPLE );
	}

	/**
	 * Check if the title has a certain prefix
	 *
	 * @param $prefix string
	 * @return bool
	 */
	private function titleHasPrefix( $prefix ) {
		return 0 === strpos( $this->getTitle(), $prefix );
	}
}
