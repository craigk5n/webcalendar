# Makefile for updating documentation automatically


all: WebCalendar-Database.html WebCalendar-Functions.html faqs.html


WebCalendar-Database.html: ../tables-mysql.sql sql2html.pl
	perl sql2html.pl < ../tables-mysql.sql > WebCalendar-Database.html

WebCalendar-Functions.html: ../includes/functions.php php2html.pl
	perl php2html.pl ../includes/functions.php > $@

faqs.html: WebCalendar-SysAdmin.html WebCalendar-UserManual.html \
	extractfaqs.pl
	perl extractfaqs.pl WebCalendar-SysAdmin.html WebCalendar-UserManual.html > faqs.html
	


