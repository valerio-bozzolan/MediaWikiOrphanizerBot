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
	 * PDC category type
	 *
	 * @var CategoryYearMonthDayType
	 */
	private $categoryType;

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
	 * When the PDC was added to the PDC type category
	 *
	 * @var date
	 */
	private $startDate;

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
	 * Properties that the raw object must have
	 *
	 * @var array
	 */
	private static $RAW_PROPERTIES = [
		'pageid',
		'title',
		'length',
		'touched',
		'protection',
		'categories'
	];

	/**
	 * Constructor
	 *
	 * @param $category_type CategoryYearMonthDayType PDC category type
	 * @param $id int PDC page id
	 * @param $title string PDC title prefixed
	 * @param $title_subject string Title of the subject page (the Wikipedia article title)
	 * @param $length int PDC length
	 * @param $start DateTime When the PDC was added to the PDC type category
	 * @param $lastedit DateTime PDC lastedit date
	 * @param $is_protected bool Is the PDC protected?
	 * @see Page::__construct()
	 * @throws PDCException
	 */
	public function __construct( $category_type, $id, $title, $title_subject, $length, DateTime $start, DateTime $lastedit, $is_protected ) {
		$this->categoryType = $category_type;
		$this->id           = $id;
		$this->titleSubject = $title_subject;
		$this->length       = $length;
		$this->startDate    = $start;
		$this->lasteditDate = $lastedit;
		$this->isProtected  = $is_protected;

		parent::__construct( $title );

		// consistence checks
		if( ! $this->isTitlePrefixValid() ) {
			throw new PDCException( 'not a PDC' );
		}
		if( $this->getDurationDays() > 7 ) {
			throw new PDCExceptionExpired( 'not anymore a PDC of type ' . $this->getType() );
		}

		$this->checkTitleSubjectConsistence();
	}

	/**
	 * Get the page id
	 *
	 * @return int
	 */
	public function getId() {
		return $this->id;
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
	 * Get the start date of this PDC (of this type)
	 *
	 * @return DateTime
	 */
	public function getStartDate() {
		return $this->startDate;
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
	 * Get the PDC type class
	 *
	 * @return class
	 */
	public function getTypeClass() {
		return $this->categoryType;
	}

	/**
	 * Get the PDC type name
	 *
	 * @return string e.g. 'consensuale'
	 */
	public function getType() {
		return $this->getTypeClass()::getPDCType();
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
	private function checkTitleSubjectConsistence() {
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

		if( $subject !== $subject_gen ) {
			throw new PDCException( sprintf(
				'the sortkey is "%s" and it is inconsistent with the PDC suffix "%s"',
				$subject_gen,
				$subject
			) );
		}
	}

	/**
	 * Static constructor
	 *
	 * @param $type CategoryYearMonthDayType Specified PDC type
	 * @param $page mixed
	 * @return self
	 * @throws PDCException
	 */
	public static function createFromRaw( $type, $page ) {

		// verify consistence
		foreach( self::$RAW_PROPERTIES as $property ) {
			if( ! isset( $page->$property ) ) {
				throw new PDCException( "PDC has not property $property" );
			}
		}

		// read the title of the subject from the {{DEFAULTSORT}} category sortkey prefix
		// get the start date of this PDC from the timestamp
		$title_subject = null;;
		$date_added_in_category = null;
		foreach( $page->categories as $category ) {
			if( ! isset( $category->title ) ) {
				throw new PDCException( 'missing category title' );
			}
			if( $category->title === $type->getTitle() ) {
				$title_subject = $category->sortkeyprefix;
				$date_added_in_category = $category->timestamp;
			}
			break;
		}
		if( ! isset( $title_subject ) ) {
			throw new PDCException( 'missing category sortkey prefix that contains title subject' );
		}
		if( ! isset( $date_added_in_category ) ) {
			throw new PDCException( 'missing timestamp that contains the PDC start date' );
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
			(int) $page->pageid,
			$page->title,
			$title_subject,
			(int) $page->length,
			DateTime::createFromFormat( DateTime::ISO8601, $date_added_in_category ),
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
				throw new PDCException( 'unexpected type' );
		}

		$temp = round( $slope * $this->getLength() + $offset );
		if ( $temp > 100 ) {
			$temp = 100;
		} elseif ( $temp < 0 ) {
			$temp = 0;
		}
		return $temp;
	}

	/**
	 * Get the number of days of duration of this PDC
	 *
	 * @return int
	 */
	public function getDurationDays() {
		return (int) $this->getStartDate()->diff( $this->getLasteditDate() )->format('%a');
	}

	/**
	 * Get an human rappresentation of the duration of this PDC
	 *
	 * @return string
	 */
	public function getHumanDuration() {
		$days = $this->getDurationDays();
		if( 0 === $days ) {
			return Template::get( 'DURATION.hours' );
		}
		if( 1 === $days ) {
			return Template::get( 'DURATION.day' );
		}
		return Template::get( 'DURATION.days', [ $days ] );
	}
}
