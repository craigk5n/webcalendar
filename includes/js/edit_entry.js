/* $Id$  */

initPhpVars( 'edit_entry' );
initGroupList();
var groups;
var bydayAr = new Object();
var bymonthdayAr = new Object();
var bysetposAr = new Object();
var bydayLabels = new Array("SU","MO","TU","WE","TH","FR","SA");

  
// do a little form verifying
function validate_and_submit () {
  if ( $('entry_brief') == "" ) {
    $('entry_brief').select ();
    if ( EVENT_EDIT_TABS == 'Y' )
      showTab ( "details" );
    $('entry_brief').focus ();
    alert ( blankSummary );
    return false;
  }
  if ( $('timetype') && 
    $('timetype').selectedIndex == 1 ) {
    h = parseInt (isNumeric( $('entry_hour').value ));
    m = parseInt (isNumeric( $('entry_minute').value ));  

    // Ask for confirmation for time of day if it is before the user's
    // preference for work hours.
    if ( h < WORK_DAY_START_HOUR && ( TIME_FORMAT == "24" || $('entry_ampmA').checked ) ) 
        if ( ! confirm( startTime ) )
          return false;
  }
 
  // is there really a change?
  changed = false;
  for ( i = 0,len = form.elements.length;i < len; i++ ) {
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
    $('entry_changed').value = "Y";
  }
 
 //Repeat Tab enabled, Select all of them
 if ( $('rpt_type') ) {
   for ( i = 0; i < $('select_exceptions').length; i++ ) {
     $('select_exceptions').options[i].selected = true;
   }
 }
 

 //set byxxxList values for submission
 var bydayStr = '';
 for ( bydayKey in bydayAr ) {
   if ( bydayAr[bydayKey].length < 8 )
     bydayStr = bydayStr + ',' + bydayAr[bydayKey];
 }
 if ( bydayStr.length > 0 )
   $('bydayList').value = bydayStr.substr(1);

 //set bymonthday values for submission
 var bymonthdayStr = '';
 for ( bymonthdayKey in bymonthdayAr ) {
   if ( bymonthdayAr[bymonthdayKey].length < 4 )
     bymonthdayStr = bymonthdayStr + ',' + bymonthdayAr[bymonthdayKey];
 }
 if ( bymonthdayStr.length > 0 )
   $('bymonthdayList').value = bymonthdayStr.substr(1);

 //set bysetpos values for submission
 var bysetposStr = '';
 for ( bysetposKey in bysetposAr ) {
   if ( bysetposAr[bysetposKey].length < 8 )
     bysetposStr = bysetposStr + ',' + bysetposAr[bysetposKey];
 }
 if ( bysetposStr.length > 0 )
   $('bysetposList').value = bysetposStr.substr(1);

 form.submit ();
 return true;
}

// This function is called when the event type combo box 
 // is changed. If the user selectes "untimed event" or "all day event",
 // the times & duration fields are hidden.
function timetype_handler () {
  if ( ! $('timetype') )
   return true;
  var i = $('timetype').selectedIndex;
  $('timeentrystart').showIf( i == 1 );
  if ( $('timezonenotice') )
    $('timezonenotice').showIf( i == 1 );
  if ( $('duration_h') )
    $('timeentryduration').showIf( i == 1);
  else
    $('timeentryend').showIf( i == 1);
}

function rpttype_handler (  ) {
  //Repeat Tab disabled
  if ( ! $('rpt_type') ) {
    return;
  }
  var expert = ( $('rpt_mode').checked);
  var i = $('rpt_type').selectedIndex;
  var val = $('rpt_type').options[i].text;
  //alert ( "val " + i + " = " + val );
  //i == 0 none
  //i == 1 daily 
  //i == 2 weekly
  //i == 3,4,5 monthlyByDay, monthlyByDate, monthlyBySetPos
  //i == 6 yearly
  //i == 7 manual  Use only Exclusions/Inclusions
  $('rpt_mode_lbl').showIf(i > 0 && i < 7, false);
  $('rptenddate1').showIf(i > 0 && i < 7);
  $('rptenddate2').showIf(i > 0 && i < 7);
  $('rptenddate3').showIf(i > 0 && i < 7);
  $('rptenddate3').showIf(i > 0 && i < 7);
  $('rptfreq').showIf(i > 0 && i < 7);
  $('rptexceptions').showIf(i > 0);
  $('weekdays_only').showIf(i == 1 && ! expert);
  $('rptwkst').showIf((i == 2 && expert) || i == 3 || i == 6);
  $('rptbymonth').showIf(expert);
  $('rptbydayln').showIf(expert && ( i == 3 || i == 6 ));
  $('rptbydayln2').showIf(expert && ( i == 3 || i == 6 ));
  $('rptbydayln3').showIf(expert && ( i == 3 || i == 6 ));
  $('rptbydayln4').showIf(expert && ( i == 3 || i == 6 ));
  $('rptbydayln1').showIf(expert && i == 3);
  $('rptbydayextended').showIf(expert || i == 2 || ( expert && i == 4 ));
  $('rptbymonthdayextended').showIf(expert && ( i == 4 || i == 6 ));
  $('rptbysetpos').showIf(i == 5);
  $('rptbyweekno').showIf(expert && i == 6);
  $('rptbyyearday').showIf(expert && i == 6);
}

