#!/usr/bin/perl

=head1 NAME
palm_datebook.pl

=head1 SYNOPSIS
Reads the events from a Palm Desktop DateBook.dat

=head1 DESCRIPTION
This file reads a Palm Desktop DateBook file (datebook.dat) and prints
all the non-expired entries (can return all, see below) to STDOUT.  It prints a pipe
separated value list by default.

=head1 USAGE
The script is set up to be used with the webcalendar and doesn't need to be
altered.  If you want to use it some other way, then change the variables in
the config section to suit your needs and edit the main program (bottom of script).
You can uncomment the $outfile config to print to a file.  The default program 
will not include expired events and takes 2 arguments:

  1. $DateBookFileName - The name/location of the datebook.dat
  2. $exc_private - If a 1 is passed private records will be skipped

The following data is available in $Entry:

$Entry->{RecordID}           =  Record ID in the Palm
$Entry->{Status}             =  Identifies new and deleted records (status in datebook)
$Entry->{Position}           =  Position in list?
$Entry->{StartTime}          =  In seconds since 1970
$Entry->{EndTime}            =  In seconds since 1970
$Entry->{Description}        =  Description of event (string)
$Entry->{Duration}           =  How long the event lasts (in minutes)
$Entry->{Note}               =  Note (string)
$Entry->{Untimed}            =  1 = true  0 = false
$Entry->{Private}            =  1 = true  0 = false
$Entry->{Category}           =  useless for Palm
$Entry->{AlarmSet}           =  1 = true  0 = false
$Entry->{AlarmAdvanceAmount} =  How many units in AlarmAdvanceType (-1 means not set)
$Entry->{AlarmAdvanceType}   =  Units: (0=minutes, 1=hours, 2=days)
$Entry->{Repeat}             =  Array containing repeat information (if repeat)
$Entry->{Repeat}->{Interval}   =  1=daily,2=weekly,3=MonthlyByDay,4=MonthlyByDate,5=Yearly
$Entry->{Repeat}->{Frequency}  =  How often event occurs. (1=every, 2=every other,etc.)
$Entry->{Repeat}->{EndTime}    =  When the repeat ends (In seconds since 1970)
$Entry->{Repeat}->{Exceptions} =  An exception to the repeat (In seconds since 1970)
$Entry->{Repeat}->{RepeatDays} =  For Weekly: What days to repeat on (7 characters...y or n for each day)
$Entry->{Repeat}->{DayNum}     =  For MonthlyByDay: Day of week (1=sun,2=mon,3=tue,4=wed,5=thu,6=fri,7=sat)
$Entry->{Repeat}->{WeekNum}    =  For MonthlyByDay: Week number (1=first,2=second,3=third,4=fourth,5=last)

=head1 CONTRIBUTERS
Eduard Martinescu - Provided patch to parse category list.

=cut

use CGI qw (:standard);
my $q = new CGI;
my ($Year, $Month, $Day);
my $DATA;

# -----------------------  Config if necessary  ----------------------------
my $DateBookFileName = $ARGV[0];  # The name of the file
my $exc_private = $ARGV[1];       # Do we want private entries? (1 = skip private)
my $inc_expired = 0;              # Do we want expired entries? (1 = include expired)
my $sep = "|";                    # what to separate the output with
#my $outfile = "/tmp/datebook_dump.txt";  # uncomment to print to file
#---------------------------------------------------------------------------

