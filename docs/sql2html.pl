#!/usr/local/bin/perl
#
# sql2html.pl
#
# Description:
#	Create HTML documentation from a sql file.
#
# History:
#	12-Oct-2002	Created
#
#######################################################################

$verbose = 0;


sub th {
  ( $s ) = @_;
  return "<TH VALIGN=\"top\" BGCOLOR=\"#C0C0C0\">$s</TH>";
}
sub td {
  ( $s ) = @_;
  return "<TD VALIGN=\"top\" BGCOLOR=\"#E0E0E0\">$s</TD>";
}


sub print_table {
  $out{$name} = "<H3><A NAME=\"$name\">$name</A></H3>\n";
  $out{$name} .= "<BLOCKQUOTE>\n";
  $out{$name} .= "$description<P>\n"
    if ( defined ( $description ) );
  $out{$name} .= "<TABLE BORDER=\"0\">";
  $out{$name} .= "<TR>" . th("Column Name") . th("Type") . th("Length") .
     th("Null") . th("Default") . th("Description") . "</TR>\n";
  for ( $i = 0; $i < @column_name; $i++ ) {
    $out{$name} .= "<TR>";
    if ( defined ( $table_keys{$column_name[$i]} ) ) {
      $out{$name} .= td("<FONT COLOR=\"#A00000\"><B>" . $column_name[$i] .
        "</B></FONT>");
    } else {
      $out{$name} .= td($column_name[$i]);
    }
    $out{$name} .= td($column_type[$i]);
    $out{$name} .= td($column_size[$i]);
    $out{$name} .= td($column_null[$i]);
    $out{$name} .= td($column_default[$i]);
    $out{$name} .= td($column_descr[$i]);
  }
  $out{$name} .= "</TABLE>\n";
  $out{$name} .= "</BLOCKQUOTE>\n";
}

# first, get WebCalendar version
open ( F, "../includes/config.php" ) || die "Error reading config.php: $!\n";
while ( <F> ) {
  if ( /PROGRAM_NAME/ ) {
    if ( /"/ ) {
      $v = $';
      if ( $v =~ /"/ ) { $v = $`; }
    }
    last;
  }
}
close ( F );
$v = "Unknown Version" if ( ! defined ( $v ) );

$line = 1;
$in_create_table = 0;
$in_comment = 0;
while ( <> ) {
  chop;
  $line++;
  #print "Line: $line\n" if ( $verbose );
  if ( $in_create_table ) {
    if ( /\/\*/ ) {
      $cmt = $';
      if ( $cmt =~ /\*\// ) {
        $descr .= " " . $`;
      }
    } elsif ( /PRIMARY\s+KEY\s+\((.*)\)/i ) {
      $keys = $1;
      $keys =~ s/ //g;
      @keys = split ( /,/, $keys );
      foreach $k ( @keys ) {
        print "Found key $k\n" if ( $verbose );
        $table_keys{$k} = 1;
      }
    } elsif ( /^\);/ ) {
      print "End of table.\n" if ( $verbose );
      $in_create_table = 0;
      &print_table ();
      # reset values
      @column_name = ();
      @column_size = ();
      @column_type = ();
      @column_null = ();
      @column_default = ();
      @column_descr = ();
      %table_keys = ();
      undef ( $description );
      undef ( $name );
    } elsif ( /(\S+)\s*(\S+)/ ) {
      $n = $1;
      $t = $2;
      $t =~ s/,//;
      print "Found column $n\n" if ( $verbose );
      push ( @column_name, $n );
      if ( $t =~ /\((\d+)\)/ ) {
        push ( @column_size, $1 );
        push ( @column_type, $` );
      } else {
        push ( @column_size, "&nbsp;" );
        push ( @column_type, $t );
      }
      if ( /not null/i ) {
        push ( @column_null, "N" );
      } elsif ( / null/i ) {
        push ( @column_null, "Y" );
      } else {
        push ( @column_null, "Y" );
      }
      if ( / DEFAULT (\S+)/i ) {
        $def = $1;
        $def =~ s/,//;
        push ( @column_default, $def );
      } else {
        push ( @column_default, "&nbsp;" );
      }
      $descr =~ s/[\r\n \t]+/ /g;
      push ( @column_descr, $descr );
      print "Column descr: $descr\n" if ( $verbose );
      $descr = "";
    }
  } elsif ( $in_comment ) {
    if ( /\*\// ) {
      $descr .= $`;
      $in_comment = 0;
      print "End comment.\n" if ( $verbose );
    } elsif ( /\*/ ) {
      $descr .= $';
      print "More comment...\n" if ( $verbose );
    }
  } else {
    if ( /CREATE\s+TABLE\s+(\S+)/ ) {
      $in_create_table = 1;
      $name = $1;
      $description = $descr;
      $descr = "";
      print "Begin table: $name\n" if ( $verbose );
    } elsif ( /\/*/ ) {
      $in_comment = 1;
      $descr = $';
      print "Begin comment.\n" if ( $verbose );
    } else {
      # ignore
    }
  }
}

@months = ( "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul",
  "Aug", "Sep", "Oct", "Nov", "Dec" );
( $mday, $mon, $year ) = ( localtime ( time ) )[3,4,5];
$now = sprintf "%02d-%s-%04d",
  $mday, $months[$mon], $year + 1900;

print<<EOF;
<HTML>
<HEAD>
<TITLE>WebCalendar Database Documentation</TITLE>
</HEAD>
<BODY BGCOLOR="#FFFFFF">
<H2>WebCalendar Database Documentation</H2>
<TABLE BORDER=0>
<TR><TD>Home Page:</TD>
  <TD><A HREF="http://webcalendar.sourceforge.net/">http://webcalendar.sourceforge.net/</A></TD></TR>
<TR><TD>Author:</TD>
  <TD><A HREF="http://www.cknudsen.com">Craig Knudsen</A>, <A HREF="mailto:cknudsen\@cknudsen.com">cknudsen\@cknudsen.com</A></TD></TR>
<TR><TD VALIGN="top">Version:</TD><TD>$v<BR>
\$Id\$ </TD></TR>
<TR><TD>Last updated:</TD><TD>$now</TD></TR>
</TABLE>
<BLOCKQUOTE>
This file is generated from <TT>tables-mysql.sql</TT>.
Below are the definitions of all WebCalendar tables along with
some descriptions of how each table is used.  Column
names shown in red are the primary keys for that table.
<P>
If you update the SQL for WebCalendar, use the sql2html.pl script
to regenerate this file.
</BLOCKQUOTE>
<P>
<H2>List of Tables</H2>
<UL>
EOF

foreach $name ( sort keys ( %out ) ) {
  print "<LI><A HREF=\"#$name\">$name</A>\n";
}

print "</UL>\n<HR>\n";

foreach $name ( sort keys ( %out ) ) {
  print "<P>\n" . $out{$name};
}

print "</BODY>\n</HTML>\n";

exit 0;
