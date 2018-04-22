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
 * Handle a generic category associated to a template
 */
abstract class CategoryTemplated extends Category {

	/**
	 * Template name of this category
	 *
	 * To be overrided
	 */
	const TEMPLATE_NAME = 'EXAMPLE_NAME';

	/**
	 * Constructor
	 *
	 * @param $args Template arguments
	 */
	public function __construct() {
		parent::__construct( $this->getTemplatedTitle() );
	}

	/**
	 * Get the name of the template
	 *
	 * @return string
	 */
	public function getTemplateName() {
		return static::TEMPLATE_NAME;
	}

	/**
	 * Get the name of the template for the page title
	 *
	 * @return string
	 */
	public function getTemplateTitleName() {
		return $this->getTemplateName() . '.title';
	}

	/**
	 * Get the name of the template for the summary
	 *
	 * @return string
	 */
	public function getTemplateSummaryName() {
		return $this->getTemplateName() . '.summary';
	}

	/**
	 * Get the name of the template for the content
	 */
	public function getTemplateContentName() {
		return $this->getTemplateName() . '.content';
	}

	/**
	 * Get the template arguments
	 *
	 * @return array
	 */
	abstract public function getTemplateArguments();

	/**
	 * Get the edit summary for this category from its template
	 *
	 * @return string
	 */
	public function getTemplatedSummary() {
		return Template::get( $this->getTemplateSummaryName(), $this->getTemplateArguments() );
	}

	/**
	 * Get the page title from its template
	 *
	 * @return string
	 */
	public function getTemplatedTitle() {
		return Template::get( $this->getTemplateTitleName(), $this->getTemplateArguments() );
	}

	/**
	 * Get the page content for this category from its template
	 *
	 * @return string
	 */
	public function getTemplatedContent() {
		return Template::get( $this->getTemplateContentName(), $this->getTemplateArguments() );
	}

	/**
	 * Save this category from the content of its template
	 *
	 * @return mixed
	 */
	public function save() {
		return $this->saveByContentSummary( $this->getTemplatedContent(), $this->getTemplatedSummary() );
	}

	/**
	 * Save this category from the content of its template if it does not exist
	 *
	 * @return self
	 */
	public function saveIfNotExists() {
		if( ! $this->exists() ) {
			$this->save();
		}
		return $this;
	}
}
