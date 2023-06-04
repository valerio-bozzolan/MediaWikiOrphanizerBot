<?php
# Copyright (C) 2019-2023 Valerio Bozzolan and contributors
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

// load some common functions
require __DIR__ . '/functions.php';

// boz-mw framework expected directory
// NOTE: this can be a symbolic link to another location
$boz_mw_dir  = __DIR__ . '/boz-mw/';

// boz-mw framework entry point providing all necessary high-level functions
$boz_mw_path = $boz_mw_dir . 'autoload-with-laser-cannon.php';

if( file_exists( $boz_mw_path ) ) {
	require $boz_mw_path;
} else {
	// add some useful tips to the end-user
	throw new Exception( sprintf(
		'This pathname must exist: %s. To fix please run this command: "%s"',
		$boz_mw_path,
		sprintf(
			'git clone %s %s',
			'https://gitpull.it/source/boz-mw/',
			$boz_mw_dir
		)
	) );
}

// load the dummy config class
require __DIR__ . '/class-Config.php';

/**
 * Require a configuration file or create it
 *
 * https://gitpull.it/source/boz-mw/browse/master/include/functions.php
 * https://gitpull.it/source/boz-mw/browse/master/include/class-cli%5CConfigWizard.php
 */
config_wizard( __DIR__ . '/../config.php' );
