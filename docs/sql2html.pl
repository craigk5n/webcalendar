#!/usr/bin/perl
#
# sql2html.pl
#
# Description:
# Create HTML documentation from a sql file.
#
# Usage:
# perl sql2html.pl < ../install/sql/tables-mysql.sql > WebCalendar-Database.html
# History:
# 18-Sep-2006 Better format HTML.  Shorter lines.
# 05-Sep-2006 Cleanup missing html tags and removed inline styles
# 13-Apr-2004 xHTML & CSS work
# 12-Oct-2002 Created
#
#######################################################################

$verbose = 0;

sub th {
  ( $s ) = @_;
  return "\n          <th>$s</th>";
}

sub td {
  ( $s ) = @_;
  return "\n          <td>$s</td>";
}

sub print_table {
  $out{ $name } =
    "\n    <h3><a name=\"$name\">$name</a></h3>\n    <blockquote>";
  $out{ $name } .= "$description<br /><br />"
    if ( defined ( $description ) );
  $out{ $name } .=
      "\n      <table>\n        <tr>"
    . th( 'Column Name' )
    . th( 'Type' )
    . th( 'Length' )
    . th( 'Null' )
    . th( 'Default' )
    . th( 'Description' )
    . "\n        </tr>";
  for ( $i = 0; $i < @column_name; $i++ ) {
    $out{ $name } .= "\n        <tr>";
    if ( defined ( $table_keys{$column_name[$i]} ) ) {
      $out{ $name } .= td( '<span>' . $column_name[ $i ] . '</span>' );
    }
    else {
      $out{$name} .= td($column_name[$i]);
    }
    $out{ $name } .=
        td( $column_type[ $i ] )
      . td( $column_size[ $i ] )
      . td( $column_null[ $i ] )
      . td( $column_default[ $i ] )
      . td( $column_descr[ $i ] )
      . "\n        </tr>";
  }
  $out{ $name } .= "\n      </table>\n    </blockquote>";
}

# first, get WebCalendar version
open( F, '../includes/classes/WebCalendar.class' )
  || die 'Error reading WebCalendar.class: ' . "$!\n";