#=================
sub ReadDateBook {
#=================
# ReadDateBook opens the file we passed (datebook.dat) and reads the entries.

  my ($FileName, $Filter) = @_;
  my (@Fields, @Entries, $FieldCount, $NumberOfEntries);
  my ($Entry, $i, $Header, $Tag);
  my ($NextFree, $CategoryCount, $Category, @Categories, $ResourceID);
  my ($FieldsPerRow, $RecordIdPos, $RecordStatusPos, $RecordPlacementPos);

  open DATEBOOK, "<".$FileName;
  binmode DATEBOOK;

  local $/ = undef;
  $_ = <DATEBOOK>;
  $GlobalPos = 0;
  close DATEBOOK;

  # First, check the initial 4 byte "tag" field.
  $Tag = ReadByteString(4);

  # Next, read the header information.
  $FileName = ReadPilotString();
  $Header   = ReadPilotString();
  $NextFree = ReadLong();

  # Read the category information
  $CategoryCount = ReadLong();
  for ($i=0; $i<$CategoryCount; $i++) {
    $Category = ReadCategory();
    push (@Categories,$Category) if ($Category ne 0);
  }

  $ResourceID         = ReadLong();
  $FieldsPerRow       = ReadLong();
  $RecordIdPos        = ReadLong();
  $RecordStatusPos    = ReadLong();
  $RecordPlacementPos = ReadLong();

  # Read the field list.
  $FieldCount = ReadShort();
  for ($i=0; $i<$FieldCount; $i++)
     {push @Fields, ReadShort();}

  # Figure out how many entries to read
  $NumberOfEntries = ReadLong() / $FieldCount;

  # Read the entries.
  for ($i=0; $i<$NumberOfEntries; $i++) {
    $Entry = ReadEntry();
    if ($Entry ne 0){
      if (!$Filter or &$Filter($Entry)){push @Entries, $Entry;}
    }
  }

  return @Entries;
}

