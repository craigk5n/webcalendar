# WebCalendar java Makefile
#
# Notes:
# If you add/remove/change the java files that need to be compiled, just
# do 'make update' to update this Makefile.
#
# This has only been tested on Linux and Windoze/Cygwin, but should work
# with any OS.
#
# And, yes, I know java people like to use Ant, but I just don't see the
# advantage in this case...
#
##############################################################################

JCC =	javac
JAR =	jar
JAVADOC = javadoc
# script to sign the JAR file
SIGNJAR = signjar.sh

PACKAGEDIR = us/k5n/webcalendar

.SUFFIXES: .java .class

.java.class:
	$(JCC) -classpath . $*.java

# START CLASSLIST
CLASSLIST = $(PACKAGEDIR)/Event.class $(PACKAGEDIR)/EventDisplayer.class $(PACKAGEDIR)/EventList.class $(PACKAGEDIR)/EventLoader.class $(PACKAGEDIR)/Login.class $(PACKAGEDIR)/LoginSession.class $(PACKAGEDIR)/MessageDisplayer.class $(PACKAGEDIR)/Reminder.class $(PACKAGEDIR)/ReminderDisplayer.class $(PACKAGEDIR)/ReminderList.class $(PACKAGEDIR)/ReminderLoader.class $(PACKAGEDIR)/ShowReminder.class $(PACKAGEDIR)/Utils.class $(PACKAGEDIR)/WebCalendarClient.class $(PACKAGEDIR)/WebCalendarErrorException.class $(PACKAGEDIR)/WebCalendarParseException.class $(PACKAGEDIR)/ui/ReminderApp.class $(PACKAGEDIR)/ui/SampleApp.class $(PACKAGEDIR)/ui/ControlPanel/UserListener.class $(PACKAGEDIR)/ui/ControlPanel/UserDialog.class $(PACKAGEDIR)/ui/ControlPanel/Main.class $(PACKAGEDIR)/ui/ControlPanel/ReadOnlyTable.class $(PACKAGEDIR)/ui/ControlPanel/UserListCellRenderer.class $(PACKAGEDIR)/User.class $(PACKAGEDIR)/UserList.class $(PACKAGEDIR)/ActivityLog.class $(PACKAGEDIR)/ActivityLogList.class 
# END CLASSLIST

all: webcalendar.jar

webcalendar.jar: $(CLASSLIST) manifest-mod
	$(JAR) cmf manifest-mod webcalendar.jar \
	us/k5n/webcalendar/*class us/k5n/webcalendar/ui/*class \
	us/k5n/webcalendar/ui/ControlPanel/*class
	@if [ -f $(SIGNJAR) ]; then \
		$(SIGNJAR) webcalendar.jar; \
	fi
	cp -p webcalendar.jar ../ws

clean:
	rm -f $(CLASSLIST) webcalendar.jar

update:
	@echo "Updating makefile"; \
	sed -n '1,/^# START CLASSLIST/ p' Makefile > Makefile.new; \
	echo "CLASSLIST = " | tr -d '\012\015' >> Makefile.new; \
	make classlist >> Makefile.new; \
	sed -n '/^# END CLASSLIST/,$$ p' Makefile >> Makefile.new; \
	mv Makefile Makefile.old; \
	mv Makefile.new Makefile


classlist:
	@find $(PACKAGEDIR) -name "*.java" -print | \
	sed 's?$(PACKAGEDIR)?$$(PACKAGEDIR)?g' | \
	sed 's;\.java;.class;g' | tr '\012\015' '  '; \
	echo ""


doc:
	$(JAVADOC) -d api us.k5n.webcalendar us.k5n.webcalendar.ui
