<?php
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );
global $GROUPS_ENABLED,$WORK_DAY_START_HOUR,$WORK_DAY_END_HOUR;

$user = $arinc[3];

?>
var bydayAr = bymonthdayAr = bysetposAr = [];

/* These are set in translate.js.php
However, I've changed the names here to match everywhere else.
var byday_labels = ['SU','MO','TU','WE','TH','FR','SA'];
var byday_names = [
  "<?php etranslate ( 'SU' ) ?>"
, "<?php etranslate ( 'MO' ) ?>"
, "<?php etranslate ( 'TU' ) ?>"
, "<?php etranslate ( 'WE' ) ?>"
, "<?php etranslate ( 'TH' ) ?>"
, "<?php etranslate ( 'FR' ) ?>"
, "<?php etranslate ( 'SA' ) ?>"];
*/

// do a little form verifying
function validate_and_submit() {
  if ( form.name.value == "" ) {
    form.name.select();
<?php
    if ( empty ( $GLOBALS['EVENT_EDIT_TABS'] ) ||
      $GLOBALS['EVENT_EDIT_TABS'] == 'Y' ) { ?>
    showTab( 'details' );
<?php } ?>
    form.name.focus();
    alert ( "<?php etranslate ( 'You have not entered a Brief Description.', true )?>");
    return false;
  }
  if ( form.timetype &&
    form.timetype.selectedIndex == 1 ) {
    h = parseInt (isNumeric( form.entry_hour.value ));
    m = parseInt (isNumeric( form.entry_minute.value ));

    // Ask for confirmation for time of day if it is before the user's
    // preference for work hours.
    <?php if ($GLOBALS['TIME_FORMAT'] == "24") {
      echo "if ( h < $WORK_DAY_START_HOUR  ) {";
    }  else {
      echo "if ( h < $WORK_DAY_START_HOUR && form.entry_ampmA.checked ) {";
    }
    ?>
    if ( ! confirm ( "<?php etranslate ( 'time prior to work hours...', true)?> "))
      return false;
   }
  }

  // is there really a change?
  changed = false;
  for ( i = 0; i < form.elements.length; i++ ) {
    field = form.elements[i];
    switch ( field.type ) {
      case "radio":
      case "checkbox":
        if ( field.checked != field.defaultChecked )
          changed = true;
        break;
      case "text":
      case "textarea":
        if ( field.value != field.defaultValue )
          changed = true;
        break;
      case "select-one":
 //Don't register a percentage change
      if ( form.elements[i].name == "percent")
        break;
//      case "select-multiple":
        for( j = 0; j < field.length; j++ ) {
          if ( field.options[j].selected != field.options[j].defaultSelected )
            changed = true;
        }
        break;
    }
  }
  if ( changed ) {
    form.entry_changed.value = "yes";
  }

//Add code to make HTMLArea code stick in TEXTAREA
 if (typeof editor != "undefined") editor._textArea.value = editor.getHTML();

 //Check if Event date is valid
  var d = form.day.selectedIndex;
  var vald = form.day.options[d].value;
  var m = form.month.selectedIndex;
  var valm = form.month.options[m].value;
  var y = form.year.selectedIndex;
  var valy = form.year.options[y].value;
  var c = new Date(valy,valm -1,vald);

 if ( c.getDate() != vald ) {
   alert ("<?php etranslate ( 'Invalid Event Date', true)?>.");
  form.day.focus();
   return false;
 }
 //Repeat Tab enabled, Select all of them
 if ( form.rpttype ) {
   for ( i = 0; i < elements['exceptions[]'].length; i++ ) {
     elements['exceptions[]'].options[i].selected = true;
   }
 }

 //set byxxxList values for submission
 var bydayStr = '';
 for ( bydayKey in bydayAr ) {
   if ( bydayKey == isNumeric ( bydayKey ) )
     bydayStr = bydayStr + ',' + bydayAr[bydayKey];
 }
 if ( bydayStr.length > 0 )
   elements['bydayList'].value = bydayStr.substr(1);
 //set bymonthday values for submission
 var bymonthdayStr = '';
 for ( bymonthdayKey in bymonthdayAr ) {
   if ( bymonthdayKey == isNumeric ( bymonthdayKey ) )
     bymonthdayStr = bymonthdayStr + ',' + bymonthdayAr[bymonthdayKey];
 }
 if ( bymonthdayStr.length > 0 )
   elements['bymonthdayList'].value = bymonthdayStr.substr(1);

 //set bysetpos values for submission
 var bysetposStr = '';
 for ( bysetposKey in bysetposAr ) {
   if ( bysetposKey == isNumeric ( bysetposKey ) )
     bysetposStr = bysetposStr + ',' + bysetposAr[bysetposKey];
 }
 if ( bysetposStr.length > 0 )
   elements['bysetposList'].value = bysetposStr.substr(1);

 //select allusers in selectedPart
 if ( form.elements['selectedPart[]'] ) {
   var userlist = form.elements['selectedPart[]'];
   for( i = 0; i < userlist.length; i++ ) {
     userlist.options[i].selected = true;
   }
 }

 form.submit();
 return true;
}