while ( <F> ) {
  if ( /PROGRAM_VERSION =/ ) {
    if ( /'/ ) {
      $v = $';
      if ( $v =~ /'/ ) { $v = $`; }
    }
    last;
  }
}
close ( F );
$v = 'Unknown Version' if ( !defined( $v ) );

$in_comment = $in_create_table = 0;
$line = 1;
while ( <> ) {
  chop;
  $line++;
  #print "Line: $line\n" if ( $verbose );
  if ( $in_create_table ) {
    if ( /\/\*/ ) {
      $cmt = $';
      if ( $cmt =~ /\*\// ) {
        $descr .= ' ' . $`;
      }
    }
    elsif ( /PRIMARY\s+KEY\s+\((.*)\)/i ) {
      $keys = $1;
      $keys =~ s/ //g;
      @keys = split ( /,/, $keys );
      foreach $k ( @keys ) {
        print "Found key $k\n" if ( $verbose );
        $table_keys{$k} = 1;
      }
    }
    elsif ( /^\);/ ) {
      print "End of table.\n" if ( $verbose );
      $in_create_table = 0;
      &print_table ();
      # reset values
      %table_keys = @column_default = @column_descr = @column_name =
        @column_null = @column_size = @column_type = ();
      undef ( $description );
      undef ( $name );
    }
    elsif ( /(\S+)\s*(\S+)/ ) {
      $n = $1;
      $t = $2;
      $t =~ s/,//;
      print "Found column $n\n" if ( $verbose );
      push ( @column_name, $n );
      if ( $t =~ /\((\d+)\)/ ) {
        push ( @column_size, $1 );
        push ( @column_type, $` );
      }
      else {
        push( @column_size, '&nbsp;' );
        push ( @column_type, $t );
      }
      if ( /not null/i ) {
        push( @column_null, 'N' );
      }
      elsif ( / null/i ) {
        push( @column_null, 'Y' );
      }
      else {
        push( @column_null, 'Y' );
      }
      if ( / DEFAULT (\S+)/i ) {
        $def = $1;
        $def =~ s/,//;
        push ( @column_default, $def );
      }
      else {
        push( @column_default, '&nbsp;' );
      }
      $descr =~ s/\r//g;
      $descr =~ s/[ \t]+/ /g;
      $descr =~ s/^\s*//;
      $descr =~ s/\s*$//;
      $descr =~ s/<u/\n            <u/g;
      $descr =~ s/<l/\n              <l/g;
      push ( @column_descr, $descr );
      print "Column descr: $descr\n" if ( $verbose );
      $descr = '';
    }
  }
  elsif ( $in_comment ) {
    if ( /\*\// ) {
      $descr .= $`;
      $in_comment = 0;
      print "End comment.\n" if ( $verbose );
    }
    elsif ( /\*/ ) {
      $descr .= $' . "\n     ";
      print "More comment...\n" if ( $verbose );
    }
  }
  else {
    if ( /CREATE\s+TABLE\s+(\S+)/ ) {
      $in_create_table = 1;
      $name = $1;
      $description = $descr;
      $descr           = '';
      print "Begin table: $name\n" if ( $verbose );
    }
    elsif ( /\/*/ ) {
      $in_comment = 1;
      $descr = $';
      print "Begin comment.\n" if ( $verbose );
    }
    else {
      # ignore
    }
  }
}

@months = (
  'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
  'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
);
( $mday, $mon, $year ) = ( localtime ( time ) )[3,4,5];
$now = sprintf "%02d-%s-%04d", $mday, $months[ $mon ], $year + 1900;

print<<EOF;
<?xml version="1.0" encoding="iso-8859-1"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
    <title>WebCalendar Database Documentation</title>
<style type="text/css">
<!--
body {
  background-color:#FFFFFF;
}
table {
        border-width:0;
  padding:1px;
}
th {
  vertical-align:top;
  background-color:#C0C0C0;
}
td {
  vertical-align:top; 
  background-color:#E0E0E0;
  padding:2px;
}
span {
  font-weight:bold; 
  color:#A00000;
}
-->
    </style>
  </head>
  <body>
    <h2>WebCalendar Database Documentation</h2>
    <table>
      <tr>
        <td>Home Page:</td>
        <td>
  <a href="http://www.k5n.us/webcalendar.php">http://www.k5n.us/webcalendar.php</a>
        </td>
      </tr>
      <tr>
        <td>Author:</td>
        <td>
          <a href="http://www.k5n.us">Craig Knudsen</a>,
          <a href="mailto:&#109;&#097;&#105;&#108;&#116;&#111;&#058;&#67;&#114;&#97;&#105;&#103;&#64;&#107;&#53;&#110;&#46;&#117;&#115;">&#67;&#114;&#97;&#105;&#103;&#64;&#107;&#53;&#110;&#46;&#117;&#115;</a>
        </td>
      </tr>
      <tr>
        <td>Version:</td>
        <td>$v<br />\$Id\$</td>
      </tr>
      <tr>
        <td>Last updated:</td>
        <td>\$Date\$<br/>(by \$Author\$)</td>
      </tr>
    </table>
<blockquote>
      This file is generated from <tt>tables-mysql.sql</tt>.
      Below are the definitions of all WebCalendar tables, along with some
      descriptions of how each table is used.  Column names shown in red are
      the primary keys for that table.
 <br /><br />
      If you update the SQL for WebCalendar, use the sql2html.pl script to
      regenerate this file.
</blockquote>
<br /><br />
<h2>List of Tables</h2>
<ul>
EOF

foreach $name ( sort keys ( %out ) ) {
  print "<li><a href=\"#$name\">$name</a></li>\n";
}

print "    </ul>\n    <hr />";

foreach $name ( sort keys ( %out ) ) {
  print "\n    <br /><br />" . $out{ $name };
}

print "\n  </body>\n</html>\n";

exit 0;
