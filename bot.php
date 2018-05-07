#!/usr/bin/php
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

// autoload classes
require __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'autoload.php';

// load credentials
require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

// create the yearly category
$year = date( 'Y' );
( new CategoryYear( $year ) )
	->saveIfNotExists();

// create the monthly category
$month = date( 'n' ); // 1-12
( new CategoryYearMonth( $year, $month ) )
	->saveIfNotExists();

// create the daily category
$day = date( 'j' );
( new CategoryYearMonthDayTypes( $year, $month, $day ) )
	->saveIfNotExists()
	->run();
