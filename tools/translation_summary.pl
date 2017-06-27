#!/usr/bin/perl
# $Id: translation_summary.pl,v 1.9 2008/09/05 18:01:30 bbannon Exp $
#
# Examine all translation files to create a report that shows how
# many translations are missing from each translation file.
#
#######################################################################
$transdir = '../translations';

opendir ( DIR, $transdir ) || die 'Error opening ' . "$transdir: $!";
# We only want *.txt files, sorted.
@files = grep ( /txt$/i, sort readdir ( DIR ) );
closedir ( DIR );

# header
printf "%-25s %s\n", 'Language file', 'No. missing translations';

foreach $f ( @files ) {
  $out = `perl check_translation.pl $transdir/$f`;
  if ( $out =~ / missing./ ) {
    # missing some translations
    @lines = split ( /\n/, $out );
    ( $l ) = grep ( / translation.s. missing/, @lines );
    if ( $l =~ /^(\d+).*\((\d\S+)% complete/ ) {
      printf "%-25s %4d (%4.1f%% complete)\n", $f . ':', $1, $2;
    }
  } else {
    # all translations found :-)
    printf "%-25s %s\n", $f . ':', 'Complete';
  }
}
