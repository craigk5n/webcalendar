# WebCalendar java Makefile
# $Id#
#

JCC =	javac
JAR =	jar
JAVADOC = javadoc

PACKAGEDIR = us/k5n/webcalendar

.SUFFIXES: .java .class

.java.class:
	$(JCC) -classpath . $*.java

# START CLASSLIST
CLASSLIST = \
$(PACKAGEDIR)/Event.class $(PACKAGEDIR)/EventDisplayer.class $(PACKAGEDIR)/EventList.class $(PACKAGEDIR)/EventLoader.class $(PACKAGEDIR)/Login.class $(PACKAGEDIR)/LoginSession.class $(PACKAGEDIR)/MessageDisplayer.class $(PACKAGEDIR)/Reminder.class $(PACKAGEDIR)/ReminderDisplayer.class $(PACKAGEDIR)/ReminderList.class $(PACKAGEDIR)/ReminderLoader.class $(PACKAGEDIR)/ShowReminder.class $(PACKAGEDIR)/ui/ReminderApp.class $(PACKAGEDIR)/ui/SampleApp.class $(PACKAGEDIR)/Utils.class $(PACKAGEDIR)/WebCalendarClient.class $(PACKAGEDIR)/WebCalendarErrorException.class $(PACKAGEDIR)/WebCalendarParseException.class 
# END CLASSLIST

all: webcalendar.jar

webcalendar.jar: $(CLASSLIST) manifest-mod
	$(JAR) cmf manifest-mod webcalendar.jar \
	us/k5n/webcalendar/*class us/k5n/webcalendar/ui/*class

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
