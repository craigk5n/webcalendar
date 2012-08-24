#!/usr/bin/perl
# $Id$
#
# This tool can update one, several or all translation file(s) by doing the following:
# - Phrases are organized by the page on which they first appear.
# - When a missing translation is found, the phrase can optionally have
#   << MISSING >>
#   right above it. And, when the "phrase" is an abbreviation of the
#   full English text, show the English text (in a comment) below.
#
# Example:
#   << MISSING >>
#   custom_script_help:
#   English text: Allows entry of custom Javascript or stylesheet text that will be inserted into the HTML "head" section of every page.
#
# Note: you will lose any comments you put in the translation file
# when using this tool (except for the comments at the very beginning).
#
# Note #2: This will overwrite the existing translation file, so a backup
# of the original can optionally be saved with a timestamp file extension.
#
# Usage:
# update_translation.pl [-m] languagefile
#    -a = update all language files
#    -b = backup first
#    -m = show missing
#    -e = use equal sign '=' to show "translation" equals "phrase"
#    -v = verbose
#
# Example for main WebCalendar translation:
# update_translation.pl French.txt
#    or
# update_translation.pl -v English-US French German   # do just these 3 with verbose
#    or
# update_translation.pl -a -b                         # do all and make backups
#
# Note #3: this utility should be run from this directory (tools).
# Note #4: you can use perltidy to format this perl script nicely:
#  http://perltidy.sourceforge.net/
# Usage:
#  perltidy -i=2 update_translation.pl
#  (which will create update_translation.pl.tdy, the new version)
#
####################################################################
use File::Copy;
use File::Find;

sub find_pgm_files {
# if the filename ends in .class, js or .php, add it to @files.
  push( @files, "$File::Find::name" )
    if ( $_ ne 'translate.js.php' && $_ =~ /\.(class|js|php)$/i );

# Add the /translations/*.txt files to @txt_files
  push( @txt_files, $_ ) # We just want the file name.
    if ( $_ =~ /\.txt$/i && "$File::Find::dir" =~ /\.\.\/translations/i );
}

sub hash_a_file {
  my ( $file_in, $hash_ref ) = @_;
  my $hdr = '';
  my $in_header = 1;

  open( F, $file_in ) || die "Error opening $file_in";
  while ( <F> ) {
    chomp; # remove newline
    s/\r*$//g; # remove annoying CR

    if ( $in_header && /^#/ ) {
      if ( /Translation last (pagified|updated)/ ) {
# Ignore since we will replace this with current date below.
     } else {
        $hdr .= $_ . "\n";
     }
   }
    next if ( /^#/ );
    $in_header = 0;

    if ( /\s*:\s*/ ) {
# after the above if () is evaluated true
# $` (dollar backtick) = the phrase to the left of colon
# $' (dollar apostrophy) = the phrase to the right of colon
      $$hash_ref{$`} = $';
   }
 }
  close( F );
  return $hdr;
}

( $this ) = reverse split( /\//, $0 );

$base_dir       = '..';
$trans_dir      = $base_dir . '/translations';
$base_trans_file= $trans_dir . '/English-US.txt';
@infiles        = ();
$notfound       = $total= 0;

$do_all       = 0; # set to 1 to update all languages files
$save_backup  = 1; # set to 0 to live dangerously
$show_missing = 0; # set to 0 to minimize translation file
$use_equals   = 0; # set to 0 to use full text
$verbose      = 0; # comments while the program runs

for ( $i = 0; $i < @ARGV; $i++ ) {
  if ( $ARGV[ $i ] eq '-a' ) {
    $do_all = ( $do_all == 0 ? 1 : 0 );
  } elsif ( $ARGV[ $i ] eq '-b' ) {
    $save_backup = ( $save_backup == 0 ? 1 : 0 );
  } elsif ( $ARGV[ $i ] eq '-m' ) {
    $show_missing = ( $show_missing == 0 ? 1 : 0 );
  } elsif ( $ARGV[ $i ] eq '-e' ) {
    $use_equals = ( $use_equals == 0 ? 1 : 0 );
  } elsif ( $ARGV[ $i ] eq '-v' ) {
    $verbose = ( $verbose == 0 ? 1 : 0 );
  } else {
    next if ( $do_all );
    push( @infiles, ( $ARGV[ $i ] . ( $ARGV[ $i ] !~ /txt$/  ? '.txt' : '' ) ) );
  }
}

print "\nFinding WebCalendar program files.\n\n" if ( $verbose );
find \&find_pgm_files, $base_dir;

@infiles = @txt_files if ( $do_all );

die "Usage: $this [-a][language1 language2 ...]\n" if ( !@infiles );

%foundin = ();
# Where are the translate & tooltip phrases?
foreach $f ( reverse sort @files ) {
  open( F, $f ) || die "Error reading $f";
  $f =~ s,^\.\.\/,,;
  print "Searching $f\n" if ( $verbose );
  while ( <F> ) {
    $data = $_;
    while ( $data =~ /(translate|tooltip)\s*\(\s*['"]\s*/ ) {
      $data = $';
      if ( $data =~ /\s*['"]\s*[,\)]/ ) {
        $foundin{$`} = $f;
      }
      $data = $';
    }
  }
  close( F );
}