function rpttype_weekly () {
  var i = $('rpt_type').selectedIndex;
  var val = $('rpt_type').options[i].text;
 if ( val == "Weekly" ) {
	cdate = parseDate ( $('entry_date'));
	c=new Date();
	c.setDate(cdate[0]);
	c.setMonth(cdate[1]);
	c.setFullYear(cdate[2]);
    dayOfWeek = c.getDay();
    rpt_day = bydayLabels[dayOfWeek];
    $(rpt_day).checked = true; 
 }
}

var tabs = new Array();
tabs[0] = "details";
tabs[1] = "participants";
tabs[2] = "pete";
tabs[3] = "reminder";

var sch_win;

// Show Availability for the first selection
function showSchedule () {
  var userlist = $('selectedPart[]');
  var delim = '';
  var users = '';
  var cols = WORK_DAY_END_HOUR - WORK_DAY_START_HOUR;
  var w = 760;
  var h = 180;
  for ( i = 0,ulen = userlist.length; i < ulen; i++ ) {
    if (userlist.options[i].selected) {
      users += delim + userlist.options[i].value;
      delim = ',';
      h += 18;
    }
  }
  if (users == '') {
    alert( noPart );
    return false;
  }
  var month = $('entry_month').toString();
  month = ( month.length == 1 ? '0' : '' ) + month;
  var day = $('entry_day').toString();
  day = ( day.length == 1 ? '0' : '' ) + day;  
  var features = 'width='+ w +',height='+ h +',resizable=yes,scrollbars=yes';
  var url = 'availability.php?users=' + users + 
  '&date=' + $('entry_year') + month + day;

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

 var exceptDate = $('except_date').value;
 var isUnique = true;
 //Test to see if this date is already in the list
  with (form)
   { 
      with ($('select_exceptions'))
      {
         for (i = 0; i < length; i++)
         {
            if(options[i].text ==  "-" + exceptDate || 
						  options[i].text ==  "+" + exceptDate){
              isUnique = false;
           } 
           if(options[i].text.length ==  1 ) {
              options[i].selected= true;
              del_selected ();
           } 
        }
     }
  } 
 if ( isUnique ) {
    $('select_exceptions').options[$('select_exceptions').length]  = new Option( sign + exceptDate, sign + exceptDate );
    $('select_exceptions').show(false);
 }
}

function del_selected () {
   with (form)
   { 
      with ($('select_exceptions'))
      {
         for (i = 0; i < length; i++)
         {
            if(options[i].selected){
         options[i] = null;
         } 
         } // end for loop
     if ( ! length ) {
       $('select_exceptions').hide(false);
     }
     }
   } // end with document
}


function toggle_byday(ele){
  var bydaytext = bydayTrans[ele.id.substr(2,1)];
  var bydayVal = bydayLabels[ele.id.substr(2,1)];
  var tmp = '';
  if (ele.value.length > 4 ) {
    //blank
    ele.value = ele.id.substr(1,1) + bydaytext;
    tmp = ele.id.substr(1,1) + bydayVal;
  } else if (ele.value == ele.id.substr(1,1) + bydaytext) {
    //positive value
    ele.value =  (parseInt(ele.id.substr(1,1)) -6 ) +  bydaytext;
    tmp = (parseInt(ele.id.substr(1,1)) -6 ) +  bydayVal;
  } else if (ele.value ==  (parseInt(ele.id.substr(1,1)) -6 ) +  bydaytext) {
    //negative value
  ele.value = "        ";
  tmp = '';
  }
  bydayAr[ele.id.substr(1)] = tmp;
}

