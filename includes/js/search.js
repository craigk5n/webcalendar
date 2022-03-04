
function selectUsers() {
  // Find id of user selection object.
  var
    dse    = document.searchformentry.elements,
    listid = 0,
    url;

  for( i = 0, j = dse.length - 1; i < j; i++ ) {
    if( dse[i].name == "users[]" )
      listid = i;
      break; // Should only be one.
  }
  url = 'usersel.php?form=searchformentry&listid=' + listid + '&users=';

  // Add currently selected users.
  for( i = 0, j = 0, k = dse[listid].length - 1; i < k; i++ ) {
    if( dse[listid].options[i].selected ) {
      url += ( j > 0 ? ',' : '' ) + dse[listid].options[i].value;
      j++;
    }
  }

  window.open( url, 'UserSelection',
    'width=500,height=500,resizable=yes,scrollbars=yes' );
}

function toggleDateRange() {
  if ($('#date_filter option:selected').index() == 3) {
    $('#startDate').show();
    $('#endDate').show();
  } else {
    $('#startDate').hide();
    $('#endDate').hide();
  }
}
