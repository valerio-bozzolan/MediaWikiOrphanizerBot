<!-- START DOCUMENTATION -->
This is the template for the content of a daily log page.

Placeholders:
	%1$d:   year
	%2$d:   month  1-12
	%2$02d: month 01-12
	%3$s:   month name
	%4$d:   day    1-31
	%4$02d: day   01-31
	%5$s:   PDC informations
	        They are generated looping through the "PAGE_LOG.section.tpl" template

<!-- START SPECIFIC EXAMPLE -->
<noinclude>{{Paginecancellare}}</noinclude>
== 20 aprile ==
{{Wikipedia:Pagine da cancellare/Conta/2017 aprile 20}}

<!--inizio procedure con votazione-->
{{Wikipedia:Pagine da cancellare/ASD}}
{{Wikipedia:Pagine da cancellare/DSA}}

<!--inizio procedure semplificate-->
{{Wikipedia:Pagine da cancellare/GGH}}
{{Wikipedia:Pagine da cancellare/HGG}}

<!-- START TEMPLATE -->
<noinclude>{{Paginecancellare}}</noinclude>
== %4$d %3$s ==
{{Wikipedia:Pagine da cancellare/Conta/%1$d %3$s %4$d}}

%5$s
