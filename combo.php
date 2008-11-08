<?php
/* $Id$
 *
 * This page handles displaying the Day/Week/Month/Year views in a single
 * page with tabs.  Content is loaded dynamically with AJAX.
 *
 * TODO:
 * - Day view
 * - Week view
 * - Task view
 * - Category selection
 * - Layers
 * - Print layout
 * - Quick add event (like Google Calendar)
 * - Delete event
 *
 * Possibilities for later:
 * - Include tab for unapproved events where users could approve from
 *   this page.
 */
include_once 'includes/init.php';
// Load Doc classes for attachments and comments
include 'includes/classes/Doc.class';
include 'includes/classes/DocList.class';
include 'includes/classes/AttachmentList.class';
include 'includes/classes/CommentList.class';

send_no_cache_header ();

$LOADING = '<div style="height: 220px; padding-top: 190px;"><center><img src="images/loading_animation.gif" alt=""/></center></div>';
$SMALL_LOADING = '<img src="images/loading_animation_small.gif" alt="..." width="16" height="16"/>';

if ( $CATEGORIES_ENABLED == 'Y' )
  load_user_categories ();

$date = getIntValue ( 'date' );
if ( empty ( $date ) )
  $date = date ( 'Ymd' );
$thisyear = substr ( $date, 0, 4 );
$thismonth = substr ( $date, 4, 2 );
$thisday = substr ( $date, 6, 2 );

$next = mktime ( 0, 0, 0, $thismonth + 1, 1, $thisyear );
$nextYmd = date ( 'Ymd', $next );
$nextyear = substr ( $nextYmd, 0, 4 );
$nextmonth = substr ( $nextYmd, 4, 2 );

$prev = mktime ( 0, 0, 0, $thismonth - 1, 1, $thisyear );
$prevYmd = date ( 'Ymd', $prev );
$prevyear = substr ( $prevYmd, 0, 4 );
$prevmonth = substr ( $prevYmd, 4, 2 );

// Can the user see event participants?
$show_participants = ( $DISABLE_PARTICIPANTS_FIELD != 'Y' );
if ( $is_admin )
  $show_participants = true;
if ( $PUBLIC_ACCESS == 'Y' && $login == '__public__' &&
  ( $PUBLIC_ACCESS_OTHERS != 'Y' || $PUBLIC_ACCESS_VIEW_PART == 'N' ) )
  $show_participants = false;

// Get width/height settings for modal dialog used to view event.
$view_width = empty ( $VIEW_EVENT_DIALOG_WIDTH ) ? "350" :
  $VIEW_EVENT_DIALOG_WIDTH;
$view_height = empty ( $VIEW_EVENT_DIALOG_HEIGHT ) ? "300" :
  $VIEW_EVENT_DIALOG_HEIGHT;

$BodyX = 'onload="load_content(' . $thisyear . ',' . $thismonth .
  ');"';

// Add Modal Dialog javascript/CSS & Tab code
$HEAD =
  '<link rel="stylesheet" href="includes/tabcontent/tabcontent.css" type="text/css" />' . "\n" .
  '<script type="text/javascript" src="includes/tabcontent/tabcontent.js"></script>' . "\n" .
  '<link rel="stylesheet" href="includes/js/dhtmlmodal/windowfiles/dhtmlwindow.css" type="text/css" />' . "\n" .
  '<script type="text/javascript" src="includes/js/dhtmlmodal/windowfiles/dhtmlwindow.js"></script>' . "\n" .
  '<link rel="stylesheet" href="includes/js/dhtmlmodal/modalfiles/modal.css" type="text/css" />' . "\n" .
  '<script type="text/javascript" src="includes/js/dhtmlmodal/modalfiles/modal.js"></script>' . "\n";

$INC =
  array ( 'js/popups.php/true', 'js/visible.php', 'js/dblclick_add.js/true' );

print_header ( $INC, $HEAD, $BodyX );

ob_start ();

?>

<div style="margin: 15px; border: 1px solid #000; background-color: #e0e0e0; color: #000; padding: 10px; text-align: center;">
<!--
<img align="left" src="http://upload.wikimedia.org/wikipedia/commons/thumb/5/57/Circle-style-warning.svg/400px-Circle-style-warning.svg.png" width="40" height="40" />
-->
This page is a prototype that will hopefully evolve into a replacement
for all four of the main views (day.php, week.php, month.php, year.php).
</div>

