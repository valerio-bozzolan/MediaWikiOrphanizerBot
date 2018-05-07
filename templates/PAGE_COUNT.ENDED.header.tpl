<!-- START DOCUMENTATION -->PAGE_COUNT.entry.multiple_note.tpl
This is the template for the content of a daily counting page.

Placeholders:
	%1$d:   year
	%2$d:   month  1-12
	%2$02d: month 01-12
	%3$s:   month name
	%4$d:   day    1-31
	%4$02d: day   01-31
	%5$s:   now (date formatted by the DATETIME.tpl)
	%6$s:   mega table of PDCs

<!-- START SPECIFIC EXAMPLE -->
Ultimo aggiornamento: 10:12 aprile 2018

<table>PDCs</table>

__NOTOC__

<!-- START TEMPLATE -->
Ultimo aggiornamento: %5$s

%6$s

__NOTOC__
