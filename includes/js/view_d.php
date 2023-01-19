
function schedule_event (h,m) {
  document.schedule.hour.value = h;
  document.schedule.minute.value = m;
  document.schedule.submit ();
  return true;
}