<?php if ( $GROUPS_ENABLED == 'Y' ) {
?>

// Set the state (selected or unselected) if a single user in the list of users.
function selectByLogin ( login ) {
  //alert ( "selectByLogin ( " + login + " )" );
  //Check Users
  var list = document.editentryform.entry_part;
  var listlen = list.options.length;
  for ( var i = 0; i < listlen; i++ ) {
    if ( list.options[i].value == login ) {
      list.options[i].selected = true;
      return true;
    }
  }
  //Check Resources
  var list = document.editentryform.res_part;
  var listlen = list.options.length;
  for ( var i = 0; i < listlen; i++ ) {
    if ( list.options[i].value == login ) {
      list.options[i].selected = true;
      return true;
    }
  }
}

function addGroup() {
  var
    list = document.editentryform.groups,
    selNum = list.selectedIndex;
    //alert ( selNum);
<?php
  $groups = get_groups ( $user );
  for ( $i = 0; $i < count ( $groups )  ; $i++ ) {
    echo "\n    if ( selNum == $i ) {\n";
    $res = dbi_execute ( 'SELECT cal_login
  FROM webcal_group_user
  WHERE cal_group_id = ?', [$groups[$i]['cal_group_id']] );
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        echo "      selectByLogin ( \"$row[0]\" );\n";
      }
      dbi_free_result ( $res );
      echo "  }\n";
    }
  }
?>
}
<?php }
// This function is called when the event type combo box
 // is changed. If the user selectes "untimed event" or "all day event",
 // the times & duration fields are hidden.
 // If they change their mind & switch it back, the original
 // values are restored for them
?>function timetype_handler() {
  if ( ! form.timetype )
   return true;
  var i = form.timetype.selectedIndex;
  var val = form.timetype.options[i].text;
  if ( i != 1 ) {
    // Untimed/All Day
    makeInvisible ( "timeentrystart" );
    if ( form.timezonenotice ) {
      makeInvisible ( "timezonenotice" );
    }
    if ( form.duration_h ) {
      makeInvisible ( "timeentryduration" );
    } else {
      makeInvisible ( "timeentryend" );
    }
     if ( form.rpttype ) {
      makeInvisible ( "rpt_until_time_date", true );
    }
  } else {
    // Timed Event
    makeVisible ( "timeentrystart" );
    if ( form.timezonenotice ) {
      makeVisible ( "timezonenotice" );
    }

    if ( form.duration_h ) {
      makeVisible ( "timeentryduration" );
    } else {
      makeVisible ( "timeentryend" );
    }
    if ( form.rpttype ) {
      makeVisible ( "rpt_until_time_date", true );
    }
  }
}

