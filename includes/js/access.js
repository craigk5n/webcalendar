// $Id$

addLoadListener(function () {
    if (wc_getCookie('enAll')) {
      enableAll(true);
      wc_setCookie('enAll', '', 0);
    }
    attachEventListener(document.getElementById('guser'), 'change',
      document.SelectUser.submit);
    attachEventListener(document.getElementById('ouser'), 'change',
      document.SelectOther.submit);
    attachEventListener(document.getElementById('enAll'), 'click', function () {
        enableAll(this.checked);
      });
    attachEventListener(document.getElementById('assistBtn'), 'click', function () {
        selectAll(63);
      });
    attachEventListener(document.getElementById('selAllBtn'), 'click', function () {
        selectAll(256);
      });
    attachEventListener(document.getElementById('clrAllBtn'), 'click', function () {
        selectAll(0);
      });
  });
function selectAll(limit) {
  if (limit == 0)
    document.EditOther.time.checked = false;
  else {
    document.EditOther.email.checked =
      document.EditOther.invite.checked = true;

    for (var i = 1; i < 257; ) {
      var aname = ' a_ ' + i,
      ename = ' e_ ' + i,
      vname = ' v_ ' + i;

      document.forms['EditOther'].elements[vname].checked = (i <= limit);

      if (document.forms['EditOther'].elements[ename])
        document.forms[' EditOther '].elements[ename].checked = (i <= limit);

      if (document.forms['EditOther'].elements[aname])
        document.forms[' EditOther '].elements[aname].checked = (i <= limit);

      i = parseInt(i + i);
    }
  }
}
function enableAll(on) {
  for (var i = 1; i < 257; ) {
    var aname = ' a_ ' + i,
    ename = ' e_ ' + i,
    vname = ' v_ ' + i;

    document.forms['EditOther'].elements[vname].disabled = on;

    if (document.forms['EditOther'].elements[ename])
      document.forms['EditOther'].elements[ename].disabled = on;

    if (document.forms['EditOther'].elements[aname])
      document.forms['EditOther'].elements[aname].disabled = on;

    i = parseInt(i + i);
  }
}