<div class="headerinfo">
<table border="0">
<tr>
<?php
if ( $single_user == 'N' ) {
  user_load_variables ( ! empty ( $user ) ? $user : $login, 'user_' );
  echo "<td valign=\"top\" class=\"username\"><nobr>" .
     htmlspecialchars ( $user_fullname ) . "</nobr></td>";
}
if ( $CATEGORIES_ENABLED == 'Y' ) {
  ?>
  <td valign="top" id="categoryselection">Categories:</td>
  <td valign="top" onmouseover="setCategoryVisibility(true)" onmouseout="setCategoryVisibility(false)">
  <img id="catexpand" src="images/expand.gif"/>
  <span id="selectedcategories">All</span><br/>
  <div id="categorylist" style="display:none">
  <?php
  foreach ( $categories as $catId => $val ) {
    $name = "cat-" . $catId;
    if ( $catId > 0 ) {
      ?>
      <nobr><input type="checkbox" id="<?php echo $name;?>" name="<?php echo $name;?>"
        onclick="handleCategoryCheckboxChange()" value="Y" /><label for="<?php echo $name;?>">
        <?php echo htmlspecialchars ( $categories[$catId]['cat_name'] ) ?>
        </label></nobr>&nbsp;
      <?php
      //$catIconFile = 'icons/cat-' . $catId . '.gif';
      //if ( file_exists ( $catIconFile ) )
    }
  }
  ?>
  <br/><input style="font-size: 80%" type="button" value="<?php etranslate("Select All");?>" onclick="selectAllCategories()"/>
    &nbsp;&nbsp;
  <input style="font-size: 80%" type="button" value="<?php etranslate("Select None");?>" onclick="selectNoCategories()" />
  <?php
?>
</div>
<?php
}
?>
</tr></table>
</div>

<ul id="viewtabs" class="shadetabs" style="margin-left: 10px;">

<li><a href="#" rel="contentDay" class="selected">Day</a></li>
<li><a href="#" rel="contentWeek">Week</a></li>
<li><a href="#" rel="contentMonth">Month</a></li>
<?php if ( $DISPLAY_TASKS_IN_GRID == 'Y' ) { ?>
<li><a href="#" rel="contentTasks">Tasks</a></li>
<?php } ?>
</ul>

<div style="border:1px solid gray; width:95%; margin-bottom: 1em; margin-left: 10px; margin-right: 10px; padding: 10px">

<div id="contentDay" class="tabcontent">
Day content goes here...
</div>

<div id="contentWeek" class="tabcontent">
Week content goes here...
</div>

<div id="contentMonth" class="tabcontent">
Month content goes here...
</div>

<?php if ( $DISPLAY_TASKS_IN_GRID == 'Y' ) { ?>
<div id="contentTasks" class="tabcontent">
Tasks content goes here...
</div>
<?php } ?>

</div>


<div id="viewEventDiv" style="display: none;">
<table border="0" width="100%">
  <tr><td colspan="2"><h2 id="name">  </h2></td></tr>
  <tr><td class="aligntop bold"><?php etranslate("Description")?>:</td>
    <td id="description">  </td></tr>
  <tr><td class="aligntop bold"><?php etranslate("Date")?>:</td>
    <td id="date">  </td></tr>
  <tr><td class="aligntop bold"><?php etranslate("Time")?>:</td>
    <td id="time">  </td></tr>
<?php if ( $DISABLE_PRIORITY_FIELD != 'Y' ) { ?>
  <tr><td class="aligntop bold"><?php etranslate("Priority")?>:</td>
    <td id="priority">  </td></tr>
<?php } ?>
  <tr><td class="aligntop bold"><?php etranslate("Access")?>:</td>
    <td id="access">  </td></tr>
<?php if ( $single_user == 'N' ) { ?>
  <tr><td class="aligntop bold"><?php etranslate("Created by")?>:</td>
    <td id="createdby">  </td></tr>
<?php } ?>
  <tr><td class="aligntop bold"><?php etranslate("Updated")?>:</td>
    <td id="updated">  </td></tr>
