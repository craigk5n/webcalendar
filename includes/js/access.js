// $Id$

if (wc_getCookie('enAll')) {
  addLoadListener(function () {
      enableAll(true);
    });
}
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