function rpttype_handler() {
  //Repeat Tab disabled
  if ( ! form.rpttype ) {
    return;
  }
  var expert = ( document.getElementById('rptmode').checked);
  var i = form.rpttype.selectedIndex;
  var val = form.rpttype.options[i].text;
  //alert ( "val " + i + " = " + val );
  //i == 0 none
  //i == 1 daily
  //i == 2 weekly
  //i == 3,4,5 monthlyByDay, monthlyByDate, monthlyBySetPos
  //i == 6 yearly
  //i == 7 manual  Use only Exclusions/Inclusions
 //Turn all off initially
  makeInvisible ( "rpt_mode" );
  makeInvisible ( "rptenddate1", true );
  makeInvisible ( "rptenddate2", true );
  makeInvisible ( "rptenddate3", true );
  makeInvisible ( "rptfreq", true );
  makeInvisible ( "weekdays_only" );
  makeInvisible ( "rptwkst" );
  //makeInvisible ( "rptday", true );
  makeInvisible ( "rptbymonth", true );
  makeInvisible ( "rptbydayln", true );
  makeInvisible ( "rptbydayln1", true );
  makeInvisible ( "rptbydayln2", true );
  makeInvisible ( "rptbydayln3", true );
  makeInvisible ( "rptbydayln4", true );
  makeInvisible ( "rptbydayextended", true );
  makeInvisible ( "rptbymonthdayextended", true );
  makeInvisible ( "rptbysetpos", true );
  makeInvisible ( "rptbyweekno", true );
  makeInvisible ( "rptbyyearday", true );
  makeInvisible ( "rptexceptions", true );
  //makeInvisible ( "select_exceptions_not", true );
  if ( i > 0 && i < 7 ) {
    //always on
    makeVisible ( "rptenddate1", true );
    makeVisible ( "rptenddate2", true );
    makeVisible ( "rptenddate3", true );
    makeVisible ( "rptfreq", true );
    makeVisible ( "rptexceptions", true);
    makeVisible ( "rpt_mode" );

    if ( i == 1 ) { //daily
      makeVisible ( "weekdays_only" );
    }

    if ( i == 2 ) { //weekly
      makeVisible ( "rptbydayextended", true );
      if (expert ) {
        makeVisible ( "rptwkst" );
      }
    }
   if ( i == 3 ) { //monthly (by day)
     if (expert ) {
        makeVisible ( "rptwkst" );
        makeVisible ( "rptbydayln", true );
        makeVisible ( "rptbydayln1", true );
        makeVisible ( "rptbydayln2", true );
        makeVisible ( "rptbydayln3", true );
        makeVisible ( "rptbydayln4", true );
     }
   }

   if ( i == 4 ) { //monthly (by date)
     if (expert ) {
       makeVisible ( "rptbydayextended", true );
       makeVisible ( "rptbymonthdayextended", true );
     }
   }

   if ( i == 5 ) { //monthly (by position)
      makeVisible ( "rptbysetpos", true );
   }

  if ( i == 6 ) {  //yearly
    if (expert ) {
        makeVisible ( "rptwkst" );
        makeVisible ( "rptbymonthdayextended", true );
        makeVisible ( "rptbydayln", true );
        makeVisible ( "rptbydayln1", true );
        makeVisible ( "rptbydayln2", true );
        makeVisible ( "rptbydayln3", true );
        makeVisible ( "rptbydayln4", true );
        makeVisible ( "rptbyweekno", true );
        makeVisible ( "rptbyyearday", true );
    }
  }
  if (expert ) {
    makeVisible ( "rptbydayextended", true );
    makeInvisible ( "weekdays_only" );
    makeVisible ( "rptbymonth", true );
  }
  }
  if ( i == 7 ) {
    makeVisible ( "rptexceptions", true);
  }
}

function rpttype_weekly() {
  var i = form.rpttype.selectedIndex;
  var val = form.rpttype.options[i].text;
 if ( val == "Weekly" ) {
   //Get Event Date values
   var d = form.day.selectedIndex;
   var vald = form.day.options[d].value;
   var m = form.month.selectedIndex;
   var valm = form.month.options[m].value -1;
   var y = form.year.selectedIndex;
   var valy = form.year.options[y].value;
   var c = new Date(valy,valm,vald);
   var dayOfWeek = c.getDay();
   var rpt_day = byday_labels[dayOfWeek];
   elements[rpt_day].checked = true;
 }
}
<?php //see the showTab function in includes/js/visible.php for common code shared by all pages
 //using the tabbed GUI.
