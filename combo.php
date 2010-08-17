<?php // $Id$
/**
 * This page handles displaying the Day/Week/Month/Year views in a single
 * page with tabs. Content is loaded dynamically with AJAX.
 * So, requests for previous & next will not force a page reload.
 *
 * TODO:
 * - Week view
 * - Task view
 * - Print layout
 * - Delete event (?)
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

//send_no_cache_header();

$LOADING = '<div style="height: 220px; padding-top: 190px;"><center><img src="images/loading_animation.gif" alt="" /></center></div>';
$SMALL_LOADING = '<img src="images/loading_animation_small.gif" alt="..." width="16" height="16" />';

if ( $CATEGORIES_ENABLED == 'Y' )
  load_user_categories();

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

// Get width/height settings for modal dialog used for "quick add"
$quick_add_width = empty ( $QUICK_ADD_DIALOG_WIDTH ) ? "550" :
  $QUICK_ADD_DIALOG_WIDTH;
$quick_add_height = empty ( $QUICK_ADD_DIALOG_HEIGHT ) ? "200" :
  $QUICK_ADD_DIALOG_HEIGHT;

$can_add = true;
if ( $readonly == 'Y' )
  $can_add = false;
else if ( access_is_enabled() )
  $can_add = access_can_access_function ( ACCESS_EVENT_EDIT );
else {
  if ( $login == '__public__' )
    $can_add = ( $GLOBALS['PUBLIC_ACCESS_CAN_ADD'] == 'Y' );
  else if ( $is_nonuser )
    $can_add = false;
}


$BodyX = 'onload="load_content(' . $thisyear . ',' . $thismonth . "," .
  $thisday . ');"';

// Add ModalBox javascript/CSS & Tab code
$HEAD = '
<script type="text/javascript" src="includes/tabcontent/tabcontent.js"></script>
<link type="text/css" href="includes/tabcontent/tabcontent.css" rel="stylesheet" />
<script type="text/javascript" src="includes/js/scriptaculous/scriptaculous.js?¬load=builder,effects"></script>
<script type="text/javascript" src="includes/js/modalbox/modalbox.js"></script>
<link rel="stylesheet" href="includes/js/modalbox/modalbox.css" type="text/css" 
media="screen" />
';


print_header(
  array( 'js/popups.js/true', 'js/visible.php' ),
  $HEAD, $BodyX );

ob_start();

?>

<div style="margin: 15px; border: 1px solid #000; background-color: #e0e0e0; color: #000; padding: 10px; text-align: center;">
<img align="left" src="images/warning.png" width="40" height="40" alt="Warning" />
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
  <img id="catexpand" src="images/expand.gif" />
  <span id="selectedcategories">All</span><br />
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
  <br /><input style="font-size: 80%" type="button" value="<?php etranslate("Select All");?>" onclick="selectAllCategories()" />
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

<li><a href="#" rel="contentDay" class="selected"><?php etranslate('Day');?></a></li>
<li><a href="#" rel="contentWeek"><?php etranslate('Week');?></a></li>
<li><a href="#" rel="contentMonth"><?php etranslate('Month')?></a></li>
<li><a href="#" rel="contentYear"><?php etranslate('Year')?></a></li>
<li><a href="#" rel="contentAgenda"><?php etranslate("Agenda");?></a></li>
<?php if ( $DISPLAY_TASKS_IN_GRID == 'Y' ) { ?>
<li><a href="#" rel="contentTasks"><?php etranslate('Tasks');?></a></li>
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

<div id="contentYear" class="tabcontent">
Year content goes here...
</div>

<div id="contentAgenda" class="tabcontent">
Agenda content goes here...
</div>

<?php if ( $DISPLAY_TASKS_IN_GRID == 'Y' ) { ?>
<div id="contentTasks" class="tabcontent">
Tasks content goes here...
</div>
<?php } ?>


</div>


<div id="viewEventDiv" style="display: none;">
<table border="0">
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
<?php if ( Doc::attachmentsEnabled() ) { ?>
  <tr><td class="aligntop bold"><?php etranslate("Attachments")?>:</td>
    <td id="attachments">  </td></tr>
<?php } ?>
<?php if ( Doc::commentsEnabled() ) { ?>
  <tr><td class="aligntop bold"><?php etranslate("Comments")?>:</td>
    <td id="comments">  </td></tr>
<?php } ?>
  <tr><td colspan="2">&nbsp;</td></tr>
  <tr><td colspan="2" id="eventlink" align="center">  </td></tr>
</table>
</div>

<!-- Hidden div tag for Quick Add dialog -->
<div id="quickAddDiv" style="display: none;">
<table border="0">
<tr><td class="aligntop bold"><?php etranslate('Date');?>:</td>
  <td><span id="quickAddDateFormatted"></span>
  <input type="hidden" id="quickAddDate" name="quickAddDate" /></td></tr>
<tr><td class="aligntop bold"><?php etranslate('Brief Description');?>:</td>
  <td><input id="quickAddName" name="quickAddName" /></td></tr>
<tr><td class="aligntop bold"><?php etranslate('Full Description');?>:</td>
  <td><textarea id="quickAddDescription" name="quickAddDescription"
       rows="4" cols="40" wrap="virtual"></textarea></td></tr>
<?php if ( $CATEGORIES_ENABLED == 'Y' ) { ?>
<tr><td class="aligntop bold"><?php etranslate('Category');?>:</td>
  <td><select id="quickAddCategory" name="quickAddCategory">
     <option value="-1"><?php etranslate('None');?></option>
     <?php
     foreach ( $categories as $K => $V ) {
       if ( $K > 1 ) {
         echo '<option value="' . $K . '">' .
           htmlspecialchars ( $categories[$K]['cat_name'] ) . "</option>\n";
       }
     }
     ?>
     </select></td></tr>
<?php } ?>
<tr><td colspan="2">&nbsp;</td></tr>
<tr><td colspan="2"><input type="button" value="<?php etranslate('Save');?>" onclick="quickAddHandler()" /><br />
<span class="clickable" onclick="addEventDetail()"><?php etranslate("Add event detail");?></span>
</td></tr>
</table>
</div>


<script type="text/javascript">

// Initialize tabs
var views=new ddtabcontent("viewtabs")
views.setpersist(true)
views.setselectedClassTarget("link") //"link" or "linkparent"
views.init()
// End init tabs

var currentYear = null, currentMonth = null, currentDay = null;

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
var viewDialogIsVisible = false;
var quickAddDialogIsVisible = null;
var catsVisible = false;
var events = new Array();
var loadedMonths = new Array(); // Key will be format "200801" For Jan 2008
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
  $users = user_get_users();
  for ( $i = 0; $i < count ( $users ); $i++ ) {
    $fname = $users[$i]['cal_fullname'];
    if ( empty ( $fname ) )
      $fname = $users[$i]['cal_login'];
    $fname = str_replace ( "'", "", $fname );
    echo 'users[\'' . $users[$i]['cal_login'] . '\'] = \'' . $fname . '\'' .
      "\n";
  }
?>

<?php if ( $CATEGORIES_ENABLED == 'Y' ) { ?>
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

function selectAllCategories()
{
<?php
  foreach ( $categories as $catId => $val ) {
    if ( $catId > 0 ) {
      $checkboxName = "cat-" . $catId;
      echo "  $('" . $checkboxName . "').checked = true;\n";
    }
  }
?>
  handleCategoryCheckboxChange();
}

function selectNoCategories()
{
<?php
  foreach ( $categories as $catId => $val ) {
    if ( $catId > 0 ) {
      $checkboxName = "cat-" . $catId;
      echo "  $('" . $checkboxName . "').checked = false;\n";
    }
  }
?>
  handleCategoryCheckboxChange();
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
  update_display ( currentYear, currentMonth, currentDay );
}

<?php } ?>

function load_content (year,month,day)
{
  var startdate = "" + year + ( month < 10 ? "0" : "" ) + month + "01";
  // First, check to see if we already have loaded the content for
  // the specified month.
  var monthKey = "" + year + ( month < 10 ? "0" : "" ) + month;
  if ( loadedMonths[monthKey] > 0 ) {
    //alert ( "Already loaded " + monthKey );
    update_display ( year, month, day );
    return;
  }
  //alert ( "Loading startdate=" + startdate );

  //$('contentDay').innerHTML = '<?php echo $LOADING;?>';
  //$('contentWeek').innerHTML = '<?php echo $LOADING;?>';
  //$('contentMonth').innerHTML = '<?php echo $LOADING;?>';
  //$('contentYear').innerHTML = '<?php echo $LOADING;?>';
  var o = $('monthstatus');
  if ( o ) o.innerHTML = '<?php echo $SMALL_LOADING;?>';
  var o = $('yearstatus');
  if ( o ) o.innerHTML = '<?php echo $SMALL_LOADING;?>';
  o = $('agendastatus');
  if ( o ) o.innerHTML = '<?php echo $SMALL_LOADING;?>';
  o = $('daystatus');
  if ( o ) o.innerHTML = '<?php echo $SMALL_LOADING;?>';
<?php if ( $DISPLAY_TASKS_IN_GRID == 'Y' ) { ?>
  $('contentTasks').innerHTML = '<?php echo $LOADING;?>';
<?php } ?>

  new Ajax.Request('events_ajax.php',
  {
    method:'get',
    parameters: { action: 'get', startdate: startdate },
    onSuccess: function( transport ) {
      if ( ! transport.responseText ) {
        alert ( '<?php etranslate('Error');?>: <?php etranslate('no response from server');?>' );
        return;
      }
      //alert ( "Response:\n" + transport.responseText );
      try  {
        var response = transport.responseText.evalJSON();
        // Hmmm... The Prototype JSON above doesn't seem to work!
        //var response = eval('(' + transport.responseText + ')');
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
      update_display ( year, month, day );
    },
    onFailure: function() { alert( '<?php etranslate( 'Error' );?>' ) }
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
  //viewDialog = dhtmlmodal.open ( 'viewEventDialog', 'div',
  //  'viewEventDiv', '<?php etranslate('View Event');?>',
  //  'width=<?php echo $view_width;?>px,height=<?php echo $view_height;?>px' +
  //  'resize=1,scrolling=1,center=1' );

  function viewWindowClosed() {
    viewDialogIsVisible = false;
  }
  Modalbox.show($('viewEventDiv'), {title: '<?php etranslate('View Event');?>', width: 450, onHide: viewWindowClosed, closeString: '<?php etranslate('Cancel');?>' });
  //Modalbox.resizeToContent();
  viewDialogIsVisible = true;

  //viewDialog.onclose = function() {
  //  viewDialog = null;
  //  return true;
  //}

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
<?php if ( Doc::attachmentsEnabled() ) { ?>
  $('attachments').innerHTML = '<?php echo $SMALL_LOADING;?>';
<?php } ?>
<?php if ( Doc::commentsEnabled() ) { ?>
  $('comments').innerHTML = '<?php echo $SMALL_LOADING;?>';
<?php } ?>

  // Load participants via AJAX
  new Ajax.Request('events_ajax.php',
  {
    method:'get',
    parameters: { action: 'eventinfo', id: myEvent._id },
    onSuccess: function( transport ) {
      if ( ! transport.responseText ) {
        alert ( '<?php etranslate('Error');?>: <?php etranslate('no response from server');?>' );
        return;
      }
      //alert ( "Response:\n" + transport.responseText );
      try  {
        var response = transport.responseText.evalJSON();
        // Hmmm... The Prototype JSON above doesn't seem to work!
        //var response = eval('(' + transport.responseText + ')');
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
        if ( text.length > 0 ) text += "<br />";
        text += fullname;
        if ( participant.status == 'W' )
          text += ' (?)';
      }
      $('participants').innerHTML = text;

<?php if ( Doc::attachmentsEnabled() ) { ?>
      text = '';
      for ( var i = 0; i < response.attachments.length; i++ ) {
        var attachment = response.attachments[i];
        var summary = attachment.summary;
        if ( text.length > 0 ) text += "<br />";
        text += summary;
      }
      if ( response.attachments.length == 0 )
        text = '<?php etranslate('None');?>';
      $('attachments').innerHTML = text;
<?php } ?>

<?php if ( Doc::commentsEnabled() ) { ?>
      text = '<dl style="margin-top: 0;">';
      for ( var i = 0; i < response.comments.length; i++ ) {
        var comment = response.comments[i];
        text += "<dt>" + comment.description + "<br />" +
          comment.owner + " @ " + comment.datetime + "</dt>" +
          "<dd>" + comment.text + "</dd>";
      }
      text += "</dl>\n";
      if ( response.comments.length == 0 )
        text = '<?php etranslate('None');?>';
      $('comments').innerHTML = text;
<?php } ?>
    },
    onFailure: function() { alert( '<?php etranslate( 'Error' );?>' ) }
  });
}

function update_display ( year, month, day )
{
  currentYear = year;
  currentMonth = month;
  currentDay = day;
  $('contentDay').innerHTML = build_day_view ( year, month, day );
  // set scroll location to 8AM (50 pixels/hour)
  $('daydiv').scrollTop = 400;
  // TODO: save the position of the scrollbar so we can preserve on next/prev
  // TODO: Use start work hour from preferences.
  $('contentWeek').innerHTML = "Not yet implemented...";
  $('contentMonth').innerHTML = build_month_view ( year, month );
  $('contentYear').innerHTML = build_year_view ( year, month );
  $('contentAgenda').innerHTML = build_agenda_view ( year, month );
<?php if ( $DISPLAY_TASKS_IN_GRID == 'Y' ) { ?>
  $('contentTasks').innerHTML = "Not yet implemented...";
<?php } ?>
}

function prev_day_link ( year, month, day )
{
  day--;
  if ( day == 0 ) {
    month--;
    if ( month == 0 ) {
      year--;
      month = 12;
    }
    day = ( year % 4 == 0 ) ? leapDaysPerMonth[month] :
      daysPerMonth[month];
  }
  return "<span id=\"prevday\" class=\"clickable fakebutton noprint\" onclick=\"load_content(" +
    year + "," + month + "," + day + ")\">&lt;</span>";
}

function next_day_link ( year, month, day )
{
  day++;
  var daysInMonth = ( year % 4 == 0 ) ? leapDaysPerMonth[month] :
    daysPerMonth[month];
  if ( day > daysInMonth ) {
    day = 1;
    month++;
    if ( month > 12 ) {
      year++;
      month = 1;
    }
  }
  return "<span id=\"nextday\" class=\"clickable fakebutton noprint\" onclick=\"load_content(" +
    year + "," + month + "," + day + ")\">&gt;</span>";
}
function prev_month_link_dayview ( year, month, day )
{
  month--;
  if ( month < 1 ) {
    month = 12;
    year--;
  }
  return "<span id=\"prevmonthdayview\" class=\"clickable fakebutton noprint\" onclick=\"load_content(" +
    year + "," + month + "," + day + ")\">&lt;&lt;</span>";
}
function next_month_link_dayview ( year, month, day )
{
  month++;
  if ( month > 12 ) {
    month = 1;
    year++;
  }
  return "<span id=\"nextmonthdayview\" class=\"clickable fakebutton noprint\" onclick=\"load_content(" +
    year + "," + month + "," + day + ")\">&gt;&gt;</span>";
}

function prev_month_link ( year, month )
{
  var m, y;
  if ( month == 1 ) {
    m = 12;
    y = year - 1;
  } else {
    m = month - 1;
    y = year;
  }
  return "<span id=\"prevmonth\" class=\"clickable fakebutton noprint\" onclick=\"load_content(" +
    y + "," + m + ",1)\">&lt;</span>";
}

function next_month_link ( year, month )
{
  var m, y;
  if ( month == 12 ) {
    m = 1;
    y = year + 1;
  } else {
    m = month + 1;
    y = year;
  }
  return "<span id=\"nextmonth\" class=\"clickable fakebutton noprint\" onclick=\"load_content(" +
    y + "," + m + ",1)\">&gt;</span>";
}

function prev_year_link ( year, month )
{
  return "<span id=\"prevyear\" class=\"clickable fakebutton noprint\" onclick=\"load_content(" + ( year - 1 ) +
    "," + month + ",1)\">&lt;&lt;</span>";
}

function next_year_link ( year, month )
{
  return "<span id=\"nextyear\" class=\"clickable fakebutton noprint\" onclick=\"load_content(" + ( year + 1 ) +
    "," + month + ",1)\">&gt;&gt;</span>";
}

function today_link()
{
  var today = new Date();
  var d = today.getDate();
  var m = today.getMonth() + 1;
  var y = today.getYear() + 1900;
  return "<span class=\"clickable fakebutton noprint\" onclick=\"load_content(" +
    y + "," + m + "," + d + ")\">" +
   '<img src="includes/menu/icons/today.png" style="vertical-align: middle;" />'
   + " <?php etranslate('Today');?></span>";
}

function monthCellClickHandler ( dateYmd )
{
  // Make sure user has not opened the view dialog. When a user clicks
  // on an event to view it, we will still receive the onclick event for
  // the td cell onclick handler below it.
  if ( viewDialogIsVisible )
    return;
  function addWindowClosed() {
    quickAddDialogIsOpen = false;
  }
  Modalbox.show($('quickAddDiv'), {title: '<?php etranslate('Add Entry');?>', width: <?php echo $quick_add_width;?>, onHide: addWindowClosed, closeString: '<?php etranslate('Cancel');?>' });

  //quickAddDialog = dhtmlmodal.open ( 'quickAddDialog', 'div',
  //  'quickAddDiv', '<?php etranslate('Add Entry');?>',
  //  'width=<?php echo $quick_add_width;?>px,height=<?php echo $quick_add_height;?>px,' +
  //  'resize=1,scrolling=1,center=1' );

  $('quickAddName').setAttribute ( 'value', "<?php etranslate('Unnamed Event');?>" );
  $('quickAddName').select();
  $('quickAddName').focus();
  $('quickAddDescription').innerHTML = "";
  $('quickAddDate').setAttribute ( 'value', dateYmd );
  $('quickAddDateFormatted').innerHTML = format_date ( "" + dateYmd, true );
  $('quickAddCategory').selectedIndex = 0;
}


// Handler for user click the "Save" button in the Add Event dialog
// window.
function quickAddHandler()
{
  var name = $('quickAddName').value;
  var description = $('quickAddDescription').value;
  var dateYmd = $('quickAddDate').value;
<?php if ( $CATEGORIES_ENABLED == 'Y' ) { ?>
  var catObj = $('quickAddCategory');
  var category = catObj.options[catObj.selectedIndex].value;
<?php } ?>
  //alert ( 'name: ' + name + '\ndescription: ' + description +
  //  '\ndate: ' + dateYmd + '\ncategory: ' + category );
  new Ajax.Request('events_ajax.php',
  {
    method:'post',
    parameters: { action: 'addevent', date: dateYmd,
      name: name, description: description<?php if ( $CATEGORIES_ENABLED == 'Y' ) { echo ', category: category';} ?> },
    onSuccess: function( transport ) {
      if ( ! transport.responseText ) {
        alert ( '<?php etranslate('Error');?>: <?php etranslate('no response from server');?>' );
        return;
      }
      //alert ( "Response:\n" + transport.responseText );
      try  {
        var response = transport.responseText.evalJSON();
        // Hmmm... The Prototype JSON above doesn't seem to work!
        //var response = eval('(' + transport.responseText + ')');
      } catch ( err ) {
        alert ( '<?php etranslate('Error');?>: <?php etranslate('JSON error');?> - ' + err + "\n\n" + transport.responseText );
        return;
      }
      if ( response.error ) {
        alert ( '<?php etranslate('Error');?>: '  + response.message );
        return;
      }
      // Successfully added :-)
      //alert('Event added');
      // force reload of data.
      Modalbox.hide ();
      var monthKey = "" + currentYear + ( currentMonth < 10 ? "0" : "" ) + currentMonth;
      loadedMonths[monthKey] = 0;
      load_content ( currentYear, currentMonth, currentDay );
    },
    onFailure: function() { alert( '<?php etranslate( 'Error' );?>' ) }
  });
}

function addEventDetail()
{
  var url = 'edit_entry.php?date=' + $('quickAddDate').value;
<?php if ( $CATEGORIES_ENABLED == 'Y' ) { ?>
  var catObj = $('quickAddCategory');
  var category = catObj.options[catObj.selectedIndex].value;
  if ( category > 0 )
    url += '&cat_id=' + category;
<?php } ?>
  url += '&name=' + escape($('quickAddName').value) +
    '&desc=' + escape($('quickAddDescription').value);
  window.location.href = url;
  return true;
}

function refresh()
{
  loadedMonths = []; // forget all events...
  load_content ( currentYear, currentMonth, currentDay );
}


// Build the HTML for the month view
function build_month_view ( year, month )
{
  var ret = "";
  try {
    var dateYmd;
    ret = prev_month_link ( year, month ) +
      next_month_link ( year, month ) +
      prev_year_link ( year, month ) +
      next_year_link ( year, month ) +
      "<span id=\"refresh\" class=\"clickable fakebutton noprint\" onclick=\"refresh()\">" +
      '<img src="images/refresh.gif" style="vertical-align: middle;" alt="<?php etranslate('Refresh');?>" /></span>' +
      today_link() +
      "&nbsp;" +
      "<span class=\"monthtitle\">" + months[month-1] + " " + year + "</span>" +
      "<span id=\"monthstatus\"> </span>" +
      "<table id=\"month_main\" class=\"main\" border=\"0\" width=\"100%\" border=\"1\"><tr>";
    for ( var i = 0; i < 7; i++ ) {
      ret += "<th>" + weekdays[i] + "</th>";
    }
    ret += "</tr>\n";

    var d = new Date();
    var today = new Date();
    d.setYear ( year );
    d.setMonth ( month - 1 );
    d.setDate ( 1 );

    var wday = d.getDay();
    var startDay = 1 - wday;
    var daysThisMonth = ( year % 4 == 0 ) ? leapDaysPerMonth[month] :
      daysPerMonth[month];

    for ( var i = startDay, j = 0; i <= daysThisMonth || j % 7 != 0; i++, j++ ) {
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
        ret += "<td class=\"" + class + "\"";
<?php if ( $can_add ) { ?>
        ret += " onclick=\"return monthCellClickHandler(" + key + ")\"";
<?php } ?>
        ret += "><span class=\"dayofmonth\">" + i + "</span><br />";
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
          ret += "<div class=\"event clickable\" onmouseover=\"showPopUp(event,'" + id + "')\"" +
            " onmouseout=\"hidePopUp('" + id + "')\"" +
            " onclick=\"view_event('" + key + "'," + l + ")\">";
          var iconImg = '';
<?php if ( $CATEGORIES_ENABLED == 'Y' ) { ?>
          if ( categories && categories.length ) {
            var catId = myEvent._category;
            if ( catId < 0 ) catId = 0 - catId;
            if ( categories[catId] && categories[catId].icon ) {
              iconImg += '<img src="' + categories[catId].icon + '" />';
            }
          }
<?php } ?>
          if ( iconImg == '' ) {
            ret += '<img src="images/event.gif" alt="." />';
          } else {
            ret += iconImg;
          }

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

// Build the HTML for the year view
function build_year_view ( year, month )
{
  var ret = "";
  try {
    var dateYmd;
    ret = prev_year_link ( year, month ) +
      next_year_link ( year, month ) +
      "<span id=\"refresh\" class=\"clickable fakebutton noprint\" onclick=\"refresh()\">" +
      '<img src="images/refresh.gif" style="vertical-align: middle;" alt="<?php etranslate('Refresh');?>" /></span>' +
      today_link() +
      "&nbsp;" +
      "<span class=\"yeartitle\">" + year + "</span>" +
      "<span id=\"yearstatus\"> </span>" +
      "<table id=\"year_main\" class=\"main\" border=\"0\" width=\"100%\" border=\"0\">";

    var d = new Date();
    var today = new Date();
    d.setYear ( year );
    for ( var n = 0; n < 12; n++ ) {
      if ( n % 4 == 0 )
        ret += "<tr>";
      ret += "<td class=\"monthblock\" valign=\"top\" align=\"center\" width=\"25%\">";
      ret += '<a href="#" onclick="load_content('+year+','+(n+1)+',1);views.expandit(2);">' +
         months[n] + "</a><br/>\n";
      ret += "<table class=\"monthtable\" border=\"0\">";

      d.setMonth ( n );
      month = n + 1;
      d.setDate ( 1 );

      var wday = d.getDay();
      var startDay = 1 - wday;
      var daysThisMonth = ( year % 4 == 0 ) ? leapDaysPerMonth[month] :
        daysPerMonth[month];

      ret += "<tr>\n";
      for ( var i = 0; i < 7; i++ ) {
        ret += "<th>" + shortWeekdays[i] + "</th>";
      }
      ret += "</tr>\n";
      for ( var i = startDay, j = 0; i <= daysThisMonth || j % 7 != 0; i++, j++ ) {
        if ( j % 7 == 0 ) ret += "<tr>";
        if ( i < 1 ) {
          ret += "<td class=\"empty dom\">&nbsp;</td>\n";
        } else if ( i > daysThisMonth ) {
          ret += "<td class=\"empty dom\">&nbsp;</td>\n";
        } else {
          ret += "<td class=\"dom\">" + i + "</td>";
        }
        if ( j % 7 == 6 ) ret += "</tr>\n";
      }

      ret += "</table>\n";
      ret += "</td>\n";
      if ( n % 4 == 3 )
        ret += "</tr>\n";
    }
    ret += "</table>\n";


  } catch ( err ) {
    alert ( "JavaScript exception:\n" + err );
  }
  return ret;
}

// Build the HTML for the Agenda view
function build_agenda_view ( year, month )
{
  var ret = "";
  try {
    ret = prev_month_link ( year, month ) +
      next_month_link ( year, month ) +
      prev_year_link ( year, month ) +
      next_year_link ( year, month ) +
      "<span id=\"refresh\" class=\"clickable fakebutton noprint\" onclick=\"refresh()\">" +
      '<img src="images/refresh.gif" style="vertical-align: middle;" alt="<?php etranslate('Refresh');?>" /></span>' +
      today_link() +
      "&nbsp;" +
      "<span class=\"monthtitle\">" + months[month-1] + " " + year + "</span>" +
      "<span id=\"agendastatus\"> </span>" +
      "<table border=\"0\">\n";

    var d = new Date();
    var today = new Date();
    d.setYear ( year );
    d.setMonth ( month - 1 );
    d.setDate ( 1 );

    var wday = d.getDay();
    var startDay = 1 - wday;
    var daysThisMonth = ( year % 4 == 0 ) ? leapDaysPerMonth[month] :
      daysPerMonth[month];

    var cnt = 0;
    for ( var i = 0; i < daysThisMonth; i++ ) {
      var key = "" + year + ( month < 10 ? "0" : "" ) + month +
        ( i < 10 ? "0": "" ) + i;
      var dateYmd = key + ( i < 10 ? "0" : "" ) + i;
      var eventArray = events[key];
      var class = cnt % 2 == 0 ? 'even' : 'odd';
      var leadIn = '';
      if ( eventArray && eventArray.length > 0 ) {
        if ( year == ( today.getYear() + 1900 ) &&
          ( month - 1 ) == today.getMonth() &&
          i == today.getDate() )
          class += ' today';
        if ( eventArray && eventArray.length > 0 )
          class += ' entry hasevents';
        class += " clickable";
        leadIn += "<td valign=\"top\" align=\"right\" class=\"" + class + "\"";
<?php if ( $can_add ) { ?>
        leadIn += ' title="<?php etranslate('Click to add entry');?>" ' +
          " onclick=\"return monthCellClickHandler(" + dateYmd + ")\"";
<?php } ?>
        leadIn += ">" + format_date ( dateYmd, true ) + "</td>\n" +
          "<td valign=\"top\" class=\"" + class + "\">";
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
          ret += leadIn + "<div class=\"event clickable\" onmouseover=\"showPopUp(event,'" + id + "')\"" +
            " onmouseout=\"hidePopUp('" + id + "')\"" +
            " onclick=\"view_event('" + key + "'," + l + ")\">";
          if ( leadIn != '' ) cnt++;
          leadIn = '';
          var iconImg = '';
<?php if ( $CATEGORIES_ENABLED == 'Y' ) { ?>
          if ( categories && categories.length ) {
            var catId = myEvent._category;
            if ( catId < 0 ) catId = 0 - catId;
            if ( categories[catId] && categories[catId].icon ) {
              iconImg += '<img src="' + categories[catId].icon + '" />';
            }
          }
<?php } ?>
          if ( iconImg == '' ) {
            ret += '<img src="images/event.gif" alt="." />';
          } else {
            ret += iconImg;
          }
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
        ret += "</td></tr>\n";
      }
    }
    ret += "</table>\n";
  } catch ( err ) {
    alert ( "JavaScript exception:\n" + err );
  }
  return ret;
}


// Build the HTML for the Day view
function build_day_view ( year, month, day )
{
  var ret = "";
  var dateYmd = year + ( month < 10 ? "0" : "" ) + month +
    ( day < 10 ? "0" : "" ) + day;
  var eventArray = events[dateYmd];

  try {
    ret = prev_day_link ( year, month, day ) +
      next_day_link ( year, month, day ) +
      prev_month_link_dayview ( year, month, day ) +
      next_month_link_dayview ( year, month, day ) +
      "<span id=\"refresh\" class=\"clickable fakebutton noprint\" onclick=\"refresh()\">" +
      '<img src="images/refresh.gif" style="vertical-align: middle;" alt="<?php etranslate('Refresh');?>" /></span>' +
      today_link() +
      "&nbsp;" +
      "<span class=\"daytitle\">" + format_date ( dateYmd, true ) +"</span>" +
      "<span id=\"daystatus\"> </span>";

    var untimedEvents = '';
    var timedEvents = '';
    for ( var l = 0; eventArray && l < eventArray.length; l++ ) {
      var myEvent = eventArray[l];
      var isTimed = ( myEvent._time >= 0 );
      var thisEvent = '';
<?php if ( $CATEGORIES_ENABLED == 'Y' ) { ?>
      // See if this event matches selected categories.
      if ( ! allCatsSelected ) {
        if ( ! eventMatchesSelectedCats ( myEvent ) )
          continue;
      }
<?php } ?>
      var id = 'popup-' + dateYmd + "-" + myEvent._id;
      thisEvent += "<div class=\"event clickable" +
        ( isTimed ? " daytimedevent" : "" ) +
        "\" onmouseover=\"showPopUp(event,'" + id + "')\"" +
        " onmouseout=\"hidePopUp('" + id + "')\"" +
        " onclick=\"view_event('" + dateYmd + "'," + l + ")\"";
var pos = '0';
      if ( isTimed ) {
        // TODO: handle overlapping events. Right now, the <div>
        // areas will overlap, possibly obscuring each other.
        // Would be nice to allow mouse-over to raise the z-index to
        // the top and have conflicting events shifted 50 pixels to
        // the right so we could always mouse over some part of the <div>.
        var mins = myEvent._localTime % 100;
        var y = ( ( myEvent._localTime - mins ) / 100 ) * 50;
        y += ( mins / 60 ) * 50;
        thisEvent += " style=\"position: absolute; left: 52px; top: " +
          y + "px;";
        if ( myEvent._duration > 0 ) {
          var h = ( myEvent._duration / 60 ) * 50;
          h = Math.ceil ( h ) - 2; // subtract 2 for border
          thisEvent += " height: " + h + "px;";
        }
        thisEvent += "\"";
      }
      thisEvent += ">";
      var iconImg = '';
<?php if ( $CATEGORIES_ENABLED == 'Y' ) { ?>
      if ( categories && categories.length ) {
        var catId = myEvent._category;
        if ( catId < 0 ) catId = 0 - catId;
        if ( categories[catId] && categories[catId].icon ) {
          iconImg += '<img src="' + categories[catId].icon + '" />';
        }
      }
<?php } ?>
      if ( iconImg == '' ) {
        thisEvent += '<img src="images/event.gif" alt="." />';
      } else {
        thisEvent += iconImg;
      }
      thisEvent += myEvent._name + "</div>";
      // Create popup
      if ( ! document.getElementById ( id ) ) {
        var popup = document.createElement('dl');
        popup.setAttribute ( 'id', id );
        popup.className = "popup";
        popup.innerHTML = "<dt>" +
          format_description ( myEvent._description ) + "</dt>";
        document.body.appendChild ( popup );
      }
      if ( isTimed ) {
        // timed event
        timedEvents += thisEvent;
      } else {
        // Untimed event
        untimedEvents += thisEvent;
      }
    }

    if ( untimedEvents != '' ) {
      ret += "<div id=\"dayuntimed\">" + untimedEvents + "</div>\n";
    }
    ret += "<div id=\"daydiv\">\n" +
      "<div id=\"dayinnerdiv\">";

    for ( var h = 0; h < 24; h++ ) {
      var y = h * 50;
      ret += "<div class=\"hourblockleft\" style=\"top: " + y + "px;\">" +
        "<span class=\"timeofday\">";
      if ( h == 0 ) ret += "12am";
      else if ( h < 12 ) ret += h + "am";
      else if ( h == 12 ) ret += "12pm";
      else ret += ( h - 12 ) + "pm";
      ret += "</span></div>\n";
      ret += "<div class=\"hourblockright\" style=\"top: " + y + "px;\"></div>";
    }
    // Now add in event info...
    ret += timedEvents;
    // End event info
    ret += "</div>\n</div>\n";
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

  var y = dateStr.substr ( 0, 4 );
  var m = dateStr.substr ( 4, 2 );
  var d = dateStr.substr ( 6, 2 );

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
    var myD = new Date();
    myD.setYear ( y );
    myD.setMonth ( m - 1 );
    myD.setDate ( d );
    wday = myD.getDay();
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

  var h = timeStr.substr ( 0, 2 );
  var m = timeStr.substr ( 2, 2 );
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
    ret = desc.replace (/\n/g,"<br />");
  }
<?php } else { ?>
  // HTML not allowed in description
  // TODO: convert URLs into active links
  ret = desc.replace (/\n/g,"<br />");
<?php } ?>
  return ret;
}

</script>

<?php

ob_end_flush();

echo print_trailer();

?>
