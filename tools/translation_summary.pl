#!/usr/bin/perl
#
# Examine all translation files to create a report that shows how
# many translations are missing from each translation file.
#
#######################################################################

$inc_dir = "../includes";

$transdir = "../translations";

opendir ( DIR, $transdir ) || die "Error opening $transdir: $!";
@files = readdir ( DIR );
closedir ( DIR );

# ignore everything except .txt files
@files = grep ( /.txt$/, @files );

# header
printf "%-20s %s\n", "Language file", "No. missing translations";

foreach $f ( @files ) {
  $out = `perl check_translation.pl ../translations/$f`;
  if ( $out =~ / missing./ ) {
    # missing some translations
    @lines = split ( /\n/, $out );
    ( $l ) = grep ( / translation.s. missing/, @lines );
    if ( $l =~ /^(\d+) / ) {
      printf "%-20s %d\n", $f . ":", $1;
    }
  } else {
    # all translations found :-)
    printf "%-20s %s\n", $f . ":", "Complete";
  }
}

