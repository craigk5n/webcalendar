#!/usr/local/bin/perl
#
# sql2html.pl
#
# Description:
#	Create HTML documentation from a sql file.
#
# History:
#	12-Oct-2002	Created
#	13-April-2004	xHTML & CSS work
#
#######################################################################

$verbose = 0;


sub th {
  ( $s ) = @_;
  return "<th style=\"vertical-align:top; background-color:#C0C0C0;\">$s</th>";
}
sub td {
  ( $s ) = @_;
  return "<td style=\"vertical-align:top; background-color:#E0E0E0;\">$s</td>";
}


sub print_table {
  $out{$name} = "<h3><a name=\"$name\">$name</a></h3>\n";
  $out{$name} .= "<blockquote>\n";
  $out{$name} .= "$description<br /><br />\n"
    if ( defined ( $description ) );
  $out{$name} .= "<table style=\"border-width:0px;\">";
  $out{$name} .= "<tr>" . th("Column Name") . th("Type") . th("Length") .
     th("Null") . th("Default") . th("Description") . "</tr>\n";
  for ( $i = 0; $i < @column_name; $i++ ) {
    $out{$name} .= "<tr>";
    if ( defined ( $table_keys{$column_name[$i]} ) ) {
      $out{$name} .= td("<span style=\"font-weight:bold; color:#A00000;\">" . $column_name[$i] .
        "</span>");
    } else {
      $out{$name} .= td($column_name[$i]);
    }
    $out{$name} .= td($column_type[$i]);
    $out{$name} .= td($column_size[$i]);
    $out{$name} .= td($column_null[$i]);
    $out{$name} .= td($column_default[$i]);
    $out{$name} .= td($column_descr[$i]);
  }
  $out{$name} .= "</table>\n";
  $out{$name} .= "</blockquote>\n";
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
<html>\n
<head>\n
	<title>WebCalendar Database Documentation</title>\n
</head>\n
<body style="background-color:#FFFFFF;">\n
<h2>WebCalendar Database Documentation</h2>\n
<table style="border-width:0px;">\n
	<tr><td>
		Home Page:</td><td>
		<a href="http://webcalendar.sourceforge.net/">http://webcalendar.sourceforge.net/</a>
	</td></tr>
	<tr><td>
		Author:</td><td>
		<a href="http://www.cknudsen.com">Craig Knudsen</a>, <a href="mailto:cknudsen\@cknudsen.com">cknudsen\@cknudsen.com</a>
	</td></tr>
	<tr><td style="vertical-align:top;">
		Version:</td><td>
		$v<br />
		\$Id\$
	</td></tr>
	<tr><td>
		Last updated:</td><td>
		$now
	</td></tr>
</table>

<blockquote>
	This file is generated from <tt>tables-mysql.sql</tt>. Below are the definitions of all WebCalendar tables along with some descriptions of how each table is used. Column names shown in red are the primary keys for that table.
	<br /><br />
	If you update the SQL for WebCalendar, use the sql2html.pl script to regenerate this file.
</blockquote>
<br /><br />

<h2>List of Tables</h2>
<ul>
EOF

foreach $name ( sort keys ( %out ) ) {
  print "<li><a href=\"#$name\">$name</a></li>\n";
}

print "</ul>\n<hr />\n";

foreach $name ( sort keys ( %out ) ) {
  print "<br /><br />\n" . $out{$name};
}

print "</body>\n</html>\n";

exit 0;
