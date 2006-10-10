#!/usr/bin/perl
#
# This tool will update a translation file by doing the following:
# - When a missing translation is found, the phrase will have
#   "<< MISSING >>"
#   right before it to make it easy to find.
# - Show the English text (in a comment) after an untranslated phrase,
#   when the "phrase" is not the same as the "translation",
#   making it easier to translate.
# - Phrases are organized by the page on which they appear.
#
# Note: you will lose any comments you put in the translation file
# when using this tool (except for the comments at the very beginning).
#
# Note #2: This will overwrite the existing translation file, so a backup
# of the original can optionally be saved with a .bak file extension.
#
# Usage:
# update_translation.pl [-p plugin] languagefile
#
# Example for main WebCalendar translation:
# update_translation.pl French.txt
#    or
# update_translation.pl French
#
# Example for plugin "tnn" translation:
# update_translation.pl -p tnn French.txt
#    or
# update_translation.pl -p tnn French
#
# Note: this utility should be run from this directory (tools).
# Note #2: you can use perltidy to format this perl script nicely:
#  http://perltidy.sourceforge.net/
# Usage:
#  perltidy -i=2 update_translation.pl
#  (which will create update_translation.pl.tdy, the new version)
#
####################################################################
use File::Find;

sub find_pgm_files {
# Skipping non Webcalendar plugins,
# if the filename ends in .class or .php, add it to @files.
  push( @files, "$File::Find::name" )
    if ( $_ =~ /\.(class|php)$/i
    && $File::Find::dir !~ /(fckeditor|htmlarea|phpmailer)/i );
}

$base_dir  = '..';
$trans_dir = '../translations';

$base_trans_file = "$trans_dir/English-US.txt";
$plugin          = '';

$show_missing = 1; # set to 0 to minimize translation file.
$show_dups    = 0; # set to 0 to minimize translation file.
$verbose      = 0;

