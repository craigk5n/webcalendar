<?php
 global $GROUPS_ENABLED,$WORK_DAY_START_HOUR,$WORK_DAY_END_HOUR, $user;
?><script type="text/javascript">
<!-- <![CDATA[
// do a little form verifying
function validate_and_submit () {
  if ( document.edittaskform.name.value == "" ) {
    document.edittaskform.name.select ();
<?php
    if ( empty ( $GLOBALS['EVENT_EDIT_TABS'] ) ||
      $GLOBALS['EVENT_EDIT_TABS'] == 'Y' ) { ?>
    showTab ( "details" );
<?php } ?>
    document.edittaskform.name.focus ();
    alert ( "<?php etranslate("You have not entered a Brief Description", true)?>." );
    return false;
  }
  // Leading zeros seem to confuse parseInt()
  if ( document.edittaskform.cal_hour.value.charAt ( 0 ) == '0' )
    document.edittaskform.cal_hour.value = document.edittaskform.cal_hour.value.substring ( 1, 2 );
  if ( 1 ) {
    h = parseInt ( document.edittaskform.cal_hour.value );
    m = parseInt ( document.edittaskform.cal_minute.value );
<?php if ($GLOBALS["TIME_FORMAT"] == "12") { ?>
    if ( document.edittaskform.ampm[1].checked ) {
      // pm
      if ( h < 12 )
        h += 12;
    } else {
      // am
      if ( h == 12 )
        h = 0;
    }
<?php } ?>
    if ( h >= 24 || h < 0 ) {
<?php
      if ( empty ( $GLOBALS['EVENT_EDIT_TABS'] ) ||
        $GLOBALS['EVENT_EDIT_TABS'] == 'Y' ) { ?>
        showTab ( "details" );
<?php } ?>
      alert ( "<?php etranslate ("You have not entered a valid time of day", true)?>." );
      document.edittaskform.cal_hour.select ();
      document.edittaskform.cal_hour.focus ();
      return false;
    }
    if ( m > 59 || m < 0 ) {
<?php
      if ( empty ( $GLOBALS['EVENT_EDIT_TABS'] ) ||
        $GLOBALS['EVENT_EDIT_TABS'] == 'Y' ) { ?>
        showTab ( "details" );
<?php } ?>
      alert ( "<?php etranslate ("You have not entered a valid time of day", true)?>." );
      document.edittaskform.cal_minute.select ();
      document.edittaskform.cal_minute.focus ();
      return false;
    }
    // Ask for confirmation for time of day if it is before the user's
    // preference for work hours.
    <?php if ($GLOBALS["TIME_FORMAT"] == "24") {
      echo "if ( h < $WORK_DAY_START_HOUR  ) {";
    }  else {
      echo "if ( h < $WORK_DAY_START_HOUR && document.edittaskform.ampm[0].checked ) {";
    }
    ?>
    if ( ! confirm ( "<?php etranslate ("The time you have entered begins before your preferred work hours.  Is this correct?", true)?> "))
      return false;
  }
  }
  // is there really a change?
  changed = false;
  form=document.edittaskform;
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
    form.task_changed.value = "yes";
  }
//Add code to make HTMLArea code stick in TEXTAREA
 if (typeof editor != "undefined") editor._textArea.value = editor.getHTML();

 //Check if Event start date is valid
 var d = document.edittaskform.start_day.selectedIndex;
  var vald = document.edittaskform.start_day.options[d].value;
  var m = document.edittaskform.start_month.selectedIndex;
  var valm = document.edittaskform.start_month.options[m].value;
  var y = document.edittaskform.start_year.selectedIndex;
  var valy = document.edittaskform.start_year.options[y].value;
  var c = new Date(valy,valm -1,vald);
 if ( c.getDate() != vald ) {
   alert ("<?php etranslate ("Invalid Event Date", true)?>.");
  document.edittaskform.start_day.focus ();
   return false;
 }

 //Check if Event due date is valid
 var d = document.edittaskform.due_day.selectedIndex;
  var vald = document.edittaskform.due_day.options[d].value;
  var m = document.edittaskform.due_month.selectedIndex;
  var valm = document.edittaskform.due_month.options[m].value;
  var y = document.edittaskform.due_year.selectedIndex;
  var valy = document.edittaskform.due_year.options[y].value;
  var c = new Date(valy,valm -1,vald);
 if ( c.getDate() != vald ) {
   alert ("<?php etranslate ("Invalid Event Date", true)?>.");
  document.edittaskform.due_day.focus ();
   return false;
 } 
  document.edittaskform.submit ();
  return true;
}

