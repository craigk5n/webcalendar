#!/usr/bin/perl
#
# Update each translation file using update_translation.pl.
#
#######################################################################

$transdir = "../translations";

opendir ( DIR, $transdir ) || die "Error opening $transdir: $!";
@files = readdir ( DIR );
closedir ( DIR );

# ignore everything except .txt files
@files = grep ( /.txt$/, @files );

foreach $f ( @files ) {
  print "update_translation.pl $f\n";
  print `perl update_translation.pl $f`;
}
print "Done.\n";
exit 0;

