# Makefile for updating documentation automatically


all: WebCalendar-Database.html


WebCalendar-Database.html: ../tables-mysql.sql sql2html.pl
	perl sql2html.pl < ../tables-mysql.sql > WebCalendar-Database.html


