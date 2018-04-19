<?php
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
 * Handle templates of text
 *
 * Templates are 'something.template' stuff into the /template directory
 */
class Template {

	const START_PLACEHOLDER = "<!-- START TEMPLATE -->\n";

	/**
	 * @param $name string Template name
	 * @return string
	 */
	static function get( $name ) {
		$path = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $name . '.template';
		if( ! file_exists( $path ) ) {
			throw new \InvalidArgumentException( 'unexisting template' );
		}

		$content = file_get_contents( $path );

		// stripping header
		$pos = strpos( $content, self::START_PLACEHOLDER );
		if( false === $pos ) {
			throw new \Exception( 'missing header in template' );
		}
		$pos += strlen( self::START_PLACEHOLDER );
		$content = substr( $content, $pos );

		return rtrim( $content );
	}
}
