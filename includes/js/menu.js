// This is for using submenus in the nav menu.  It's not quite working right
// so the submenus are not currently being used.
/*
$('.dropdown-menu a.dropdown-toggle').on('click', function() {
  if (!$(this).next().hasClass('show')) {
    $(this).parents('.dropdown-menu').first().find('.show').removeClass('show');
  }

  const $subMenu = $(this).next('.dropdown-menu');
  $subMenu.toggleClass('show');


  $(this).parents('li.nav-item.dropdown.show').on('hidden.bs.dropdown', function() {
    $('.dropdown-submenu .show').removeClass('show');
  });

  return false;
});
*/
