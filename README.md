# MediaWiki Orphanizer bot

This software is an Italian Wikipedia bot. It delinks page titles. Info:

* https://it.wikipedia.org/wiki/Utente:OrfanizzaBot

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
* `list-summary` (string) - The summary to use when editing the page list.
* `done-text` (string) - What to replace a processed wlink with. $1 is the pointed title.
* `ns` (array|string) - To only edit pages on the given namespaces. `null` means all namespaces. You can specify also a string with values separated by a pipe, like in command line.
* `warmup` (int) - number of __seconds__ to wait before starting (after last edit on the list)
* `cooldown` (int) - number of __edits__ to do until shutdown (you may want to re-schedule)
* `delay` (int) - number of __seconds__ to wait before saving
* `turbofresa` (int) - if the list is older than this number of seconds, a turbofresa will be spawned to clear the list

An example:

```
{
    "summary": "Bot: orphanizing links",
    "list-summary": "Updating list",
    "done-text": "* [[Special:WhatLinksHere/$1]] - {{done}}"
    "ns": [
        0
    ],
    "warmup": 120,
    "cooldown": 10,
    "delay": 30
}
```

## Usage from command line

	./orphanizer.php --help

```
Welcome in your MediaWiki Orphanizer bot!

Available options, most of them also on-wiki:
 --wiki=VALUE
        Specify a wiki from its UID
 --cfg=VALUE
        Title of an on-wiki configuration page with JSON content model
 --list=VALUE
        Specify a pagename that should contain the wikilinks to be orphanized
 --summary=VALUE
        Edit summary
 --list-summary=VALUE
        Edit summary for editing the list
 --done-text=VALUE
        Replacement for the wikilink in the list
 --ns=VALUE
        Namespace whitelist (values separated by pipe)
 --delay=VALUE
        Additional delay between each edit
 --warmup=VALUE
        Start only if the last edit on the list was done at least $warmup seconds ago
 --cooldown=VALUE
        End early when reaching this number of edits
 --turbofresa=VALUE
        If the list is older than this number of seconds a turbofresa will be spawned to clean the list
 --turbofresa-text=VALUE
        Text that will be saved to clean an old list
 --turbofresa-summary=VALUE
        Edit summary to be used when cleaning an old list
 --seealso=VALUE
        Title of your local "See also" section
 --skip-permissions
        Execute the bot even if the list was last edited by a non-sysop (or by the bot itself)
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

## License

Copyright (C) 2019, 2020, 2021 [Valerio Bozzolan](https://it.wikipedia.org/wiki/Utente:Valerio_Bozzolan)

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