function toggle_bymonthday(ele){
  var tmp = '';
  if (ele.value .length > 3) {
    //blank
  ele.value = tmp = ele.id.substr(10);
  } else if (ele.value == ele.id.substr(10)) {
    //positive value
  ele.value =  tmp = parseInt(ele.id.substr(10)) -32;
  } else if (ele.value ==  (parseInt(ele.id.substr(10)) -32 )) {
    //negative value
  ele.value = "     ";
  tmp = '';
  }
  bymonthdayAr[ele.id] = tmp;
}

function toggle_bysetpos(ele){
  var tmp = '';
  if (ele.value.length > 3) {
    //blank
    ele.value = tmp = ele.id.substr(1);
  } else if (ele.value == ele.id.substr(1)) {
    //positive value
    ele.value =  tmp = parseInt(ele.id.substr(1)) -32;
  } else if (ele.value ==  (parseInt(ele.id.substr(1)) -32 )) {
    //negative value
    ele.value = "    ";
    tmp = '';
  }
  bysetposAr[ele.id.substr(1)] = tmp;
}

function toggle_until() {
  //Repeat Tab disabled
  if ( ! $('rpt_type') ) {
    return;
  }
 //use date
  $('rpt_end_date').disabled = ( $('rpt_untilu').checked != true );

 //use count
 $('rpt_count').disabled = 
   $('rpt_untilc').checked == true ? false :'disabled' ; 
 if ( $('rpt_ampmA') ) {
	 $('rpt_ampmA').disabled = $('rpt_ampmP').disabled =
	   $('rpt_untilu').checked ? false :'disabled';
  }
}

function toggle_rem_when() {
  //Reminder Tab disabled

  if ( ! $('rem_when_date') ) {
    return;
  }
 if ( $('reminder_ampmA') ) {
	 $('reminder_ampmA').disabled = $('reminder_ampmP').disabled =
	   $('rem_when_date').checked == true ? false : 'disabled';
 }
 $('rem_days').disabled =
   $('rem_hours').disabled =
   $('rem_minutes').disabled =
   $('rem_beforeY').disabled =
   $('rem_relatedS').disabled =
   $('rem_beforeN').disabled =
   $('rem_relatedE').disabled = 
   $('rem_when_date').checked != true ? false : 'disabled';

  $('reminder_date').disabled = 
   $('rem_when_date').checked == true ? false : 'disabled';
}

function toggle_reminders() {
  //Reminder Tab disabled
  if ( ! $('rem_when_date') ) {
    return;
  }
  toggle_rem_when();
	$('reminder_when').showIf ($('reminderYes').checked == true, false );
	$('reminder_repeat').showIf ($('reminderYes').checked == true, false );
}

function toggle_rem_rep(){
 $('rem_rep_days').disabled =
 $('rem_rep_hours').disabled =
 $('rem_rep_minutes').disabled = 
 ( $('rem_rep_count').value == 0 );
}


function displayInValid(myvar)
{
  alert ( invalidTime );
  myvar.select ();
  myvar.focus ();
}


function completed_handler () {
  if ( $('task_percent') ) {
    $('completed_date').disabled =
      ( $('task_percent').selectedIndex != 0 || $('others_complete') == 'N')? false:'disabled';
  }
}

function onLoad () {
  //make sure AJAX is finished first
  if ( typeof( window[ 'SU' ] ) == "undefined") {
    setTimeout ( "onLoad()", 100 );
  return false;
  }
  //define these variables here so they are valid
  form = document.editentryform;
  elements = document.editentryform.elements;
  elementlength = document.editentryform.elements.length;
  //initialize byxxxAr Objects
  if ( $('bydayList') ) {
    if ( $('bydayList').value.search( /,/ ) > -1 ) {
      bydayList = $('bydayList').split ( ',' );
      for ( key in bydayList ) {
        bydayAr[bydayList[key]] = bydayList[key];
      }
    } else if ( $('bydayList').length > 0 ) {
      bydayAr[bydayList] = $('bydayList');
    }
  }
  
  if ( $('bymonthdayList') ) {
    if ( $('bymonthdayList').value.search( /,/ ) > -1 ) {
      bymonthdayList = $('bymonthdayList').value.split ( ',' );
      for ( key in bymonthdayList ) {
        bymonthdayAr[bymonthdayList[key]] = bymonthdayList[key];
      }
    } else if ( $('bymonthdayList').length > 0 ) {
      bymonthdayAr[bymonthdayList] = $('bymonthdayList');
    }
  }

  if ( $('bysetposList') ) {
    if ( $('bysetposList').value.search( /,/ ) > -1 ) {
      bysetposList = $('bysetposList').value.split ( ',' );
      for ( key in bysetposList ) {
        bysetposAr[bysetposList[key]] = bysetposList[key];
      }
    } else if ( $('bysetposList').length > 0 ){
      bysetposAr[bysetposList] = $('bysetposList');
    }
  }

  timetype_handler();
  rpttype_handler();
  toggle_until();
  toggle_reminders();
  toggle_rem_rep();
  completed_handler();
  
  //hopefully, AJAX has set these values by now
  bydayTrans = new Array ( SU, MO, TU, WE, TH, FR, SA ); 
}

