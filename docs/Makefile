# Makefile for updating documentation automatically


all: WebCalendar-Database.html phpdoc/index.html faqs.html


# List include files that will be searched for function documentation
INCLUDES = \
 ../includes/access.php \
 ../includes/assert.php \
 ../includes/config.php \
 ../includes/functions.php \
 ../includes/init.php \
 ../includes/php-dbi.php \
 ../includes/translate.php \
 ../includes/classes/user/User.class.php \
 ../includes/views.php \
 ../includes/translate.php \
 ../includes/classes/WebCalendar.class.php \
 ../includes/classes/Event.class.php \
 ../includes/classes/RptEvent.class.php \
 ../report.php

WebCalendar-Database.html: ../install/sql/tables-mysql.sql sql2html.pl
	perl sql2html.pl < ../install/sql/tables-mysql.sql > WebCalendar-Database.html

phpdoc/index.html: $(INCLUDES)
	phpdoc -f `echo $(INCLUDES) | sed -e 's/ /,/g'` -t phpdoc -ti "WebCalendar Function Documentation" -s on

faqs.html: WebCalendar-SysAdmin.html WebCalendar-UserManual.html \
	WebCalendar-DeveloperGuide.html \
	extractfaqs.pl
	perl extractfaqs.pl WebCalendar-SysAdmin.html WebCalendar-UserManual.html WebCalendar-DeveloperGuide.html > faqs.html
	


