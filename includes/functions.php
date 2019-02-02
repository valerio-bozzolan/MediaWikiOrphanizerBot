<?php
# Copyright (C) 2019 Valerio Bozzolan
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

/**
 * Show the help message and die
 */
function show_help() {

	global $argv;

	echo "Welcome in your MediaWiki Orphanizer bot!\n\n";
	echo "Available options:\n";

	// show every available option
	foreach( \cli\Opts::instance()->getParams() as $param ) {

		// long option
		if( $param->hasLongName() ) {
			echo " --{$param->getLongName()}";
			if( $param->isValueRequired() ) {
				echo "=VALUE";
			}
			echo "\n";
		}

		// short option
		if( $param->hasShortName() ) {
			echo " -{$param->getShortName()}";
			if( $param->isValueRequired() ) {
				echo "=VALUE";
			}
			echo "\n";
		}

		// description
		echo " \t{$param->getDescription()}\n";
	}

	echo "\n Example:\n"                                               .
	     " \t{$argv[0]} --wiki=itwiki --list=Wikipedia:PDC/Elenco\n\n" .
	     " Have fun! by Valerio Bozzolan, Daimona Eaytoy\n"            ;

	exit( 0 );

}
