# Makefile for updating documentation automatically


all: WebCalendar-Database.html phpdoc/index.html faqs.html


# List include files that will be searched for function documentation
INCLUDES = \
 ../access.php \
 ../admin.php \
 ../category_handler.php \
 ../edit_entry.php \
 ../edit_entry_handler.php \
 ../edit_layer_handler.php \
 ../edit_remotes_handler.php \
 ../edit_report.php \
 ../export_handler.php \
 ../icalclient.php \
 ../import.php \
 ../import_outlookcsv.php \
 ../import_palmdesktop.php \
 ../includes/access.php \
 ../includes/assert.php \
 ../includes/classes/AttachmentList.class \
 ../includes/classes/CommentList.class \
 ../includes/classes/Doc.class \
 ../includes/classes/DocList.class \
 ../includes/classes/Event.class \
 ../includes/classes/hkit/hcal.profile.php \
 ../includes/classes/RptEvent.class \
 ../includes/classes/WebCalendar.class \
 ../includes/classes/WebCalMailer.class \
 ../includes/config.php \
 ../includes/dbi4php.php \
 ../includes/dbtable.php \
 ../includes/formvars.php \
 ../includes/functions.php \
 ../includes/gradient.php \
 ../includes/help_list.php \
 ../includes/init.php \
 ../includes/menu/index.php \
 ../includes/moon_phases.php \
 ../includes/translate.php \
 ../includes/user.php \
 ../includes/validate.php \
 ../includes/views.php \
 ../includes/xcal.php \
 ../install/default_config.php \
 ../install/install_functions.php \
 ../list_unapproved.php \
 ../purge.php \
 ../register.php \
 ../report.php \
 ../tools/send_reminders.php \
 ../upcoming.php \
 ../view_entry.php \
 ../view_r.php \
 ../view_t.php \
 ../week_details.php \
 ../ws/get_events.php \
 ../ws/get_reminders.php \
 ../ws/get_unapproved.php \
 ../ws/ws.php

WebCalendar-Database.html: ../install/sql/tables-mysql.sql sql2html.pl
	perl sql2html.pl < ../install/sql/tables-mysql.sql > WebCalendar-Database.html

phpdoc/index.html: $(INCLUDES)
	phpdoc -f `echo $(INCLUDES) | sed -e 's/ /,/g'` -t phpdoc -ti "WebCalendar Function Documentation" -s on

faqs.html: WebCalendar-SysAdmin.html WebCalendar-UserManual.html \
	WebCalendar-DeveloperGuide.html \
	extractfaqs.pl
	perl extractfaqs.pl WebCalendar-SysAdmin.html WebCalendar-UserManual.html WebCalendar-DeveloperGuide.html > faqs.html
