# Italian Wikipedia Deletion Bot

This projects aims to handle the public deletion log of Italian Wikipedia pages.

## History of the project

The script was originally developed by Mauro762 (aka MauroBot) in 2013 using JavaScript.

## TODO

* [X] Yearly category creation
* [X] Monthy category creation
* [X] Daily category creation
* [ ] Most of the work :)

## Installation

	apt-get install php-cli
    git clone --recursive https://github.com/valerio-bozzolan/ItalianWikipediaDeletionBot

## Configuration

Fill `config-example.php` and save-as `config.php`.

## Usage

    ./bot.php

## Hacking

The content of the categories and the text of edit summaries can be changed hacking in the [/templates](/templates) directory.

You may not want to know this: HTTP connections, MediaWiki APIs (including login and tokens etc.), and other stuff, is handled by the [boz-mw](https://github.com/valerio-bozzolan/boz-mw) framework.

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
