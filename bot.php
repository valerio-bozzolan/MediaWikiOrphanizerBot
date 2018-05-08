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

// the first argument is the days to go backward
$DAYS = isset( $argv[ 1 ] ) ? (int) $argv[ 1 ] : 1;

// the second argument is a 'YYYY-MM-DD' date
$DATE = isset( $argv[ 2 ] ) ? $argv[ 2 ] : 'now';

$bot = Bot::createFromString( $DATE );
for( $i = 0; $i < $DAYS; $i++ ) {
	$bot->run()->previousDay();
}
