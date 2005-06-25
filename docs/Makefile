# Makefile for updating documentation automatically


all: WebCalendar-Database.html phpdoc/index.html faqs.html


# List include files that will be searched for function documentation
INCLUDES = \
 ../includes/functions.php \
 ../includes/php-dbi.php \
 ../includes/init.php \
 ../includes/config.php \
 ../includes/translate.php \
 ../report.php \
 ../includes/translate.php \
 ../includes/classes/WebCalendar.class \
 ../includes/classes/Event.class \
 ../includes/classes/RptEvent.class

WebCalendar-Database.html: ../tables-mysql.sql sql2html.pl
	perl sql2html.pl < ../tables-mysql.sql > WebCalendar-Database.html

phpdoc/index.html: $(INCLUDES)
	phpdoc -f `echo $(INCLUDES) | sed -e 's/ /,/g'` -t phpdoc -ti "WebCalendar Function Documentation" -s on

faqs.html: WebCalendar-SysAdmin.html WebCalendar-UserManual.html \
	WebCalendar-DeveloperGuide.html \
	extractfaqs.pl
	perl extractfaqs.pl WebCalendar-SysAdmin.html WebCalendar-UserManual.html WebCalendar-DeveloperGuide.html > faqs.html
	


