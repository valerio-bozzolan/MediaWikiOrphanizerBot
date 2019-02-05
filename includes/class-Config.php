<?php
# Copyright (C) 2019 Valerio Bozzolan, Daimona Eaytoy
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

namespace orphanizerbot;

/**
 * Dummy class used to store static config
 */
class Config {

	/**
	 * Get the singleton instance
	 *
	 * @return self
	 */
	public static function instance() {
		static $self = false;
		if( ! $self ) {
			$self = new self();
		}
		return $self;
	}

	/**
	 * Set a configuration value
	 *
	 * @param $name string
	 * @param $v mixed
	 * @return self
	 */
	public function set( $name, $v ) {
		$this->$name = $v;
		return $this;
	}

	/**
	 * Check existence of a configuration
	 *
	 * @param $name string
	 * @return bool
	 */
	public function has( $name ) {
		return isset( $this->$name );
	}

	/**
	 * Get a configuration value or a default one
	 *
	 * @param $name string
	 * @param $default mixed
	 * @return mixed
	 */
	public function get( $name, $default = null ) {
		return $this->has( $name ) ? $this->$name : $default;
	}

}
