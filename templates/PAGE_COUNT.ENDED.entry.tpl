<!-- START DOCUMENTATION -->
This is the template for a single ended PDC in the daily counting page.

Placeholders:
	%1$s: page title
	%2$d: temperature
	%3$s: '1' if this PDC is multiple
	%4$s: color associated to the PDC e.g. #fff
	%5$d: number of the row
	%6$s: PDC type e.g. 'consensuale'
	%7$s: duration
	%8$s: title of the log page
	%9$s: date

<!-- START SPECIFIC EXAMPLE -->
{{Conteggio cancellazioni/Concluse/Voce|i = 0 |voce = ASD |tipo = consensuale |data = %9$s |durata = 3 giorni |multipla = 1 }}

<!-- START TEMPLATE -->
{{Conteggio cancellazioni/Concluse/Voce|i = %5$d |voce = %1$s |tipo = %6$s |data = %9$s |durata = %7$s |multipla = %3$s }}
