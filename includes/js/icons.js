function sendURL(url) {
  $('#urlname').val(url.substring(9)); // 'wc-icons/'
  $('#urlpic').attr('src',url);
  $('#cat_icon').attr("style", ""); // make visible
  $('#remove_icon').attr("style", ""); // make visible
  $('#modalclosebtn').click();
}
