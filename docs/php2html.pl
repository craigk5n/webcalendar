#!/usr/bin/perl
#
# php2html.pl
#
# Image library
#
# Description:
#  Create HTML documentation from a PHP include file.
#  The PHP file must use a specific syntax for documenting functions.
#
# History:
#  21-Jan-2005  Craig Knudsen <cknudsen@cknudsen.com>
#      Updated
#  30-Nov-2002  Craig Knudsen <cknudsen@cknudsen.com>
#      Created
#
################################################################################

$TITLE = 'WebCalendar Function Documentation';

sub add_links {
  my ( $in ) = @_;

  $in =~ s/(webcal_[a-z_]+)\s+table/<a href="WebCalendar-Database.html#$1"><span class="tt">$1<\/span><\/a> table/g;

  foreach $funcName ( keys ( %funcNames ) ) {
    $in =~ s/($funcName)\s+function/<a href="#$1"><span class="tt">$1<\/span><\/a> function/ig;
  }

  $in =~ s/&/&amp;/g;
  $in =~ s/&amp&amp;/&amp;/g;
  $in =~ s/<br\s*\>(Note|Notes):/<br\><br\><span class=\"note\">$1<\/span>/gi;
  $in =~ s/<br\s*\>(TODO):/<br\><br\><span class=\"note\">$1<\/span>/gi;

  return $in;
}

sub print_function {
  my ( $loc ) = @_;
  $out{$name} = "<h3><a name=\"$name\">$name</a></h3>\n";
  $out{$name} .= "<span class=\"tt\">$name ( "
    . '$' . join ( ', $', @params ) . " )</span><br><br>\n";
  if ( defined ( $description ) ) {
    $out{$name} .= "<span class=\"prompt\">Description:</span>"
      . "<blockquote>";
    $out{$name} .= add_links ( $description ) . "</blockquote>\n";
  }
  $out{$name} .= "<span class=\"prompt\">Parameters:</span><br>\n<ul>\n";
  if ( @params == 0 ) {
    $out{$name} .= "<li>None</li>\n";
  }
  foreach $p ( @params ) {
    $out{$name} .= "<li><span class=\"tt\">\$$p</span>";
    $out{$name} .= " - " . add_links ( $paramDescr{$p} )
      if ( defined ( $paramDescr{$p} ) );
    $out{$name} .= "</li>\n";
  }
  $out{$name} .= "</ul>\n";
  $out{$name} .= "<span class=\"prompt\">Returns:</span><blockquote>"
    . ( $returns eq '' ? "Nothing" : add_links ( $returns ) )
    . "</blockquote>\n";
  $out{$name} .= "<span class=\"prompt\">Location:</span>"
    . "<blockquote>$loc</blockquote>\n";
  $out{$name} .= "<br><br>\n";
}

%funcNames = ( );

# Ok... this is kind of lame... but we read each file twice.
# The first scan, we gather all the function names.
# This will allow us to create links from one function to another
# when the documentation of one function mentions another.
foreach $f ( @ARGV ) {
  open ( F, $f ) || die "Error opening $f";
  while ( <F> ) {
    if ( /^\/\*\*\s+(\S+)/ ) {
      $funcNames{$1} = 1;
    }
  }
  close ( F );
}