function selectDate (  day, month, year, current, evt ) {
  // get currently selected day/month/year
  monthobj = eval ( 'document.edittaskform.' + month );
  curmonth = monthobj.options[monthobj.selectedIndex].value;
  yearobj = eval ( 'document.edittaskform.' + year );
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
  url = "datesel.php?form=edittaskform&fday=" + day +
    "&fmonth=" + month + "&fyear=" + year + "&date=" + date;
  var colorWindow = window.open(url,"DateSelection","width=300,height=200,"  + MyPosition);
}

<?php if ( $GROUPS_ENABLED == "Y" ) { 
?>function selectUsers () {
  // find id of user selection object
  var listid = 0;
  for ( i = 0; i < document.edittaskform.elements.length; i++ ) {
    if ( document.edittaskform.elements[i].name == "participants[]" )
      listid = i;
  }
  url = "usersel.php?form=edittaskform&listid=" + listid + "&users=";
  // add currently selected users
  for ( i = 0, j = 0; i < document.edittaskform.elements[listid].length; i++ ) {
    if ( document.edittaskform.elements[listid].options[i].selected ) {
      if ( j != 0 )
        url += ",";
      j++;
      url += document.edittaskform.elements[listid].options[i].value;
    }
  }
  //alert ( "URL: " + url );
  // open window
  window.open ( url, "UserSelection",
    "width=500,height=500,resizable=yes,scrollbars=yes" );
}
<?php } ?>

function rpttype_handler () {
  var i = document.edittaskform.rpttype.selectedIndex;
  var val = document.edittaskform.rpttype.options[i].text;
  //alert ( "val " + i + " = " + val );
  //i == 0 when event does not repeat
  if ( i != 0 ) {
    // none (not repeating)
    makeVisible ( "rptenddate" );
    makeVisible ( "rptfreq" );
    if ( i == 2 ) {
      makeVisible ( "rptday" );
    } else {
      makeInvisible ( "rptday" );
    }
  } else {
    // Timed Event
    makeInvisible ( "rptenddate" );
    makeInvisible ( "rptfreq" );
    makeInvisible ( "rptday" );
  }
}

<?php //see the showTab function in includes/js/visible.php for common code shared by all pages
 //using the tabbed GUI.
?>
var tabs = new Array();
tabs[0] = "details";
tabs[1] = "participants";
tabs[2] = "pete";

var sch_win;

function getElementId ( elename ) {
  var listid = 0;
  for ( i = 0; i < document.edittaskform.elements.length; i++ ) {
    if ( document.edittaskform.elements[i].name == elename )
      listid = i;
  }
  return listid;
}

// Show Availability for the first selection
function showSchedule () {
  //var agent=navigator.userAgent.toLowerCase();
  //var agent_isIE=(agent.indexOf("msie") > -1);
  var myForm = document.edittaskform;
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
    alert("<?php etranslate("Please add a participant", true)?>" );
    return false;
  }
  var features = 'width='+ w +',height='+ h +',resizable=yes,scrollbars=no';
  var url = 'availability.php?users=' + users + 
           '&year='  + myForm.start_year.value + 
           '&month=' + myForm.start_month.value + 
           '&day='   + myForm.start_day.options[myForm.start_day.selectedIndex].text;

  if (sch_win != null && !sch_win.closed) {
     h = h + 30;
     sch_win.location.replace( url );
     sch_win.resizeTo(w,h);
  } else {
     sch_win = window.open( url, "showSchedule", features );
  }
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
  var cat_ids = document.edittaskform.elements['cat_id'].value;
  var user = '<?php echo $user ?>';
  url = "catsel.php?form=edittaskform&cats=" + cat_ids;
  if (user ) {
  url += "&user=" + user;
 }
  var catWindow = window.open(url,"EditCat","width=365,height=200,"  + MyPosition);
}
//]]> -->
</script>