// $Id$

// See the showTab function in "includes/js/visible.js"
// for common code shared by all pages using the tabbed GUI.
var tabs = ['',
  'settings',
  'themes',
  'email',
  'boss',
  'subscribe',
  'header',
  'colors',
];

linkFile('includes/js/visible.js');

addLoadListener(function () {
  altrows();
  showTab(wc_getCookie('currenttab'));

  for (var i = tabs.length - 1; i > 1; i--) {
    toggleVisible('tabscontent_' + tabs[i], 'visible', 'none');

    attachEventListener(document.getElementById('tab_' + tabs[i]), 'click', function () {
      return setTab(tabs[i]);
    });
  }
  attachEventListener(document.getElementsByTagName('img'), 'click', function () {
    window.open('help_pref.php', 'cal_help',
      'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,outerWidth=420');
  });
  attachEventListener(document.getElementById('prefform'), 'submit', function () {
    return valid_form(this);
  });
  attachEventListener(document.getElementById('selLoca'), 'change', function () {
    location = this.options[this.selectedIndex].value;
  });
  attachEventListener(document.getElementById('previewBtn'), 'click', function () {
    return showPreview();
  });

  var user = wc_getCookie('user');

  if (user != '') {
    wc_setCookie('user', '');
    var btn = ['header', 'script', 'trailer'];
    for (var i = 0; i < 3; i++) {
      attachEventListener(document.getElementById(btn[i] + 'Btn'), 'click', function () {
        window.open('edit_template.php?type=' + substr(btn[i], 0, 1).toUpperCase + '&user=' + user,
          'cal_template',
          'dependent,menubar,scrollbars,height=500,width=500,outerHeight=520,outerWidth=520');
      });
    }
  }
});
function setTab(tab) {
  document.forms['prefform'].currenttab.value = tab;
  showTab(tab);
  return false;
}
function showPreview() {
  var theme = document.forms['prefform'].pref_THEME.value;

  if (theme == 'none')
    return false;

  var previewWindow = window.open('themes/' + theme.toLowerCase() + '_pref.php',
      'Preview', 'resizable=yes,scrollbars=yes');
}
function valid_form(form) {
  var colorErr = false,
  err = '',
  useColors;

  useColors = wc_getCookie('useColors');

  if (useColors) {
    if (!valid_color(form.pref_BGCOLOR.value))
      err += xlate['formatColorRGB']; // translate( 'Invalid doc BG color' )

    if (!valid_color(form.pref_H2COLOR.value))
      err += xlate['invalidTitleFG']; // translate( 'Invalid doc title color' )

    if (!valid_color(form.pref_CELLBG.value))
      err += xlate['invalidCellBG']; // translate( 'Invalid table cell BG color' )

    if (!valid_color(form.pref_TODAYCELLBG.value))
      err += xlate['invalidTodayBG']; // translate( 'Invalid table cell today BG' )
  }
  if (err.length > 0)
    colorErr = true;

  if (!validWorkHours(form)) {
    err += xlate['invalidHours']// translate( 'Invalid work hours.' )
     + form.pref_WORK_DAY_START_HOUR.value + ' > '
     + form.pref_WORK_DAY_END_HOUR.value + "\n";
  }
  if (colorErr) {
    alert(xlate['errorXXX'].replace(/XXX/, err) // translate( 'Error XXX' )
       + "\n\n" + xlate['formatColorRGB']); // translate( 'Color format should be RGB' )
    return false;
  } else if (err.length > 0) {
    alert(xlate['errorXXX'].replace(/XXX/, err));
    return false;
  }
  return true;
}
function validWorkHours(form) {
  return (parseInt(form.pref_WORK_DAY_START_HOUR.value) <
    parseInt(form.pref_WORK_DAY_END_HOUR.value));
}
