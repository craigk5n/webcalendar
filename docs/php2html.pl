#!/usr/local/bin/perl
#
# h2html.pl
#
# Image library
#
# Description:
#	Create HTML documentation from a C include file.
#
# History:
#	29-Nov-99	Craig Knudsen	cknudsen@radix.net
#			Updated to show prototype
#	20-Aug-99	Craig Knudsen	cknudsen@radix.net
#			Misc. bug fix
#	19-Jul-99	Craig Knudsen	cknudsen@radix.net
#			Modified for nicer looking output.
#	29-May-96	Craig Knudsen	cknudsen@radix.net
#			Created
#
#######################################################################


sub print_function {
  $out{$name} = "<h3><a name=\"$name\">$name</a></h3>\n";
  $out{$name} .= "<tt>$ret_type $name ( $args )</tt><br /><br />\n";
  $out{$name} .= "$description<br /><br />\n"
    if ( defined ( $description ) );
  $out{$name} .= "Returns: <tt>$ret_type</tt><br /><br />\n" .
    "Input Parameters:<br />\n<ul>\n";
  for ( $i = 0; $i < $num_ivars; $i++ ) {
    $out{$name} .= "<li><tt>$vars[$i]</tt>";
    $out{$name} .= " - $comments[$i]" if ( defined ( $comments[$i] ) );
    $out{$name} .= "</li>\n";
  }
  $out{$name} .= "</ul><br /><br />\n";
  if ( $i < $num_vars ) {
    $out{$name} .= "Output Parameters:<br />\n<ul><br /><br />\n";
    for ( ; $i < $num_vars; $i++ ) {
      $out{$name} .= "<li><tt>$vars[$i]</tt>";
      $out{$name} .= " - $comments[$i]" if ( defined ( $comments[$i] ) );
      $out{$name} .= "</li>\n";
    }
    $out{$name} .= "</ul>\n";
  }
}

$line = 1;
$functions_found;
while ( <> ) {
  chop;
  $line++;
  if ( /Description:/ ) {
    $in_info = 1;
  } elsif ( /History:/ ) {
    $in_info = 0;
  } elsif ( $in_info ) {
    if ( /\*\s+/ ) {
      $info .= " " if length ( $info );
      $info .= $';
    }
  } elsif ( ! $functions_found ) {
    if ( /^\*\* Functions/ ) {
      $functions_found = 1;
    } else {
      next;
    }
  }
  elsif ( /^([^\*]\S+)\s+(\S+)\s+\(/ ) {
    # start of a function.
    $name = $2;
    $ret_type = $1;
    if ( $name =~ /^\*/ ) {
      $name = $';
      $ret_type .= " *";
    }
    $name =~ s/^_//;
    $num_vars = 0;
    $num_ivars = 0;
  } elsif ( defined ( $name ) ) {
    if ( /^\s+(\S.*)\s+\/\*\s*(\S.*)\s*\*\// ) {
      $vars[$num_vars] = $1;
      $comments[$num_vars] = $2;
      if ( $comments[$num_vars] =~ /^out: / ) {
        $comments[$num_vars] =~ s/^out:\s*//;;
      } else {
        $num_ivars++;
      }
      $vars[$num_vars] =~ s/[\s,]+$//;
      $args .= ", " if ( $args ne "" );
      $args .= $vars[$num_vars];
      $num_vars++;
    } elsif ( /^\);/ ) {
      &print_function;
      undef ( $name );
      undef ( @vars );
      undef ( @comments );
      undef ( $description );
      undef ( $args );
    } elsif ( /\s+\/\*\s*(\S.*)\s*\*\// ) {
if ( $num_vars <= 0 ) { print "ERROR ($line): $_\n"; exit ( 1 ); }
      $comments[$num_vars-1] .= " " . $1;
    }
  } elsif ( /^\*+\// ) {
    # end comment
  } elsif ( /^\*+\s*(\S.*)$/ ) {
    $description .= " " if ( length ( $description ) );
    $description .= $1;
  } elsif ( /^\/*/ ) {
    undef ( $description );
  }
}

@months = ( "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul",
  "Aug", "Sep", "Oct", "Nov", "Dec" );
( $mday, $mon, $year ) = ( localtime ( time ) )[3,4,5];
$now = sprintf "%02d-%s-%04d",
  $mday, $months[$mon], $year + 1900;

print<<EOF;
<html>
<head>
<title>ILib API Documentation</title>
</head>
<body style="background-color:#FFFFFF;">
<h2>Ilib Image Library</h2>
<blockquote>
$info
</blockquote>
<table style="border-width:0px;">
<tr><td>Home Page:</td>
  <td><a href="http://www.radix.net/~cknudsen/Ilib/">http://www.radix.net/~cknudsen/Ilib/</a></td></tr>
<tr><td>Author:</td>
  <td><a href="http://www.radix.net/~cknudsen/">Craig Knudsen</a>, <a href="mailto:cknudsen\@radix.net">cknudsen\@radix.net</a></td></tr>
<tr><td>Last updated:</td><td>$now</td></tr>
</table>
<h2>API Documentation</h2>
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