( $this ) = reverse split( /\//, $0 );

$save_backup = 0;  # set to 1 to create backups

for ( $i = 0; $i < @ARGV; $i++ ) {
  if ( $ARGV[ $i ] eq '-p' ) {
    $plugin = $ARGV[ ++$i ];
  }
  elsif ( $ARGV[ $i ] eq '-v' ) {
    $verbose++;
  }
  else {
    $infile = $ARGV[ $i ];
  }
}

die "Usage: $this [-p plugin] language\n" if ( $infile eq '' );

if ( $plugin ne '' ) {
  $p_trans_dir       = "$base_dir/$plugin/translations";
  $p_base_trans_file = "$p_trans_dir/English-US.txt";
  $p_base_dir        = "$base_dir/$plugin";
}
else {
  $p_trans_dir       = $trans_dir;
  $p_base_trans_file = $base_trans_file;
  $p_base_dir        = $base_dir;
}

if ( $infile !~ /txt$/ ) {
  $infile .= '.txt';
}
if ( -f "$trans_dir/$infile" || -f "$p_trans_dir/$infile" ) {
  $b_infile = "$trans_dir/$infile";
  $infile   = "$p_trans_dir/$infile";
}

#print "infile: $infile\nb_infile: $b_infile\ntrans_dir: $trans_dir\n";

die "Usage: $this [-p plugin] language\n" if ( !-f $infile );

print "Translation file: $infile\n" if ( $verbose );

# Now load the base translation(s) file (English) so that we can include
# the English text, below the untranslated phrase, in a comment.
open( F, $base_trans_file ) || die "Error opening $base_trans_file";
print "Reading base translation file: $base_trans_file\n" if ( $verbose );
while ( <F> ) {
  chop;
  s/\r*$//g; # remove annoying CR
  next if ( /^#/ );
  if ( /\s*:\s*/ ) {
    $abbrev = $`;
    $base_trans{ $abbrev } = $' if ( $abbrev ne 'charset' );
  }
}
close( F );

# read in the plugin base translation file
if ( $plugin ne '' ) {
  print "Reading plugin base translation file: $p_base_trans_file\n"
    if ( $verbose );
  open( F, $p_base_trans_file ) || die "Error opening $p_base_trans_file";
  while ( <F> ) {
    chop;
    s/\r*$//g; # remove annoying CR
    next if ( /^#/ );
    if ( /\s*:\s*/ ) {
      $abbrev = $`;
      $base_trans{ $abbrev } = $';
    }
  }
  close( F );
}

#
# Now load the translation file we are going to update.
#
$old = '';
if ( -f $infile ) {
  print "Reading current translations from $infile\n" if ( $verbose );
  open( F, $infile ) || die "Error opening $infile";
  $in_header = 1;
  while ( <F> ) {
    $old .= $_;
    chop;
    s/\r*$//g; # remove annoying CR
    if ( $in_header && /^#/ ) {
      if ( /Translation last (pagified|updated)/ ) {
# ignore since we will replace this with current date below
      }
      else {
        $header .= $_ . "\n";
      }
    }
    next if ( /^#/ );
    $in_header = 0;
    if ( /\s*:\s*/ ) {
      $abbrev = $`;
      $trans{ $abbrev } = $';
    }
  }
}

$trans{'direction'} = 'ltr' if ( !defined( $trans{'direction'} ) );
$trans{'charset'} = 'iso-8859-1' if ( !defined( $trans{'charset'} ) );

if ( $plugin ne '' ) {
  print "Reading current WebCalendar translations from $b_infile\n"
    if ( $verbose );
  open( F, $b_infile ) || die "Error opening $b_infile";
  $in_header = 1;
  while ( <F> ) {
    chop;
    s/\r*$//g; # remove annoying CR
    if ( /\s*:\s*/ ) {
      $abbrev = $`;
      $webcaltrans{ $abbrev } = $';
    }
  }
}

#
# Save a backup copy of old translation file.
#
if ( $save_backup ) {
  open( F, ">$infile.bak" ) || die "Error writing $infile.bak";
  print F $old;
  close( F );
  print "Backup of translation saved in $infile.bak\n";
}

( $day, $mon, $year ) = ( localtime( time() ) )[ 3, 4, 5 ];
$header .=
  '# Translation last updated on '
  . sprintf( "%02d-%02d-%04d", $mon + 1, $day, $year + 1900 ) . "\n";

print "\nFinding Webcalendar class and php files.\n\n" if ( $verbose );
find \&find_pgm_files, $base_dir;

#
# Write new translation file.
#
$notfound = 0;
open( OUT, ">$infile" ) || die "Error writing $infile: ";
print OUT $header;
if ( $plugin eq '' ) {
  $text{ 'charset' }    = $text{ 'direction' }    = 1;
  $foundin{ 'charset' } = $foundin{ 'direction' } = ' top of this file';

  print OUT '

################################################################################
################################################################################
#                        DO NOT TRANSLATE THIS SECTION                         #
#          "drection" is language "left to right" or "right to left"?          #

direction: ' . $trans{'direction'} . '

#                                                                              #
#                                                                              #
################################################################################
################################################################################


########################################
# Specify a charset (will be sent within meta tag for each page)
#
charset: ' . $trans{'charset'} . '
';
}

foreach $f ( @files ) {
  open( F, $f ) || die "Error reading $f";
  $f =~ s,^\.\.\/,,;
  $pageHeader =
    "\n########################################\n# Page: $f\n#\n";
  print "Searching $f\n" if ( $verbose );
  %thispage = ();
  while ( <F> ) {
    $data = $_;
    while ( $data =~ /(translate|tooltip)\s*\(\s*['"]/ ) {
      $data = $';
      if ( $data =~ /['"]\s*[,\)]/ ) {
        $text = $`;
        if ( defined( $thispage{ $text } ) || $text eq 'charset' ) {
# already found
        }
        elsif ( defined( $text{ $text } ) ) {
          if ( !show_dups ) {
            print OUT $pageHeader;
            print OUT "# \"$text\" previously defined (in $foundin{$text})\n";
            $pageHeader = '';
          }
          $thispage{ $text } = 1;
        }
        else {
          print OUT $pageHeader;
          if ( !length( $trans{ $text } ) ) {
            if ( $show_missing ) {
              if ( length( $webcaltrans{ $text } ) ) {
                print OUT "# \"$text\" defined in WebCalendar translation\n";
              }
              else {
                print OUT "#\n# << MISSING >>\n# $text:\n";
                print OUT "# English text: $base_trans{$text}\n#\n"
                  if ( length( $base_trans{ $text } )
                  && $base_trans{ $text } ne $text );
              }
            }
            $notfound++ if ( !length( $webcaltrans{ $text } ) );
          }
          else {
            printf OUT ( "%s: %s\n", $text, $trans{ $text } );
          }
          $foundin{ $text } = $f;
          $pageHeader       = '';
          $text{ $text }    = $thispage{ $text } = 1;
        }
        $data = $';
      }
    }
  }
  close( F );
}

if ( !$notfound ) {
  print STDERR "All text was found in $infile.  Good job :-)\n";
}
else {
  print STDERR "$notfound translation(s) missing.\n";
}

exit 0;
