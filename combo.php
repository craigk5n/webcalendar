<?php
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
 * - Honor access_can_access_function ( ACCESS_WEEK/ACCESS_MONTH/ACCESS_DAY )
 *
 * Possibilities for later:
 * - Include tab for unapproved events where users could approve from
 *   this page.
 *
 * Note: some of the icons for this page were downloaded from the following
 * page.  If you want to add more icons, check there first.
 *	http://rrze-icon-set.berlios.de/gallery.html
 * License info (Creative Commons 3.0)
 *	http://rrze-icon-set.berlios.de/licence.html
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

$user    = getValue ( 'user', '[A-Za-z0-9_\.=@,\-]*', true );
if ( ! empty ( $user ) ) {
  // Make sure this user has permission to view the other user's calendar
  if ( ! access_user_calendar( 'view', $user ) ) {
     // Not allowed.
     $user = $login;
  } 
} 

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


$bodyExtras = 'onload="onLoadInit()"';

// Add ModalBox javascript/CSS & Tab code, Auto-complete
$headExtras = '
<script type="text/javascript" src="includes/tabcontent/tabcontent.js"></script>
<link type="text/css" href="includes/tabcontent/tabcontent.css" rel="stylesheet" />
<script type="text/javascript" src="includes/js/modalbox/modalbox.js"></script>
<link rel="stylesheet" href="includes/js/modalbox/modalbox.css" type="text/css" 
media="screen" />
<script type="text/javascript" src="includes/js/autocomplete.js"></script>
';

print_header(
  array( 'js/popups.js/true', 'js/visible.php', 'js/datesel.php' ),
  $headExtras, $bodyExtras );

?>