?>
var sch_win, tabs = ["details","participants","pete","reminder"]

// Show Availability for the first selection
function showSchedule() {
  //var agent=navigator.userAgent.toLowerCase();
  //var agent_isIE=(agent.indexOf("msie") > -1);
  var userlist = form.elements['selectedPart[]'];
  var delim = '';
  var users = '';
  var cols = <?php echo $WORK_DAY_END_HOUR - $WORK_DAY_START_HOUR ?>;
  //var w = 140 + ( cols * 31 );
  var w = 760;
  var h = 180;
  for ( i = 0; i < userlist.length; i++ ) {
      users += delim + userlist.options[i].value;
      delim = ',';
      h += 18;
    }
  if (users == '') {
    alert("<?php etranslate ( 'Please add a participant', true)?>" );
    return false;
  }
  var mX = 100, mY = 200;
  var MyPosition = 'left=' + mX + ',top=' + mY + ',screenx=' + mX + ',screeny=' + mY;
  var features = MyPosition + ',width='+ w +',height='+ h +',resizable=yes,scrollbars=yes';
  var url = 'availability.php?users=' + users +
           '&form='  + 'editentryform' +
           '&year='  + form.year.value +
           '&month=' + form.month.value +
           '&day='   + form.day.options[form.day.selectedIndex].text;

  if (sch_win != null && !sch_win.closed) {
     h = h + 30;
     sch_win.location.replace( url );
     sch_win.resizeTo(w,h);
  } else {
     sch_win = window.open ( url, "showSchedule", features );
  }
}

function add_exception (which) {
 var sign = "-";
 if (which ) {
    sign = "+";
 }
 var ymd = $('except_YMD').value;
 var y = ymd.substr ( 0, 4 );
 var m = ymd.substr ( 4, 2 );
 if ( m.substr ( 0, 1 ) == '0' )
   m = m.substr = ( 1, 1 );
 var d = ymd.substr ( 6, 2 );
 if ( d.substr ( 0, 1 ) == '0' )
   d = d.substr = ( 1, 1 );

 var c = new Date(parseInt(y),parseInt(m)-1,parseInt(d));
 if ( c.getDate() != d ) {
   alert ("<?php etranslate ( 'Invalid Date',true ) ?>");
   return false;
 }
 //alert ( c.getFullYear() + " "  + c.getMonth() + " " + c.getDate());
 //var exceptDate = String((c.getFullYear() * 100 + c.getMonth() +1) * 100 + c.getDate());
 var exceptDate = ymd;
 var isUnique = true;
 //Test to see if this date is already in the list
  with (form)
   {
      with (elements['exceptions[]'])
      {
         for (i = 0; i < length; i++)
         {
            if ( options[i].text == "-" + exceptDate || options[i].text == "+" + exceptDate ) {
         isUnique = false;
         }
     }
   }
  }
 if ( isUnique ) {
    elements['exceptions[]'].options[elements['exceptions[]'].length]  = new Option( sign + exceptDate, sign + exceptDate );
    makeVisible ( "select_exceptions" );
    makeInvisible ( "select_exceptions_not" );
 }
}
function del_selected() {
   with (form)
   {
      with (elements['exceptions[]'])
      {
         for (i = 0; i < length; i++)
         {
            if ( options[i].selected ) {
         options[i] = null;
         }
         } // end for loop
     if ( ! length ) {
       makeInvisible ( "select_exceptions" );
       makeVisible ( "select_exceptions_not" );
     }
     }
   } // end with document
}

function toggle_byday( ele ) {
  var bydaytext = byday_names[ele.id.substr(2,1)];
  var bydayVal = byday_labels[ele.id.substr(2,1)];
  var tmp = '';
  if (ele.value.length > 4 ) {
    //blank
    ele.value = ele.id.substr(1,1) + bydaytext;
    tmp = ele.id.substr(1,1) + bydayVal;
  } else if (ele.value == ele.id.substr(1,1) + bydaytext) {
    //positive value
    ele.value = ( parseInt( ele.id.substr( 1,1 ) ) -6 ) + bydaytext;
    tmp = ( parseInt( ele.id.substr( 1,1 ) ) -6 ) + bydayVal;
  } else if ( ele.value == ( parseInt( ele.id.substr( 1,1 ) ) -6 ) + bydaytext ) {
    //negative value
  ele.value = "        ";
  tmp = '';
  }
  bydayAr[ele.id.substr(1)] = tmp;
}

