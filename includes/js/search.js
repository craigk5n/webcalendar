// $Id$

var show_adv = wc_getCookie('show_adv');
if (show_adv) {
  linkFile('includes/js/visible.js');
}
linkFile('includes/js/autocomplete.js');

new Autocomplete('keywordsadv', {
    serviceUrl : 'autocomplete_ajax.php'
  });

addLoadListener(function () {
    var vis = ['hidden', 'visible'],
    v_ar;

    attachEventListener(document.getElementById('date_filter'), 'change', toggleDateRange);
    attachEventListener(document.getElementById('searchUsers'), 'click', selectUsers);

    // If we're going to have javascript show them,
    // then we need to have javascript hide them initially.
    // Or people without javascript will never see them.
    v_ar = [
      ['adv', vis[show_adv]],
      ['advlink', vis[!show_adv]],
      ['catfilter', vis[show_adv]],
      ['datefilter', vis[show_adv]],
      ['endDate', 'hidden'],
      ['extrafilter', vis[show_adv]],
      ['startDate', 'hidden'],
    ];

    for (var i = 0; i < 7; i++) {
      document.getElementById(v_ar[i][0]).style.visibility = v_ar[i][1];
    }
  });
function selectUsers() {
  // Find id of user selection object.
  var dse = document.searchformentry.elements,
  listid = 0,
  url;

  for (var i = 0, j = dse.length - 1; i < j; i++) {
    if (dse[i].name == 'users[]') {
      listid = i;
      break; // Should only be one.
    }
  }
  url = 'usersel.php?form=searchformentry&listid=' + listid + '&users=';

  // Add currently selected users.
  for (var i = 0, j = 0, k = dse[listid].length - 1; i < k; i++) {
    if (dse[listid].options[i].selected) {
      url += (j > 0 ? ',' : '') + dse[listid].options[i].value;
      j++;
    }
  }
  window.open(url, 'UserSelection',
    'width=500,height=500,resizable=yes,scrollbars=yes');
}
function toggleDateRange() {
  if (document.searchformentry.date_filter.selectedIndex == 3) {
    makeVisible('startDate');
    makeVisible('endDate');
  } else {
    makeInvisible('startDate');
    makeInvisible('endDate');
  }
}
