// $Id$

addLoadListener(function () {
    var oHelp = document.getElementById('openHelp');

    attachEventListener(oHelp, 'mouseover', function () {
        window.status = '';
        return true;
      });
    attachEventListener(oHelp, 'click', function () {
        window.open('help_index.php', 'cal_help',
          'dependent,menubar,scrollbars,height=500,width=600,innerHeight=520,'
           + 'outerWidth=620');
      });
      targeTo('_blank', 'programname');
  });
function openAbout() {
  var mX = (screen.width / 2) - 123,
  mY = 200;

  window.open('about.php', 'cal_about',
    'dependent,toolbar=0, height=300,width=245,innerHeight=310,outerWidth=255,'
     + 'location=0,left=' + mX + ',top=' + mY + ',screenx=' + mX
     + ',screeny=' + mY);
}
function sortTasks(order, cat_id, ele) {
  document.body.style.cursor = ele.style.cursor = 'wait';

  var ajax = new Ajax.Request('ajax.php', {
        method: 'post',
        parameters: 'page=minitask&name=' + order
         + (cat_id > -99 ? '&cat_id=' + cat_id : ''),
        onComplete: showResponse
      });
}
function showResponse(originalRequest) {
  miniTask = document.getElementById('minitask');

  if (originalRequest.responseText) {
    text = originalRequest.responseText;
    miniTask.innerHTML = text;
  }
  document.body.style.cursor = 'default';
}
function altrows() {
  if (!document.getElementsByTagName)
    return false;

  var rows = $$('div tbody tr');

  for (var i in rows) {
    if (!i.hasClassName('ignore')) {
      i.onmouseover = function () {
        $(this).addClassName('alt');
      }
      i.onmouseout = function () {
        $(this).removeClassName('alt');
      }
    }
  }
}
function altps() {
  if (!document.getElementsByTagName)
    return false;

  var rows = $$('div p');

  for (var i in rows) {
    if (!i.hasClassName('ignore')) {
      i.onmouseover = function () {
        $(this).addClassName('alt');
      }
      i.onmouseout = function () {
        $(this).removeClassName('alt');
      }
    }
  }
}