function toggle_bymonthday( ele ) {
  var tmp = '';
  if (ele.value .length > 3) {
    //blank
  ele.value = tmp = ele.id.substr(10);
  } else if (ele.value == ele.id.substr(10)) {
    //positive value
  ele.value = tmp = parseInt( ele.id.substr( 10 ) ) -32;
  } else if ( ele.value == ( parseInt( ele.id.substr( 10 ) ) -32 ) ) {
    //negative value
  ele.value = "     ";
  tmp = '';
  }
  bymonthdayAr[ele.id.substr(10)] = tmp;
}

function toggle_bysetpos( ele ) {
  var tmp = '';
  if (ele.value .length > 3) {
    //blank
  ele.value = tmp = ele.id.substr(8);

  } else if (ele.value == ele.id.substr(8)) {
    //positive value
  ele.value = tmp = parseInt( ele.id.substr( 8 ) ) -32;
  } else if ( ele.value == ( parseInt( ele.id.substr( 8 ) ) -32 ) ) {
    //negative value
  ele.value = "    ";
  tmp = '';
  }
  bysetposAr[ele.id.substr(8)] = tmp;
}

function toggle_until() {
  //Repeat Tab disabled
  if ( ! form.rpttype ) {
    return;
  }
 //use date
  elements['rpt_year'].disabled =
  elements['rpt_month'].disabled =
  elements['rpt_day'].disabled =
  elements['rpt_hour'].disabled =
  elements['rpt_minute'].disabled =
  ( form.rpt_untilu.checked != true );

 //use count
 elements['rpt_count'].disabled =
  ( form.rpt_untilc.checked != true );
 if ( elements['rpt_ampmA'] ) {
   if ( form.rpt_untilu.checked ) { //use until date
     document.getElementById('rpt_ampmA').disabled = false;
     document.getElementById('rpt_ampmP').disabled = false;
   } else {
     document.getElementById('rpt_ampmA').disabled = 'disabled';
     document.getElementById('rpt_ampmP').disabled = 'disabled';
   }
  }
}

function toggle_rem_when() {
  //Reminder Tab disabled
  if ( ! form.rem_when ) {
    return;
  }
 if ( elements['reminder_ampmA'] ) {
   if ( elements['rem_when_date'].checked == true ) {
   document.getElementById('reminder_ampmA').disabled = false;
   document.getElementById('reminder_ampmP').disabled = false;
  } else {
   document.getElementById('reminder_ampmA').disabled = 'disabled';
   document.getElementById('reminder_ampmP').disabled = 'disabled';
  }
 }
 elements['rem_days'].disabled =
   elements['rem_hours'].disabled =
   elements['rem_minutes'].disabled =
   elements['rem_beforeY'].disabled =
   elements['rem_relatedS'].disabled =
   elements['rem_beforeN'].disabled =
   elements['rem_relatedE'].disabled =
   elements['rem_when_date'].checked;

 //$('dateselIcon_reminder').disabled =
 elements['reminder_year'].disabled =
   elements['reminder_month'].disabled =
   elements['reminder_day'].disabled =
   elements['reminder_hour'].disabled =
   elements['reminder_minute'].disabled =
  ( elements['rem_when_date'].checked != true );
}

function toggle_reminders() {
  //Reminder Tab disabled
  if ( ! form.rem_when ) {
    return;
  }
  toggle_rem_when();
  makeInvisible ( "reminder_when",true );
  makeInvisible ( "reminder_repeat", true );
  if ( elements['reminderYes'].checked == true ) {
   makeVisible ( "reminder_when", true );
   makeVisible ( "reminder_repeat", true );
  }
}