<?php if ( $show_participants ) { ?>
  <tr><td class="aligntop bold"><?php etranslate("Participants")?>:</td>
    <td id="participants">  </td></tr>
<?php } ?>
<?php if ( Doc::attachmentsEnabled () ) { ?>
  <tr><td class="aligntop bold"><?php etranslate("Attachments")?>:</td>
    <td id="attachments">  </td></tr>
<?php } ?>
<?php if ( Doc::commentsEnabled () ) { ?>
  <tr><td class="aligntop bold"><?php etranslate("Comments")?>:</td>
    <td id="comments">  </td></tr>
<?php } ?>

  <tr><td colspan="2" id="eventlink" align="center">  </td></tr>
</table>
</div>

<script type="text/javascript">

// Initialize tabs
var views=new ddtabcontent("viewtabs")
views.setpersist(true)
views.setselectedClassTarget("link") //"link" or "linkparent"
views.init()
// End init tabs

var currentYear = null, currentMonth = null;

<?php if ( $CATEGORIES_ENABLED == 'Y' ) { ?>

var allCatsSelected = true;
var selectedCats = [];
var categories = [];
<?php
  foreach ( $categories as $catId => $val ) {
    if ( $catId > 0 ) {
      echo 'categories[' . $catId . '] = ' .
        '{ id : ' . $catId .
        ', state: 1' .
        ', owner: "' . $categories[$catId]['cat_owner'] . '"' .
        ', name: "' . $categories[$catId]['cat_name'] . '"' .
        ', color: "' . $categories[$catId]['cat_color'] . '"' .
        ', global: ' . ( $categories[$catId]['cat_global'] ? '0' : '1' );
      $catIconFile = 'icons/cat-' . $catId . '.gif';
      if ( file_exists ( $catIconFile ) )
        echo ', icon: "' . $catIconFile . '"';
      echo " };\n";
    }
  }
?>
<?php } ?>
var viewDialog = null;
var catsVisible = false;
var events = new Array ();
var loadedMonths = new Array (); // Key will be format "200801" For Jan 2008
var months = [
  <?php
    for ( $i = 0; $i < 12; $i++ ) {
      if ( $i ) echo ", ";
      echo "'" . month_name ( $i ) . "'";
    }
  ?>
  ];
var shortMonths = [
  <?php
    for ( $i = 0; $i < 12; $i++ ) {
      if ( $i ) echo ", ";
      echo "'" . month_name ( $i, 'M' ) . "'";
    }
  ?>
  ];
var weekdays = [
  <?php
    for ( $i = 0; $i < 7; $i++ ) {
      if ( $i ) echo ", ";
      echo "'" . weekday_name ( $i, 'l' ) . "'";
    }
  ?>
  ];
var shortWeekdays = [
  <?php
    for ( $i = 0; $i < 7; $i++ ) {
      if ( $i ) echo ", ";
      echo "'" . weekday_name ( $i, 'D' ) . "'";
    }
  ?>
  ];
var daysPerMonth = [ <?php echo implode ( ", ", $days_per_month ); ?> ];
var leapDaysPerMonth = [ <?php echo implode ( ", ", $ldays_per_month ); ?> ];
var users = [];
<?php
  $users = user_get_users ();
  for ( $i = 0; $i < count ( $users ); $i++ ) {
    $fname = $users[$i]['cal_fullname'];
    if ( empty ( $fname ) )
      $fname = $users[$i]['cal_login'];
    $fname = str_replace ( "'", "", $fname );
    echo 'users[\'' . $users[$i]['cal_login'] . '\'] = \'' . $fname . '\'' .
      "\n";
  }
?>

function setCategoryVisibility (newIsVisible)
{
  if ( newIsVisible ) {
    $('categorylist').style.display = "block";
    $('catexpand').src = "images/collapse.gif";
    catsVisible = true;
  } else {
    $('categorylist').style.display = "none";
    $('catexpand').src = "images/expand.gif";
    catsVisible = false;
  }
}

function selectAllCategories ()
{
<?php
  foreach ( $categories as $catId => $val ) {
    if ( $catId > 0 ) {
      $checkboxName = "cat-" . $catId;
      echo "  $('" . $checkboxName . "').checked = true;\n";
    }
  }
?>
  handleCategoryCheckboxChange ();
}

