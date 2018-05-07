<!-- START DOCUMENTATION -->
This is the template for a single ended PDC in the daily counting page.

Placeholders:
	%1$s: page title
	%2$d: temperature
	%3$s: small note shown only if this PDC is multiple (PAGE_COUNT.entry.multiple_note.tpl)
	%4$s: color associated to the PDC e.g. #fff
	%5%d: number of the row
	%6$s: PDC type e.g. 'consensuale'
	%7%s: duration
	%8%s: title of the log page
	%9$s: goto action label e.g. 'pagina di discussione'

<!-- START SPECIFIC EXAMPLE -->
<tr bgcolor="#fff">
	<td style="width:3em; text-align:center;">1</td>
	<td style="width:3em; text-align:center;">consensuale</td>
	<td style="width:6em; text-align:center;">8 giorni</td>
	<td>'''[[:ASD]]''' ([[{{#ifeq:{{PAGENAME}}|Pagine da cancellare|Pagine da cancellare|Wikipedia:Pagine da cancellare/ASD|Wikipedia:Pagine da cancellare/Log/2018 aprile 17#ASD}}|vai alla discussione]])  '''Nota:''' [[Wikipedia:Cancellazioni multiple|procedura di cancellazione multipla]]</td>
</tr>

<!-- START TEMPLATE -->
<tr bgcolor="%4$s">
	<td style="width:3em; text-align:center;">%5$d</td>
	<td style="width:3em; text-align:center;">%6$s</td>
	<td style="width:6em; text-align:center;">%7$s</td>
	<td>'''[[:%1$s]]''' ([[:{{#ifeq:{{PAGENAME}}|Pagine da cancellare|%1$s|%8$s#%1$s}}|vai alla %9$s]]) %3$s</td>
</tr>

