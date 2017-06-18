<?php
/* $Id: about.php,v 1.16.2.3 2007/08/06 02:28:29 cknudsen Exp $ */
include_once 'includes/init.php';

$credits = getPostValue ( 'Credits' );
$data = '';

if ( ! empty ( $credits ) ) {
  // Get Names from AUTHORS file.
  if ( $fd = @fopen ( 'AUTHORS', 'r' ) ) {
    // Read in contents of entire file first.
    while ( ! feof ( $fd ) && empty ( $error ) ) {
      $data .= fgets ( $fd, 4096 );
    }
    fclose ( $fd );
  }
  // $data = unhtmlentities ( $data );
  $data = preg_replace ( '/<.+>+/', '', $data );
  $data = preg_replace ( "/\n\s/", '<br />&nbsp;', $data );
  $data = preg_replace ( '/\s\s+/', '&nbsp;&nbsp;', $data );
  $data = preg_replace ( '/\n/', '<br />', $data );
}

print_header ( '', '', '', true, false, true );
echo '
    <div align="left" style="margin-left:4px; position:absolute; bottom:0">';
if ( empty ( $credits ) )
  echo '
      <a title="' . $PROGRAM_NAME . '" href="' . $PROGRAM_URL . '" target="_blank">
      <h2 style="margin:0">' . translate ( 'Title' ) . '</h2>
      <p>' . str_replace ( 'XXX', $PROGRAM_VERSION,
        // translate ( 'version' )
        translate ( 'version XXX' ) ) . '</p>
      <p>' . $PROGRAM_DATE . '</p></a>
      <p>&nbsp;</p>
      <p>' . translate ( 'WebCalendar is a PHP application used...' ) . '</p>';
else {

  ?>
      <script language="javascript1.2" type="text/javascript">
        var
          scrollW="235px",
          scrollH="250px",
          copyS=scrollS=1,
          pauseS=0,
          scrollcontent='<?php echo $data ?>',
          actualH='',
          cross_scroll;

        function populate (){
          cross_scroll=document.getElementById("scroller");
          cross_scroll.innerHTML=scrollcontent;
          actualH=cross_scroll.offsetHeight;
          lefttime=setInterval("scrollMe ()",30);
        }

        window.onload=populate;

        function scrollMe (){
          if (parseInt (cross_scroll.style.top)>(actualH* (-1)+8))
            cross_scroll.style.top=parseInt(cross_scroll.style.top)-copyS+"px";
          else
            cross_scroll.style.top=parseInt(scrollH)+8+"px";
        }

        with (document){
          write('<div style="position:relative; width:'+scrollW+'; height: '
            + scrollH +'; overflow:hidden;" onMouseover="copyS=pauseS" '
            + 'onMouseout="copyS=scrollS"><div id="scroller"></div></div>');
        }
      </script>
<?php
}

echo '
      <hr />
      <div align="center" style="margin:10px;">
        <form action="about.php" name="aboutform" method="post">
          <input type="submit" name=' . ( empty ( $credits )
  ? '"Credits" value="' . translate ( 'Credits' )
  : '"About" value="<< ' . translate ( 'About' ) )
 . '" />' . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
          <input type="button" name="ok" value="' . translate ( 'OK' )
 . '" onclick="window.close()" />
        </form>
      </div>
    </div>
    ' . print_trailer ( false, true, true );

?>
