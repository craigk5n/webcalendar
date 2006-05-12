<?php /* $Id$  */
 global $GROUPS_ENABLED,$WORK_DAY_START_HOUR,$WORK_DAY_END_HOUR;
 $user = $arinc[3];
?>
// do a little form verifying
function validate_and_submit () {
  if ( document.editentryform.name.value == "" ) {
    document.editentryform.name.select ();
<?php
    if ( empty ( $GLOBALS['EVENT_EDIT_TABS'] ) ||
      $GLOBALS['EVENT_EDIT_TABS'] == 'Y' ) { ?>
    showTab ( "details" );
<?php } ?>
    document.editentryform.name.focus ();
    alert ( "<?php etranslate( 'You have not entered a Brief Description', true)?>.");
    return false;
  }
  if ( document.editentryform.timetype && 
    document.editentryform.timetype.selectedIndex == 1 ) {
    h = parseInt (isNumeric( document.editentryform.entry_hour.value ));
    m = parseInt (isNumeric( document.editentryform.entry_minute.value ));  

    // Ask for confirmation for time of day if it is before the user's
    // preference for work hours.
    <?php if ($GLOBALS["TIME_FORMAT"] == "24") {
      echo "if ( h < $WORK_DAY_START_HOUR  ) {";
    }  else {
      echo "if ( h < $WORK_DAY_START_HOUR && document.editentryform.entry_ampm[0].checked ) {";
    }
    ?>
    if ( ! confirm ( "<?php etranslate ( 'The time you have entered begins before your preferred work hours.  Is this correct?', true)?> "))
      return false;
   }
  }
 
  // is there really a change?
  changed = false;
  form=document.editentryform;
  for ( i = 0; i < form.elements.length; i++ ) {
    field = form.elements[i];
    switch ( field.type ) {
      case "radio":
      case "checkbox":
        if ( field.checked != field.defaultChecked )
          changed = true;
        break;
      case "text":
//      case "textarea":
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
 var d = document.editentryform.day.selectedIndex;
  var vald = document.editentryform.day.options[d].value;
  var m = document.editentryform.month.selectedIndex;
  var valm = document.editentryform.month.options[m].value;
  var y = document.editentryform.year.selectedIndex;
  var valy = document.editentryform.year.options[y].value;
  var c = new Date(valy,valm -1,vald);
 if ( c.getDate() != vald ) {
   alert ("<?php etranslate ( 'Invalid Event Date', true)?>.");
  document.editentryform.day.focus ();
   return false;
 }
 //Select all Repeat Exception Dates
 for ( i = 0; i < document.editentryform.elements.length; i++ ) {
  if ( document.editentryform.elements[i].name == "exceptions[]" )
      exceptionid = i;
 }
 //Repeat Tab enabled
 if ( document.editentryform.rpttype ) {
   for ( i = 0; i < document.editentryform.elements[exceptionid].length; i++ ) {
     document.editentryform.elements[exceptionid].options[i].selected = true;
   }
 }
 
 if ( document.editentryform.due_day ) {
   //Check if Event due date is valid
   var d = document.editentryform.due_day.selectedIndex;
   var vald = document.editentryform.due_day.options[d].value;
   var m = document.editentryform.due_month.selectedIndex;
   var valm = document.editentryform.due_month.options[m].value;
   var y = document.editentryform.due_year.selectedIndex;
   var valy = document.editentryform.due_year.options[y].value;
   var c = new Date(valy,valm -1,vald);
   if ( c.getDate() != vald ) {
     alert ("<?php etranslate ( 'Invalid Event Date', true)?>.");
     document.editentryform.due_day.focus ();
     return false;
   }
 } 
 
 document.editentryform.submit ();
 return true;
}

function selectDate (  day, month, year, current, evt ) {
  // get currently selected day/month/year
  monthobj = eval ( 'document.editentryform.' + month );
  curmonth = monthobj.options[monthobj.selectedIndex].value;
  yearobj = eval ( 'document.editentryform.' + year );
  curyear = yearobj.options[yearobj.selectedIndex].value;
  date = curyear;

  if (document.getElementById) {
    mX = evt.clientX   + 40;
    mY = evt.clientY  + 120;
  }
  else {
    mX = evt.pageX + 40;
    mY = evt.pageY +130;
  }
 var MyPosition = 'scrollbars=no,toolbar=no,left=' + mX + ',top=' + mY + ',screenx=' + mX + ',screeny=' + mY ;
  if ( curmonth < 10 )
    date += "0";
  date += curmonth;
  date += "01";
  url = "datesel.php?form=editentryform&fday=" + day +
    "&fmonth=" + month + "&fyear=" + year + "&date=" + date;
  var colorWindow = window.open(url,"DateSelection","width=300,height=200,"  + MyPosition);
}

<?php if ( $GROUPS_ENABLED == 'Y' ) { 
?>function selectUsers () {
  var user = "<?php echo $user ?>";
  // find id of user selection object
  var listid = 0;
  for ( i = 0; i < document.editentryform.elements.length; i++ ) {
    if ( document.editentryform.elements[i].name == "participants[]" )
      listid = i;
  }
  url = "usersel.php?form=editentryform&listid=" + listid + "&user=" + user + "&users=";
  // add currently selected users
  for ( i = 0, j = 0; i < document.editentryform.elements[listid].length; i++ ) {
    if ( document.editentryform.elements[listid].options[i].selected ) {
      if ( j != 0 )
        url += ",";
      j++;
      url += document.editentryform.elements[listid].options[i].value;
    }
  }
  //alert ( "URL: " + url );
  // open window
  window.open ( url, "UserSelection",
    "width=500,height=500,resizable=yes,scrollbars=yes" );
}
<?php }
// This function is called when the event type combo box 
 // is changed. If the user selectes "untimed event" or "all day event",
 // the times & duration fields are hidden.
 // If they change their mind & switch it back, the original 
 // values are restored for them
?>function timetype_handler () {
  if ( ! document.editentryform.timetype )
   return true;
  var i = document.editentryform.timetype.selectedIndex;
  var val = document.editentryform.timetype.options[i].text;
  if ( i != 1 ) {
    // Untimed/All Day
    makeInvisible ( "timeentrystart" );
  if ( document.editentryform.timezonenotice ) {
      makeInvisible ( "timezonenotice" );
  }
    if ( document.editentryform.duration_h ) {
      makeInvisible ( "timeentryduration" );
    } else {
      makeInvisible ( "timeentryend" );
    }
  } else {
    // Timed Event
    makeVisible ( "timeentrystart" );
  if ( document.editentryform.timezonenotice ) {
      makeVisible ( "timezonenotice" );
  }

    if ( document.editentryform.duration_h ) {
      makeVisible ( "timeentryduration" );
    } else {
      makeVisible ( "timeentryend" );
    }
  }
}

function rpttype_handler (  ) {
  //Repeat Tab disabled
  if ( ! document.editentryform.rpttype ) {
    return;
  }
  var expertid = getElementId('rptmode');
  var expert = document.editentryform.elements[expertid].checked;
  var i = document.editentryform.rpttype.selectedIndex;
  var val = document.editentryform.rpttype.options[i].text;
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
      makeVisible ( "rptbydayextended", true );
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

function rpttype_weekly () {
  var i = document.editentryform.rpttype.selectedIndex;
  var val = document.editentryform.rpttype.options[i].text;
 if ( val == "Weekly" ) {
   var rpt_days = new Array("SU","MO","TU","WE","TH","FR","SA");
   //Get Event Date values
   var d = document.editentryform.day.selectedIndex;
   var vald = document.editentryform.day.options[d].value;
   var m = document.editentryform.month.selectedIndex;
   var valm = document.editentryform.month.options[m].value -1;
   var y = document.editentryform.year.selectedIndex;
   var valy = document.editentryform.year.options[y].value;
   var c = new Date(valy,valm,vald);
   var dayOfWeek = c.getDay();
   var rpt_day = rpt_days[dayOfWeek];
   document.editentryform.elements[rpt_day].checked = true; 
 }
}
<?php //see the showTab function in includes/js/visible.php for common code shared by all pages
 //using the tabbed GUI.
?>
var tabs = new Array();
tabs[0] = "details";
tabs[1] = "participants";
tabs[2] = "pete";
tabs[3] = "reminder";

var sch_win;

function getElementId ( elename ) {
  var listid = 0;
  for ( i = 0; i < document.editentryform.elements.length; i++ ) {
    if ( document.editentryform.elements[i].name == elename )
      listid = i;
  }
  return listid;
}

// Show Availability for the first selection
function showSchedule () {
  //var agent=navigator.userAgent.toLowerCase();
  //var agent_isIE=(agent.indexOf("msie") > -1);
  var myForm = document.editentryform;
  var userlist = myForm.elements[getElementId('participants[]')];
  var delim = '';
  var users = '';
  var cols = <?php echo $WORK_DAY_END_HOUR - $WORK_DAY_START_HOUR ?>;
  //var w = 140 + ( cols * 31 );
  var w = 760;
  var h = 180;
  for ( i = 0; i < userlist.length; i++ ) {
    if (userlist.options[i].selected) {
      users += delim + userlist.options[i].value;
      delim = ',';
      h += 18;
    }
  }
  if (users == '') {
    alert("<?php etranslate( 'Please add a participant', true)?>" );
    return false;
  }
  var features = 'width='+ w +',height='+ h +',resizable=yes,scrollbars=no';
  var url = 'availability.php?users=' + users + 
           '&form='  + 'editentryform' +     
           '&year='  + myForm.year.value + 
           '&month=' + myForm.month.value + 
           '&day='   + myForm.day.options[myForm.day.selectedIndex].text;

  if (sch_win != null && !sch_win.closed) {
     h = h + 30;
     sch_win.location.replace( url );
     sch_win.resizeTo(w,h);
  } else {
     sch_win = window.open( url, "showSchedule", features );
  }
}

function add_exception (which) {
 var sign = "-";
 if (which ) {
    sign = "+";
 }
 var d = document.editentryform.except_day.selectedIndex;
 var vald = document.editentryform.except_day.options[d].value;
 var m = document.editentryform.except_month.selectedIndex;
 var valm = document.editentryform.except_month.options[m].value;
 var y = document.editentryform.except_year.selectedIndex;
 var valy = document.editentryform.except_year.options[y].value;
 var c = new Date(valy,valm -1,vald);
 if ( c.getDate() != vald ) {
   alert ("<?php etranslate( 'Invalid Date',true ) ?>");
   return false;
 }
 //alert ( c.getFullYear() + " "  + c.getMonth() + " " + c.getDate());
 var exceptDate = String((c.getFullYear() * 100 + c.getMonth() +1) * 100 + c.getDate());
 var isUnique = true;
 //Test to see if this date is already in the list
  with (document.editentryform)
   { 
      with (document.editentryform.elements['exceptions[]'])
      {
         for (i = 0; i < length; i++)
         {
            if(options[i].text ==  "-" + exceptDate || options[i].text ==  "+" + exceptDate){
         isUnique = false;
         } 
     }
   }
  } 
 if ( isUnique ) {
    document.editentryform.elements['exceptions[]'].options[document.editentryform.elements['exceptions[]'].length]  = new Option( sign + exceptDate, sign + exceptDate );
    makeVisible ( "select_exceptions" );
    makeInvisible ( "select_exceptions_not" );
 }
}
function del_selected () {
   with (document.editentryform)
   { 
      with (document.editentryform.elements['exceptions[]'])
      {
         for (i = 0; i < length; i++)
         {
            if(options[i].selected){
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


function toggle_byday(ele){
  if (ele.value.length > 4 ) {
    //blank
    ele.value = ele.id;
  } else if (ele.value == ele.id) {
    //positive value
    ele.value =  (parseInt(ele.id.substr(0,1)) -6 ) +  ele.id.substr(1,2);
  } else if (ele.value ==  (parseInt(ele.id.substr(0,1)) -6 ) +  ele.id.substr(1,2)) {
    //negative value
  ele.value = "        ";
  }
  for ( i = 0; i < document.editentryform.elements.length; i++ ) {
    if ( document.editentryform.elements[i].name == "bydayext2[]" ){
      if ( document.editentryform.elements[i].id == ele.id ) 
      document.editentryform.elements[i].value = ele. value;
    }
  }
}

function toggle_bymonthday(ele){
  if (ele.value .length > 3) {
    //blank
  ele.value = ele.id.substr(10);
  } else if (ele.value == ele.id.substr(10)) {
    //positive value
  ele.value =  parseInt(ele.id.substr(10)) -32;
  } else if (ele.value ==  (parseInt(ele.id.substr(10)) -32 )) {
    //negative value
  ele.value = "     ";
  }
  for ( i = 0; i < document.editentryform.elements.length; i++ ) {
    if ( document.editentryform.elements[i].name == "bymonthday[]" ){
      if ( document.editentryform.elements[i].id == ele.id ) 
      document.editentryform.elements[i].value = ele. value;
    }
  }
}

function toggle_bysetpos(ele){
  //alert(ele.id.substr(10)); 
  if (ele.value .length > 3) {
    //blank
  ele.value = ele.id.substr(8);
  } else if (ele.value == ele.id.substr(8)) {
    //positive value
  ele.value =  parseInt(ele.id.substr(8)) -32;
  } else if (ele.value ==  (parseInt(ele.id.substr(8)) -32 )) {
    //negative value
  ele.value = "     ";
  }
  for ( i = 0; i < document.editentryform.elements.length; i++ ) {
    if ( document.editentryform.elements[i].name == "bysetpos2[]" ){
      if ( document.editentryform.elements[i].id == ele.id ) 
      document.editentryform.elements[i].value = ele. value;
    }
  }
}

function toggle_until() {
  //Repeat Tab disabled
  if ( ! document.editentryform.rpttype ) {
    return;
  }
 for ( i = 0; i < document.editentryform.elements.length; i++ ) {
    if ( document.editentryform.elements[i].name == "rpt_day" )
      rpt_dayid = i;
    if ( document.editentryform.elements[i].name == "rpt_month" )
      rpt_monthid = i;
    if ( document.editentryform.elements[i].name == "rpt_year" )
      rpt_yearid = i;
    if ( document.editentryform.elements[i].name == "rpt_btn" )
      rpt_btnid = i;
 }
 document.editentryform.elements[rpt_dayid].disabled = true;
 document.editentryform.elements[rpt_monthid].disabled = true;
 document.editentryform.elements[rpt_yearid].disabled = true;
 document.editentryform.elements[rpt_btnid].disabled = true;
 document.editentryform.elements['rpt_hour'].disabled = true;
 document.editentryform.elements['rpt_minute'].disabled = true;
 if ( document.editentryform.elements['rpt_ampm'] ) {
   document.getElementById('rpt_ampmA').disabled = 'disabled';
   document.getElementById('rpt_ampmP').disabled = 'disabled';
 }
 document.editentryform.elements['rpt_count'].disabled = true;
 if ( document.editentryform.rpt_untilu.checked ) { //use until date
   document.editentryform.elements[rpt_dayid].disabled = false;
   document.editentryform.elements[rpt_monthid].disabled = false;
   document.editentryform.elements[rpt_yearid].disabled = false;
   document.editentryform.elements[rpt_btnid].disabled = false;
   document.editentryform.elements['rpt_hour'].disabled = false;
   document.editentryform.elements['rpt_minute'].disabled = false;
   if ( document.editentryform.elements['rpt_ampm'] ) {
     document.getElementById('rpt_ampmA').disabled = false;
     document.getElementById('rpt_ampmP').disabled = false;
   }
 } else if ( document.editentryform.rpt_untilc.checked ) { //use count
   document.editentryform.elements['rpt_count'].disabled = false; 
 }
}

function toggle_rem_when() {
  //Reminder Tab disabled
  if ( ! document.editentryform.rem_when ) {
    return;
  }
 if ( document.editentryform.elements['rem_when_date'].checked == true ) {
   document.editentryform.elements['reminder_day'].disabled = false;
   document.editentryform.elements['reminder_month'].disabled = false;
   document.editentryform.elements['reminder_year'].disabled = false;
   document.editentryform.elements['reminder_btn'].disabled = false;
   document.editentryform.elements['reminder_hour'].disabled = false;
   document.editentryform.elements['reminder_minute'].disabled = false;
   if ( document.editentryform.elements['reminder_ampm'] ) {
   document.getElementById('reminder_ampmA').disabled = false;
   document.getElementById('reminder_ampmP').disabled = false;
   }
   document.editentryform.elements['rem_days'].disabled = true;
   document.editentryform.elements['rem_hours'].disabled = true;
   document.editentryform.elements['rem_minutes'].disabled = true;
   document.editentryform.elements['rem_beforeY'].disabled = true;
   document.editentryform.elements['rem_relatedS'].disabled = true;
   document.editentryform.elements['rem_beforeN'].disabled = true;
   document.editentryform.elements['rem_relatedE'].disabled = true;
 } else if ( document.editentryform.elements['rem_when_offset'].checked == true ) { 
   document.editentryform.elements['reminder_day'].disabled = true;
   document.editentryform.elements['reminder_month'].disabled = true;
   document.editentryform.elements['reminder_year'].disabled = true;
   document.editentryform.elements['reminder_btn'].disabled = true;
   document.editentryform.elements['reminder_hour'].disabled = true;
   document.editentryform.elements['reminder_minute'].disabled = true;
   if ( document.editentryform.elements['reminder_ampm'] ) {
   document.getElementById('reminder_ampmA').disabled = 'disabled';
   document.getElementById('reminder_ampmP').disabled = 'disabled';
   }
   document.editentryform.elements['rem_days'].disabled = false;
   document.editentryform.elements['rem_hours'].disabled = false;
   document.editentryform.elements['rem_minutes'].disabled = false;
   document.editentryform.elements['rem_beforeY'].disabled = false;
   document.editentryform.elements['rem_relatedS'].disabled = false;
   document.editentryform.elements['rem_beforeN'].disabled = false;
   document.editentryform.elements['rem_relatedE'].disabled = false;
 }
}

function toggle_reminders() {
  //Reminder Tab disabled
  if ( ! document.editentryform.rem_when ) {
    return;
  }
  toggle_rem_when();
  makeInvisible ( "reminder_when" );
  makeInvisible ( "reminder_repeat" );
  if ( document.editentryform.elements['reminderYes'].checked == true ) { 
   makeVisible ( "reminder_when", true ); 
   makeVisible ( "reminder_repeat", true );
  }
}

function toggle_rem_rep(){
 var cnt = document.editentryform.elements['rem_rep_count'].value;
 document.editentryform.elements['rem_rep_days'].disabled = (cnt == 0 );
 document.editentryform.elements['rem_rep_hours'].disabled = (cnt == 0 );
 document.editentryform.elements['rem_rep_minutes'].disabled = (cnt == 0 );
}


function editCats (  evt ) {
  if (document.getElementById) {
    mX = evt.clientX   -160;
    mY = evt.clientY  + 150;
  }
  else {
    mX = evt.pageX  -160;
    mY = evt.pageY + 150;
  }
  var MyPosition = 'scrollbars=no,toolbar=no,left=' + mX + ',top=' + mY + ',screenx=' + mX + ',screeny=' + mY ;
  var cat_ids = document.editentryform.elements['cat_id'].value;
  var user = '<?php echo $user ?>';
  url = "catsel.php?form=editentryform&cats=" + cat_ids;
  if (user ) {
  url += "&user=" + user;
 }
  var catWindow = window.open(url,"EditCat","width=385,height=250,"  + MyPosition);
}

function displayInValid(myvar)
{
  alert ( "<?php etranslate ( 'You have not entered a valid time of day', true)?>.");
  myvar.select ();
  myvar.focus ();
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

function completed_handler () {
  if ( document.editentryform.percent ) {
    var mypercent = document.editentryform.percent.selectedIndex;
    var others_complete = document.editentryform.others_complete.value;
    if ( mypercent == 10 && others_complete == 'yes' ) {
      document.editentryform.elements['completed_day'].disabled = false;
      document.editentryform.elements['completed_month'].disabled = false;
      document.editentryform.elements['completed_year'].disabled = false;
      document.editentryform.elements['completed_btn'].disabled = false;
    } else {
      document.editentryform.elements['completed_day'].disabled = true;
      document.editentryform.elements['completed_month'].disabled = true;
      document.editentryform.elements['completed_year'].disabled = true;
      document.editentryform.elements['completed_btn'].disabled = true;
    }
  }
}


function onLoad () {
  timetype_handler();
  rpttype_handler();
  toggle_until();
  toggle_reminders();
  toggle_rem_rep();
  completed_handler();
}