function toggle_rem_rep() {
 elements['rem_rep_days'].disabled =
 elements['rem_rep_hours'].disabled =
 elements['rem_rep_minutes'].disabled =
 ( elements['rem_rep_count'].value == 0 );
}

function editCats ( evt ) {
  var obj;

  function catWindowClosed () {
  }
  Modalbox.show($('editCatsDiv'), {title: '<?php etranslate('Categories');?>', width: 350, transitions: false, onHide: catWindowClosed, closeString: '<?php etranslate('Cancel');?>' });

  var cat_ids = elements['cat_id'].value;
  var selected_ids = cat_ids.split ( ',' );

<?php
  load_user_categories();
  foreach ( $categories as $catid => $cat ) {
    if ( $catid == 0 || $catid == -1 )
      continue; // Ignore these special cases (0=All, -1=None)
    ?>
    var checkboxId = 'cat_<?php echo $catid;?>';
    obj = document.getElementById ( checkboxId );
    if ( obj ) {
      // Is this selected??
      var sel = false;
      for ( i = 0; i < selected_ids.length; i++ ) {
        if ( selected_ids[i] == <?php echo $catid;?> )
          sel = true;
      }
      obj.checked = sel;
    } else {
      // Note: this happens when an admin edits a user's personal event.
      //alert ( "Could not find '" + checkboxId + "' in DOM" );
    }
  <?php
  }
  ?>

}

function catOkHandler () {
  // Get selected categories
  var catIds = '', catNames = '';
<?php
  foreach ( $categories as $catid => $cat ) {
    if ( $catid == 0 || $catid == -1 )
      continue; // Ignore these special cases (0=All, -1=None)
    ?>
  var checkboxId = 'cat_<?php echo $catid;?>';
  var nameId = 'cat_<?php echo $catid;?>_text';
  obj = document.getElementById ( checkboxId );
  if ( obj ) {
    if ( obj.checked ) {
      if ( catIds.length > 0 ) {
        catIds += ',';
        catNames += ', ';
      }
      catIds += '<?php echo $catid;?>';
      catNames += '<?php echo $cat['cat_name'];?>';
    }
  } else {
    // Note: this can happen when an admin is editing a user's personal
    // event.
    //if ( ! obj )
    //  alert ( "Could not find " + checkboxId );
    //else
    //  alert ( "Could not find " + nameId );
  }
<?php
  }
?>
  $('entry_categories').innerHTML = catNames;
  $('cat_id').value = catIds;
  Modalbox.hide ();
  return true;
}

function displayInValid(myvar)
{
  alert ( "<?php etranslate ( 'You have not entered a valid time of day', true)?>.");
  myvar.select();
  myvar.focus();
}

function isNumeric(sText)
{
   //allow blank values. these will become 0
   if ( sText.length == 0 )
     return sText;
   var validChars = "0123456789";
   var Char;
   for (i = 0; i < sText.length && sText != 99; i++)
   {
      Char = sText.charAt(i);
      if (validChars.indexOf(Char) == -1)
      {
        sText = 99;
      }
   }
   return sText;
}

function completed_handler() {
  if ( form.percent ) {
    //elements['dateselIcon_completed'].disabled =
    elements['completed_year'].disabled =
    elements['completed_month'].disabled =
    elements['completed_day'].disabled =
      ( form.percent.selectedIndex != 10 || form.others_complete.value != 'yes' );
  }
}

