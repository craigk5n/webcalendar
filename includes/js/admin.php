<script type="text/javascript">
<!-- <![CDATA[
// error check the colors
function valid_color ( str ) {
  var ch, j;
  var valid = "0123456789abcdefABCDEF";

  if ( str.length == 0 )
    return true;

  if ( str.charAt ( 0 ) != '#' || str.length != 7 )
    return false;

  for ( j = 1; j < str.length; j++ ) {
   ch = str.charAt ( j );
   if ( valid.indexOf ( ch ) < 0 )
     return false;
  }
  return true;
}

function valid_form ( form ) {
  var err = "";

  if ( form.admin_server_url.value == "" ) {
    err += "<?php etranslate("Server URL is required")?>.\n";
    form.admin_server_url.select ();
    form.admin_server_url.focus ();
  }
  else if ( form.admin_server_url.value.charAt (
    form.admin_server_url.value.length - 1 ) != '/' ) {
    err += "<?php etranslate("Server URL must end with '/'")?>.\n";
    form.admin_server_url.select ();
    form.admin_server_url.focus ();
  }

  if ( parseInt ( form.admin_WORK_DAY_START_HOUR.value ) >=
    parseInt ( form.admin_WORK_DAY_END_HOUR.value ) ) {
    err += "<?php etranslate("Invalid work hours")?>.\n";
    form.admin_WORK_DAY_START_HOUR.focus ();
  }

  if ( err != "" ) {
    alert ( "Error:\n\n" + err );
    return false;
  }

  if ( ! valid_color ( form.admin_BGCOLOR.value ) ) {
    err += "<?php etranslate("Invalid color for document background")?>.\n";
    form.admin_BGCOLOR.select ();
    form.admin_BGCOLOR.focus ();
  }
  else if ( ! valid_color ( form.admin_H2COLOR.value ) ) {
    err += "<?php etranslate("Invalid color for document title")?>.\n";
    form.admin_H2COLOR.select ();
    form.admin_H2COLOR.focus ();
  } else if ( ! valid_color ( form.admin_CELLBG.value ) ) {
    err += "<?php etranslate("Invalid color for table cell background")?>.\n";
    form.admin_CELLBG.select ();
    form.admin_CELLBG.focus ();
  } else if ( ! valid_color ( form.admin_TABLEBG.value ) ) {
    err += "<?php etranslate("Invalid color for table grid")?>.\n";
    form.admin_TABLEBG.select ();
    form.admin_TABLEBG.focus ();
  } else if ( ! valid_color ( form.admin_THBG.value ) ) {
    err += "<?php etranslate("Invalid color for table header background")?>.\n";
    form.admin_THBG.select ();
    form.admin_THBG.focus ();
  } else if ( ! valid_color ( form.admin_THFG.value ) ) {
    err += "<?php etranslate("Invalid color for table text background")?>.\n";
    form.admin_THFG.select ();
    form.admin_THFG.focus ();
  } else if ( ! valid_color ( form.admin_POPUP_BG.value ) ) {
    err += "<?php etranslate("Invalid color for event popup background")?>.\n";
    form.admin_POPUP_BG.select ();
    form.admin_POPUP_BG.focus ();
  } else if ( ! valid_color ( form.admin_POPUP_FG.value ) ) {
    err += "<?php etranslate("Invalid color for event popup text")?>.\n";
    form.admin_POPUP_FG.select ();
    form.admin_POPUP_FG.focus ();
  } else if ( ! valid_color ( form.admin_TODAYCELLBG.value ) ) {
    err += "<?php etranslate("Invalid color for table cell background for today")?>.\n";
    form.admin_TODAYCELLBG.select ();
    form.admin_TODAYCELLBG.focus ();
  }

  if ( err.length > 0 ) {
    alert ( "Error:\n\n" + err + "\n\n<?php etranslate("Color format should be '#RRGGBB'")?>" );
    return false;
  }
  return true;
}
function selectColor ( color ) {
  url = "colors.php?color=" + color;
  var colorWindow = window.open(url,"ColorSelection","width=390,height=350,resizable=yes,scrollbars=yes");
}


// Gets called on page load and when user changes setting for
// "Allow public access".
function public_handler () {
  var enabled = document.prefform.admin_public_access[0].checked;
  //alert ( "public enabled =  " + enabled );
  if ( enabled ) {
    // Public Access enabled
    makeVisible ( "pa1" );
    makeVisible ( "pa2" );
    makeVisible ( "pa3" );
    makeVisible ( "pa4" );
    makeVisible ( "pa5" );
    makeVisible ( "pa6" );
  } else {
    // Public Access disabled
    makeInvisible ( "pa1" );
    makeInvisible ( "pa2" );
    makeInvisible ( "pa3" );
    makeInvisible ( "pa4" );
    makeInvisible ( "pa5" );
    makeInvisible ( "pa6" );
  }
}

// Gets called on page load and when user changes setting for
// "Allow external users".
function eu_handler () {
  var enabled = document.prefform.admin_allow_external_users[0].checked;
  //alert ( "allow external =  " + enabled );
  if ( enabled ) {
    // External Users enabled
    makeVisible ( "eu1" );
    makeVisible ( "eu2" );
    makeVisible ( "eu3" );
    makeVisible ( "eu4" );
  } else {
    makeInvisible ( "eu1" );
    makeInvisible ( "eu2" );
    makeInvisible ( "eu3" );
    makeInvisible ( "eu4" );
  }

}


// Gets called on page load and when user changes setting for
// "Email enabled".
function email_handler () {
  var enabled = document.prefform.admin_send_email[0].checked;
  //alert ( "allow external =  " + enabled );
  if ( enabled ) {
    // Email enabled
    makeVisible ( "em1" );
    makeVisible ( "em2" );
    makeVisible ( "em3" );
    makeVisible ( "em4" );
    makeVisible ( "em5" );
    makeVisible ( "em6" );
    makeVisible ( "em7" );
  } else {
    makeInvisible ( "em1" );
    makeInvisible ( "em2" );
    makeInvisible ( "em3" );
    makeInvisible ( "em4" );
    makeInvisible ( "em5" );
    makeInvisible ( "em6" );
    makeInvisible ( "em7" );
  }
}


//]]> -->
</script>
