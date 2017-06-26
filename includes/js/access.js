function selectAll( limit ) {
  if( limit == 0 )
    document.EditOther.time.checked = false;

  document.EditOther.email.checked = ( limit != 0 );
  document.EditOther.invite.checked = ( limit != 0 );

  for( i = 1; i <= 256; ) {
    var
      aname = 'a_' + i,
      ename = 'e_' + i,
      vname = 'v_' + i;

    document.forms['EditOther'].elements[vname].checked = ( i <= limit );

    if( document.forms['EditOther'].elements[ename] )
      document.forms['EditOther'].elements[ename].checked = ( i <= limit );

    if( document.forms['EditOther'].elements[aname] )
      document.forms['EditOther'].elements[aname].checked = ( i <= limit );

    i = parseInt( i+i );
  }
}
function enableAll( on ) {
  for( i = 1; i <= 256; ) {
    var
      aname = 'a_' + i,
      ename = 'e_' + i,
      vname = 'v_' + i;

    document.forms['EditOther'].elements[vname].disabled = on;

    if( document.forms['EditOther'].elements[ename] )
      document.forms['EditOther'].elements[ename].disabled = on;

    if( document.forms['EditOther'].elements[aname] )
      document.forms['EditOther'].elements[aname].disabled = on;

    i = parseInt( i+i );
  }
}