function selectNoCategories ()
{
<?php
  foreach ( $categories as $catId => $val ) {
    if ( $catId > 0 ) {
      $checkboxName = "cat-" . $catId;
      echo "  $('" . $checkboxName . "').checked = false;\n";
    }
  }
?>
  handleCategoryCheckboxChange ();
}

function handleCategoryCheckboxChange()
{
  var newText = '';
  var cnt = 0, cntOff = 0;
  var all = false;
  selectedCats = [];
<?php
  foreach ( $categories as $catId => $val ) {
    if ( $catId > 0 ) {
      $checkboxName = "cat-" . $catId;
      $varName = "cat" . $catId;
      echo "  var $varName = document.getElementById('$checkboxName');\n";
      echo "  if ( " . $varName . ' && ' . $varName . '.checked ) {' . "\n";
      echo '    selectedCats[cnt] = ' . $catId . ';' . "\n";
      echo "    if ( cnt++ > 0 ) newText += ', ';\n";
      echo "    newText += \"" . $categories[$catId]['cat_name'] . "\";\n";
      echo '    categories[' . $catId . '].state = 1;' . "\n";
      echo '  } else { ' . "\n";
      echo '    cntOff++;' . "\n";
      echo '    categories[' . $catId . '].state = 0;' . "\n";
      echo '  }' . "\n";
    }
  }
?>
  if ( cnt == 0 || cntOff == 0 ) {
    newText = '<?php etranslate('All');?>';
    for ( var catId in categories ) {
      categories[catId].state = 1;
    }
    allCatsSelected = true;
  } else {
    allCatsSelected = false;
  }
  $('selectedcategories').innerHTML = newText;

  // Update display
  update_display ( currentYear, currentMonth );
}

function load_content (year,month)
{
  var startdate = "" + year + ( month < 10 ? "0" : "" ) + month + "01";
  // First, check to see if we already have loaded the content for
  // the specified month.
  var monthKey = "" + year + ( month < 10 ? "0" : "" ) + month;
  if ( loadedMonths[monthKey] > 0 ) {
    //alert ( "Already loaded " + monthKey );
    update_display ( year, month );
    return;
  }
  //alert ( "Loading startdate=" + startdate );

  //$('contentDay').innerHTML = '<?php echo $LOADING;?>';
  //$('contentWeek').innerHTML = '<?php echo $LOADING;?>';
  //$('contentMonth').innerHTML = '<?php echo $LOADING;?>';
  var o = $('monthstatus');
  if ( o )
    o.innerHTML = '<?php echo $SMALL_LOADING;?>';
<?php if ( $DISPLAY_TASKS_IN_GRID == 'Y' ) { ?>
  $('contentTasks').innerHTML = '<?php echo $LOADING;?>';
<?php } ?>

  new Ajax.Request('events_ajax.php',
  {
    method:'get',
    parameters: { action: 'get', startdate: startdate },
    onSuccess: function(transport){
      if ( ! transport.responseText ) {
        alert ( '<?php etranslate('Error');?>: <?php etranslate('no response from server');?>' );
        return;
      }
      //alert ( "Response:\n" + transport.responseText );
      try  {
        //var response = transport.responseText.evalJSON ();
        // Hmmm... The Prototype JSON above doesn't seem to work!
        var response = eval('(' + transport.responseText + ')');
      } catch ( err ) {
        alert ( '<?php etranslate('Error');?>: <?php etranslate('JSON error');?> - ' + err + "\n\n" + transport.responseText );
        return;
      }
      if ( response.error ) {
        alert ( '<?php etranslate('Error');?>: '  + response.message );
        return;
      }
      for ( var key in response.dates ) {
        events[key] = response.dates[key];
      }
      loadedMonths[monthKey] = 1;
      update_display ( year, month );
    },
    onFailure: function(){ alert('<?php etranslate("Error");?>') }
  });
  return true;
}

