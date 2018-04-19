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
	 */
	const TEMPLATE_NAME = 'TEMPLATE_NAME';

	/**
	 * Get the template arguments
	 *
	 * To be overrided
	 *
	 * @return array
	 */
	abstract protected function getTemplateArguments();

	/**
	 * Get the edit summary for this category from its template
	 *
	 * @return string
	 */
	public function getTemplateSummary() {
		return Template::get( static::TEMPLATE_NAME . '_SUMMARY', $this->getTemplateArguments() );
	}

	/**
	 * Get the page content for this category from its template
	 *
	 * @return string
	 */
	public function getTemplateContent() {
		return Template::get( static::TEMPLATE_NAME . '_CONTENT', $this->getTemplateArguments() );
	}

	/**
	 * Save this category from the content of its template
	 *
	 * @return mixed
	 */
	public function save() {
		return $this->saveByTitleContentSummary( $this->getTitle(), $this->getTemplateContent(), $this->getTemplateSummary() );
	}

	/**
	 * Save this category from the content of its template if not exists
	 *
	 * @return bool|mixed
	 */
	public function saveIfNotExists() {
		if( $this->exists() ) {
			return $this->save();
		}
		return false;
	}

	/**
	 * Check if this category exists
	 *
	 * @return bool
	 */
	public function exists() {
		return $this->existsByTitle( $this->getTitle() );
	}
}
