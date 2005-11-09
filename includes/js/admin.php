<script type="text/javascript">
<!-- <![CDATA[
// error check the colors
function valid_color ( str ) {
 var validColor = /^#[0-9a-fA-F]{3}$|^#[0-9a-fA-F]{6}$/;

 return validColor.test ( str );
}

function valid_form ( form ) {
  var err = "";

  if ( form.admin_SERVER_URL.value == "" ) {
    err += "<?php etranslate("Server URL is required", true)?>.\n";
    form.admin_SERVER_URL.select ();
    form.admin_SERVER_URL.focus ();
  }
  else if ( form.admin_SERVER_URL.value.charAt (
    form.admin_SERVER_URL.value.length - 1 ) != '/' ) {
    err += "<?php etranslate("Server URL must end with '/'", true)?>.\n";
    form.admin_SERVER_URL.select ();
    form.admin_SERVER_URL.focus ();
  }

  if ( parseInt ( form.admin_WORK_DAY_START_HOUR.value ) >=
    parseInt ( form.admin_WORK_DAY_END_HOUR.value ) ) {
    err += "<?php etranslate("Invalid work hours", true)?>.\n";
    form.admin_WORK_DAY_START_HOUR.focus ();
  }

  if ( err != "" ) {
    alert ( "<?php etranslate ( "Error", true ) ?>" + ":\n\n" + err );
    return false;
  }

  if ( ! valid_color ( form.admin_BGCOLOR.value ) ) {
    err += "<?php etranslate("Invalid color for document background", true)?>.\n";
    form.admin_BGCOLOR.select ();
    form.admin_BGCOLOR.focus ();
  }
  else if ( ! valid_color ( form.admin_H2COLOR.value ) ) {
    err += "<?php etranslate("Invalid color for document title", true)?>.\n";
    form.admin_H2COLOR.select ();
    form.admin_H2COLOR.focus ();
  } else if ( ! valid_color ( form.admin_CELLBG.value ) ) {
    err += "<?php etranslate("Invalid color for table cell background", true)?>.\n";
    form.admin_CELLBG.select ();
    form.admin_CELLBG.focus ();
  } else if ( ! valid_color ( form.admin_TABLEBG.value ) ) {
    err += "<?php etranslate("Invalid color for table grid", true)?>.\n";
    form.admin_TABLEBG.select ();
    form.admin_TABLEBG.focus ();
  } else if ( ! valid_color ( form.admin_THBG.value ) ) {
    err += "<?php etranslate("Invalid color for table header background", true)?>.\n";
    form.admin_THBG.select ();
    form.admin_THBG.focus ();
  } else if ( ! valid_color ( form.admin_THFG.value ) ) {
    err += "<?php etranslate("Invalid color for table text background", true)?>.\n";
    form.admin_THFG.select ();
    form.admin_THFG.focus ();
  } else if ( ! valid_color ( form.admin_POPUP_BG.value ) ) {
    err += "<?php etranslate("Invalid color for event popup background", true)?>.\n";
    form.admin_POPUP_BG.select ();
    form.admin_POPUP_BG.focus ();
  } else if ( ! valid_color ( form.admin_POPUP_FG.value ) ) {
    err += "<?php etranslate("Invalid color for event popup text", true)?>.\n";
    form.admin_POPUP_FG.select ();
    form.admin_POPUP_FG.focus ();
  } else if ( ! valid_color ( form.admin_TODAYCELLBG.value ) ) {
    err += "<?php etranslate("Invalid color for table cell background for today", true)?>.\n";
    form.admin_TODAYCELLBG.select ();
    form.admin_TODAYCELLBG.focus ();
  }

  if ( err.length > 0 ) {
    alert ( "<?php etranslate ( "Error", true ) ?>" + ":\n\n" + err + "\n\n<?php etranslate("Color format should be '#RRGGBB'", true)?>" );
    return false;
  }
  return true;
}
function selectColor ( color ) {
  url = "colors.php?color=" + color;
  var colorWindow = window.open(url,"ColorSelection","width=390,height=350,resizable=yes,scrollbars=yes");
}

// Updates the background-color of a table cell
// Parameters:
//    input - <input> element containing the new color value
// Note: this function relies on the following structure:
//   <td><input onkeyup="updateColor(this);" /></td>
//   <td>(this is the cell to be updated)</td>
function updateColor ( input ) {
 // The cell to be updated
 var colorCell = input.parentNode.nextSibling;
 // The new color
 var color = input.value;

 if (!valid_color ( color ) ) {
   // Color specified is invalid; use black instead
  colorCell.style.backgroundColor = "#000000";
 } else {
  colorCell.style.backgroundColor = color;
 }
}

// Gets called on page load and when user changes setting for
// "Allow public access".
function public_handler () {
  var enabled = document.prefform.admin_PUBLIC_ACCESS[0].checked;
  var ohd = document.prefform.admin_PUBLIC_ACCESS_OTHERS[0].checked;
  //alert ( "public enabled =  " + enabled );
  if ( enabled ) {
    // Public Access enabled
    makeVisible ( "pa1" );
    makeVisible ( "pa2" );
    makeVisible ( "pa3" );
    makeVisible ( "pa4" );
    makeVisible ( "pa5" );
    makeVisible ( "pa6" );
    makeVisible ( "pa7" );
    makeVisible ( "pa7a" );
  } else {
    // Public Access disabled
    makeInvisible ( "pa1" );
    makeInvisible ( "pa2" );
    makeInvisible ( "pa3" );
    makeInvisible ( "pa4" );
    makeInvisible ( "pa5" );
    makeInvisible ( "pa6" );
    makeInvisible ( "pa7" );
    makeInvisible ( "pa7a" );
  }
}


// Gets called on page load and when user changes setting for
// "Allow external users".
function eu_handler () {
  var enabled = document.prefform.admin_ALLOW_EXTERNAL_USERS[0].checked;
  //alert ( "allow external =  " + enabled );
  if ( enabled ) {
    // External Users enabled
    makeVisible ( "eu1" );
    makeVisible ( "eu2" );
//    makeVisible ( "eu3" );
//    makeVisible ( "eu4" );
  } else {
    makeInvisible ( "eu1" );
    makeInvisible ( "eu2" );
//    makeInvisible ( "eu3" );
//    makeInvisible ( "eu4" );
  }
}

// Gets called on page load and when user changes setting for
// "Allow self registration".
function sr_handler () {
  var enabled = document.prefform.admin_ALLOW_SELF_REGISTRATION[0].checked;
  if ( enabled ) {
    // Self Registration enabled
    makeVisible ( "sr1" );
    makeVisible ( "sr2" );
  } else {
    makeInvisible ( "sr1" );
    makeInvisible ( "sr2" );
  }
}

// Gets called on page load and when user changes setting for
// "Email enabled".
function email_handler () {
  var enabled = document.prefform.admin_SEND_EMAIL[0].checked;
  var mailer = document.prefform.admin_EMAIL_MAILER.selectedIndex;
  var auth = document.prefform.admin_SMTP_AUTH[0].checked;

  //alert ( "allow external =  " + enabled );
  if ( enabled ) {
    // Email enabled
    makeVisible ( "em1" );
    makeVisible ( "em2" );
    if ( mailer == 0 ) {
      makeVisible ( "em3" );
      makeVisible ( "em3a" );
      makeVisible ( "em4" );
      if ( auth ) {      
        makeVisible ( "em5" )
        makeVisible ( "em6" );
      } else {
        makeInvisible ( "em5" )
        makeInvisible ( "em6" );      
      }
    } else {
      makeInvisible ( "em3" );
      makeInvisible ( "em3a" );
      makeInvisible ( "em4" );
      makeInvisible ( "em5" )
      makeInvisible ( "em6" );    
    }
    makeVisible ( "em7" );
    makeVisible ( "em8" );
    makeVisible ( "em9" );
    makeVisible ( "em10" );
    makeVisible ( "em11" );
    makeVisible ( "em12" );
  } else {
    makeInvisible ( "em1" );
    makeInvisible ( "em2" );
    makeInvisible ( "em3" );
    makeInvisible ( "em3a" );
    makeInvisible ( "em4" );
    makeInvisible ( "em5" );
    makeInvisible ( "em6" );
    makeInvisible ( "em7" );
    makeInvisible ( "em8" );
    makeInvisible ( "em9" );
    makeInvisible ( "em10" );
    makeInvisible ( "em11" );
    makeInvisible ( "em12" );
  }
}

<?php //see the showTab function in includes/js/visible.php for common code shared by all pages
 //using the tabbed GUI.
?>var tabs = new Array();
tabs[1] = "settings";
tabs[2] = "public";
tabs[3] = "uac";
tabs[4] = "groups";
tabs[5] = "nonuser";
tabs[6] = "other";
tabs[8] = "email";
tabs[9] = "colors";
//]]> -->
</script>
