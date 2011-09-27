// $Id$

linkFile('includes/js/visible.js');

addLoadListener(function () {
    altps();
    attach_handler();
    comment_handler();
    email_handler();
    eu_handler();
    popup_handler();
    public_handler();
    sr_handler();
    
    showTab(wc_getCookie('currenttab'));
    
    attachEventListener(document.getElementsByTagName('img'), 'click',
      function () {
        window.open('help_admin.php', 'cal_help',
          'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,outerWidth=420');
      });
    attachEventListener(document.getElementById('prefform'), 'submit',
      function () {
        return valid_form(this);
      });
  });
function valid_form(form) {
  var err = '';
  
  if (form.admin_SERVER_URL.value == '') {
    err = xlate['reqServerURL']; // translate( 'Server URL is required.' )
    form.admin_SERVER_URL.select();
    form.admin_SERVER_URL.focus();
  } else if (form.admin_SERVER_URL.value.charAt(
      form.admin_SERVER_URL.value.length - 1) != '/') {
    err = xlate['endServerURL']; // translate( 'Server URL must end with /.' )
    form.admin_SERVER_URL.select();
    form.admin_SERVER_URL.focus();
  }
  
  if (parseInt(form.admin_WORK_DAY_START_HOUR.value) >=
    parseInt(form.admin_WORK_DAY_END_HOUR.value)) {
    err += xlate['invalidHours']; // translate( 'Invalid work hours.' )
    form.admin_WORK_DAY_START_HOUR.select();
    form.admin_WORK_DAY_START_HOUR.focus();
  }
  
  if (err != '') {
    alert(xlate['error'] + err); // translate( 'Error')
    return false;
  }
  
  if (!valid_color(form.admin_BGCOLOR.value)) {
    err = xlate['invalidDocuBG']; // translate( 'Invalid color for document background.' )
    form.admin_BGCOLOR.select();
    form.admin_BGCOLOR.focus();
  } else if (!valid_color(form.admin_H2COLOR.value)) {
    err = xlate['invalidTitleFG']; // translate( 'Invalid color for document title.' )
    form.admin_H2COLOR.select();
    form.admin_H2COLOR.focus();
  } else if (!valid_color(form.admin_CELLBG.value)) {
    err = xlate['invalidCellBG']; // translate( 'Invalid color for table cell background.' )
    form.admin_CELLBG.select();
    form.admin_CELLBG.focus();
  } else if (!valid_color(form.admin_TABLEBG.value)) {
    err = xlate['invalidGridFG']; // translate( 'Invalid color for table grid.' )
    form.admin_TABLEBG.select();
    form.admin_TABLEBG.focus();
  } else if (!valid_color(form.admin_THBG.value)) {
    err = xlate['invalidTHBG']; // translate( 'Invalid color for table header background.' )
    form.admin_THBG.select();
    form.admin_THBG.focus();
  } else if (!valid_color(form.admin_THFG.value)) {
    err = xlate['invalidTextFG']; // translate( 'Invalid color for table header text.' )
    form.admin_THFG.select();
    form.admin_THFG.focus();
  } else if (!valid_color(form.admin_POPUP_BG.value)) {
    err = xlate['invalidPopupBG']; // translate( 'Invalid color for event popup background.' )
    form.admin_POPUP_BG.select();
    form.admin_POPUP_BG.focus();
  } else if (!valid_color(form.admin_POPUP_FG.value)) {
    err = xlate['invalidPopupFG']; // translate( 'Invalid color for event popup text.' )
    form.admin_POPUP_FG.select();
    form.admin_POPUP_FG.focus();
  } else if (!valid_color(form.admin_TODAYCELLBG.value)) {
    err = xlate['invalidTodayBG']; // translate( 'Invalid color for table cell background for today.' )
    form.admin_TODAYCELLBG.select();
    form.admin_TODAYCELLBG.focus();
  }
  
  if (err.length > 0) {
    alert(xlate['error'] + err + "\n\n" + xlate['formatColorRGB']); // translate( 'Color format should be RRGGBB.' )
    return false;
  }
  return true;
}

// Gets called on page load and when user changes setting for
// "Disable popup".
function popup_handler() {
  if (document.prefform.admin_DISABLE_POPUPS[0].checked) {
    // Popups disabled
    makeInvisible('pop');
  } else {
    // Popups allowed
    makeVisible('pop');
  }
}

// Gets called on page load and when user changes setting for
// "Allow public access".
function public_handler() {
  if (document.prefform.admin_PUBLIC_ACCESS[0].checked) {
    // Public Access enabled
    makeVisible('pa');
  } else {
    // Public Access disabled
    makeInvisible('pa');
  }
}

// Gets called on page load and when user changes setting for
// "Allow external users".
function eu_handler() {
  if (document.prefform.admin_ALLOW_EXTERNAL_USERS[0].checked) {
    // External Users enabled
    makeVisible('eu');
  } else {
    makeInvisible('eu');
  }
}

// Gets called on page load and when user changes setting for
// "Allow self registration".
function sr_handler() {
  if (document.prefform.admin_ALLOW_SELF_REGISTRATION[0].checked) {
    // Self Registration enabled
    makeVisible('sr');
  } else {
    makeInvisible('sr');
  }
}

// Gets called on page load and when user changes setting for
// "Allow attachments".
function attach_handler() {
  if (document.prefform.admin_ALLOW_ATTACH[0].checked) {
    makeVisible('at1');
  } else {
    makeInvisible('at1');
  }
}

// Gets called on page load and when user changes setting for
// "Allow comments".
function comment_handler() {
  if (document.prefform.admin_ALLOW_COMMENTS[0].checked) {
    makeVisible('com1');
  } else {
    makeInvisible('com1');
  }
}

// Gets called on page load and when user changes setting for
// "Email enabled".
function email_handler() {
  if (document.prefform.admin_SEND_EMAIL[0].checked) {
    // Email enabled
    makeVisible('em');
    if (document.prefform.admin_EMAIL_MAILER.selectedIndex == 0) {
      makeVisible('em_smtp');
      if (document.prefform.admin_SMTP_AUTH[0].checked) {
        makeVisible('em_auth')
      } else {
        makeInvisible('em_auth')
      }
    } else {
      makeInvisible('em_smtp');
    }
  } else {
    makeInvisible('em');
  }
}

//See the showTab function in includes/js/visible.js
//for common code shared by all pages using the tabbed GUI.
var tabs = ['', 'settings', 'public', 'uac',
  'groups', 'nonuser', 'other', 'email', 'colors'];

function showPreview() {
  var theme = document.forms['prefform'].admin_THEME.value.toLowerCase();
  
  if (theme == 'none') {
    return false;
  }
  var previewWindow =
    window.open('themes/' + theme + '.php',
      'Preview', 'resizable=yes,scrollbars=yes');
}

function setTab(tab) {
  document.forms['prefform'].currenttab.value = tab;
  showTab(tab);
  return false;
}
 