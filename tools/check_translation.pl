#!/usr/bin/perl
# $Id: check_translation.pl,v 1.21.2.2 2007/08/06 02:28:33 cknudsen Exp $
#
# This tool helps with the translation into other languages by indicating
# whether all text specified in translate() and tooltip() within the application
# has a corresponding entry in the translation data file.
#
# Usage:
#  check_translation.pl languagefile
#    ... or to check the most recently modified translation file
#  check_translation.pl
#
# Example:
#  check_translation.pl ../translations/English-US.txt
#
# Note: this utility should be run from this directory (tools).
#
###########################################################################
use File::Find;

sub find_pgm_files {
# Skipping non WebCalendar plugins,
# if the filename ends in .class or .php, add it to @files.
  push( @files, "$File::Find::name" )
    if ( $_ =~ /\.(class|php)$/i
    && $File::Find::dir !~ /(fckeditor|htmlarea|phpmailer)/i );
}

$trans_dir = '../translations';

$infile = $ARGV[0];

if ( $infile eq '' ) {
  opendir( DIR, $trans_dir ) || die 'error opening ' . $trans_dir;
  @files = grep ( /\.txt$/, readdir(DIR) );
  closedir(DIR);
  $last_mtime = 0;
  foreach $f (@files) {
    ($mtime) = ( stat("$trans_dir/$f") )[9];
    if ( $mtime > $last_mtime ) {
      $last_mtime = $mtime;
      $infile     = "$trans_dir/$f";
    }
  }
}

if ( $infile ne '' && !-f $infile ) {
  if ( -f "$trans_dir/$infile" ) {
    $infile = "$trans_dir/$infile";
  } else {
    $infile = "$trans_dir/$infile.txt";
  }
}

@files = ();

# First get the list of .class and .php files.
find \&find_pgm_files, '..';

foreach $f (@files) {
  open( F, $f ) || die 'Error reading ' . $f;

  #print "Checking $f for text.\n";
  while (<F>) {
    $data = $_;
    while ( $data =~ /(translate|tooltip)\s*\(\s*['"]/ ) {
      $data = $';
      if ( $data =~ /['"]\s*[,\)]/ ) {
        $text        = $`;
        $text{$text} = 1;
        $data        = $';
      }
    }
  }
  close(F);
}

#print "Found the following entries:\n";
#foreach $text ( sort { uc($a) cmp uc($b) } keys ( %text ) ) {
#  print "$text\n";
#}

# Now load the translation file
if ( !-f $infile ) {
  die "Usage: $0 translation-file\n";
}
open( F, $infile ) || die 'Error opening ' . $infile;
while (<F>) {
  chop;
  next if (/^#/);
  if (/\s*:/) {
    $abbrev = $`;
    $trans{$abbrev} = $';
  }
}

$notfound = $total = 0;
foreach $text ( sort { uc($a) cmp uc($b) } keys(%text) ) {
  if ( !defined( $trans{$text} ) ) {
#    if ( !$notfound ) {
#      print "The following text did not have a translation in $infile:\n\n";
#    }
#    print "$text\n";
    $notfound++;
  }
  $total++;
}

# Check for translations that are not used...
$extra = 0;
foreach $text ( sort { uc($a) cmp uc($b) } keys(%trans) ) {
  if ( !defined( $text{$text} ) ) {
#    if ( !$extra ) {
#      print "\nThe following translation text is not needed in $infile:\n\n";
#    }
#    print "$text\n";
    $extra++;
  }
}

if ( !$notfound ) {
  print "All text was found in $infile.  Good job :-)\n";
}
else {
  printf "\n$notfound of $total translation(s) missing. (%2.1f%% complete)\n",
    ( 100 * ( $total - $notfound ) / $total );
}

exit 0;
