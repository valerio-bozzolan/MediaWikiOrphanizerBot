<!-- START DOCUMENTATION -->
This is the template for the content of a daily counting page.

Placeholders:
	%1$d:   year
	%2$d:   month  1-12
	%2$02d: month 01-12
	%3$s:   month name
	%4$d:   day    1-31
	%4$02d: day   01-31
	%5$s:   mega table of PDCs

<!-- START TEMPLATE -->
{{#ifexpr:{{#timel:U}}-{{#timel:U|2018-4-28}}<8*24*3600|{{Utente:MauroBot/BotCancellazioni/Avvisi}}}}
%5$s
__NOTOC__
