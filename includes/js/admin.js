
function init_admin() {
  attach_handler();
  comment_handler();
  email_handler();
  eu_handler();
  popup_handler();
  public_handler();
  sr_handler();

  return true;
}

function valid_form( form ) {
  var err = '';

  if ( form.admin_SERVER_URL.value == '' ) {
    err = xlate['reqServerURL'];
    form.admin_SERVER_URL.select();
    form.admin_SERVER_URL.focus();
  }
  else if ( form.admin_SERVER_URL.value.charAt(
    form.admin_SERVER_URL.value.length - 1 ) != '/' ) {
      err = xlate['endServerURL'];
      form.admin_SERVER_URL.select();
      form.admin_SERVER_URL.focus();
  }

  if ( parseInt( form.admin_WORK_DAY_START_HOUR.value ) >=
    parseInt( form.admin_WORK_DAY_END_HOUR.value ) ) {
      err += xlate['invalidHours'];
      form.admin_WORK_DAY_START_HOUR.select();
      form.admin_WORK_DAY_START_HOUR.focus();
  }

  if ( err != '' ) {
    alert( xlate['error'] + err );
    return false;
  }

  if ( !valid_color( form.admin_BGCOLOR.value ) ) {
    err = xlate['invalidDocuBG'];
    form.admin_BGCOLOR.select();
    form.admin_BGCOLOR.focus();
  }
  else if ( !valid_color( form.admin_H2COLOR.value ) ) {
    err = xlate['invalidTitleFG'];
    form.admin_H2COLOR.select();
    form.admin_H2COLOR.focus();
  }
  else if ( !valid_color( form.admin_CELLBG.value ) ) {
    err = xlate['invalidCellBG'];
    form.admin_CELLBG.select();
    form.admin_CELLBG.focus();
  }
  else if ( !valid_color( form.admin_TABLEBG.value ) ) {
    err = xlate['invalidGridFG'];
    form.admin_TABLEBG.select();
    form.admin_TABLEBG.focus();
  }
  else if ( !valid_color( form.admin_THBG.value ) ) {
    err = xlate['invalidTHBG'];
    form.admin_THBG.select();
    form.admin_THBG.focus();
  }
  else if ( !valid_color( form.admin_THFG.value ) ) {
    err = xlate['invalidTextFG'];
    form.admin_THFG.select();
    form.admin_THFG.focus();
  }
  else if ( !valid_color( form.admin_POPUP_BG.value ) ) {
    err = xlate['invalidPopupBG'];
    form.admin_POPUP_BG.select();
    form.admin_POPUP_BG.focus();
  }
  else if ( !valid_color( form.admin_POPUP_FG.value ) ) {
    err = xlate['invalidPopupFG'];
    form.admin_POPUP_FG.select();
    form.admin_POPUP_FG.focus();
  }
  else if ( !valid_color( form.admin_TODAYCELLBG.value ) ) {
    err = xlate['invalidTodayBG'];
    form.admin_TODAYCELLBG.select();
    form.admin_TODAYCELLBG.focus();
  }

  if ( err.length > 0 ) {
    alert( xlate['error'] + err + "\n\n" + xlate['formatColorRGB'] );
    return false;
  }
  return true;
}

// Gets called on page load and when user changes setting for
// "Disable popup".
function popup_handler() {
  if ($('#admin_DISABLE_POPUPS_Y').is(':checked')) {
    // Popups disabled
    $('#pop').hide();
  } else {
    // Popups allowed
    $('#pop').show();
  }
}

// Gets called on page load and when user changes setting for
// "Allow public access".
function public_handler() {
  if ($('#admin_PUBLIC_ACCESS_Y').is(':checked')) {
    // Public Access enabled
    $('#pa').show();
  } else {
    // Public Access disabled
    $('#pa').hide();
  }
}

// Gets called on page load and when user changes setting for
// "Allow external users".
function eu_handler() {
  if ($('#admin_ALLOW_EXTERNAL_USERS_Y').is(':checked')) {
    // External Users enabled
    $('#eu').show();
  } else {
    $('#eu').hide();
  }
}

// Gets called on page load and when user changes setting for
// "Allow self registration".
function sr_handler() {
  if ($('#admin_ALLOW_SELF_REGISTRATION_Y').is(':checked')) {
    // Self Registration enabled
    $('#sr').show();
  } else {
    $('#sr').hide();
  }
}

// Gets called on page load and when user changes setting for
// "Allow attachments".
function attach_handler() {
  if ($('#admin_ALLOW_ATTACH_Y').is(':checked')) {
    $('#at1').show();
    $('#at1a').show();
  } else {
    $('#at1').hide();
    $('#at1a').hide();
  }
}

// Gets called on page load and when user changes setting for
// "Allow comments".
function comment_handler() {
  if ($('#admin_ALLOW_COMMENTS_Y').is(':checked')) {
    $('#com1').show();
    $('#com1a').show();
  } else {
    $('#com1').hide();
    $('#com1a').hide();
  }
}

// Gets called on page load and when user changes setting for
// "Email enabled".
function email_handler() {
  if ($('#admin_SEND_EMAIL_Y').is(':checked')) {
    // Email enabled
    $('#em').show();
    if ( document.prefform.admin_EMAIL_MAILER.selectedIndex == 0 ) {
      $('#em_smtp').show();
      if ( document.prefform.admin_SMTP_AUTH[0].checked ) {
        $('#em_auth').show();
      } else {
        $('#em_auth').hide();
      }
    } else {
      $('#em_smtp').hide();
    }
  } else {
    $('#em').hide();
  }
}

function showPreview() {
  var theme = document.forms['prefform'].admin_THEME.value.toLowerCase();

  if ( theme == 'none' ) {
    return false;
  }
  var previewWindow =
    window.open( 'themes/' + theme  + '.php',
      'Preview','resizable=yes,scrollbars=yes' );
}

