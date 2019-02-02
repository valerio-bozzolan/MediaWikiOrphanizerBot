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

use \cli\Log;
use \cli\Opts;

/**
 * Show the help message and die
 */
function show_help() {

	global $argv;

	echo "Welcome in your MediaWiki Orphanizer bot!\n\n";
	echo "Available options, most of them also on-wiki:\n";

	// show every available option
	foreach( Opts::instance()->getParams() as $param ) {

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

/**
 * Get an option value
 *
 * It's retrieved from a command line argument,
 * or from the on-wiki configuration page,
 * or from the default value provided.
 *
 * @param $name string
 * @param $default mixed Default value
 */
function option( $name, $default = null ) {

	// try the wiki option, then the $default
	$value = wiki_option( $name, $default );

	// try the cli option, then wiki option
	return Opts::instance()->getArg( $name, $value );
}

/**
 * Get an on-wiki option value
 *
 * The option is retrieved from a wiki page
 * with a JSON content model.
 *
 * @param $name string
 * @param $default mixed Default value
 */
function wiki_option( $name, $default = null ) {

	$config = wiki_config();

	// retrieve the option if exists
	return isset( $config->{ $name } )
	            ? $config->{ $name }
	            : $default;
}

/**
 * Load the wiki configuration (once)
 *
 * @return object
 */
function wiki_config() {
	static $config = null;

	// retrieve the configuration only once
	if( $config === null ) {
		$page = Opts::instance()->getArg( 'cfg', 'Utente:OrfanizzaBot/Configurazione' );
		$config = fetch_json_page( $page );
	}

	return $config;
}

/**
 * Fetch a MediaWiki page with the JSON
 * content model.
 *
 * @param $pagename string
 * @return object
 */
function fetch_json_page( $pagename ) {

	global $wiki;

	Log::info( "reading $pagename" );

	$response =
		$wiki->fetch( [
			'action'  => 'query',
			'titles'  => $pagename,
			'prop'    => 'revisions',
			'rvslots' => 'main',
			'rvprop'  => 'content',
		] );

	$revision = reset( $response->query->pages )->revisions[0];
	if ( $revision->slots->main->contentmodel !== 'json' ) {
		Log::error( "the page $pagename must have JSON content model" );
		exit( 1 );
	}

	return json_decode( $revision->slots->main->{ '*' } );
}
