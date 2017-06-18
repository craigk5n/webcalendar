<?php /* $Id: set_entry_cat.php,v 1.8 2007/07/23 23:25:10 bbannon Exp $  */ ?>
function editCats (  evt ) {
  if (document.getElementById) {
    mX = evt.clientX   +150;
    mY = evt.clientY  + 150;
  }
  else {
    mX = evt.pageX  +150;
    mY = evt.pageY + 150;
  }
  var MyPosition = 'scrollbars=no,toolbar=no,left=' + mX + ',top=' + mY + ',screenx=' + mX + ',screeny=' + mY;
  var cat_ids = document.selectcategory.elements['cat_id'].value;
  url = "catsel.php?form=selectcategory&cats=" + cat_ids;
  var catWindow = window.open (url,"EditCat","width=385,height=250,"  + MyPosition);
}