<div class="headerinfo">
<table>
<tr>
<?php
if ( $single_user == 'N' ) {
  user_load_variables ( ! empty ( $user ) ? $user : $login, 'user_' );
  echo "<td class=\"aligntop username\"><nobr>" .
     htmlspecialchars ( $user_fullname ) . "</nobr></td>";
}
if ( $CATEGORIES_ENABLED == 'Y' ) {
  ?>
  <td class="aligntop" id="categoryselection">Categories:</td>
  <td class="aligntop" onmouseover="setCategoryVisibility(true)" onmouseout="setCategoryVisibility(false)">
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
<li><a href="#" rel="contentTasks"><?php etranslate('Tasks');?></a></li>
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

<div id="contentTasks" class="tabcontent">
<table id="tasktable">
</table>
<br/>
<span id="addtask" class="clickable fakebutton"
  onclick="taskAddPopup()"/><?php etranslate('Add Task');?></span>
</div>


</div>


<div id="viewEventDiv" style="display: none;">
<table>
  <tr><td colspan="2"><h3 id="name" class="eventName"> </h3></td></tr>
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
  <tr><td colspan="2" id="eventlink" class="aligncenter">  </td></tr>
</table>
</div>

<!-- Hidden div tag for Quick Add dialog -->
<div id="quickAddDiv" style="display: none;">
<input type="hidden" name="quickAddParticipants" id="quickAddParticipants" value="" />
<table>
<tr><td class="aligntop bold"><?php etranslate('Date');?>:</td>
  <td><?php echo datesel_Print ( 'quickAddDate', $date );?>
  </td></tr>
<tr><td class="aligntop bold"><?php etranslate('Brief Description');?>:</td>
  <td><input id="quickAddName" name="quickAddName" onfocus="this.select();" /></td></tr>
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
<tr><td class="aligntop bold"><?php etranslate('Participants');?>:</td>
  <td><span id="quickAddParticipantList"></span>
      &nbsp;&nbsp;
      <input type="text" id="quickAddNewParticipant" name="quickAddNewParticipant" size="20" /></td></tr>
<tr><td colspan="2">&nbsp;</td></tr>
<tr><td colspan="2"><input type="button" value="<?php etranslate('Save');?>" onclick="eventAddHandler()" /><br />
<span class="clickable" onclick="addEventDetail()"><?php etranslate("Add event detail");?></span>
</td></tr>
</table>
</div>

<!-- Hidden div tag for Add Task dialog -->
<div id="taskAddDiv" style="display: none;">
<form name="taskAddForm" id="taskAddForm">
<table>
<tr><td class="aligntop bold"><?php etranslate('Start Date');?>:</td>
  <td><?php echo datesel_Print ( 'task_start_date', $date );?></td></tr>
<tr><td class="aligntop bold"><?php etranslate('Due Date');?>:</td>
  <td><?php echo datesel_Print('task_due_date', $date); ?></td></tr>
<tr><td class="aligntop bold"><?php etranslate('Brief Description');?>:</td>
  <td><input id="taskAddName" name="taskAddName" /></td></tr>
<tr><td class="aligntop bold"><?php etranslate('Full Description');?>:</td>
  <td><textarea id="taskAddDescription" name="taskAddDescription"
       rows="4" cols="40" wrap="virtual"></textarea></td></tr>
<?php if ( $CATEGORIES_ENABLED == 'Y' ) { ?>
<tr><td class="aligntop bold"><?php etranslate('Category');?>:</td>
  <td><select id="taskAddCategory" name="taskAddCategory">
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
<tr><td colspan="2"><input type="button" value="<?php etranslate('Save');?>" onclick="taskAddHandler()" /><br />
<span class="clickable" onclick="taskAddDetail()"><?php etranslate("Add task detail");?></span>
</td></tr>
</table>
</form>
</div>

<script type="text/javascript">

// Called when page is loaded.
// Load events for the current month.
// Load all tasks.
function onLoadInit ()
{
  ajax_get_events('<?php echo $thisyear;?>','<?php echo intval($thismonth);?>',
    '<?php echo intval($thisday);?>');
  ajax_get_tasks();
}

// Initialize tabs
var views=new ddtabcontent("viewtabs")
views.setpersist(true)
views.setselectedClassTarget("link") //"link" or "linkparent"
views.init()
// End init tabs

var login = '<?php echo $login;?>';
var user = '<?php echo $user;?>';
var currentYear = null, currentMonth = null, currentDay = null;
var switchingToDayView = false;
// Sort mode for task table
var SORT_BY_NAME = 0, SORT_BY_DUE_DATE = 1, SORT_BY_PRIORITY = 2,
  SORT_BY_CATEGORY = 3;
var taskSortAsc = true;
var taskSortCol = SORT_BY_DUE_DATE;
var dateYmd = '';

<?php if ( $CATEGORIES_ENABLED == 'Y' ) { ?>

var allCatsSelected = true;
var selectedCats = [];
var categories = [];
<?php
  // Create a javascript array of all categories this user can see that
  // includes category name, owner, colors, global status, icon.
  foreach ( $categories as $catId => $val ) {
    if ( $catId > 0 ) {
      echo 'categories[' . $catId . '] = ' .
        '{ id : ' . $catId .
        ', state: 1' .
        ', owner: "' . $categories[$catId]['cat_owner'] . '"' .
        ', name: "' . $categories[$catId]['cat_name'] . '"' .
        ', color: "' . $categories[$catId]['cat_color'] . '"' .
        ', global: ' . ( $categories[$catId]['cat_global'] ? '0' : '1' );
      $gifIconFile =  'icons/cat-' . $catId . '.gif';
      $pngIconFile =  'icons/cat-' . $catId . '.png';
      $jpgIconFile =  'icons/cat-' . $catId . '.jpg';
      if ( file_exists ( $gifIconFile ) ) {
        echo ', icon: "' . $gifIconFile . '"';
      } else if ( file_exists ( $pngIconFile ) ) {
        echo ', icon: "' . $pngIconFile . '"';
      } else if ( file_exists ( $jpgIconFile ) ) {
        echo ', icon: "' . $jpgIconFile . '"';
      }
      echo " };\n";
    }
  }
?>
<?php } ?>
var viewDialogIsVisible = false;
var quickAddDialogIsVisible = null;
var catsVisible = false;
var events = tasks = [];
// loadedMonths is used to keep track of which months we have loaded events
// for.  This prevents us from re-loading a month's events that we
// previously loaded.
var loadedMonths = []; // Key will be format "200801" For Jan 2008
// loadedTasks set to true when tasks have been loaded.  We don't load tasks
// based on date, so it is a single scalar variable rather than an array.
var loadedTasks = false;
var months = [
  <?php
    // Create javascript array of month names localized to the user's
    // language preference.
    for ( $i = 0; $i < 12; $i++ ) {
      if ( $i ) echo ", ";
      echo "'" . month_name ( $i ) . "'";
    }
  ?>
  ];
var shortMonths = [
  <?php
    // Create javascript array of shortened month names localized to the user's
    // language preference.
    for ( $i = 0; $i < 12; $i++ ) {
      if ( $i ) echo ", ";
      echo "'" . month_name ( $i, 'M' ) . "'";
    }
  ?>
  ];
var weekdays = [
  <?php
    // Create javascript array of weekday names localized to the user's
    // language preference.
    for ( $i = 0; $i < 7; $i++ ) {
      if ( $i ) echo ", ";
      echo "'" . weekday_name ( $i, 'l' ) . "'";
    }
  ?>
  ];
var shortWeekdays = [
  <?php
    // Create javascript array of shortened weekday names localized to the
    // user's language preference.
    for ( $i = 0; $i < 7; $i++ ) {
      if ( $i ) echo ", ";
      echo "'" . weekday_name ( $i, 'D' ) . "'";
    }
  ?>
  ];
var daysPerMonth = [ <?php echo implode ( ", ", $days_per_month ); ?> ];
var leapDaysPerMonth = [ <?php echo implode ( ", ", $ldays_per_month ); ?> ];
var userLogins = [];
var userNames = [];
var users = [];
<?php
  // Create a javascript array of all users this user has access to see.
  // Note: not using this yet in the javascript anywhere....
  $users = user_get_users();
  for ( $i = 0; $i < count ( $users ); $i++ ) {
    $fname = $users[$i]['cal_fullname'];
    if ( empty ( $fname ) )
      $fname = $users[$i]['cal_login'];
    $fname = str_replace ( "'", "", $fname );
    echo 'userLogins[' . $i . '] = \'' . $users[$i]['cal_login'] . "';\n";
    echo 'userNames[' . $i . '] = \'' . $fname . "';\n";
    echo 'users["' . $users[$i]['cal_login'] . '"] = \'' . $fname . "';\n";
  }
?>

// Callback for autocomplete on usernames.  We return an object
// that includes matching user logins and names.
function autocompleteUserSearch ( q )
{
  var suggestions = [];
  var data = [];
  var cnt = 0;

  var words = q.toLowerCase().split ( ' ' );
  for ( var i = 0; i < userLogins.length; i++ ) {
    var match = 0;
    for ( var j = 0; j < words.length && ! match; j++ ) {
      var q1 = words[j];
      if ( q1.length == 0 ) {
        // ignore
      } else if ( userLogins[i].toLowerCase().indexOf ( q1 ) >= 0 ) {
        match = 1;
      } else if ( userNames[i].toLowerCase().indexOf ( q1 ) >= 0 ) {
        match = 1;
      }
    }
    if ( match ) {
      suggestions[cnt] = userNames[i];
      data[cnt] = userLogins[i];
      cnt++;
    }
  }
  var resp = { suggestions: suggestions, data: data };
  //alert('resp.suggestions=' + resp.suggestions + ", cnt=" + cnt );
  return resp;
}

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

// Load events for the specified month AND update ALL the event tabs
// (year, month, day, agenda).  We pass in the day so we know
// which day of the month to put in the day view.
// NOTE: This does not affect the "Tasks" tab.
function ajax_get_events ( year, month, day )
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

  $('contentDay').innerHTML = '<?php echo $LOADING;?>';
  $('contentWeek').innerHTML = '<?php echo $LOADING;?>';
  $('contentMonth').innerHTML = '<?php echo $LOADING;?>';
  $('contentYear').innerHTML = '<?php echo $LOADING;?>';
  $('contentAgenda').innerHTML = '<?php echo $LOADING;?>';

  new Ajax.Request('events_ajax.php',
  {
    method:'get',
    parameters: { action: 'get', startdate: startdate, user: user },
    onSuccess: function( transport ) {
      if ( ! transport.responseText ) {
        alert ( '<?php etranslate('Error');?>: <?php etranslate('no response from server');?>' + ': events_ajax.php?action=get' );
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
  switchingToDayView = false;
  return true;
}

// Load all tasks and update the "Tasks" tab accordingly.
// Note: this does not affect the event tabs (year, month, day, agenda).
function ajax_get_tasks ()
{
  if ( loadedTasks ) {
    //alert ( "Already loaded " + monthKey );
    update_task_display ();
    return;
  }
  //$('contentTasks').innerHTML = '<?php echo $LOADING;?>';

  new Ajax.Request('events_ajax.php',
  {
    method:'get',
    parameters: { action: 'gett', user: user },
    onSuccess: function( transport ) {
      if ( ! transport.responseText ) {
        alert ( '<?php etranslate('Error');?>: <?php etranslate('no response from server');?>' + ': events_ajax.php?action=gett' );
        return;
      }
      //alert ( "Get Tasks Response:\n" + transport.responseText );
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
      tasks = [];
      var i = 0;
      for ( var i = 0; i < response.tasks.length; i++ ) {
        tasks[i] = response.tasks[i];
      }
      loadedTasks = true;
      update_task_display ();
    },
    onFailure: function() { alert( '<?php etranslate( 'Error' );?>' ) }
  });
  return true;
}

// View the event
// key is the array index of the events[] object (which returns an array)
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
  function viewWindowClosed() {
    viewDialogIsVisible = false;
  }
  Modalbox.show($('viewEventDiv'), {title: '<?php etranslate('View Event');?>', width: 450, onHide: viewWindowClosed, closeString: '<?php etranslate('Cancel');?>' });
  //Modalbox.resizeToContent();
  viewDialogIsVisible = true;

  $('name').innerHTML = myEvent._name;
  $('description').innerHTML = format_description ( myEvent._description );
  $('date').innerHTML = format_date ( myEvent._localDate, true );
  $('time').innerHTML = format_time ( myEvent._localTime, false );
  $('updated').innerHTML = format_date ( myEvent._localDate, false ) + ' ' +
    format_time ( myEvent._modtime, false ) + ' GMT';
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
        alert ( '<?php etranslate('Error');?>: <?php etranslate('no response from server');?>' + ': events_ajax.php?action=eventinfo&id=' + myEvent._id );
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

// Update the day, week, month and agenda content (but not the tasks which
// are loaded by a different ajax call).
// This is called from ajax_get_events (which loads new event data) and
// the callbacks for selecting categories.
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

  var today = new Date ();
  dateYmd = "" + year;
  if ( month < 10 )
    dateYmd += '0';
  dateYmd += "" + month;
  if ( day < 10 )
    dateYmd += '0';
  dateYmd += "" + day;
}

// Update the task display.
function update_task_display ()
{
  build_task_view ();
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
  return "<span id=\"prevday\" class=\"clickable fakebutton noprint\" onclick=\"ajax_get_events(" +
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
  return "<span id=\"nextday\" class=\"clickable fakebutton noprint\" onclick=\"ajax_get_events(" +
    year + "," + month + "," + day + ")\">&gt;</span>";
}
function prev_month_link_dayview ( year, month, day )
{
  month--;
  if ( month < 1 ) {
    month = 12;
    year--;
  }
  return "<span id=\"prevmonthdayview\" class=\"clickable fakebutton noprint\" onclick=\"ajax_get_events(" +
    year + "," + month + "," + day + ")\">&lt;&lt;</span>";
}
function next_month_link_dayview ( year, month, day )
{
  month++;
  if ( month > 12 ) {
    month = 1;
    year++;
  }
  return "<span id=\"nextmonthdayview\" class=\"clickable fakebutton noprint\" onclick=\"ajax_get_events(" +
    year + "," + month + "," + day + ")\">&gt;&gt;</span>";
}

function prev_month_link ( year, month )
{
  var m, y;
  if ( month == 1 ) {
    m = 12;
    y = parseInt(year) - 1;
  } else {
    m = parseInt(month) - 1;
    y = year;
  }
  return '<span id="prevmonth" class="clickable noprint" onclick="ajax_get_events(' +
    y + ',' + m + ',1)"><img src="images/combo-prev.png" alt="' +
    shortMonths[m-1] + '"/></span>';
}

function next_month_link ( year, month )
{
  var m, y;
  if ( month == 12 ) {
    m = 1;
    y = parseInt(year) + 1;
  } else {
    m = parseInt(month) + 1;
    y = year;
  }
  return '<span id="nextmonth" class="clickable noprint" onclick="ajax_get_events(' +
    y + ',' + m + ',1)"><img src="images/combo-next.png" alt="' + shortMonths[m-1] + '"/></span>';
}

// Build a table of quick links to all the months in the current
// year and a link to the next and previous years.
function month_view_nav_links ( year, month )
{
  var ret, i;

  ret = '<table class="noprint monthnavlinks">';
  ret += '<tr><td rowspan="2" class="aligncenter clickable" onclick="ajax_get_events(' + (parseInt(year)-1) +
      ',' + month + ',1)">' +
    '<img src="images/combo-prev.png"/><br/>' + (year-1) + '</td>';
  for ( i = 1; i <= 6; i++ ) {
    ret += '<td class="';
    if ( i == month )
      ret += 'currentMonthLink ';
    ret += 'clickable" onclick="ajax_get_events(' + year +
      ',' + i + ',1)">' + shortMonths[i-1] + '</td>';
  }
  ret += '<td rowspan="2" class="aligncenter clickable" onclick="ajax_get_events(' + (parseInt(year)+1) +
      ',' + parseInt(month) + ',1)">' +
    '<img src="images/combo-next.png"/><br/>' + (parseInt(year)+1) + '</td>';
  // Add link to today
  var today = new Date();
  var d = today.getDate();
  var m = today.getMonth() + 1;
  var y = today.getYear() + 1900;
  ret += '<td rowspan="2" class="aligncenter clickable" onclick="ajax_get_events(' +
    y + ',' + m + ',' + d + ')">' +
   '<img src="images/combo-today.png" style="vertical-align: middle;" />'
   + "<br/><?php etranslate('Today');?></td></tr>";
  // Jul - Dec
  for ( i = 7; i <= 12; i++ ) {
    ret += '<td class="';
    if ( i == month )
      ret += 'currentMonthLink ';
    ret += 'clickable" onclick="ajax_get_events(' + year +
      ',' + i + ',1)">' + shortMonths[i-1] + '</td>';
  }

  ret += '</table>';

  return ret;
}

function prev_year_link ( year, month )
{
  year = parseInt(year);
  return "<span id=\"prevyear\" class=\"clickable fakebutton noprint\" onclick=\"ajax_get_events(" + ( year - 1 ) +
    "," + month + ",1)\">&lt;&lt;" + ( year -1  ) + "</span>";
}

function next_year_link ( year, month )
{
  year = parseInt(year);
  return "<span id=\"nextyear\" class=\"clickable fakebutton noprint\" onclick=\"ajax_get_events(" + ( year + 1 ) +
    "," + month + ",1)\">" + ( year + 1 ) + "&gt;&gt;</span>";
}

function today_link()
{
  var today = new Date();
  var d = today.getDate();
  var m = today.getMonth() + 1;
  var y = today.getYear() + 1900;
  return "<span class=\"clickable fakebutton noprint\" onclick=\"ajax_get_events(" +
    y + "," + m + "," + d + ")\">" +
   '<img src="images/combo-today.png" style="vertical-align: middle;" />'
   + " <?php etranslate('Today');?></span>";
}

// Callback for the user clicking on a cell in the month view, which
// will allow the user to create a new event.
function monthCellClickHandler ( dateYmd )
{
  // Make sure user has not opened the view dialog. When a user clicks
  // on an event to view it, we will still receive the onclick event for
  // the td cell onclick handler below it.
  if ( viewDialogIsVisible )
    return;
  // If user clicked on the day in the month view, we are switching to
  // the day view, so ignore the click event.
  if ( switchingToDayView )
    return;
  function addWindowClosed() {
    quickAddDialogIsOpen = false;
  }
  // Display quick add popup
  Modalbox.show($('quickAddDiv'), {title: '<?php etranslate('Add Entry');?>', width: <?php echo $quick_add_width;?>, transitions: false, onHide: addWindowClosed, closeString: '<?php etranslate('Cancel');?>' });
  Modalbox.resizeToContent();

  $('quickAddName').setAttribute ( 'value', "<?php etranslate('Unnamed Event');?>" );
  $('quickAddName').select();
  $('quickAddName').focus();
  $('quickAddDescription').innerHTML = "";
  $('quickAddDate_YMD').setAttribute ( 'value', dateYmd );
  $('quickAddDate_fmt').innerHTML = format_date ( "" + dateYmd, true );
  $('quickAddCategory').selectedIndex = 0;
  $('quickAddParticipants').value = '<?php echo $login;?>';
  buildHiddenParticipantList ();
  // Initialize auto-complete of username
  new Autocomplete('quickAddNewParticipant', {
    //serviceUrl:'users_ajax.php',
    localServiceFunction: autocompleteUserSearch,
    maxHeight:400,
    width:300,
    deferRequestBy:100,
    // callback function:
    onSelect: function(value, data){
        //alert('data= "' + data + '", value="' + value + '"' );
        quickAddNewParticipant ( data );
      }
  });
}

// Add the specified user to the list of participants.
function quickAddNewParticipant ( username )
{
  var value = $('quickAddParticipants').value;
  if ( value.length > 0 )
    value += ',';
  value += username;
  $('quickAddParticipants').value = value;
  buildHiddenParticipantList ();
  // Clear out text input
  $('quickAddNewParticipant').value = '';
}

function buildHiddenParticipantList ()
{
  var html = '';
  var ar = $('quickAddParticipants').value.split ( ',' );
  ar.sort ();
  for ( var i = 0; i < ar.length; i++ ) {
    if ( ar[i].length > 0 )
      html += quickAddBuildUserElement ( ar[i] );
  }
  $('quickAddParticipantList').innerHTML = html;
}

// Remove the specified login from the list of participants in
// the quick add dialog.
function quickAddRemoveUser ( login )
{
  var newv = [];
  var cnt = 0;

  var value = $('quickAddParticipants').value;
  var logins = value.split ( ',' );
  for ( var i = 0; i < logins.length; i++ ) {
    if ( logins[i] != login ) {
      newv[cnt] = logins[i];
      cnt++;
    }
  }
  $('quickAddParticipants').value = newv;
  buildHiddenParticipantList ();
}

function quickAddBuildUserElement ( login )
{
  var ret = '<span class="participant" id="p_' + login +
    '">' + login + '<span class="partX">' +
    '<img src="images/close.png" onclick="quickAddRemoveUser(\'' +
    login + '\')" alt="x" />' +
    '</span></span>';
  return ret;
}

// Handler for user clicking the "Save" button in the Add Event dialog
// window.
function eventAddHandler()
{
  var name = $('quickAddName').value;
  var description = $('quickAddDescription').value;
  var dateYmd = $('quickAddDate_YMD').value;
  var participants = $('quickAddParticipants').value;
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
      name: name, description: description,
      participants: participants<?php if ( $CATEGORIES_ENABLED == 'Y' ) { echo ', category: category';} ?> },
    onSuccess: function( transport ) {
      if ( ! transport.responseText ) {
        alert ( '<?php etranslate('Error');?>: <?php etranslate('no response from server');?>' + ': events_ajax.php?action=addevent' );
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
      ajax_get_events ( currentYear, currentMonth, currentDay );
    },
    onFailure: function() { alert( '<?php etranslate( 'Error' );?>' ) }
  });
}

// Send the user to a new page where they can create an event with more
// advanced options (select other participants, repeating, reminders, etc.)
function addEventDetail()
{
  var url = 'edit_entry.php?date=' + $('quickAddDate_YMD').value;
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

// Callback for the user pressing the "Add Task" button.
// Show the "Add Task" popup dialog allowing the user to create a new task.
function taskAddPopup ()
{
  var today = new Date ();
  var ymd = "" + ( today.getYear () + 1900 );
  if ( today.getMonth() - 1 < 10 )
    ymd += '0';
  ymd += ( today.getMonth() + 1 );
  if ( today.getDate() < 10 )
    ymd += '0';
  ymd += today.getDate ();

  Modalbox.show($('taskAddDiv'), {title: '<?php etranslate('Add Task');?>', width: <?php echo $quick_add_width;?>, transitions: false, closeString: '<?php etranslate('Cancel');?>' });
  Modalbox.resizeToContent();

  $('taskAddName').setAttribute ( 'value', "<?php etranslate('Unnamed Task');?>" );
  $('taskAddName').select();
  $('taskAddName').focus();
  $('taskAddDescription').innerHTML = "";
  var dateStr = "" + ( today.getMonth() + 1 ) + "/" + today.getDate() +
    "/" + ( today.getYear() + 1900 );
  //$('taskAddStartDate').setAttribute ( 'value', dateStr );
  //$('taskAddDueDate').setAttribute ( 'value', dateStr );
  //$('taskAddDateFormatted').innerHTML = format_date ( "" + dateYmd, true );
  $('taskAddCategory').selectedIndex = 0;
}

// Handler for user click the "Save" button in the Add Task dialog
// window.
function taskAddHandler()
{
  var name = $('taskAddName').value;
  var description = $('taskAddDescription').value;
  var startDate = $('task_start_date_YMD').value;
  var dueDate = $('task_due_date_YMD').value;
<?php if ( $CATEGORIES_ENABLED == 'Y' ) { ?>
  var catObj = $('taskAddCategory');
  var category = catObj.options[catObj.selectedIndex].value;
<?php } ?>
  //alert ( 'name: ' + name + '\ndescription: ' + description +
  //  '\ndate: ' + dateYmd + '\ncategory: ' + category );
  new Ajax.Request('events_ajax.php',
  {
    method:'post',
    parameters: { action: 'addtask', startdate: startDate,
      duedate: dueDate, name: name,
      description: description<?php if ( $CATEGORIES_ENABLED == 'Y' ) { echo ', category: category';} ?> },
    onSuccess: function( transport ) {
      if ( ! transport.responseText ) {
        alert ( '<?php etranslate('Error');?>: <?php etranslate('no response from server');?>' + ': events_ajax.php?action=addtask' );
        return;
      }
      //alert ( "Response:\n" + transport.responseText );
      try  {
        var response = transport.responseText.evalJSON();
      } catch ( err ) {
        alert ( '<?php etranslate('Error');?>: <?php etranslate('JSON error');?> - ' + err + "\n\n" + transport.responseText );
        return;
      }
      if ( response.error ) {
        alert ( '<?php etranslate('Error');?>: '  + response.message );
        return;
      }
      // Successfully added :-)
      //alert('Task added');
      Modalbox.hide ();

      // force reload of data.
      // may need to get tasks when we start showing task due dates
      // in calendar...
      //var monthKey = "" + currentYear + ( currentMonth < 10 ? "0" : "" ) + currentMonth;
      //loadedMonths[monthKey] = 0;
      //ajax_get_events ( currentYear, currentMonth, currentDay );
      loadedTasks = false;
      ajax_get_tasks ();
    },
    onFailure: function() { alert( '<?php etranslate( 'Error' );?>' ) }
  });
}

// Forget all events, reload events for the current month, and update
// the display accordingly.
function refresh()
{
  loadedMonths = []; // forget all events...
  ajax_get_events ( currentYear, currentMonth, currentDay );
}


// Build the HTML for the month view
function build_month_view ( year, month )
{
  var ret = "";
  //if ( month.substring ( 0, 1 ) == '0' )
  //  month = month.substring ( 1 );
  try {
    var dateYmd;
    ret = '<table><tr><td class="aligncenter" width="70%">' +
      '<table><tr><td width="30%" class="alignright">' +
      prev_month_link ( year, month ) + '</td><td width="40%" class="aligncenter">' +
      '<span class="monthtitle">' + months[month-1] + " " + year + "</span>" +
      '<span id="monthstatus"> </span>' +
      '</td><td width="30%" class="alignleft">' +
      next_month_link ( year, month ) +
      '</td></tr></table>' +
      '</td><td class="alignright">' +
       month_view_nav_links ( year, month ) +
      '</td></tr></table>' +
      "<table id=\"month_main\" class=\"main\"  border=\"1\"><tr>";
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
        var className = ( j % 7 == 0 || j % 7 == 6 ) ? 'weekend' : '';
        if ( year == ( today.getYear() + 1900 ) &&
          ( month - 1 ) == today.getMonth() &&
          i == today.getDate() )
          className = 'today';
        // The following two lines will change the cell background to indicate
        // that there are events on that day.
        //if ( eventArray && eventArray.length > 0 )
        //  className += ' entry hasevents';
        ret += "<td class=\"" + className + "\"";
<?php if ( $can_add ) { ?>
        ret += " onclick=\"return monthCellClickHandler(" + key + ")\"";
<?php } ?>
        ret += "><span class=\"dayofmonth\">" +
          '<a href="#" onclick="switchingToDayView=true;ajax_get_events('+year+','+month+','+i+');views.expandit(0);">' + i + "</a></span><br />";
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
          var iconImg = '';
          var catColorClass = 'cat_none';
<?php if ( $CATEGORIES_ENABLED == 'Y' ) { ?>
          if ( categories && categories.length ) {
            var catId = myEvent._category;
            if ( catId < 0 ) catId = 0 - catId;
            if ( categories[catId] && categories[catId].icon ) {
              iconImg += '<img src="' + categories[catId].icon + '" />';
            }
            if ( categories[catId] && categories[catId].color ) {
              catColorClass = "cat_" + catId;
            }
          }
<?php } ?>
          var id = 'popup-' + key + "-" + myEvent._id;

          var eventRet = "<div class=\"event clickable " + catColorClass +
            "\" onmouseover=\"showPopUp(event,'" + id + "')\"" +
            " onmouseout=\"hidePopUp('" + id + "')\"" +
            " onclick=\"view_event('" + key + "'," + l + ")\"";
          //if ( catColor.length )
          //  eventRet += ' "style=background-color:' + catColor + ';"';
          eventRet += ">";
          if ( iconImg == '' ) {
            //eventRet += '<img src="images/event.gif" alt="." />';
          } else {
            eventRet += iconImg;
          }
          eventRet += '<span class="eventname">';

          // Display time of event
          if ( myEvent._localTime > 0 ) {
            eventRet += format_time ( myEvent._localTime, true );
<?php if ( $DISPLAY_END_TIMES == 'Y' ) { ?>
            eventRet += '-' + format_time (
              add_time_duration ( myEvent._localTime, myEvent._duration ),
              true );
<?php } ?>
            eventRet += '<?php echo $TIME_SPACER;?>';
          }

          eventRet += myEvent._name;
          //eventRet += "cat=" + myEvent._category;
          eventRet += "</span></div>";
          ret += month_view_event ( eventRet );
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

// Right now this doesn't do much.  We may use it in the future to
// add rounded corners.  Most I've tried don't work well in this layout :-(
// Most rounded corner implementations want static content 8-(
function month_view_event ( content )
{
  return content;
}

// Build the HTML for the year view.
// Right now we are just showing dates and no event info.
// We may add event info later, but I'm not sure I want to ask for a year's
// worth of events for every user every time the come to the combo.php page.
// That could have some significant performance implications.
function build_year_view ( year, month )
{
  var ret = "";
  try {
    var dateYmd;
    ret = prev_year_link ( year, month ) +
      next_year_link ( year, month ) +
      "<span id=\"refresh\" class=\"clickable fakebutton noprint\" onclick=\"refresh()\">" +
      '<img src="images/combo-refresh.png" style="vertical-align: middle;" alt="<?php etranslate('Refresh');?>" /></span>' +
      today_link() +
      "&nbsp;" +
      "<span class=\"yeartitle\">" + year + "</span>" +
      "<span id=\"yearstatus\"> </span>" +
      "<table id=\"year_main\" class=\"main\">";

    var d = new Date();
    var today = new Date();
    d.setYear ( year );
    for ( var n = 0; n < 12; n++ ) {
      if ( n % 4 == 0 )
        ret += "<tr>";
      ret += "<td class=\"monthblock aligntop aligncenter\" width=\"25%\">";
      ret += '<a href="#" onclick="ajax_get_events('+year+','+(n+1)+',1);views.expandit(2);">' +
         months[n] + "</a><br/>\n";
      ret += "<table class=\"monthtable\">";

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
// This will display a list of all events for the specified month.
// We may want to adopt the google calendar approach where we display
// just events from today forward and give them the option to load months
// further into the future with a "More events" button at the bottom.
function build_agenda_view ( year, month )
{
  var ret = "";
  try {
    ret = prev_month_link ( year, month ) +
      next_month_link ( year, month ) +
      prev_year_link ( year, month ) +
      next_year_link ( year, month ) +
      "<span id=\"refresh\" class=\"clickable fakebutton noprint\" onclick=\"refresh()\">" +
      '<img src="images/combo-refresh.png" style="vertical-align: middle;" alt="<?php etranslate('Refresh');?>" /></span>' +
      today_link() +
      "&nbsp;" +
      "<span class=\"monthtitle\">" + months[month-1] + " " + year + "</span>" +
      "<span id=\"agendastatus\"> </span>" +
      "<table>\n";

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
      var className = cnt % 2 == 0 ? 'even' : 'odd';
      var leadIn = '';
      if ( eventArray && eventArray.length > 0 ) {
        if ( year == ( today.getYear() + 1900 ) &&
          ( month - 1 ) == today.getMonth() &&
          i == today.getDate() )
          className += ' today';
        if ( eventArray && eventArray.length > 0 )
          className += ' entry hasevents';
        className += " clickable";
        leadIn += "<td class=\"aligntop alignright\"" + className + "\"";
<?php if ( $can_add ) { ?>
        leadIn += ' title="<?php etranslate('Click to add entry');?>" ' +
          " onclick=\"return monthCellClickHandler(" + dateYmd + ")\"";
<?php } ?>
        leadIn += ">" + format_date ( dateYmd, true ) + "</td>\n" +
          "<td class=\"aligntop " + className + "\">";
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

// Callback handler for the user clicking on the column header in the task
// table.  If it is a new column, sort asc/desc dependent on the column type
// (see below).  If same column, toggle between ascending and descending.
function task_sort_handler ( col )
{
  if ( taskSortCol != col ) {
    if ( col == SORT_BY_DUE_DATE || col == SORT_BY_NAME || col == SORT_BY_CATEGORY )
      taskSortAsc = true;
    else
      taskSortAsc = false;
  } else {
    taskSortAsc = ! taskSortAsc;
  }
  taskSortCol = col;
  build_task_view ();
}

// Emulate the C strcmp function (and why doesn't JavaScript have a
// strcmp function???)  Lame, lame, lame...
// -1 if string1 comes first, 1 if string2 comes first, 0 if equal
function strcmp ( string1, string2 )
{
  // Handle null values first
  if ( string1 == null && string2 == null )
    return 0;
  if ( string1 == null )
    return -1;
  else if ( string2 == null )
    return 1;
  // Compare non-null values
  var str1 = string1.toLowerCase ();
  var str2 = string2.toLowerCase ();
  if ( str1 == str2 ) return 0;

  for ( var i = 0; i < str1.length && i < str2.length; i++ ) {
    if ( str1.charAt ( i ) < str2.charAt ( i ) )
      return -1;
    else if ( str1.charAt ( i ) > str2.charAt ( i ) )
      return 1;
  }
  if ( str1.length < str2.length )
    return -1;
  else if ( str1.length > str2.length )
    return 1;
  // Shouldn't ever reach here...
  alert ( 'strcmp bug! string1= "' + str1 + '", string2= "' + str2 + '"' );
}

function intcmp ( int1, int2 )
{
  if ( int1 == int2 )
    return 0;
  if ( int1 < int2 )
    return -1;
  else
    return 1;
}

// Compare two tasks (used to sort tasks).
function compare_tasks ( task1, task2 )
{
  if ( taskSortCol == SORT_BY_NAME ) {
    if ( taskSortAsc ) {
      return strcmp ( task1._name, task2._name );
    } else {
      return strcmp ( task2._name, task1._name );
    }
  } else if ( taskSortCol == SORT_BY_DUE_DATE ) {
    if ( taskSortAsc ) {
      return intcmp ( task1._dueDate, task2._dueDate );
    } else {
      return intcmp ( task2._dueDate, task1._dueDate );
    }
  } else if ( taskSortCol == SORT_BY_PRIORITY ) {
    if ( taskSortAsc ) {
      return intcmp ( task2._priority, task1._priority );
    } else {
      return intcmp ( task1._priority, task2._priority );
    }
  } else if ( taskSortCol == SORT_BY_CATEGORY ) {
    if ( taskSortAsc ) {
      return intcmp ( task1._category, task2._category );
    } else {
      return intcmp ( task2._category, task1._category );
    }
  }
}

// Build the HTML for the Task view
function build_task_view ()
{
  // Sort tasks first
  tasks.sort ( compare_tasks );

  var img = ['sort-none', 'sort-none', 'sort-none', 'sort-none'];
  img[taskSortCol] = ( taskSortAsc ? 'sort-up' : 'sort-down' );

  var content =
    '<tr><th class="clickable" onclick="task_sort_handler(0)"><?php etranslate('Name');?><img src="images/' + img[0] + '.png"/></th>' +
    '<th class="clickable" onclick="task_sort_handler(1)"><?php etranslate('Due Date');?><img src="images/' + img[1] + '.png"/></th>' +
    '<th class="clickable" onclick="task_sort_handler(2)"><?php etranslate('Priority');?><img src="images/' + img[2] + '.png"/></th>' +
    '<th class="clickable" onclick="task_sort_handler(3)"><?php etranslate('Category');?><img src="images/' + img[3] + '.png"/></th>' +
    '</tr>' + "\n";
  for ( var i = 0; i < tasks.length; i++ ) {
    var task = tasks[i];
    if ( ! tasks[i] || ! tasks[i]._name )
      continue;
    var iconImg = '';
    var catId = task._category;
    if ( catId && catId > 0 && categories[catId] && categories[catId].icon ) {
      iconImg += '<img src="' + categories[catId].icon + '" />';
    }
    var cl = ( i % 2 == 0 ) ? 'even' : 'odd';
    content += '<tr><td class="' + cl + '">' +
      iconImg + task._name + '</td><td class="' + cl + '">' + 
      format_date ( task._dueDate, false ) + '</td><td class="' + cl + '">';
    if ( task._priority < 4 )
      content += '<?php etranslate('High');?>';
    else if ( task._priority < 7 )
      content += '<?php etranslate('Medium');?>';
    else
      content += '<?php etranslate('Low');?>';
    content += '</td><td class="' + cl +
      '">';
      var catId = task._category;
      if ( catId < 0 ) catId = 0 - catId;
      if ( catId == 0 ) {
        content += "&nbsp;";
      } else {
        if ( categories[catId] )
          content += categories[catId].name;
        else
          content += "Unknown category (" + catId + ")";
      }
      content += '</td></tr>' + "\n";
  }
  $('tasktable').innerHTML = content;

  // some test code for testing the strcmp function.
  //alert ( 'strcmp(AAA,BBB) = ' + strcmp('AAA','BBB' ) + "\n" +
  //  'strcmp(BBB,AAA) = ' + strcmp('BBB','AAA' ) + "\n" +
  //  'strcmp(abc,ABC) = ' + strcmp('abc','ABC' ) + "\n" +
  //  'strcmp(B,AAA) = ' + strcmp('B','A' ) + "\n" +
  //  'strcmp(BBB,aaa) = ' + strcmp('BBB','aaa' ) + "\n" +
  //  'strcmp(20100801,20110801) = ' + strcmp('20100801','20110801' ) + "\n" +
  //  'strcmp(ABC,DEF) = ' + strcmp('ABC','DEF' ) + "\n" );
}


// Build the HTML for the Day view
// TODO: Handle events that overlap.  Right now, they will just cover
// each other up.
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
      '<img src="images/combo-refresh.png" style="vertical-align: middle;" alt="<?php etranslate('Refresh');?>" /></span>' +
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
// Does the specified event match the list of currently selected categories?
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

// Convenience function...
function isInArray ( val, searchArr )
{
  for ( var i = 0; i < searchArr.length; i++ ) {
    if ( searchArr[i] == val )
      return true;
  }
  return false;
}
<?php } ?>


function getUserSuggestion ( str )
{
  var ret = [];
  var cnt = 0;

  for ( var i = 0; i < userLogins.length; i++ ) {
    if ( userLogins[i].match(/str/i) ) {
      ret[cnt++] = userLogins[i];
    }
  }
  return ret;
}

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
// This is assumed to be the current local time in "HHMMSS" or "HHMM" format.
function format_time ( timeStr, abbreviate )
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
  if ( m == 0 && abbreviate )
    ret = h + ampm;
  else
    ret = h + ':' + m + ampm;
<?php } else { ?>
  ret = h + ':' + m;
<?php } ?>
  return ret;
}

// Take a HHMM formatted time and add the specified duration (in minutes)
// Return time in HHMM format
function add_time_duration ( timeStr, duration )
{
  if ( timeStr < 0 )
  return '';

  var h = timeStr.substr ( 0, 2 );
  var m = timeStr.substr ( 2, 2 );

  while ( duration > 60 ) {
    h++;
    duration -= 60;
  }
  m += duration;
  if ( m >= 60 ) {
    h++;
    m -= 60;
  }
  if ( h >= 24 )
    h -= 24;

  var ret = '';
  if ( h < 10 )
    ret = '0';
  ret += "" + h;
  if ( m < 10 )
    ret += "0";
  ret += "" + m;

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

echo print_trailer();

?>
