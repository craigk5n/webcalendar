# Makefile for updating documentation automatically
# If you have phpdoc installed, you can use that to
# generate additional documentation.


all: WebCalendar-Database.html faqs.html

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
 ../includes/classes/AttachmentList.php \
 ../includes/classes/CommentList.php \
 ../includes/classes/Doc.php \
 ../includes/classes/DocList.php \
 ../includes/classes/Event.php \
 ../includes/classes/hkit/hcal.profile.php \
 ../includes/classes/RptEvent.php \
 ../includes/classes/WebCalendar.php \
 ../includes/classes/WebCalMailer.php \
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
 ../week_details.php

WebCalendar-Database.html: ../install/sql/tables-mysql.sql sql2html.pl
	perl sql2html.pl < ../install/sql/tables-mysql.sql > WebCalendar-Database.html

# You need phpdoc to generate the following documentation.
phpdoc/index.html: $(INCLUDES)
	phpdoc -f `echo $(INCLUDES) | sed -e 's/ /,/g'` -t phpdoc -ti "WebCalendar Function Documentation" -s on

# The faqs.html is used on the k5n.us website
faqs.html: WebCalendar-SysAdmin.html WebCalendar-UserManual.html \
	WebCalendar-DeveloperGuide.html \
	extractfaqs.pl
	perl extractfaqs.pl WebCalendar-SysAdmin.html WebCalendar-UserManual.html WebCalendar-DeveloperGuide.html > faqs.html