# Load the base translation file (English) so every phrase has a default.
print "Reading base translation file: $base_trans_file\n" if ( $verbose );
$base_header = hash_a_file( $base_trans_file, \%base_trans );

# This hash needs to be full-text.
foreach $k ( keys %base_trans ) {
  $total++;
  if ( $base_trans{$k} eq '=' ) {
    $base_trans{$k} = $k;
  }
}

foreach $k (
    'charset',
    'direction',
    'PROGRAM_DATE',
    'PROGRAM_NAME',
    'PROGRAM_URL',
    'PROGRAM_VERSION',
    '__mm__/__dd__/__yyyy__',
    '__month__ __dd__',
    '__month__ __dd__, __yyyy__',
    '__month__ __yyyy__',
    ) {
  $foundin{$k} = 'top of page';
}

( $day, $mon, $year ) = ( localtime( time() ) )[ 3, 4, 5 ];

# Read in 'summary.txt' here to write back out later.
hash_a_file( 'summary.txt', \%summ ) if ( -f 'summary.txt' );

foreach $i ( @infiles ) {
  print "\nUpdating $i\n";

  #
  # Save a backup copy of old translation file before we mess with it.
  #
  if ( $save_backup ) {
    $bak = "$trans_dir/$i";
    $bak =~ s/txt$//;

    print 'Attempting to backup ' . $i . '. ';

    if ( copy( "$trans_dir/$i", $bak . ( stat( "$trans_dir/$i" ) )[9] ) ) {
      print "Success!\n";
    } else {
      warn "Failure!:\n$!";
    }
  }
  print "Reading translations from $i\n" if ( $verbose );

  if ( $i =~ /english-us/i ) {
    %trans = %base_trans; # We already read in English.
    $header = $base_header;
  } else {
    %trans = ();
    #
    # If not updating English, load the translation file we need
    #
    $header = hash_a_file( "$trans_dir/$i", \%trans );
  }

  # Set heading defaults.
  foreach $k ( keys %trans ) {
    if ( $i =~ /english-us/i ) {
      next if ( $k eq 'charset' || $k eq 'direction' );
    }
    if ( $use_equals ) {
      $trans{$k} = ( $trans{$k} eq $base_trans{$k} ? '=' : $trans{$k} );
    } else {
      $trans{$k} = ( $trans{$k} eq '=' ? $base_trans{$k} : $trans{$k} );
    }
  }

  $header .= '# Translation last updated: '
    . sprintf( "%02d-%02d-%04d", $mon + 1, $day, $year + 1900 ) . "\n";

  #
  # Write new translation file.
  #
  $notfound = 0;

  open( OUT, ">$trans_dir/$i" ) || die "Error writing $i: ";
  print OUT $header;

  # Set these once instead of trying to remember to change them in six files.
  print OUT '
PROGRAM_NAME: WebCalendar
PROGRAM_VERSION: v1.2.5
PROGRAM_DATE: 29 Feb 2012
PROGRAM_URL: http://www.k5n.us/webcalendar.php' if ( $i =~ /english-us/i );

  print OUT '
' . ( '#' x 80 ) . '
#                       DO NOT "TRANSLATE" THIS SECTION                        #
' . ( '#' x 80 ) if ( $i !~ /english-us/i );

  if ( $use_equals ) {
    print OUT '

# A lone equal sign "=" to the right of the FIRST colon ": " indicates that
# the "translation" is identical to the ';

    print OUT 'phrase on the left.
' if ( $i =~ /english-us/i );
    print OUT 'English text.
' if ( $i !~ /english-us/i );
  }

  print OUT '
# Specify a charset (will be sent within meta tag for each page).' if ( $i !~ /english-us/i );

  print OUT '

charset: ' . $trans{'charset'};

  print OUT '

# "direction" need only be changed if using a right to left language.
# Options are: ltr (left to right, default) or rtl (right to left).
' if ( $i !~ /english-us/i );

  print OUT '
direction: ' . $trans{'direction'};

  print OUT '

# In the date formats, change only the format of the terms.
# For example in German.txt the proper "translation" would be
#   __month__ __dd__, __yyyy__: __dd__. __month__ __yyyy__
' if ( $i !~ /english-us/i );

  print OUT '
__mm__/__dd__/__yyyy__: ' . $trans{'__mm__/__dd__/__yyyy__'} . '
__month__ __dd__: ' . $trans{'__month__ __dd__'} . '
__month__ __dd__, __yyyy__: ' . $trans{'__month__ __dd__, __yyyy__'} . '
__month__ __yyyy__: ' . $trans{'__month__ __yyyy__'} . '
';

  print OUT '
' . ( '#' x 80 ) . '
' . ( '#' x 80 ) . '
' if ( $i !~ /english-us/i );

  foreach $j ( sort @files ) {
    $pageheader = "\n" . ( '#' x 40 ) . "\n# Page: $j\n#\n";
    foreach $text ( keys %foundin ) {
      next if ( $j ne $foundin{$text} );
      if ( exists $trans{$text} ) {
        print OUT $pageheader . $text . ': ' . ( $use_equals
            && ( ( $i =~ /english-us/i && $text eq $base_trans{$text} )
              || ( $i !~ /english-us/i && $trans{$text} eq $base_trans{$text} ) )
          ? '='
          : $trans{$text} ) . "\n";
        $pageheader = '';
      } else {
        $notfound++;
        if ( $show_missing ) {
          print OUT $pageheader . "#\n# << MISSING >>\n# $text:\n";
          $pageheader = '';
          print OUT "# English text: $base_trans{$text}\n#\n"
            if ( length( $base_trans{$text} ) && $base_trans{$text} ne $text );
        }
      }
    }
  }

  $summ{$i} = ( $notfound
    ? sprintf ( "%4d (%4.1f%% complete)", $notfound,
      ( 100 - ( $notfound / $total ) * 100 ) )
    : 'COMPLETE        ' );

  print STDERR (
    !$notfound
    ? "All text was found in $i.  Good job :-)\n"
    : "$notfound translation(s) missing.\n"
  );
}

# Update "summary.txt" while we're heere.
open( OUT, ">summary.txt" ) || die 'Can\'t write "summary.txt"';
printf OUT ( "%-24s %21s\n\n", 'Language file', '# missing translations' );
foreach $k ( sort keys %summ ) {
  printf OUT ( "%-24s %21s\n", "$k:", $summ{$k} );
}
close( OUT );

exit 0;
