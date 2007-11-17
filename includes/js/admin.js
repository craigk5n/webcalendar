/* $Id$  */
initPhpVars( 'admin' );

function init_admin () {
  attach_handler ();
  comment_handler ();
  email_handler ();
  eu_handler ();
  popup_handler ();
  sr_handler ();
	altrows();
}

function valid_form ( form ) {
  var err = "";

  if ( form.admin_SERVER_URL.value == "" ) {
    err += SERVER_URL;
    form.admin_SERVER_URL.select ();
    form.admin_SERVER_URL.focus ();
  }
  else if ( form.admin_SERVER_URL.value.charAt (
    form.admin_SERVER_URL.value.length - 1 ) != '/' ) {
    err += SERVER_URL_END;
    form.admin_SERVER_URL.select ();
    form.admin_SERVER_URL.focus ();
  }

  if ( parseInt ( form.admin_WORK_DAY_START_HOUR.value ) >=
    parseInt ( form.admin_WORK_DAY_END_HOUR.value ) ) {
    err += WORK_DAY_END_HOUR;
    form.admin_WORK_DAY_START_HOUR.focus ();
  }

  if ( err != "" ) {
    alert ( Error + ":\n\n" + err );
    return false;
  }

  if ( ! valid_color ( form.admin_BGCOLOR.value ) ) {
    err += BGCOLOR;
    form.admin_BGCOLOR.select ();
    form.admin_BGCOLOR.focus ();
  } else if ( ! valid_color ( form.admin_H2COLOR.value ) ) {
    err += H2COLOR;
    form.admin_H2COLOR.select ();
    form.admin_H2COLOR.focus ();
  } else if ( ! valid_color ( form.admin_TEXTCOLOR.value ) ) {
    err += TEXTCOLOR;
    form.admin_TEXTCOLOR.select ();
    form.admin_TEXTCOLOR.focus ();
  } else if ( ! valid_color ( form.admin_MYEVENTS.value ) ) {
    err += MYEVENTS;
    form.admin_MYEVENTS.select ();
    form.admin_MYEVENTS.focus ();
  } else if ( ! valid_color ( form.admin_TABLEBG.value ) ) {
    err += TABLEBG;
    form.admin_TABLEBG.select ();
    form.admin_TABLEBG.focus ();
  } else if ( ! valid_color ( form.admin_THBG.value ) ) {
    err += THBG;
    form.admin_THBG.select ();
    form.admin_THBG.focus ();
  } else if ( ! valid_color ( form.admin_THFG.value ) ) {
    err += THFG;
    form.admin_THFG.select ();
    form.admin_THFG.focus ();
  } else if ( ! valid_color ( form.admin_CELLBG.value ) ) {
    err += CELLBG;
    form.admin_CELLBG.select ();
    form.admin_CELLBG.focus ();
  } else if ( ! valid_color ( form.admin_TODAYCELLBG.value ) ) {
    err += TODAYCELLBG;
    form.admin_TODAYCELLBG.select ();
    form.admin_TODAYCELLBG.focus ();
  } else if ( ! valid_color ( form.admin_HASEVENTSBG.value ) ) {
    err += HASEVENTSBG;
    form.admin_HASEVENTSBG.select ();
    form.admin_HASEVENTSBG.focus ();
  } else if ( ! valid_color ( form.admin_WEEKENDBG.value ) ) {
    err += WEEKENDBG;
    form.admin_WEEKENDBG.select ();
    form.admin_WEEKENDBG.focus ();
  } else if ( ! valid_color ( form.admin_OTHERMONTHBG.value ) ) {
    err += OTHERMONTHBG;
    form.admin_OTHERMONTHBG.select ();
    form.admin_OTHERMONTHBG.focus ();
  } else if ( ! valid_color ( form.admin_WEEKNUMBER.value ) ) {
    err += WEEKNUMBER;
    form.admin_WEEKNUMBER.select ();
    form.admin_WEEKNUMBER.focus ();
  } else if ( ! valid_color ( form.admin_POPUP_BG.value ) ) {
    err += POPUP_BG;
    form.admin_POPUP_BG.select ();
    form.admin_POPUP_BG.focus ();
  } else if ( ! valid_color ( form.admin_POPUP_FG.value ) ) {
    err += POPUP_FG;
    form.admin_POPUP_FG.select ();
    form.admin_POPUP_FG.focus ();
  }

  if ( err.length > 0 ) {
    alert ( Error + ":\n\n" + err + "\n\n" + colorFormat );
    return false;
  }
  return true;
}

// Gets called on page load and when user changes setting for
// "Disable popup".
function popup_handler () {
	$('popup').showIf(document.prefform.admin_DISABLE_POPUPS.checked);
}

// Gets called on page load and when user changes setting for
// "Allow external users".
function eu_handler () {
	$('eu').showIf(document.prefform.admin_ALLOW_EXTERNAL_USERS.checked);
}

// Gets called on page load and when user changes setting for
// "Allow self registration".
function sr_handler () {
	$('sr').showIf(document.prefform.admin_ALLOW_SELF_REGISTRATION.checked);
}

// Gets called on page load and when user changes setting for
// "Allow attachments".
function attach_handler () {
	$('attach').showIf(document.prefform.admin_ALLOW_ATTACH.checked);
}

// Gets called on page load and when user changes setting for
// "Allow comments".
function comment_handler () {
	$('comment').showIf(document.prefform.admin_ALLOW_COMMENTS.checked);
}

// Gets called on page load and when user changes setting for
// "Email enabled".
function email_handler () {
	var sm = document.prefform.admin_SEND_EMAIL.checked;
	var em = document.prefform.admin_EMAIL_MAILER.selectedIndex;
  var sa = document.prefform.admin_SMTP_AUTH.checked;
	$('em').showIf(sm);
	$('em_smtp').showIf(sm && em == 0);
	$('em_auth').showIf(sm && em == 0 && sa);
}

var tabs = new Array();
tabs[1] = "settings";
tabs[2] = "events";
tabs[3] = "groups";
tabs[4] = "users";
tabs[5] = "other";
tabs[6] = "email";
tabs[7] = "colors";
//]]> -->

function showPreview() {
  var theme = document.forms['prefform'].admin_THEME.value;
  if (theme == 'none' ) return false;
  url = "themes/" + theme.toLowerCase()  + ".php";
  var previewWindow = window.open(url,"Preview","resizable=yes,scrollbars=yes");
}

