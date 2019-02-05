<?php
// Fill this file with your Wikimedia bot credentials and save-as 'config.php'
mw\API::$DEFAULT_USERNAME = 'Foo';
mw\API::$DEFAULT_PASSWORD = 'Bar';

// fill with your timezone
date_default_timezone_set( 'Europe/Rome' );

// set other common configurations (see --help)
Config::instance()
	->set( 'wiki',    'itwiki' )
	->set( 'seealso', 'Voci correlate' );
