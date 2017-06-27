<?php /* $Id: view_d.php,v 1.7 2006/04/14 23:37:00 umcesrjones Exp $  */ ?>
function schedule_event (h,m) {
  document.schedule.hour.value = h;
  document.schedule.minute.value = m;
  document.schedule.submit ();
  return true;
}

