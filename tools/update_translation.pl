#!/usr/local/bin/perl
#
# This tool will update a translation file by doing the following:
# - Insert an empty translation when a missing translation is found.
#   The translation will have "<< MISSING >>" right above it to
#   make it easy to find.
# - Show the English translation (in a comment) before a missing translation,
#   making it easier to add the translation.
# - Translations will be reorganized so that they are divided up into
#   the pages that they appear on.
#
# Note: you will lose any comments you put in the translation file
# when using this tool (except for the comments at the very beginning).
#
# Note #2: This will overwrite the existing translation file, so a save
# backup of the translation will be saved with a .bak file extension.
#
# Usage:
#	update_translation.pl languagefile
#
# Example:
#	update_translation.pl French.txt
#
# Note: this utility should be run from this directory (tools).
#
###########################################################################

$trans_dir = "../translations";

$base_trans = "$trans_dir/English-US.txt";

$infile = $ARGV[0];
if ( -f "$trans_dir/$infile" ) {
  $infile = "$trans_dir/$infile";
}

die "Usage: $0 translationfile\n" if ( ! -f $infile );

# Now load the base translation file (English), so that we can include
# the English translation text above a missing translation in a comment.
open ( F, $base_trans ) || die "Error opening $base_trans";
while ( <F> ) {
  chop;
  s/\r*$//g; # remove annoying CR
  next if ( /^#/ );
  if ( /\s*:\s*/ ) {
    $abbrev = $`;
    $base_trans{$abbrev} = $';
  }
}


#
# Now load the translation file we are going to update.
#
$old = "";
if ( -f $infile ) {
  open ( F, $infile ) || die "Error opening $infile";
  $in_header = 1;
  while ( <F> ) {
    $old .= $_;
    chop;
    s/\r*$//g; # remove annoying CR
    if ( $in_header && /^#/ ) {
      if ( /Translation last (pagified|updated)/ ) {
	# ignore since we will replace this with current date below
      } else {
        $header .= $_ . "\n";
      }
    }
    next if ( /^#/ );
    $in_header = 0;
    if ( /\s*:\s*/ ) {
      $abbrev = $`;
      $trans{$abbrev} = $';
    }
  }
}

#
# Save a backup copy of old translation file.
#
open ( F, ">$infile.bak" ) || die "Error writing $infile.bak";
print F $old;
close ( F );
print "Backup of translation saved in $infile.bak\n";


if ( $header !~ /Translation last updated/ ) {
  ( $day, $mon, $year ) = ( localtime ( time() ) )[3,4,5];
  $header .= "# Translation last updated on " .
    sprintf ( "%02d-%02d-%04d", $mon + 1, $day, $year + 1900 ) . "\n";
}

# First get the list of .php files
opendir ( DIR, ".." ) || die "Error opening ..";
@files = grep ( /\.php$/, readdir ( DIR ) );
closedir ( DIR );

opendir ( DIR, "../includes" ) || die "Error opening ../includes";
@incfiles = grep ( /\.php$/, readdir ( DIR ) );
closedir ( DIR );
foreach $f ( @incfiles ) {
  push ( @files, "includes/$f" );
}
push ( @files, "tools/send_reminders.php" );

#
# Write new translation file.
#
$notfound = 0;
open ( OUT, ">$infile" ) || die "Error writing $infile: ";
print OUT $header;
foreach $f ( @files ) {
  print OUT "\n\n###############################################\n# Page: $f\n#\n";
  $file = "../$f";
  open ( F, $file ) || die "Error reading $file";
  #print "Checking $f for text.\n";
  %thispage = ();
  while ( <F> ) {
    $data = $_;
    while ( $data =~ /(translate|tooltip)\s*\(\s*"/ ) {
      $data = $';
      if ( $data =~ /"\s*\)/ ) {
        $text = $`;
	if ( defined ( $thispage{$text} ) ) {
          # text already found within this page...
	} elsif ( defined ( $text{$text} ) ) {
          print OUT "# \"$text\" previously defined (in $foundin{$text})\n";
	  $thispage{$text} = 1;
	} else {
          if ( ! length ( $trans{$text} ) ) {
            print OUT "#\n# << MISSING >>\n# $text:\n";
            print OUT "# English text: $base_trans{$text}\n#\n"
              if ( length ( $base_trans{$text} ) );
            $notfound++;
	  } else {
            $text{$text} = 1;
	    $foundin{$text} = $f;
	    $thispage{$text} = 1;
            printf OUT ( "%s: %s\n", $text, $trans{$text} );
	  }
        }
        $data = $';
      }
    }
  }
  close ( F );
}

if ( ! $notfound ) {
  print STDERR "All text was found in $infile.  Good job :-)\n";
} else {
  print STDERR "$notfound translation(s) missing.\n";
}

exit 0;
