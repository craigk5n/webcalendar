#!/usr/bin/perl
#
# extractfaqs.pl
#
# Description:
#	Extract FAQs from all the user documentation to create a single
#	document that contains all FAQs.
#	We don't put HTML header/trailer stuff since the intent is to
#	embed the results into some other web page.
#
#
# History:
#	20-Jan-2005	Craig Knudsen <cknudsen@cknudsen.com>
#			Created
#
#######################################################################


my @files = ( );
my @questions = ( );
my @answers = ( );
my @file = ( );

for ( $i = 0; $i < @ARGV; $i++ ) {
  if ( -f $ARGV[$i] ) {
    push ( @files, $ARGV[$i] )
  } else {
    print STDERR "Ignoring $ARGV[$i]\n";
  }
}

foreach $f ( @files )
{
  process_file ( $f );
}

# Do some regex replacements to both questions and answers.
for ( $i = 0; $i < @questions; $i++ ) {
  $questions[$i] =~ s/\s+/ /g;
  # remove the "new window" icons
  $questions[$i] =~ s/<a href=\"\S+\"[^>]+><img[^>]+><\/a>//g;
  $answers[$i] =~ s/<a href=\"\S+\"[^>]+><img[^>]+><\/a>//g;

  # remove any href or name tags in question
  $questions[$i] =~ s/<a name=\"[a-z]+\">(.*)<\/a>/$1/ig;
  $questions[$i] =~ s/<a href=\"\S+\"[^>]*>(.+)<\/a>/$1/gi;
  if ( $questions[$i] =~ /href/ ) {
     die "Error removing link: $questions[$i]\n";
  }

  # For 
  if ( $answers[$i] =~ /<a href="#([a-z]+)">/ ) {
    $answers[$i] = $` . "<a href=\"" .
      makeCVSURL ( $file[$i], $1 ) . "\">" . $';
  }
  $answers[$i] =~ s/<a href=\"\S+\"[^>]+><img[^>]+><\/a>//g;
}

print "<ul>\n";
for ( $i = 0; $i < @questions; $i++ ) {
  $anchor = "faq_" . ( $i + 1 );
  $q = $questions[$i];
  print "  <li><a href=\"#$anchor\">$q</a></li>\n";
}
print "</ul>\n<hr/>\n<dl>\n";

for ( $i = 0; $i < @questions; $i++ ) {
  $q = $questions[$i];
  $anchor = "faq_" . ( $i + 1 );
  print "<dt><a name=\"$anchor\">$q</a></dt>\n";
  print "<dd>$answers[$i]</dd>\n";
}
print "</dl>\n";


exit 0;

#######################################################################
# Subroutines start below here...
#######################################################################

sub makeCVSURL {
  my ( $f, $anchor ) = @_;

  return "http://cvs.sourceforge.net/viewcvs.py/*checkout*/webcalendar/webcalendar/docs/" . $f .
    "?rev=HEAD&amp;content-type=text/html#" . $anchor;
}


sub process_file {
  my ( $f ) = @_;

  open ( F, $f ) || die "Error reading $f: ";
  my $inFAQ = 0;
  my $text = '';
  while ( <F> ) {
    if ( /START FAQ/ ) {
      $inFAQ = 1;
    } elsif ( /END FAQ/ ) {
      $inFAQ = 0;
    } else {
      if ( $inFAQ ) {
        $text .= $_;
      }
    }
  }
  close ( F );

  # Now parse the text
  @q = split ( /<dt>/, $text );
  shift ( @q ); # ignore junk at beginning
  foreach $q ( @q ) {
    if ( $q =~ /<\/dt>/ ) {
      $thisQ = $`;
      $rest = $';
      if ( $rest =~ /<dd>/ ) {
        $rest = $';
        if ( $rest =~ /<\/dd>/ ) {
          $thisA = $`;
          push ( @questions, $thisQ );
          push ( @answers, $thisA );
          push ( @file, $f );
          #print STDERR "Question successfully parsed.\n";
        } else {
          print STDERR "Found no </dd> for question: $thisQ\n";
        }
      } else {
        print STDERR "Found no <dd> for question: $thisQ\n";
      }
    } else {
      print STDERR "Found no </dt>";
    }
  }
}