function onLoad() {
  if ( ! document.editentryform )
    return false;
  //define these variables here so they are valid
  form = document.editentryform;
  elements = document.editentryform.elements;
  elementlength = document.editentryform.elements.length;

  //initialize byxxxAr Objects
  if ( form.bydayList ) {
    bydayList = form.bydayList.value;
    if ( bydayList.search( /,/ ) > -1 ) {
      bydayList = bydayList.split ( ',' );
      for ( key in bydayList ) {
        if ( key == isNumeric ( key ) )
        bydayAr[bydayList[key]] = bydayList[key];
      }
    } else if ( bydayList.length > 0 ) {
      bydayAr[bydayList] = bydayList;
    }
  }

  if ( form.bymonthdayList ) {
    bymonthdayList = form.bymonthdayList.value;
    if ( bymonthdayList.search( /,/ ) > -1 ) {
      bymonthdayList = bymonthdayList.split ( ',' );
      for ( key in bymonthdayList ) {
        if ( key == isNumeric ( key ) )
          bymonthdayAr[bymonthdayList[key]] = bymonthdayList[key];
      }
    } else if ( bymonthdayList.length > 0 ) {
      bymonthdayAr[bymonthdayList] = bymonthdayList;
    }
  }

  if ( form.bysetposList ) {
    bysetposList = form.bysetposList.value;
    if ( bysetposList.search( /,/ ) > -1 ) {
      bysetposList = bysetposList.split ( ',' );
      for ( key in bysetposList ) {
        if ( key == isNumeric ( key ) )
          bysetposAr[bysetposList[key]] = bysetposList[key];
      }
    } else if ( bysetposList.length > 0 ) {
      bysetposAr[bysetposList] = bysetposList;
    }
  }

  timetype_handler();
  rpttype_handler();
  toggle_until();
  toggle_reminders();
  toggle_rem_rep();
  completed_handler();
}

function selAdd( btn ) {
  with (form)
  {
    with (form.entry_part)
    {
      for (i = 0; i < length; i++)
      {
        if(options[i].selected) {
          with (options[i])
          {
            if(is_unique(value)) {
              form.sel_part.options[form.sel_part.length]  = new Option( text, value );
            }
            options[i].selected = false;
          } //end with options
        }
      } // end for loop
    } // end with islist1
  } // end with document
}

function is_unique ( val ) {
   unique = true;
   var sel = form.sel_part;
   for ( j = 0; j < sel.length; j++ ) {
     if ( sel.options[j].value == val )
       unique = false;
   }
   return unique;
}

function selResource( btn ) {
  with (form)
  {
    with (form.res_part)
    {
      for (r = 0; r < length; r++)
      {
        if(options[r].selected) {
          with (options[r])
          {
            if(is_unique(value)) {
              form.sel_part.options[form.sel_part.length]  = new Option( text, value );
            }
            options[r].selected = false;
          } //end with options
        }
      } // end for loop
    }
  } // end with document
}
function selRemove( btn ) {
   with (form)
   {
      with (form.sel_part)
      {
         for (i = 0; i < length; i++)
         {
            if( options[i].selected ) {
              options[i] = null;
         }
         } // end for loop
     }
   } // end with document
}

function lookupName() {
  var selectid = -1;
  var x = stringLength( form.lookup.value );
    var lower = stringToLowercase(form.lookup.value );
  form.entry_part.selectedIndex = -1;
  form.res_part.selectedIndex = -1;
  if ( form.groups )
    form.groups.selectedIndex = -1;
  //check userlist
  for ( i = 0; i < form.entry_part.length; i++ ) {
    str = form.entry_part.options[i].text;
    if ( stringToLowercase( str.substring( 0,x ) ) == lower ) {
      selectid = i;
    i = form.entry_part.length;
   }
  }
  if ( selectid  > -1) {
    form.entry_part.selectedIndex = selectid;
    return true;
  }
  //check resource list
  for ( i = 0; i < form.res_part.length; i++ ) {
    str = form.res_part.options[i].text;
    if ( stringToLowercase( str.substring( 0,x ) ) == lower ) {
      selectid = i;
    i = form.res_part.length;
   }
  }
  if ( selectid > -1 ) {
    form.res_part.selectedIndex = selectid;
    return true;
  }
  //check groups if enabled
  if ( form.groups ) {
    for ( i = 0; i < form.groups.length; i++ ) {
      str = form.groups.options[i].text;
      if ( stringToLowercase( str.substring( 0,x ) ) == lower ) {
        selectid = i;
      i = form.groups.length;
     }
    }
    if ( selectid > -1) {
      form.groups.selectedIndex = selectid;
      return true;
    }
  }
}

function stringLength(inputString)
{
  return inputString.length;
}
function stringToLowercase(inputString)
{
  return inputString.toLowerCase();
}
