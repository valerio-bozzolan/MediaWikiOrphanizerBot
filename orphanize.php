#!/usr/bin/php
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

use \wm\WikipediaIt;

namespace itwikidelbot;

// autoload classes
require __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'autoload.php';

// load credentials
require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

use \cli\Log;
use \web\MediaWikis;

$TITLE_SOURCE = 'Utente:.avgas/Wikilink da orfanizzare';

// wiki identifier
$wiki_uid = 'itwiki';

// wiki instance
$wiki = Mediawikis::findFromUid( $wiki_uid );

// query last revision
$revision =
	$wiki->fetch( [
		'action'  => 'query',
		'prop'    => 'revisions',
		'titles'  => $TITLE_SOURCE,
		'rvslots' => 'main',
		'rvprop'  => 'content',
		'rvlimit' => 1,
	] );

$content = null;
foreach( $revision->query->pages as $page ) {
	foreach( $page->revisions as $revision ) {
		$content = $wiki->createWikitext( $content );
	}
}

$content->pregMatchAll( '\[\[.*\]\]', $matches );

var_dump( $matches );
