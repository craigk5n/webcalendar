# Makefile for updating documentation automatically


all: WebCalendar-Database.html WebCalendar-Functions.html faqs.html


# List include files that will be searched for function documentation
INCLUDES = \
	../includes/functions.php \
	../includes/php-dbi.php \
	../includes/init.php \
	../includes/config.php \
	../includes/translate.php

WebCalendar-Database.html: ../tables-mysql.sql sql2html.pl
	perl sql2html.pl < ../tables-mysql.sql > WebCalendar-Database.html

WebCalendar-Functions.html: php2html.pl $(INCLUDES)
	perl php2html.pl $(INCLUDES) > $@

faqs.html: WebCalendar-SysAdmin.html WebCalendar-UserManual.html \
	extractfaqs.pl
	perl extractfaqs.pl WebCalendar-SysAdmin.html WebCalendar-UserManual.html > faqs.html
	