function selectUsers () {
  url = "usersel.php?form=editentryform&listid=entry_part&user=" + user + "&users=";
  // add currently selected users
  for ( i = 0, j = 0; i < $('entry_part').length; i++ ) {
    if ( $('entry_part').options[i].selected ) {
      if ( j != 0 )
        url += ",";
      j++;
      url += $('entry_part').options[i].value;
    }
   }
   //alert ( "URL: " + url );
   // open window
   window.open ( url, "UserSelection",
     "width=500,height=500,resizable=yes,scrollbars=yes" );
  }
  
function setTab( tab ) {
  showTab(tab);
  return false;
}

// Set the state (selected or unselected) if a single user in the list of users.
function selectByLogin ( login ) {
  //alert ( "selectByLogin ( " + login + " )" );
  //Check Users
	var i = 0;
  var list = document.editentryform.entry_part;
  var listlen = list.options.length;
  for ( i = 0; i < listlen; i++ ) {
    if ( list.options[i].value == login ) {
      list.options[i].selected = true;
      return true;
    }
  }
  //Check Resources
  var list = document.editentryform.res_part;
  var listlen = list.options.length;
  for ( i = 0; i < listlen; i++ ) {
    if ( list.options[i].value == login ) {
      list.options[i].selected = true;
      return true;
    }
  }
}

function addGroup () {
  var
    list = document.editentryform.groups,
    selNum = list.selectedIndex;
    //generated by AJAX
		if (grouplist.groups)
		  grouplist.groups.each(function(ids) {
		  if ( selNum == ids.grp ) {
				selectByLogin (ids.id );
			}
		});
}

function selAdd(btn){
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
   for (j = 0; j < sel.length; j++){
     if ( sel.options[j].value == val )
       unique = false;
   } 
   return unique;
}

function selResource(btn){ 
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
function selRemove(btn){
   with (form)
   { 
      with (form.sel_part)
      {
         for (i = 0; i < length; i++)
         {   
            if(options[i].selected){
              options[i] = null;
         }      
         } // end for loop
     }
   } // end with document
}

function lookupName(){
  var selectid = -1;
  var x =  stringLength(form.lookup.value);
	var lower = stringToLowercase(form.lookup.value );
  form.entry_part.selectedIndex = -1;
  form.res_part.selectedIndex = -1;
  if ( form.groups )
    form.groups.selectedIndex = -1;
  //check userlist
  for ( i = 0; i < form.entry_part.length; i++ ) {
    str = form.entry_part.options[i].text;
    if ( stringToLowercase(str.substring(0,x)) == lower){
      selectid = i;
    i =  form.entry_part.length;
   }
  }
  if ( selectid  > -1) {
    form.entry_part.selectedIndex = selectid;
    return true;
  }
  //check resource list
  for ( i = 0; i < form.res_part.length; i++ ) {
    str = form.res_part.options[i].text;
    if ( stringToLowercase(str.substring(0,x)) == lower){
      selectid = i;
    i =  form.res_part.length;
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
      if ( stringToLowercase(str.substring(0,x)) == lower){
        selectid = i;
      i =  form.groups.length;
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

function initGroupList() {
  var url = 'ajax.php';
  var params = 'page=edit_entry_groups';
  var ajax = new Ajax.Request(url,
    {method: 'post', 
    parameters: params, 
    onComplete: setGroupList});
}
function setGroupList(originalRequest) {
  if (originalRequest.responseText) {
		grouplist = originalRequest.responseText.evalJSON();
  }
}

