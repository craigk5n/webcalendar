<?php /* $Id: view_d.php,v 1.7.2.2 2007/08/06 02:28:27 cknudsen Exp $  */ ?>
function schedule_event (h,m) {
  document.schedule.hour.value = h;
  document.schedule.minute.value = m;
  document.schedule.submit ();
  return true;
}

