<?php
include_once 'includes/init.php';
$INC = '';
print_header($INC,'','',true);

$icon_path = 'icons/';
  
if(!is_dir($icon_path)) echo "window.close ();\n";
if($d = dir($icon_path)) {
  while (false !== ($entry = $d->read())) {
    if(substr($entry,-3,3) == "gif" ) {
      $data = '';
      //we''ll compare the files to eliminate duplicates
      $fd = @fopen ( $icon_path . $entry, 'rb' );
      if ( $fd ) {
        //we only need to compare the first 1kb
        $data .= fgets ( $fd, 1024 );
        $icons[md5( $data )] = $entry;
      }
      fclose ( $fd );        
    }
  }
  $d->close();
  //remove duplicates are replace keys with 0...n
  $icons = array_unique ( $icons );
  sort ( $icons );
  $title_str = translate ( "Click to Select" );
       
  echo "<script language=\"JavaScript\" type=\"text/javascript\">\n";
  echo "<!-- <![CDATA[\n";
  echo "function sendURL ( url ) {\n";
  echo "var thisInput = window.opener.document.catform.urlname;\n";
  echo "var thisPic = window.opener.document.images.urlpic;\n";
  echo "thisInput.value = url.substring (6);\n";
  echo "thisPic.src = url;\n";
  echo "window.close ();\n";
  echo "}\n";       
  echo " //]]> -->\n";
  echo "</script>\n"; 
  
  echo "<table align=\"center\" border=\"0\"><tr>\n";
  echo "<td colspan=\"8\" align=\"center\">";
  echo "<h2>" .  translate ( "Current Icons on Server" )  . "</h2>";   
  echo "</td></tr>\n<tr>";
  for ( $i = 0; $i < count ( $icons ); $i++ ) {  
    echo "<td><a href=\"#\" onclick=\"sendURL('".$icon_path. $icons[$i]."')\" >" .
      "<img src=\"".$icon_path . $icons[$i]."\" border=\"0\" title=\"" . 
      $title_str . "\" alt=\"" . $title_str . "\" /></a></td>\n";
    if ( $i > 0 && $i%8 == 0 ) 
      echo "</tr><tr>\n";
  }
  echo "</tr><tr><td colspan=\"8\" align=\"center\">";
  echo $title_str . "</td>\n";
  echo "</tr>\n</body>\n</html>\n";    

}  
    
?>