<?php
/*
 * $Id$
 *
 * Page Description:
 *	This page will present the HTML form to edit an entry
 *	in the cal_report table, and this page will also process the
 *	form.
 *	This is only used for editing the custom header/trailer.
 *	The report_id is always 0.
 *
 * Input Parameters:
 *	type - "header" or "trailer"
 *
 * Security:
 *	Admin permissions are checked in connect.php.
 */
include_once 'includes/init.php';

$report_id = 0;
$error = '';

$type = getValue ( "type", "S|H|T", true );
$cur = '';
$found = false;

// Get existing value.
$res = dbi_query ( "SELECT cal_template_text " .
  "FROM webcal_report_template " .
  "WHERE cal_template_type = '$type' AND caL_report_id = 0" );
if ( $res ) {
  if ( $row = dbi_fetch_row ( $res ) ) {
    $cur = $row[0];
    $found = true;
  }
  dbi_free_result ( $res );
}

if ( empty ( $REQUEST_METHOD ) )
  $REQUEST_METHOD = $_SERVER['REQUEST_METHOD'];

// Handle form submission
if ( $REQUEST_METHOD == 'POST' ) {
  //$template = getPostValue ( "template" );
  $template = $_POST['template'];
  //echo "Template: " . htmlentities ( $template ) . "<br />\n"; exit;
  if ( $found ) {
    $sql = "UPDATE webcal_report_template " .
      "SET cal_template_text = '$template' " .
      "WHERE cal_template_type = '$type' AND cal_report_id = 0";
  } else {
    $sql = "INSERT INTO webcal_report_template " .
      "( cal_template_type, cal_report_id, cal_template_text ) " .
      "VALUES ( '$type', 0, '$template' )";
  }
  if ( ! dbi_query ( $sql ) ) {
    $error = translate("Database error") . ": " . dbi_error ();
  } else {
    //echo "SQL: $sql <br />\n";
    echo "<html>\n<head>\n</head>\n<body onload=\"window.close();\">\nDone</body>\n</html>";
    exit;
  }
}

print_header( '', '', '', true );
//echo "report_id: $report_id <br />\n";
//echo "report_name: $report_name <br />\n";
//echo "report_user: $report_user <br />\n";
?>

<h2><?php
if ( $type == 'S' )
  etranslate("Edit Custom Script/Stylesheet");
else if ( $type == 'H' )
  etranslate("Edit Custom Header");
else
  etranslate("Edit Custom Trailer");
?></h2>

<?php
if ( ! empty ( $error ) ) {
  echo "<h2>" . translate("Error") . "</h2>\n" .
    $error . "\n";
} else {
?>
<form action="edit_template.php" method="post" name="reportform">

<input type="hidden" name="type" value="<?php echo $type;?>" />
<textarea rows="15" cols="60" name="template"><?php echo htmlentities ( $cur )?></textarea>

<br />
<input type="button" value="<?php etranslate("Cancel")?>" onclick="window.close();" />
<input type="submit" value="<?php etranslate("Save")?>" />
</form>

<?php }
	print_trailer ( false, true, true );
?>
</body>
</html>