# Italian Wikipedia Deletion Bot

This project aims to handle the public deletion log of Italian Wikipedia pages.

## History of the project

The script was originally developed by Mauro762 (aka MauroBot) in 2013 using JavaScript and previously developed in Python {{Citation needed}}.

## TODO

* [X] Yearly category creation
* [X] Monthy category creation
* [X] Daily category creation
* [X] Daily log page update
* [X] Daily counting page update
* [X] Most of the work :)
* [ ] Be sure that It doesn't destroy anything

## Installation

	apt-get install php-cli
    git clone --recursive https://github.com/valerio-bozzolan/ItalianWikipediaDeletionBot

## Basic configuration

Fill `config-example.php` and save-as `config.php`.

## Basic usage

    ./bot.php

## Hacking

Most of the behaviours — as the content of the categories, the pages, their edit summaries, etc. — can be changed simply hacking the content of the files from the [/templates](/templates) directory. Trust me, you are able to do it.

Start becoming familiar with their structure:

	<!-- START DOCUMENTATION -->
	This is the place for some documentation, expecially about "placeholders".
	<!-- START SPECIFIC EXAMPLE -->
	This is the place for a specific example
	<!-- START TEMPLATE -->
	This is the place for the most important part of this file.
	This part is what this template will generates.
	It uses stuff like "$1" or "%1%02d" as generic placeholders.

To be honest: everything above the `<!-- START TEMPLATE -->` line it's pure documentation sugar. It's written to help you. I've spent some minutes on them. Please RTFM. asd

A non-traumatic template example can be found [here](templates/CATEGORY_YEAR.content.tpl). It describes the generation of the yearly category.

## Credits

You may not want to know this: HTTP connections, MediaWiki APIs (including login and tokens etc.), and other stuff, are handled by the [boz-mw](https://github.com/valerio-bozzolan/boz-mw) framework.

## License

Original files released under [Creative Commons BY-SA 3.0 International](https://creativecommons.org/licenses/by-sa/3.0/) and [WMF terms](https://wikimediafoundation.org/wiki/Special:MyLanguage/Terms_of_Use/it) by [Mauro742](https://it.wikipedia.org/wiki/Utente:Mauro742)/[MauroBot](https://it.wikipedia.org/wiki/Utente:MauroBot):
* https://it.wikipedia.org/wiki/Utente:MauroBot/BotCancellazioni/main.js
* https://it.wikipedia.org/wiki/Utente:MauroBot/BotCancellazioni/bot.js
* https://it.wikipedia.org/wiki/Utente:MauroBot/BotCancellazioni/category.js
* https://it.wikipedia.org/wiki/Utente:MauroBot/BotCancellazioni/dateFunctions.js
* https://it.wikipedia.org/wiki/Utente:MauroBot/BotCancellazioni/core.js
* https://it.wikipedia.org/wiki/Utente:MauroBot/BotCancellazioni/globals.js
* https://it.wikipedia.org/wiki/Utente:MauroBot/BotCancellazioni/gui.js

Copyright (C) 2018 [Valerio Bozzolan](https://it.wikipedia.org/wiki/Utente:Valerio_Bozzolan)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
