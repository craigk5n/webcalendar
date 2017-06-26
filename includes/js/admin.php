<?php /* $Id: admin.php,v 1.51.2.2 2007/08/06 02:28:27 cknudsen Exp $ */
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );
?>
function init_admin () {
  attach_handler ();
  comment_handler ();
  email_handler ();
  eu_handler ();
  popup_handler ();
  public_handler ();
  sr_handler ();
  altps();
  
  return true;
}

function valid_form ( form ) {
  var err = '';

  if ( form.admin_SERVER_URL.value == '' ) {
    err += "<?php etranslate ( 'Server URL is required.', true)?>\n";
    form.admin_SERVER_URL.select ();
    form.admin_SERVER_URL.focus ();
  }
  else if ( form.admin_SERVER_URL.value.charAt (
    form.admin_SERVER_URL.value.length - 1 ) != '/' ) {
    err += "<?php etranslate ( 'Server URL must end with /.', true ) ?>\n";
    form.admin_SERVER_URL.select ();
    form.admin_SERVER_URL.focus ();
  }

  if ( parseInt ( form.admin_WORK_DAY_START_HOUR.value ) >=
    parseInt ( form.admin_WORK_DAY_END_HOUR.value ) ) {
    err += "<?php etranslate ( 'Invalid work hours.', true)?>\n";
    form.admin_WORK_DAY_START_HOUR.focus ();
  }

  if ( err != '' ) {
    alert ( "<?php etranslate ( 'Error', true ) ?>:\n\n" + err );
    return false;
  }

  if ( ! valid_color ( form.admin_BGCOLOR.value ) ) {
    err += "<?php etranslate ( 'Invalid color for document background.', true ) ?>\n";
    form.admin_BGCOLOR.select ();
    form.admin_BGCOLOR.focus ();
  }
  else if ( ! valid_color ( form.admin_H2COLOR.value ) ) {
    err += "<?php etranslate ( 'Invalid color for document title.', true ) ?>\n";
    form.admin_H2COLOR.select ();
    form.admin_H2COLOR.focus ();
  } else if ( ! valid_color ( form.admin_CELLBG.value ) ) {
    err += "<?php etranslate ( 'Invalid color for table cell background.', true ) ?>\n";
    form.admin_CELLBG.select ();
    form.admin_CELLBG.focus ();
  } else if ( ! valid_color ( form.admin_TABLEBG.value ) ) {
    err += "<?php etranslate ( 'Invalid color for table grid.', true ) ?>\n";
    form.admin_TABLEBG.select ();
    form.admin_TABLEBG.focus ();
  } else if ( ! valid_color ( form.admin_THBG.value ) ) {
    err += "<?php etranslate ( 'Invalid color for table header background.', true ) ?>\n";
    form.admin_THBG.select ();
    form.admin_THBG.focus ();
  } else if ( ! valid_color ( form.admin_THFG.value ) ) {
    err += "<?php etranslate ( 'Invalid color for table text background.', true ) ?>\n";
    form.admin_THFG.select ();
    form.admin_THFG.focus ();
  } else if ( ! valid_color ( form.admin_POPUP_BG.value ) ) {
    err += "<?php etranslate ( 'Invalid color for event popup background.', true ) ?>\n";
    form.admin_POPUP_BG.select ();
    form.admin_POPUP_BG.focus ();
  } else if ( ! valid_color ( form.admin_POPUP_FG.value ) ) {
    err += "<?php etranslate ( 'Invalid color for event popup text.', true ) ?>\n";
    form.admin_POPUP_FG.select ();
    form.admin_POPUP_FG.focus ();
  } else if ( ! valid_color ( form.admin_TODAYCELLBG.value ) ) {
    err += "<?php
     etranslate ( 'Invalid color for table cell background for today.', true ) ?>\n";
    form.admin_TODAYCELLBG.select ();
    form.admin_TODAYCELLBG.focus ();
  }

  if ( err.length > 0 ) {
    alert ( "<?php etranslate ( 'Error', true ) ?>:\n\n" + err + "\n\n<?php
  etranslate ('Color format should be RRGGBB.', true ) ?>" );
    return false;
  }
  return true;
}

// Gets called on page load and when user changes setting for
// "Disable popup".
function popup_handler () {
  var noPopups = document.prefform.admin_DISABLE_POPUPS[0].checked;

  if ( noPopups ) {
    // Popups disabled
    makeInvisible ( 'pop' );
  } else {
    // Popups disabled
    makeVisible ( 'pop' );
  }
}

// Gets called on page load and when user changes setting for
// "Allow public access".
function public_handler () {
  var enabled = document.prefform.admin_PUBLIC_ACCESS[0].checked;

  if ( enabled ) {
    // Public Access enabled
    makeVisible ( 'pa' );
  } else {
    // Public Access disabled
    makeInvisible ( 'pa' );
  }
}

// Gets called on page load and when user changes setting for
// "Allow external users".
function eu_handler () {
  var enabled = document.prefform.admin_ALLOW_EXTERNAL_USERS[0].checked;

  if ( enabled ) {
    // External Users enabled
    makeVisible ( 'eu' );
  } else {
    makeInvisible ( 'eu' );
  }
}

// Gets called on page load and when user changes setting for
// "Allow self registration".
function sr_handler () {
  var enabled = document.prefform.admin_ALLOW_SELF_REGISTRATION[0].checked;

  if ( enabled ) {
    // Self Registration enabled
    makeVisible ( 'sr' );
  } else {
    makeInvisible ( 'sr' );
  }
}

// Gets called on page load and when user changes setting for
// "Allow attachments".
function attach_handler () {
  var enabled = document.prefform.admin_ALLOW_ATTACH[0].checked;

  if ( enabled ) {
    makeVisible ( 'at1' );
  } else {
    makeInvisible ( 'at1' );
  }
}

// Gets called on page load and when user changes setting for
// "Allow comments".
function comment_handler () {
  var enabled = document.prefform.admin_ALLOW_COMMENTS[0].checked;

  if ( enabled ) {
    makeVisible ( 'com1' );
  } else {
    makeInvisible ( 'com1' );
  }
}

// Gets called on page load and when user changes setting for
// "Email enabled".
function email_handler () {
  var
    enabled = document.prefform.admin_SEND_EMAIL[0].checked,
    mailer = document.prefform.admin_EMAIL_MAILER.selectedIndex,
    auth = document.prefform.admin_SMTP_AUTH[0].checked;

  if ( enabled ) {
    // Email enabled
    makeVisible ( 'em' );
    if ( mailer == 0 ) {
      makeVisible ( 'em_smtp' );
      if ( auth ) {
        makeVisible ( 'em_auth' )
      } else {
        makeInvisible ( 'em_auth' )
      }
    } else {
      makeInvisible ( 'em_smtp' );
    }
  } else {
    makeInvisible ( 'em' );
  }
}

<?php //see the showTab function in includes/js/visible.php for common code shared by all pages
 //using the tabbed GUI.
?>var tabs = new Array (
  '',
  'settings',
  'public',
  'uac',
  'groups',
  'nonuser',
  'other',
  'email',
  'colors'
);
//]]> -->

function showPreview () {
  var
    theme = document.forms['prefform'].admin_THEME.value,
    tmp = theme.toLowerCase ();

  if ( theme == 'none' )
    return false;

  url = 'themes/' + tmp  + '.php';
  var previewWindow = window.open ( url,"Preview","resizable=yes,scrollbars=yes" );
}

function setTab ( tab ) {
  document.forms['prefform'].currenttab.value = tab;
  showTab ( tab );
  return false;
}
