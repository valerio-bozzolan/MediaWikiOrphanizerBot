# MediaWiki Orphanizer bot

This software is an Italian Wikipedia bot. It delinks page titles.

Actually this is just a proof-of-concept from an idea of [Parma1983](https://it.wikipedia.org/wiki/Utente:Parma1983) and [.avgas](https://it.wikipedia.org/wiki/Utente:.avgas) and other awesome wikiguys.

## Installation

	sudo apt install git php-cli
	git clone --recursive https://github.com/valerio-bozzolan/MediaWikiOrphanizerBot.git

## Configuration

You know you should provide your bot credentials in order to use a tool.

1. Open the file [`config-example.php`](config-example.php) with a text editor
2. Fill your bot credentials
3. Save-as `config.php`

## On-wiki configuration
You need two pages: one with a list of links pointing to the pages to orphanize (pass its title via the `list` parameter),
and one with generic config (to be passed via `cfg`). The latter should be a JSON page and can have the following options:

 * `summary` (string) - The summary to use when editing.
 * `ns` (array) - To only edit pages on the given namespaces. `null` means all namespaces.

## Usage

	./orphanizer.php --help

```
Welcome in your MediaWiki Orphanizer bot!

 Usage:   ./orphanize.php [OPTIONS]
 Options: --wiki UID          Specify a wiki from it's UID.
          --list PAGENAME     Specify a pagename that should
                              contain the wikilinks to be
                              orphanized by this bot.
          --cfg PAGENAME      Read the config from the specified
                              wikipage
          --debug             increase verbosity
          --help              Show this message and quit.
 Example:
          ./orphanize.php --wiki itwiki --list Wikipedia:PDC/Elenco

 Have fun! by Valerio Bozzolan
```

## License

Copyright (C) 2019 [Valerio Bozzolan](https://it.wikipedia.org/wiki/Utente:Valerio_Bozzolan)

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
