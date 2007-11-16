/* $Id$  */

initPhpVars( 'pref' );

function valid_form ( form ) {
  var err = "";
  var colorErr = false;
  if ( ALLOW_COLOR_CUSTOMIZATION == 'Y') {
    if ( ! valid_color ( form.pref_BGCOLOR.value ) ) {
      err += BGCOLOR;
    } else if ( ! valid_color ( form.pref_H2COLOR.value ) ) {
      err += H2COLOR;
    } else if ( ! valid_color ( form.pref_TEXTCOLOR.value ) ) {
      err += TEXTCOLOR;
    } else if ( ! valid_color ( form.pref_MYEVENTS.value ) ) {
      err += MYEVENTS;
    } else if ( ! valid_color ( form.pref_TABLEBG.value ) ) {
      err += TABLEBG;
    } else if ( ! valid_color ( form.pref_THBG.value ) ) {
      err += THBG;
    } else if ( ! valid_color ( form.pref_THFG.value ) ) {
      err += THFG;
    } else if ( ! valid_color ( form.pref_CELLBG.value ) ) {
      err += CELLBG;
    } else if ( ! valid_color ( form.pref_TODAYCELLBG.value ) ) {
      err += TODAYCELLBG;
    } else if ( ! valid_color ( form.pref_HASEVENTSBG.value ) ) {
      err += HASEVENTSBG;
    } else if ( ! valid_color ( form.pref_WEEKENDBG.value ) ) {
      err += WEEKENDBG;
    } else if ( ! valid_color ( form.pref_OTHERMONTHBG.value ) ) {
      err += OTHERMONTHBG;
    } else if ( ! valid_color ( form.pref_WEEKNUMBER.value ) ) {
      err += WEEKNUMBER;
    } else if ( ! valid_color ( form.pref_POPUP_BG.value ) ) {
      err += POPUP_BG;
    } else if ( ! valid_color ( form.pref_POPUP_FG.value ) ) {
      err += POPUP_FG;
    }
  } 
  if ( err.length > 0 )
    colorErr = true;
  if ( ! validWorkHours ( form ) ) {
    err += adm4;
    err += form.pref_WORK_DAY_START_HOUR.value + " > " + form.pref_WORK_DAY_END_HOUR.value + "\n";
  }
  if ( colorErr ) {
    alert ( Error + ":\n\n" + err + "\n\n" + colorFormat );
    return false;
  } else if ( err.length > 0 ) {
    alert ( Error + ":\n\n" + err );
    return false;
  }
  return true;
}

function validWorkHours ( form ) {
  return ( parseInt ( form.pref_WORK_DAY_START_HOUR.value ) <
    parseInt ( form.pref_WORK_DAY_END_HOUR.value ) );
}

function showPreview() {
  var theme = document.forms['prefform'].pref_THEME.value;
  if (theme == 'none' ) return false;
  url = "themes/" + theme.toLowerCase()  + ".php";
  var previewWindow = window.open(url,"Preview","resizable=yes,scrollbars=yes");
}

function initJS2PHP() {
  var sw = screen.width;
  var sh = screen.height;
  var url = 'ajax.php';
  var params = 'page=initJS2PHP&sw=' + sw + '&sh=' + sh;
  var ajax = new Ajax.Request(url,
    {method: 'post', 
    parameters: params});
}

var tabs = new Array();
tabs[1] = "settings";
tabs[2] = "themes";
tabs[3] = "email";
tabs[4] = "boss";
tabs[5] = "subscribe";
tabs[6] = "header";
tabs[7] = "colors";


initJS2PHP();
