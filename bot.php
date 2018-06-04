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

use cli\Log;

// load credentials
require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

// the bot must be in sync with the italian community
date_default_timezone_set( 'Europe/Rome' );

// allowed only from command line interface
if( ! isset( $argv[ 0 ] ) ) {
	exit( 1 );
}

// command line arguments
$opts = getopt( 'h', [
	'ask',     // ask for every edit   | default: no
	'days:',   // days to be processed | default: only one
	'from:',   // date to start from   | default: from today
	'help',    // help message
	'verbose', // debug mode           | default: no
] );

// help message
if( isset( $opts[ 'help' ] ) || isset( $opts[ 'h' ] ) ) {
	printf( "usage: %s [OPTIONS]\n\n", $argv[ 0 ] );
	echo "    --days=DAYS       	how many days to be processed (default: 1)\n";
	echo "    --from=YYYY-MM-DD 	starting date (default: today)\n";
	echo "    --ask             	ask before saving\n";
	echo "    --verbose         	verbose mode\n";
	echo " -h --help            	show this help and exit\n";
	exit( 0 );
}

// days to be processed from now to the past
$DAYS = isset( $opts[ 'days' ] )
	? (int) $opts[ 'days' ]
	: 1;

// starting date formatted as 'YYYY-MM-DD'
$DATE = isset( $opts[ 'from' ] )
	? $opts[ 'from' ]
	: 'now';

// ask for every edit
if( isset( $opts[ 'ask' ] ) ) {
	Page::$ASK_BEFORE_SAVING = true;
}

// verbose mode
if( isset( $opts[ 'verbose' ] ) ) {
	Log::$DEBUG = true;
}

Log::info( 'start' );

$bot = Bot::createFromString( $DATE );
for( $i = 0; $i < $DAYS; $i++ ) {
	$bot->run()->previousDay();
}

Log::info( 'end' );