// View the event
// key is the arring index of the events[] object (which returns an array)
// location is the index in the array
function view_event ( key, location )
{
  var myEvent = null;
  var found = 0;
  if ( events && events[key] ) {
    var daysEvents = events[key];
    if ( daysEvents && daysEvents[location] ) {
      var myEvent = daysEvents[location];
      found = 1;
    }
  }
  if ( ! found ) {
    alert ( "Argh!  Event not found." );
    return;
  }
  // Use the modal dialog to display the event.
  // First update the <div> content with the information from this
  // event.
  viewDialog = dhtmlmodal.open ( 'viewEventDialog', 'div',
    'viewEventDiv', '<?php etranslate('View Event');?>',
    'width=<?php echo $view_width;?>px,height=<?php echo $view_height;?>px' +
    'resize=1,scrolling=1,center=1' );

  $('name').innerHTML = myEvent._name;
  $('description').innerHTML = format_description ( myEvent._description );
  $('date').innerHTML = format_date ( myEvent._localDate, true );
  $('time').innerHTML = format_time ( myEvent._localTime );
  $('updated').innerHTML = format_date ( myEvent._localDate, false ) + ' ' +
    format_time ( myEvent._modtime ) + ' GMT';
  $('createdby').innerHTML = users[myEvent._owner] ?
    users[myEvent._owner] : myEvent._owner;
  if ( myEvent._priority < 4 )
    $('priority').innerHTML = '<?php etranslate('High');?>';
  else if ( myEvent._priority < 7 )
    $('priority').innerHTML = '<?php etranslate('Medium');?>';
  else
    $('priority').innerHTML = '<?php etranslate('Low');?>';
  if ( myEvent._access == 'P' )
    $('access').innerHTML = '<?php etranslate('Public');?>';
  else if ( myEvent._access == 'C' )
    $('access').innerHTML = '<?php etranslate('Confidential');?>';
  else
    $('access').innerHTML = '<?php etranslate('Private');?>';
<?php if ( $CATEGORIES_ENABLED == 'Y' ) { ?>
<?php } ?>
 
  $('eventlink').innerHTML = '<a href="view_entry.php?id=' + myEvent._id +
<?php if ( ! empty ( $user ) && $login != $user ) { echo "'&amp;user=$user' + "; } ?>
    '" class="fakebutton"><?php etranslate('View Event')?></a>';

  // For now, blank out participants.
  $('participants').innerHTML = '<?php echo $SMALL_LOADING;?>';
  $('attachments').innerHTML = '<?php echo $SMALL_LOADING;?>';
  $('comments').innerHTML = '<?php echo $SMALL_LOADING;?>';

  // Load participants via AJAX
  new Ajax.Request('events_ajax.php',
  {
    method:'get',
    parameters: { action: 'eventinfo', id: myEvent._id },
    onSuccess: function(transport){
      if ( ! transport.responseText ) {
        alert ( '<?php etranslate('Error');?>: <?php etranslate('no response from server');?>' );
        return;
      }
      //alert ( "Response:\n" + transport.responseText );
      try  {
        //var response = transport.responseText.evalJSON ();
        // Hmmm... The Prototype JSON above doesn't seem to work!
        var response = eval('(' + transport.responseText + ')');
      } catch ( err ) {
        alert ( '<?php etranslate('Error');?>: <?php etranslate('JSON error');?> - ' + err + "\n\n" + transport.responseText );
        return;
      }
      if ( response.error ) {
        alert ( '<?php etranslate('Error');?>: '  + response.message );
        return;
      }
      var text = '';
      for ( var i = 0; i < response.participants.length; i++ ) {
        var participant = response.participants[i];
        var login = participant.login;
        var fullname = users[login] ? users[login] : login;
        if ( text.length > 0 ) text += "<br/>";
        text += fullname;
        if ( participant.status == 'W' )
          text += ' (?)';
      }
      $('participants').innerHTML = text;

      text = '';
      for ( var i = 0; i < response.attachments.length; i++ ) {
        var attachment = response.attachments[i];
        var summary = attachment.summary;
        if ( text.length > 0 ) text += "<br/>";
        text += summary;
      }
      if ( response.attachments.length == 0 )
        text = '<?php etranslate('None');?>';
      $('attachments').innerHTML = text;

      text = '<dl style="margin-top: 0;">';
      for ( var i = 0; i < response.comments.length; i++ ) {
        var comment = response.comments[i];
        text += "<dt>" + comment.description + "<br/>" +
          comment.owner + " @ " + comment.datetime + "</dt>" +
          "<dd>" + comment.text + "</dd>";
      }
      text += "</dl>\n";
      if ( response.comments.length == 0 )
        text = '<?php etranslate('None');?>';
      $('comments').innerHTML = text;
    },
    onFailure: function(){ alert('<?php etranslate("Error");?>') }
  });
}

