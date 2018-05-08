<!-- START DOCUMENTATION -->
This is the template for a single running PDC in the daily counting page.

Placeholders:
	%1$s: page title
	%2$d: temperature
	%3$s: '1' if it's multiple
	%4$s: color associated to the PDC e.g. '#fff'
	%5%d: number of the row
	%6$s: PDC type e.g. 'consensuale'
	%7%s: duration e.g. 'un giorno'
	      see the template DURATION_*.tpl
	%8%s: title of the log page
	%9$s: goto action label e.g. 'pagina di discussione'

<!-- START SPECIFIC EXAMPLE -->
{{Conteggio cancellazioni/In corso/Voce|i = 1 |voce = ASD |tipo = votazione |multipla = 1 |temperatura = 42 }}

<!-- START TEMPLATE -->
{{Conteggio cancellazioni/In corso/Voce|i = %5$d |voce = %1$s |tipo = %6$s |multipla = %3$s |temperatura = %2$d }}
