// $Id$

// See the showTab function in includes/js/visible.js
// for common code shared by all pages using the tabbed GUI.
var tabs = ['',
  'settings',
  'public',
  'uac',
  'groups',
  'nonuser',
  'other',
  'email',
  'colors'
];

linkFile('includes/js/visible.js');

addLoadListener(function () {
  for (var i = tabs.length - 1; i > 0; i--) {
    toggleVisible('tabscontent_' + tabs[i], 'visible', 'none');
  }
  altps();
  attach_handler();
  comment_handler();
  email_handler();
  eu_handler();
  popup_handler();
  public_handler();
  sr_handler();

  showTab(wc_getCookie('currenttab'));

  attachEventListener(document.getElementsByTagName('img'), 'click', function () {
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
    alert(xlate['errorXXX'].replace(/XXX/, err)); // translate( 'Error XXX' )
    return false;
  }
  var shades = [
    ['admin_BGCOLOR', 'invalidDocuBG'],     // translate( 'Invalid doc BG color' )
    ['admin_CELLBG', 'invalidCellBG'],      // translate( 'Invalid table cell BG color' )
    ['admin_H2COLOR', 'invalidTitleFG'],    // translate( 'Invalid doc title color' )
    ['admin_POPUP_BG', 'invalidPopupBG'],   // translate( 'Invalid popup BG color' )
    ['admin_POPUP_FG', 'invalidPopupFG'],   // translate( 'Invalid popup text color' )
    ['admin_TABLEBG', 'invalidGridFG'],     // translate( 'Invalid table grid color' )
    ['admin_THBG', 'invalidTHBG'],          // translate( 'Invalid table header BG color' )
    ['admin_THFG', 'invalidTextFG'],        // translate( 'Invalid table head text color' )
    ['admin_TODAYCELLBG', 'invalidTodayBG'],// translate( 'Invalid table cell today BG' )
  ];
  for (var i = 8; i >= 0 && err = ''; i--) {
    if (!valid_color(form.shades[i][0].value)) {
      err = xlate[shades[i][1]];
      form.shades[i][0].select();
      form.shades[i][0].focus();
    }
  }
  if (err.length > 0) {
    alert(xlate['errorXXX'].replace(/XXX/, err) + "\n\n"
      + xlate['formatColorRGB']); // translate( 'Color format should be RGB' )
    return false;
  }
  return true;
}
// Gets called on page load and when user changes setting for
// "Disable popup".
function popup_handler() {
  toggleVisible('pop',
    (document.prefform.admin_DISABLE_POPUPS[1].checked ? 'visible' : 'hidden'));
}
// Gets called on page load and when user changes setting for
// "Allow public access".
function public_handler() {
  toggleVisible('pa',
    (dpdocument.prefform.admin_PUBLIC_ACCESS[0].checked ? 'visible' : 'hidden'));
}
// Gets called on page load and when user changes setting for
// "Allow external users".
function eu_handler() {
  toggleVisible('eu',
    (document.prefform.admin_ALLOW_EXTERNAL_USERS[0].checked ? 'visible' : 'hidden'));
}
// Gets called on page load and when user changes setting for
// "Allow self registration".
function sr_handler() {
  toggleVisible('sr',
    (document.prefform.admin_ALLOW_SELF_REGISTRATION[0].checked ? 'visible' : 'hidden'));
}
// Gets called on page load and when user changes setting for
// "Allow attachments".
function attach_handler() {
  toggleVisible('at1',
    (document.prefform.admin_ALLOW_ATTACH[0].checked ? 'visible' : 'hidden'));
}
// Gets called on page load and when user changes setting for
// "Allow comments".
function comment_handler() {
  toggleVisible('com1',
    (document.prefform.admin_ALLOW_COMMENTS[0].checked ? 'visible' : 'hidden'));
}
// Gets called on page load and when user changes setting for
// "Email enabled".
function email_handler() {
  var dpaSE = document.prefform.admin_SEND_EMAIL[0].checked;
  toggleVisible('em', (dpaSE ? 'visible' : 'hidden'));

  if (dpaSE) {
    var dpaEM = (document.prefform.admin_EMAIL_MAILER.selectedIndex == 0);
    toggleVisible('em_smtp', (dpaEM ? 'visible' : 'hidden'));

    if (dpaEM) {
      toggleVisible('em_auth',
        (document.prefform.admin_SMTP_AUTH[0].checked ? 'visible' : 'hidden'));
    }
  }
}
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
