#!/usr/bin/perl
#
# $Id$
#
# sql2html.pl
#
# Description:
# Create HTML documentation from a sql file.
#
# Usage:
# perl sql2html.pl < ../install/sql/tables-mysql.sql > WebCalendar-Database.html
# History:
# 02-Oct-2012 Make it work with comments attached to the database.
# 18-Sep-2006 Better format HTML. Shorter lines.
# 05-Sep-2006 Cleanup missing HTMLtags and removed inline styles
# 13-Apr-2004 xHTML & CSS work
# 12-Oct-2002 Created
#
#######################################################################

$verbose = 0;

sub th {
  ( $s ) = @_;
  return '
            <th>' . $s . '</th>';
}

sub td {
  ( $s ) = @_;
  return '
            <td>' . $s . '</td>';
}

sub print_table {
  $out{ $name } = '
    <h3><a name="' . $name . '">' . $name . '</a></h3>
    <blockquote>';
  $out{ $name } .= '
      ' . $hdr . '<br>' if ( defined ( $hdr ) );
  $out{ $name } .= '
      <table summary="Schema for table ' . $name . '">
        <thead>
          <tr>'
    . th( 'Column Name' )
    . th( 'Type' )
    . th( 'Length' )
    . th( 'Null' )
    . th( 'Default' )
#    . th( 'Unique' )
    . th( 'Description' ) . '
          </tr>
        </thead>
        <tbody>';
  for ( $i = 0; $i < @column_name; $i++ ) {
    $out{ $name } .= '
          <tr>';
    if ( defined ( $table_keys{$column_name[$i]} ) ) {
      $out{ $name } .= td( '<span>' . $column_name[ $i ] . '</span>' );
    } else {
      $out{$name} .= td($column_name[$i]);
    }
    $out{ $name } .=
        td( $column_type[ $i ] )
      . td( $column_size[ $i ] )
      . td( $column_null[ $i ] )
      . td( $column_default[ $i ] )
#      . td( $column_unique[ $i ] )
      . td( $column_descr{ $column_name[$i] } ) . '
          </tr>';
  }
  $out{ $name } .= '
        </tbody>
      </table>
    </blockquote>';
}

# first, get WebCalendar version
open ( F, '../includes/classes/WebCalendar.class' )
  || die 'Error reading WebCalendar.class:' . "$!\n";
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

$hdr = $fld_desc = '';
$in_create_table = $in_header = 0;

while ( <> ) {
  next if ( /^#/ );
  chomp;
  s/\r//g;
  s/\t/ /g;
  s/  / /g;
  s/ ,/,/g;
  if ( /\/\*\*/ ) {
    $in_header = 1;
    print "Begin table header.\n" if ( $verbose );
  } elsif ( $in_header ) {
    if ( /\* / ) {
      $hdr .= $';
    } elsif (  /\*\// ) {
      $in_header = 0;
      $hdr =~ s/<u/\n      <u/;
      $hdr =~ s/<l/\n        <l/g;
      $hdr =~ s/<\/u/\n      <\/u/;
    }
  } elsif ( /CREATE TABLE (\S+)/i ) {
    $in_create_table = 1;
    $name = $1;
    print "Begin table:$name\n" if ( $verbose );
  } elsif ( $in_create_table ) {
    if ( /PRIMARY KEY \((.*)\)/i ) {
      $keys = $1;
      $keys =~ s/ //g;
      print "Found key $keys\n" if ( $verbose );
      @keys = split ( /,/, $keys );
      foreach $k ( @keys ) {
        $table_keys{$k} = 1;
      }
    } elsif ( /\) DEFAULT CHARSET=UTF8;/i ) {
      print "End of table.\n" if ( $verbose );
      $in_create_table = 0;
      &print_table();
      # reset values
      %table_keys = @column_default = %column_descr = @column_name =
        @column_null = @column_size = @column_type = @column_unique = ();
      undef ( $hdr );
      undef ( $name );
    } else {
      if ( / COMMENT '/i ) {
          $fld_desc = $';
          $_ = $`;
          $fld_desc =~ s/',$//;
          print "Column descr:$fld_desc\n" if ( $verbose );
      } elsif ( /\/\*(.*)\*\// ) {
        $fld_desc = $1;
        $_ = '';
      }

      if ( /(\S+)\s*(\S+)/ ) {
        $n = $1;
        $t = $2;
        $t =~ s/,//;
        print "Found column $n\n" if ( $verbose );
        push ( @column_name, $n );

        if ( $t =~ /\((\d+)\)/ ) {
          push ( @column_size, $1 );
          push ( @column_type, $` );
        }  else {
          push ( @column_size, '&nbsp;' );
          push ( @column_type, $t );
        }

        push ( @column_null, ( / not null/i ? 'N' : 'Y' ) );

        if ( / DEFAULT (\S+)/i ) {
          $def = $1;
          $def =~ s/,$//;
          push ( @column_default, $def );
        } else {
          push( @column_default, '&nbsp;' );
        }

        push ( @column_unique, ( / unique /i ? 'Y' : 'N' ) );
      }
      
      if ( defined ( $fld_desc ) ) {
        $fld_desc =~ s/''/'/g;
        $fld_desc =~ s/\r//g;
        $fld_desc =~ s/\s+$//;
        $fld_desc =~ s/^\s+//;
        $fld_desc =~ s/<u/\n            <u/g;
        $fld_desc =~ s/<l/\n              <l/g;
        $fld_desc =~ s/<\/u/\n            <\/u/g;
        $column_descr{$n} .= $fld_desc;
        undef ( $fld_desc );
      }
    }
  }
}

print<<EOF;
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>WebCalendar Database Documentation</title>
    <link href="DBs.css" rel="stylesheet">
  </head>
  <body>
    <h2>WebCalendar Database Documentation</h2>
    <div id="DB_Doc">
      <p><label>Home Page:</label><a href="http://www.k5n.us/webcalendar.php">http://www.k5n.us/webcalendar.php</a></p>
      <p><label>Author:</label><a href="http://www.k5n.us">Craig Knudsen</a>,<a href="mailto:&#109;&#097;&#105;&#108;&#116;&#111;&#058;&#67;&#114;&#97;&#105;&#103;&#64;&#107;&#53;&#110;&#46;&#117;&#115;">&#67;&#114;&#97;&#105;&#103;&#64;&#107;&#53;&#110;&#46;&#117;&#115;</a></p>
      <p></p><label>Version:</label>$v, \$Id\$</p>
    </div>
    <blockquote>
      <p>This file was generated using <span class="tt">/install/sql/tables-mysql.sql</span>.</p>
      <p>Below are the definitions of all WebCalendar tables, along with some descriptions of how each table is used.<br>Column names shown in red are the primary keys for that table.</p>
      <p>If you update the SQL for WebCalendar, run the <span class="tt">/docs/sql2html.pl</span> script to regenerate this file.</p>
    </blockquote>
    <br>
    <h2>List of Tables</h2>
    <ul>
EOF

foreach $name ( sort keys %out ) {
  print '
      <li><a href="#' . "$name\">$name" . '</a></li>';
}

print '
    </ul>
    <hr>';

foreach $name ( sort keys %out ) {
  print '
    ' . $out{ $name } . '<br>';
}

print '
  </body>
</html>
';

exit 0;
