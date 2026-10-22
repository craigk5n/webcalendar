#!/usr/bin/perl
#
# sql2html.pl
#
# Description:
#	Create HTML documentation from an SQL file.
#
# Usage:
#	perl sql2html.pl < ../install/sql/tables-mysql.sql > WebCalendar-Database.html
#
################################################################################

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
    <h3><a name="' . "$name\">$name" . '</a></h3>
    <blockquote>';
  $out{ $name } .= '
      ' . $description . '<br><br>' if ( defined ( $description ) );
  $out{ $name } .= '
      <table summary="Schema for table ' . $name . '">
        <tr>'
    . th( 'Column Name' )
    . th( 'Type' )
    . th( 'Length' )
    . th( 'Null' )
    . th( 'Default' )
    . th( 'Description' ) . '
        </tr>';
  for ( $i = 0; $i < @column_name; $i++ ) {
    $out{ $name } .= '
        <tr>';
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
      . td( $column_descr[ $i ] ) . '
        </tr>';
  }
  $out{ $name } .= '
      </table>
    </blockquote>';
}

# first, get WebCalendar version
open ( F, '../includes/config.php' ) || die "Error reading config.php: $!\n";
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
  #print "Line:$line\n" if ( $verbose );
  if ( $in_create_table ) {
    if ( /\/\*+/ ) {
      $cmt = $';
      if ( $cmt =~ /\*\// ) {
        $descr .= ' ' . $`;
      }
    }
    elsif ( /INDEX\s/i ) {
      # ignore for now...
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
      $descr =~ s/\s\*\s/ /;
      $descr =~ s/<u/\n            <u/g;
      $descr =~ s/<l/\n              <l/g;
      push ( @column_descr, $descr );
      print "Column descr:$descr\n" if ( $verbose );
      $descr = '';
    }
  }
  elsif ( $in_comment ) {
    if ( /\*\// ) {
      $descr .= $`;
      $in_comment = 0;
      print "End comment.\n" if ( $verbose );
    }
    elsif ( /\*+/ ) {
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
      print "Begin table:$name\n" if ( $verbose );
    }
    elsif ( /\/*+/ ) {
      $in_comment = 1;
      $descr = $';
      print "Begin comment.\n" if ( $verbose );
    }
    else {
      # ignore
    }
  }
}

print<<EOF;
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>WebCalendar Database Documentation</title>
    <link href="../includes/css/docs.css" rel="stylesheet">
    <style>
      span {
        color: rgb(160, 0, 0);
        font-weight: bold;
      }

      table {
        padding: 1px;
      }

      th {
        background-color: rgb(192, 192, 192);
      }

      td {
        background-color: rgb(224, 224, 224);
        padding: 2px;
      }

      th,
      td {
        vertical-align: top;
      }

      #DB_Doc blockquote {
        margin-inline-start: 15px;
      }

      #DB_Doc label {
        clear: left;
        float: left;
        inline-size: 13%;
        font-weight: bold;
        line-height: 120%;
      }

      #DB_Doc p {
        margin: 0;
        padding: 0;
      }

    </style>
  </head>
  <body>
    <h2>WebCalendar Database Documentation</h2>
    <div id="DB_Doc">
      <p><label>Home Page:</label> <a href="http://k5n.us/webcalendar" target="_blank">http://k5n.us/webcalendar</a></p>
      <p><label>Author:</label><a href="http://k5n.us" target="_blank">Craig Knudsen</a>,<a href="mailto:&#109;&#097;&#105;&#108;&#116;&#111;&#058;&#67;&#114;&#97;&#105;&#103;&#64;&#107;&#53;&#110;&#46;&#117;&#115;">&#67;&#114;&#97;&#105;&#103;&#64;&#107;&#53;&#110;&#46;&#117;&#115;</a></p>
      <p><label>Version:</label>$v</p>
    </div>
    <blockquote>
      <p>This file is generated from <span class="tt">tables-mysql.sql</span>. Below are the definitions of all WebCalendar tables, along with some descriptions of how each table is used. Column names shown in red are the primary keys for that table.</p>
      <p>If you update the SQL for WebCalendar, use the sql2html.pl script to regenerate this file.</p>
    </blockquote>
    <br><br>
    <h2>List of Tables</h2>
    <ul>
EOF

foreach $name ( sort keys ( %out ) ) {
  print '
      <li><a href="#' . "$name\">$name" . '</a></li>';
}

print '
    </ul>
    <hr>';

foreach $name ( sort keys ( %out ) ) {
  print '
    <br><br>
    ' . $out{ $name };
}

print '
  </body>
</html>
';

exit 0;
