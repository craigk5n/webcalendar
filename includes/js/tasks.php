<script type="text/javascript">
<!-- <![CDATA[

// detect browser
NS4 = (document.layers) ? 1 : 0;
IE4 = (document.all) ? 1 : 0;
// W3C stands for the W3C standard, implemented in Mozilla (and Netscape 6)
// and IE5
W3C = (document.getElementById) ? 1 : 0; 


// do a little form verifying
function validate_and_submit_task () {
  if ( document.forms[0].brief_desc.value == "" ) {
    document.forms[0].brief_desc.select ();
    document.forms[0].brief_desc.focus ();
    alert ( "<?php etranslate("You have not entered a Brief Description")?>." );
    return false;
  }

  // is there really a change?
  changed = false;
  form=document.forms[0];
  for ( i = 0; i < form.elements.length; i++ ) {
    field = form.elements[i];
    switch ( field.type ) {
      case "radio":
      case "checkbox":
        if ( field.checked != field.defaultChecked )
          changed = true;
        break;
      case "text":
      case "textarea":
        if ( field.value != field.defaultValue )
          changed = true;
        break;
      case "select-one":
//      case "select-multiple":
        for( j = 0; j < field.length; j++ ) {
          if ( field.options[j].selected != field.options[j].defaultSelected )
            changed = true;
        }
        break;
    }
  }
  if ( changed ) {
    form.task_changed.value = "yes";
  }

  // would be nice to also check date to not allow Feb 31, etc...
  document.forms[0].submit ();
  return true;
}


function selectDate (  day, month, year, current, evt ) {
  // get currently selected day/month/year
  monthobj = eval ( 'document.taskform.' + month );
  curmonth = monthobj.options[monthobj.selectedIndex].value;
  yearobj = eval ( 'document.taskform.' + year );
  curyear = yearobj.options[yearobj.selectedIndex].value;
  date = curyear;

  if (document.getElementById) {
    mX = evt.clientX   + 40;
    mY = evt.clientY  + 120;
  }
  else {
    mX = evt.pageX + 40;
    mY = evt.pageY +130;
  }
  var MyPosition = 'scrollbars=no,toolbar=no,left=' + mX + ',top=' + mY + ',screenx=' + mX + ',screeny=' + mY ;
  if ( curmonth < 10 )
    date += "0";
  date += curmonth;
  date += "01";
  url = "datesel.php?form=taskform&fday=" + day +
    "&fmonth=" + month + "&fyear=" + year + "&date=" + date;
  var colorWindow = window.open(url,"DateSelection","width=300,height=200,"  + MyPosition);
}


// do a little form verifying
function validate_and_submit_category () {
  if ( document.forms[0].catname.value == "" ) {
    document.forms[0].catname.select ();
    document.forms[0].catname.focus ();
    alert ( "<?php etranslate("You have not entered a Category Name")?>." );
    return false;
  }

  document.forms[0].submit ();
  return true;
}


//]]> -->
</script>