function update_display ( year, month )
{
  currentYear = year;
  currentMonth = month;
  $('contentDay').innerHTML = "Not yet implemented...";
  $('contentWeek').innerHTML = "Not yet implemented...";
  $('contentMonth').innerHTML = build_month_view ( year, month );
<?php if ( $DISPLAY_TASKS_IN_GRID == 'Y' ) { ?>
  $('contentTasks').innerHTML = "Not yet implemented...";
<?php } ?>
}

function prev_month_link ( year, month )
{
  var m, y;
  if ( month == 1 )  {
    m = 12;
    y = year - 1;
  } else {
    m = month - 1;
    y = year;
  }
  return "<span class=\"clickable fakebutton\" onclick=\"load_content(" +
    y + "," + m + ")\">&lt;</span>";
}
function next_month_link ( year, month )
{
  var m, y;
  if ( month == 12 )  {
    m = 1;
    y = year + 1;
  } else {
    m = month + 1;
    y = year;
  }
  return "<span class=\"clickable fakebutton\" onclick=\"load_content(" +
    y + "," + m + ")\">&gt;</span>";
}

// Build the HTML for the month view
function build_month_view ( year, month )
{
  var ret = "";
  try {
    var dateYmd;
    ret = prev_month_link ( year, month ) +
      next_month_link ( year, month ) + "&nbsp;" +
      "<span class=\"monthtitle\">" + months[month-1] + " " + year + "</span>" +
      "<span id=\"monthstatus\"> </span>" +
      "<table id=\"month_main\" class=\"main\" border=\"0\" width=\"100%\" border=\"1\"><tr>";
    for ( var i = 0; i < 7; i++ ) {
      ret += "<th>" + weekdays[i] + "</th>";
    }
    ret += "</tr>\n";

    var d = new Date ();
    var today = new Date ();
    d.setYear ( year );
    d.setMonth ( month - 1 );
    d.setDate ( 1 );

    var wday = d.getDay ();
    var startDay = 1 - wday;
    var daysThisMonth = ( year % 4 == 0 ) ? leapDaysPerMonth[month] :
      daysPerMonth[month];

    for ( var i = startDay, j = 0; i <= daysThisMonth || j % 7 != 0 ; i++, j++ ) {
      if ( j % 7 == 0 ) ret += "<tr>";
      if ( i < 1 ) {
        ret += "<td class=\"othermonth\">&nbsp;</td>\n";
      } else if ( i > daysThisMonth ) {
        ret += "<td class=\"othermonth\">&nbsp;</td>\n";
      } else {
        var key = "" + year + ( month < 10 ? "0" : "" ) + month +
          ( i < 10 ? "0": "" ) + i;
        var eventArray = events[key];
        var class = ( j % 7 == 0 || j % 7 == 6 ) ? 'weekend' : '';
        if ( year == ( today.getYear() + 1900 ) &&
          ( month - 1 ) == today.getMonth() &&
          i == today.getDate() )
          class = 'today';
        if ( eventArray && eventArray.length > 0 )
          class += ' entry hasevents';
        ret += "<td class=\"" + class + "\">";
        ret += "<span class=\"dayofmonth\">" + i + "</span><br/>";
        // If eventArray is null here, that means we have not loaded
        // event data for that date.
        for ( var l = 0; eventArray && l < eventArray.length; l++ ) {
          var myEvent = eventArray[l];
<?php if ( $CATEGORIES_ENABLED == 'Y' ) { ?>
          // See if this event matches selected categories.
          if ( ! allCatsSelected ) {
            if ( ! eventMatchesSelectedCats ( myEvent ) )
              continue;
          }
<?php } ?>
          var id = 'popup-' + key + "-" + myEvent._id;
          ret += "<div class=\"clickable\" onmouseover=\"showPopUp(event,'" + id + "')\"" +
            " onmouseout=\"hidePopUp('" + id + "')\"" +
            " onclick=\"view_event('" + key + "'," + l + ")\">";
<?php if ( $CATEGORIES_ENABLED == 'Y' ) { ?>
          if ( categories && categories.length ) {
            var catId = myEvent._category;
            if ( catId < 0 ) catId = 0 - catId;
            if ( categories[catId] && categories[catId].icon ) {
              ret += '<img src="' + categories[catId].icon + '"/>';
            }
          }
<?php } ?>
          ret += myEvent._name + "</div>";
          // Create popup
          if ( ! document.getElementById ( id ) ) {
            var popup = document.createElement('dl');
            popup.setAttribute ( 'id', id );
            popup.className = "popup";
            popup.innerHTML = "<dt>" +
              format_description ( myEvent._description ) + "</dt>";
            document.body.appendChild ( popup );
          }
        }
        ret += "</td>\n";
      }
      if ( j % 7 == 6 ) ret += "</tr>\n";
    }
    ret += "</table>\n";
  } catch ( err ) {
    alert ( "JavaScript exception:\n" + err );
  }
  return ret;
}