#==============
sub ReadEntry {
#==============
# ReadPalmEntry reads a single entry from the datebook, stores it in a local
# hash, and returns a reference to that hash.  The reference can safely
# be stored in an array for later use.

  my (%Entry);

  $Entry{RecordID}           = ReadPilotField();
  $Entry{Status}             = ReadPilotField();
  $Entry{Position}           = ReadPilotField();
  $Entry{StartTime}          = ReadPilotField();
  $Entry{EndTime}            = ReadPilotField();
  $Entry{Description}        = ReadPilotField();
  $Entry{Duration}           = ReadPilotField();
  $Entry{Note}               = ReadPilotField();
  $Entry{Untimed}            = ReadPilotField();
  $Entry{Private}            = ReadPilotField();
  $Entry{Category}           = ReadPilotField();
  $Entry{AlarmSet}           = ReadPilotField();
  $Entry{AlarmAdvanceAmount} = ReadPilotField();
  $Entry{AlarmAdvanceType}   = ReadPilotField();
  $Entry{Repeat}             = ReadPilotField();

  #Should return as -1 if not set, but is returning as 4294967295
  $Entry{AlarmAdvanceAmount} = "-1" if ($Entry{AlarmAdvanceAmount} eq '4294967295');

  # Remove Crap from the DateBook5 Application
  $Entry{Note} = '' if ($Entry{Note} =~ /^\#\#[fcdxX\@]/);

  # Filter single quotes, \n\r
  $Entry{Description} = &filter_quotes($Entry{Description});
  $Entry{Note} = &filter_quotes($Entry{Note});

  # Calculate duration in minutes
  $Entry{Duration} = ($Entry{EndTime} - $Entry{StartTime}) / 60;

  # Skip private records if $exc_private
  if (($exc_private) && ($Entry{Private} == 1)) {
    return 0;
  # Skip Record if not in Palm (no RecordID) or marked for deletion
  } elsif (($Entry{RecordID} == 0) || ($Entry{Status} == 129) || ($Entry{Status} == 4)){
    return 0;
  # Skip events that are past endtime (except repeats that aren't expired) unless $inc_expired
  } elsif (($Entry{EndTime} < time()) && (!$Entry{Repeat}) && (!$inc_expired)){
    return 0;
  } elsif (($Entry{Repeat}) && ($Entry{Repeat}{EndTime} < time())&& ($Entry{Repeat}{EndTime} != 0) && (!$inc_expired)){
    return 0;
  } else {
#print $Entry{RecordID} . "\n";
    return \%Entry;
  }
}

#===================
sub ReadPilotField {
#===================
# ReadPilotField returns a single field from the datebook file.
  my ($Type, $N, $sun, $mon, $tue, $wed, $thu, $fri, $sat);
  my ($i, $DatesToSkip, $Repeat, $Interval, $Frequency, $Duration, $Position, $EndTime, $exceptions, @E);
  my (%RA);

  $Type = ReadLong();

  if ($Type == 1 or $Type == 3 or $Type == 6) {
    return ReadLong();
  } elsif ($Type == 5) {
     ReadLong();  # Skip the long of all zeroes
     return ReadPilotString();
  } elsif ($Type == 8) {
     $DatesToSkip = ReadShort();
     for ($i=1; $i<=$DatesToSkip; $i++)
        {$exceptions .= ReadLong().":";}
     chop $exceptions;
     $Repeat = ReadShort();
     if ($Repeat == 0xFFFF) {
       ReadShort();
       $Skip = ReadShort();
       SkipBytes($Skip);
     } elsif ($Repeat and $Repeat != 0x8001) { #($Repeat == 0x1a40 or $Repeat == 0xb3c0 or $Repeat == 0xe750)
#         print "       DEBUG: Repeat is $Repeat\n";
#         ReadLong();
     }
     if ($Repeat) {
       $Interval   = ReadLong();
       $Frequency  = ReadLong();
       $EndTime    = ReadLong();
       if ($EndTime eq 1956542399) {       # No EndTime
         $EndTime = '';
       }

	     ReadLong();
       $DayNum =  ReadLong();
       if ($Interval == 2) {
         $Position = ReadByte();
       } elsif ($Interval == 3) {
         $Position = ReadLong();
       } elsif ($Interval == 5) {
         $Position = ReadLong();
       } else {
         $Position == 0;
       }

       # Build the Repeat array to return
       $RA{Interval} = $Interval;
       $RA{Frequency} = $Frequency;
       $RA{EndTime}  = $EndTime;
       if ($exceptions){ $RA{Exceptions} = $exceptions;}

       if ($Interval == 2) {            # Weekly repeat
         # $Position is an integer that tells what days of the week
         # to repeat on. (sun=1,mon=2,tue=4,wed=8,thu=16,fri=32,sat=64)
         # The numbers are added together to give a unique integer.
         # We will break it down since the WebCalendar doesn't use this format.
         $N = $Position;

          # Check for Saturday
          if ($N - 64 >= 0) {
            $sat = 'y';
            $N -= 64;
          } else {
            $sat = 'n';
          }

          # Check for Friday
          if ($N - 32 >= 0) {
            $fri = 'y';
            $N -= 32;
          } else {
            $fri = 'n';
          }

          # Check for Thursday
          if ($N - 16 >= 0) {
            $thu = 'y';
            $N -= 16;
          } else {
            $thu = 'n';
          }

          # Check for Wednesday
          if ($N - 8 >= 0) {
            $wed = 'y';
            $N -= 8;
          } else {
            $wed = 'n';
          }

          # Check for Tuesday
          if ($N - 4 >= 0) {
            $tue = 'y';
            $N -= 4;
          } else {
            $tue = 'n';
          }

          # Check for Monday
          if ($N - 2 >= 0) {
            $mon = 'y';
            $N -= 2;
          } else {
            $mon = 'n';
          }

          # Check for Sunday
          if ($N - 1 >= 0) {
            $sun = 'y';
            $N -= 1;
          } else {
            $sun = 'n';
          }
          $RA{RepeatDays} = $sun.$mon.$tue.$wed.$thu.$fri.$sat;
       } elsif ($Interval == 3) {      # Monthlybyday repeat
         $RA{DayNum} = $DayNum + 1;    # Day of week (1=sun,2=mon,3=tue,4=wed,5=thu,6=fri,7=sat)
         $RA{WeekNum} = $Position + 1; # Week number (1=first,2=second,3=third,4=fourth,5=last)
       }
       return \%RA;
     } else {
       return 0;  # No repeat
     }
  } else {
#      print STDERR "There's a problem with this pilot field of type $Type\n";
   return undef;
  }
}


#===================
sub ReadByteString {
#===================
# ReadByteString reads the number of bytes passed to it as a parameter
# and returns it as a character string.

   my ($Count) = @_;
   $GlobalPos += $Count;
   return substr ($_, $GlobalPos-$Count, $Count);
}

#====================
sub ReadPilotString {
#====================
# ReadPilotString reads a pilot formatted string, which is a size (one
# byte, unless the byte is 0xFF, then it's the two bytes after the 0xFF),
# followed by that many bytes, and returns a Perl string.

  my ($String) = "";
  my ($Length, $i);

  $Length = unpack ('C', substr ($_, $GlobalPos, 1));
  $GlobalPos++;
  if ($Length == 255) {
    $Length = ReadShort();
  }

  $GlobalPos += $Length;
  return substr ($_, $GlobalPos-$Length, $Length);
}

#==============
sub SkipBytes {
#==============
# SkipBytes is just like ReadByteString, except it throws away the data,
# rather than returning it.

  my ($Count) = @_;
  $GlobalPos += $Count;
}

#=============
sub ReadByte {
#=============
# ReadByte reads a single byte, and returns it as an integer.

  $GlobalPos++;
  return unpack ('C', substr($_, $GlobalPos-1, 1));
}

#==============
sub ReadShort {
#==============
# ReadShort reads two bytes, and returns them as an integer (low order
# byte is the first one read).

  $GlobalPos+=2;
  return unpack ('S', substr($_, $GlobalPos-2, 2));
}

#=============
sub ReadLong {
#=============
# ReadLong reads four bytes, and returns them as an integer (low order
# byte is the first one read).

  $GlobalPos+=4;
  return unpack ('L', substr($_, $GlobalPos-4, 4));
}

#====================
sub ByDateAscending {
#====================
# Sort records by StartTime

  return $a->{StartTime} <=> $b->{StartTime};
}

#==================
sub filter_quotes {
#==================
# Filter newline/return
   
  my $temp = $_[0];
#  $temp =~ s/'/\\'/g;
  $temp =~ s/\n|\r/ /g; # Remove newline
  return ($temp);
}

#=================
sub ReadCategory {
#=================
# ReadCategory reads a single category entry from the datebook,
# stores it in a local hash, and returns a reference to that hash.
# The reference can safely be stored in an array for later use.

  my (%Entry);

  $Entry{Index}         = ReadLong();
  $Entry{CategoryID}    = ReadLong();
  $Entry{DirtyFlag}     = ReadLong();
  $Entry{LongName}      = ReadPilotString();
  $Entry{ShortName}     = ReadPilotString();
  return \%Entry;
}

#-----------------------------  Main Program -------------------------------

foreach $Entry (sort ByDateAscending ReadDateBook($DateBookFileName)) {
  $DATA .=  $Entry->{RecordID}. $sep;
  $DATA .=  $Entry->{StartTime}. $sep;
  $DATA .=  $Entry->{EndTime}. $sep;
  $DATA .=  $Entry->{Description}. $sep;
  $DATA .=  $Entry->{Duration}. $sep;
  $DATA .=  $Entry->{Note}. $sep;
  $DATA .=  $Entry->{Untimed}. $sep;
  $DATA .=  $Entry->{Private}. $sep;
  $DATA .=  $Entry->{Category}. $sep;
  $DATA .=  $Entry->{AlarmSet}. $sep;
  $DATA .=  $Entry->{AlarmAdvanceAmount}. $sep;
  $DATA .=  $Entry->{AlarmAdvanceType}. $sep;
  $DATA .=  $Entry->{Repeat}->{Interval}. $sep;
  $DATA .=  $Entry->{Repeat}->{Frequency}. $sep;
  $DATA .=  $Entry->{Repeat}->{EndTime}. $sep;
  $DATA .=  $Entry->{Repeat}->{Exceptions}. $sep;
  $DATA .=  $Entry->{Repeat}->{RepeatDays}. $sep;
#  $DATA .=  $Entry->{Repeat}->{DayNum}. $sep;
  $DATA .=  $Entry->{Repeat}->{WeekNum}. $sep;
  $DATA .=  "\n";
}

if ($outfile) {
  die "Couldn't open $outfile: $!" if ((open OUT, ">$outfile") eq undef);
  flock (OUT, 2);print OUT $DATA;flock (OUT, 8);close OUT;
} else {
  print STDOUT $DATA;
}
exit;