foreach $f ( @ARGV ) {
  open ( F, $f ) || die "Error opening $f";
  $line = 1;
  $state = 'none';
  print STDERR "Reading $f\n";
  ( $basefile ) =  ( reverse split ( /\//, $f ) )[0];
  while ( <F> ) {
    chop;
    $line++;
    if ( /^\/\*\*\s+(\S+)/ ) {
      if ( $name ne '' ) {
        die "Doc syntax error at line $line of $f: starting function '$1' without properly " .
          "ending '$name'\n";
      }
      # start of a function.
      $name = $1;
      $state = 'start';
      @params = ( );
      $param = '';
      %paramDescr = ( );
      $description = '';
      $returns = '';
      $funcLine = $line;
    } elsif ( $name ne '' ) {
      if ( /\*\s*Description:/i ) {
        $state = 'description';
      } elsif ( /\*\s*Parameters:/i ) {
        $state = 'parameters';
      } elsif ( /\*\s*(Returns|Return):/i ) {
        $state = 'returns';
      } elsif ( /\*\// ) {
        &print_function ( "$basefile, line $funcLine" );
        undef ( $name );
        undef ( $description );
        undef ( $returns );
        undef ( @params );
        undef ( $param );
        undef ( %paramDescr );
        undef ( $funcLine );
        $state = 'none';
      } elsif ( $state ne 'none' && defined ( $name ) ) {
        if ( $state eq 'description' ) {
          if ( /\*\s+/ ) {
            $description .= ' ' if ( $description ne '' );
            $description .= $';
          }
        } elsif ( $state eq 'parameters' ) {
          if ( /\${0,1}(\S+)\s*-\s*/ ) {
            $param = $1;
            push ( @params, $param );
            $paramDescr{$param} = $';
          } elsif ( /\*\s+/ ) {
            # continuation line for same parameter
            $paramDescr{$param} .= ' ' . $';
          }
        } elsif ( $state eq 'returns' ) {
          if ( /\*\s+/ ) {
            $returns .= ' ' if ( $returns ne '' );
            $returns .= $';
          }
        } else {
          die "Unknown state: $state";
        }
      }
    } else {
      # do nothing... we are not in a comment right now...
    }
  }
  close ( F );
}

@months = ( "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul",
  "Aug", "Sep", "Oct", "Nov", "Dec" );
( $mday, $mon, $year ) = ( localtime ( time ) )[3,4,5];
$now = sprintf "%02d-%s-%04d",
  $mday, $months[$mon], $year + 1900;

# Get version info
open ( F, "../includes/config.php" ) ||
  die "Error reading ../includes/config.php";
$version = "UnknownVersion";
$date = "?? ??? ????";
$url = "?";
while ( <F> ) {
  if ( /PROGRAM_VERSION.*"(\S+)"/ ) {
    $version = $1;
  } elsif ( /PROGRAM_DATE.*"(\S.*)"/ ) {
    $date = $1;
  } elsif ( /PROGRAM_URL.*"(\S.*)"/ ) {
    $url = $1;
  }
}
close ( F );

print <<EOF;
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>$TITLE</title>
    <link href="../includes/css/docs.css" rel="stylesheet">
    <style>
      blockquote {
        margin-block-start: 5px;
      }

      h3 {
        background-color: rgb(25, 25, 112);
        color: var(--wht);
      }

      p {
        margin-block-start: 2px;
      }

      pre {
        margin-inline: 25px;
      }

      ul {
        margin-block: 2px;
      }

      .note {
        background-color: rgb(204, 204, 204);
        color: var(--blk);
        border: 1px solid var(--blk);
        padding: 1px;
      }

      .prompt {
        font-weight: bold;
      }

      .tip {
        background-color: rgb(255, 255, 0);
        margin-inline-end: 10px;
        border: 1px solid var(--blk);
        padding: 1px 5px;
      }
    </style>
  </head>
  <body>
    <h2>$TITLE</h2>
    <blockquote>
      $info
    </blockquote>
    <table>
      <tr>
        <td>Home Page:</td>
        <td><a href="$url">$url</a></td>
      </tr>
      <tr>
        <td>WebCalendar Version:</td>
        <td>$version ($date)</td>
      </tr>
      <tr>
        <td>Last updated:</td>
        <td>$now</td>
      </tr>
    </table>
    <h2>List of Functions</h2>
    <ul>
EOF

foreach $name ( sort keys ( %out ) ) {
  print "\n      <li><a href=\"#$name\">$name</a></li>";
}

print "\n    </ul>\n    <hr>";

foreach $name ( sort keys ( %out ) ) {
  print "\n    <br><br>\n    " . $out{$name};
}

print <<EOF;
    <hr>
    <p><a href="http://validator.w3.org/check?uri=referer" target="_blank">Validate></a></p>
  </body>
</html>
EOF

exit 0;