<?php if ( $CATEGORIES_ENABLED == 'Y' ) { ?>
function eventMatchesSelectedCats ( event ) {
  if ( ! event._categories || event._categories.length == 0 )
    return false;
  for ( var i = 0; i < event._categories.length; i++ ) {
    var catId = event._categories[i];
    if ( isInArray ( catId, selectedCats ) ) {
      return true;
    }
  }
  return false;
}

function isInArray ( val, searchArr )
{
  for ( var i = 0; i < searchArr.length; i++ ) {
    if ( searchArr[i] == val )
      return true;
  }
  return false;
}
<?php } ?>

// This function mimics the date_to_str PHP function found in
// includes/functions.php.
function format_date ( dateStr, showWeekday )
{
  var fmt = '<?php echo $DATE_FORMAT;?>';

  var y = dateStr.substring ( 0, 4 );
  var m = dateStr.substring ( 4, 6 );
  var d = dateStr.substring ( 6, 8 );

  var ret = fmt;
  ret = ret.replace ( /__dd__/, d );
  ret = ret.replace ( /__j__/, d );
  ret = ret.replace ( /__mm__/, m );
  ret = ret.replace ( /__mon__/, shortMonths[m-1] );
  ret = ret.replace ( /__month__/, months[m-1] );
  ret = ret.replace ( /__n__/, m );
  ret = ret.replace ( /__yy__/, y % 100 );
  ret = ret.replace ( /__yyyy__/, y );

  var w = '';
  if ( showWeekday ) {
    var myD = new Date ();
    myD.setYear ( y );
    myD.setMonth ( m - 1 );
    myD.setDate ( d );
    wday = myD.getDay ();
    w = weekdays[wday] + ', ';
  }

  return w + ret;
}

// TODO: modify this to handle different time formats, timezones, etc...
// The code for different timezones could get ugly here...
function format_time ( timeStr )
{
  if ( timeStr < 0 )
    return '';

  var h = timeStr.substring ( 0, 2 );
  var m = timeStr.substring ( 2, 4 );
  var ret;

<?php if ( $TIME_FORMAT == '12' ) { ?>
  if ( h < 0 )
    h += 24; 
  var ampm = ( h >= 12 ? '<?php etranslate('pm')?>' : '<?php etranslate('am')?>' );
  h %= 12;
  if ( h == 0 )
    h = 12;
  ret = h + ':' + m + ampm;
<?php } else { ?>
  ret = h + ':' + m;
<?php } ?>
  return ret;
}

function format_description ( desc )
{
  var ret;
<?php if ( ! empty ( $ALLOW_HTML_DESCRIPTION ) &&
  $ALLOW_HTML_DESCRIPTION == 'Y' ) { ?>
  // HTML is allowed in description
  if ( desc.indexOf ( '<' ) >= 0 ) {
    ret = desc;
  } else {
    // No HTML found, replace \n with line breaks
    ret = desc.replace (/\n/g,"<br/>");
  }
<?php } else { ?>
  // HTML not allowed in description
  // TODO: convert URLs into active links
  ret = desc.replace (/\n/g,"<br/>");
<?php } ?>
  return ret;
}

</script>

<?php

ob_end_flush ();

echo print_trailer ();

?>
