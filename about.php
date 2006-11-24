<?php
/* $Id$ */
include_once 'includes/init.php';

$fullname = translate ( 'About' );
$creditStr = translate ( 'Credits' );
$credits = getPostValue ( 'Credits' );
$data = '';

if ( ! empty ( $credits ) ) {
  //Get Names from AUTHORS file
  if ( !$fd = @fopen( 'AUTHORS', 'r' ) ) {
    $data = '';
  } else {
		// Read in contents of entire file first
		$data = '';
		while ( !feof( $fd ) && empty( $error ) ) {
			$data .= fgets( $fd, 4096 );
		}
		fclose( $fd );
  }
  //$data = unhtmlentities ( $data );
  $data = preg_replace ( "/<.+>+/", '', $data );
  $data = preg_replace ( "/\n\s/", '<br />&nbsp;', $data );
  $data = preg_replace ( '/\s\s+/', '&nbsp;&nbsp;', $data );
  $data = preg_replace ( '/\n/', '<br />', $data );
}
$version = '<a title="' . $GLOBALS['PROGRAM_NAME'] . '" ' .
  'id="programname" href="' . $GLOBALS['PROGRAM_URL'] . '" target="_blank">' .
  $GLOBALS['PROGRAM_NAME'] . "</a>\n";

print_header( '', '', '', true, false, true );
?>
<div align="left" style="margin-left:4px;position:absolute; bottom:0px" >
<?php if ( empty ( $credits ) ) { ?>
<h2 style="margin:0px"><?php etranslate ( 'Title' ); ?></h2>
<p><?php echo translate ( 'version' ) 
  . ' ' . $PROGRAM_VERSION ?></p>
<p><?php echo $PROGRAM_DATE ?></p>
<p>&nbsp;</p>
<p>WebCalendar is a PHP application 
used to maintain a calendar for a single user or an intranet group 
of users. It can also be configured as an event calendar.</p>
<?php } else {?>
<script language="javascript1.2" type="text/javascript"> 
var scrollW="235px", scrollH="250px" ;
var copyS=scrollS=1, pauseS=0;
var scrollcontent='<?php echo $data ?>'; 
var actualH='', cross_scroll;

function populate(){ 
  cross_scroll=document.getElementById("scroller"); 
  cross_scroll.innerHTML=scrollcontent;
  actualH=cross_scroll.offsetHeight; 
  lefttime=setInterval("scrollMe()",30);
}
window.onload=populate; 

function scrollMe(){ 
  if (parseInt(cross_scroll.style.top)>(actualH*(-1)+8)) 
    cross_scroll.style.top=parseInt(cross_scroll.style.top)-copyS+"px" ;
  else 
    cross_scroll.style.top=parseInt(scrollH)+8+"px" 
}; 
 
with (document){ 
 write('<div style="position:relative;width:'+scrollW+';height:'+scrollH+';overflow:hidden" onMouseover="copyS=pauseS" onMouseout="copyS=scrollS">');
 write('<div id="scroller"></div></div>');
} 
</script> 
<?php } ?>
<hr />
<div align="center" style="margin:10px; ">
<form action="about.php" name="aboutform"  method="post">
<?php if ( empty ( $credits ) ) { ?>
 <input type="submit" name="Credits" value="<?php echo $creditStr ?>" />
<?php } else {?>
 <input type="submit" name="About" value="<< <?php echo $fullname ?>" />
<?php } ?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
 <input type="button" name="ok" value="<?php 
   etranslate ( 'Ok' ); ?>" onclick="window.close()" />
</form>
</div>
</div>

<?php echo print_trailer ( false, true, true );
?>

