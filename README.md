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

Available options, most of them also on-wiki:
 --wiki=VALUE
 	Specify a wiki from it's UID
 --cfg=VALUE
 	Title of an on-wiki configuration page with JSON content model
 --list=VALUE
 	Specify a pagename that should contain the wikilinks to be orphanized
 --summary=VALUE
 	Edit summary
 --ns=VALUE
 	Namespace whitelist
 --warmup=VALUE
 	Start only if the last edit on the list was done at least $warmup seconds ago
 --cooldown=VALUE
 	End early when reaching this number of edits
 --debug
 	Increase verbosity
 --help
 -h
 	Show this message and quit
 --no-interaction
 	Do not confirm every change

 Example:
 	./orphanize.php --wiki=itwiki --list=Wikipedia:PDC/Elenco

 Have fun! by Valerio Bozzolan, Daimona Eaytoy
```
{
    "summary": "Bot: orphanizing links",
    "ns": [
        0
    ],
    "warmup": 120,
    "cooldown": 10
}
```

* `summary` is the edit summary
* `ns` if provided, list of whitelisted namespaces
* `warmup` if provided, number of __seconds__ to wait before starting (after last edit on the list)
* `cooldown` if provided, number of __edits__ to do until shutdown (you may want to re-schedule)

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
