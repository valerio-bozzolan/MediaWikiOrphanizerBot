<?php
# Copyright (C) 2019, 2020, 2021 Valerio Bozzolan
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

// autoload boz-mw classes
require __DIR__ . '/boz-mw/autoload-with-laser-cannon.php';

// load the dummy config class
require __DIR__ . '/class-Config.php';

/**
 * Require a configuration file or create it
 *
 * https://gitpull.it/source/boz-mw/browse/master/include/functions.php
 * https://gitpull.it/source/boz-mw/browse/master/include/class-cli%5CConfigWizard.php
 */
config_wizard( __DIR__ . '/../config.php' );
