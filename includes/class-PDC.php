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
 *
 * It is a page like "Wikipedia:Pagine da cancellare/ASD"
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
	 * @var string
	 */
	private $type;

	/**
	 * Page id
	 *
	 * @var int
	 */
	private $id;

	/**
	 * Title of the PDC subject page (the Wikipedia article title)
	 *
	 * Obtained from {{DEFAULTSORT}} category sortkey
	 *
	 * @var string
	 */
	private $titleSubject;

	/**
	 * Page length in bytes
	 *
	 * @var int
	 */
	private $length;

	/**
	 * Date of the PDC
	 *
	 * @var date
	 */
	private $creationDate;

	/**
	 * Last update
	 *
	 * @var date
	 */
	private $lasteditDate;

	/**
	 * Is Protected?
	 *
	 * @var bool
	 */
	private $isProtected;

	/**
	 * Constructor
	 *
	 * @param $type string PDC type
	 * @param $id int PDC page id
	 * @param $title string PDC title prefixed
	 * @param $title_subject string Title of the subject page (the Wikipedia article title)
	 * @param $length int PDC length
	 * @param $creation DateTime PDC creation date
	 * @param $lastedit DateTime PDC lastedit date
	 * @param $is_protected bool Is the PDC protected?
	 * @see Page::__construct()
	 * @throws PDCException
	 */
	public function __construct( $type, $id, $title, $title_subject, $length, DateTime $creation, DateTime $lastedit, $is_protected ) {
		$this->type         = $type;
		$this->id           = $id;
		$this->titleSubject = $title_subject;
		$this->length       = $length;
		$this->creationDate = $creation;
		$this->lasteditDate = $lastedit;
		$this->isProtected  = $is_protected;

		parent::__construct( $title );

		if( ! $this->isTitlePrefixValid() ) {
			throw new PDCException( 'not a PDC' );

		}

		if( ! $this->isTitleSubjectConsistent() ) {
			throw new PDCException( 'inconsistent title subject' );
		}
	}

	/**
	 * Get the title of the subject page (the Wikipedia article title)
	 *
	 * Obtained from {{DEFAULTSORT}} category sortkey
	 *
	 * @return string
	 */
	public function getTitleSubject() {
		return $this->titleSubject;
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
	 * Get the creation date
	 *
	 * @return DateTime
	 */
	public function getCreationDate() {
		return $this->creationDate;
	}

	/**
	 * Get the latest update date
	 *
	 * @return DateTime
	 */
	public function getLasteditDate() {
		return $this->lasteditDate;
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
	 * Check if this page is protected
	 *
	 * @return bool
	 */
	public function isProtected() {
		return $this->isProtected;
	}

	/**
	 * Check if this PDC has a known prefix
	 *
	 * @return bool
	 */
	private function isTitlePrefixValid() {
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
	 * Get the title prefix
	 *
	 * @return string
	 */
	private function getTitlePrefix() {
		return $this->isMultiple()
			? self::PREFIX_MULTIPLE
			: self::PREFIX;
	}

	/**
	 * Get the title unprefixed
	 *
	 * @return string
	 */
	private function getTitleUnprefixed() {
		return substr( $this->getTitle(), strlen( $this->getTitlePrefix() ) );
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

	/**
	 * Verify if the title of the subject is consistent with the PDC title
	 *
	 * @return bool
	 */
	private function isTitleSubjectConsistent() {
		// subject from {{DEFAULTSORT}} category prefix
		$subject = $this->getTitleSubject();

		// subject generated unprefixing page title
		$subject_gen = $this->getTitleUnprefixed();

		if( $subject !== $subject_gen ) {
			// Check if the PDC title ends with an enumeration e.g. 'asd/14'
			$status = preg_match( '@(.*)/[0-9]+$@', $subject_gen, $matches );
			if( 1 === $status ) {
				// Get the interesting part e.g. 'asd'
				$subject_gen = $matches[ 1 ];
			}
		}

		return $subject === $subject_gen;
	}

	/**
	 * Statical constructor
	 *
	 * @param $type string Specified PDC class name
	 * @param $creation DateTime Creation date (PDC date)
	 * @param $page mixed
	 * @return self
	 * @throws PDCException
	 */
	public static function createFromRaw( $type, DateTime $creation, $page ) {

		// verify consistence
		if( ! isset(
				$page->pageid,
				$page->title,
				$page->length,
				$page->touched,
				$page->protection,
				$page->categories
			) ) {
				throw new PDCException( 'invalid PDC' );
		}

		// read the title of the subject from the {{DEFAULTSORT}} category sortkey prefix
		$title_subject = '';
		foreach( $page->categories as $category ) {
			if( ! isset( $category->sortkeyprefix ) ) {
				throw new PDCException( 'missing category sortkey prefix' );
			}
			$title_subject = $category->sortkeyprefix;
			break;
		}

		// read protection status
		$is_protected = false;
		foreach( $page->protection as $protection ) {
			if( 'edit' === $protection->type && 'sysop' === $protection->level ) {
				$is_protected = true;
				break;
			}
		}

		return new self(
			$type,
			$page->pageid,
			$page->title,
			$title_subject,
			$page->length,
			$creation,
			DateTime::createFromFormat( DateTime::ISO8601, $page->touched ),
			$is_protected
		);
	}

	/**
	 * Get the PDC temperature
	 *
	 * The temperature is a value betweeen 0-100
	 *
	 * @return int
	 */
	public function getTemperature() {
		$slope  = null;
		$offset = null;
		switch( $this->getType() ) {
			case CategoryYearMonthDayTypeSimple::getPDCType():
				$slope  =   0.0365;
				$offset = -24.0;
				break;
			case CategoryYearMonthDayTypeConsensual::getPDCType():
				$slope  =   0.0075;
				$offset = -12.81;
				break;
			case CategoryYearMonthDayTypeProlonged::getPDCType():
				$slope  =  0.0035;
				$offset = -9.76;
				break;
			case CategoryYearMonthDayTypeOrdinary::getPDCType():
			case CategoryYearMonthDayTypeVoting  ::getPDCType():
				$slope  =   0.0025;
				$offset = -16.43;
				break;
			default:
				throw new \Exception( 'unexpected type' );
		}

		$temp = round( $slope * $this->getLength() + $offset );
		if ( $temp > 100 ) {
			$temp = 100;
		} elseif ( $temp < 0 ) {
			$temp = 0;
		}
		return $temp;
	}
}
