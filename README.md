# MediaWiki Orphanizer bot

This software is an Italian Wikipedia bot. It delinks page titles. Info:

* https://it.wikipedia.org/wiki/Utente:OrfanizzaBot

## Installation

Preparation:

```
sudo apt install git php-cli
```

Installation:

```
git clone https://gitlab.wikimedia.org/valeriobozzolan/mediawiki-orphanizer-bot.git
git clone https://gitpull.it/source/boz-mw/ mediawiki-orphanizer-bot/includes/boz-mw
```

## Configuration

You know you should provide your bot credentials in order to use a tool.

1. Open the file `config-example.php` with a text editor
2. Fill your bot credentials
3. Save-as `config.php`

## On-wiki configuration
You need two pages: one with a list of links pointing to the pages to orphanize (pass its title via the `list` parameter),
and one with generic config (to be passed via `cfg`). The latter should be a JSON page and can have the following options:

* `summary` (string) - The summary to use when editing.
* `seealso` (string) - The canonical text for the "See also" section
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

A live example:

https://it.wikipedia.org/wiki/Utente:OrfanizzaBot/Configurazione

## Usage from command line

```
./orphanizer.php --help
```

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

Basic usage:

```
./orphanize.php --wiki=itwiki
```

Have fun!

## Sandboxed Tests

Example sandboxed usage:

```
./orphanize.php --ns=2 --list="User:Foo/SandboxList" --skip-permissions
```

So you can have a page like `User:Foo/SandboxList` with this content:

```
* [[ACME COMPANY SELLINGS NONSENSE THINGS]]
```

And then creating a fake article in `User:Foo/ExampleArticle` with:

```
Yeah I'm a Wikipedia Article that talks about things
including [[ACME COMPANY SELLINGS NONSENSE THINGS]] but not limited to
[[ACME COMPANY SELLINGS NONSENSE THINGS|things]]. Yeah. This is a test.
The links will be orphanized soon probably. But not [[this one]].
```

Have fun!

## Questions

https://it.wikipedia.org/wiki/Discussioni_utente:OrfanizzaBot

## Framework

This tool uses the `boz-mw` framework. Source code:

https://gitpull.it/source/boz-mw/

Official documentation of `boz-mw`:

https://gitpull.it/w/first_steps_with_boz-mw/

## Debugging and Troubleshooting

You do not need any particular technical permission to test this bot in a sandbox.

Example debug manual run:

```
./orphanize.php \
	--debug \
	--wiki=itwiki \
	--ns=4 \
	--cfg=Utente:OrfanizzaBot/Configurazione/Sandbox \
	--list=Utente:OrfanizzaBot/Wikilink_da_orfanizzare/Sandbox \
	--summary="[TEST] Orphanizing things" \
	--skip-permissions
```

In this way you can operate on the namespace 4 (that is `Wikipedia:`) simulating Wikipedia articles with links
to be orphanized.

Example sandbox Wikipedia article (that will be edited):

https://it.wikipedia.org/wiki/Wikipedia:Pagina_delle_prove

https://it.wikipedia.org/w/index.php?title=Wikipedia:Pagina_delle_prove&oldid=133820814

Example sandbox list (that will be edited):

https://it.wikipedia.org/wiki/Utente:OrfanizzaBot/Wikilink_da_orfanizzare/Sandbox

https://it.wikipedia.org/w/index.php?title=Utente:OrfanizzaBot/Wikilink_da_orfanizzare/Sandbox&oldid=133820805

Example sandbox configuration (that will be read):

https://it.wikipedia.org/wiki/Utente:OrfanizzaBot/Configurazione/Sandbox

IMPORTANT: Only try to orphanize nonsenses titles like `[[Puffolo test]]` in order to do NOT touch any unrelated page.

## Update Production

To update the bot in production hosted by Wikimedia Toolforge, you need access to the Tool:itwiki. Documentation:

https://wikitech.wikimedia.org/wiki/Tool:Itwiki

Then just run this command from your computer:

```
ssh login.toolforge.org <<EOF
  become itwiki
  cd orphanizerbot
  git pull
EOF
```

## License

Copyright (C) 2020-2023 [Valerio Bozzolan](https://it.wikipedia.org/wiki/Utente:Valerio_Bozzolan) and contributors

Copyright (C) 2019 [Valerio Bozzolan](https://it.wikipedia.org/wiki/Utente:Valerio_Bozzolan), Daimona Eaytoy and contributors

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
