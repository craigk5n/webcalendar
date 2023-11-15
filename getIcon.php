<?php
require_once 'includes/init.php';

$cat_id = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;

// Query to get the icon blob data from the database
$query = "SELECT cat_icon_blob, cat_icon_mime FROM webcal_categories WHERE cat_id = ?";
$res = dbi_execute($query, [$cat_id]);

if ($res) {
    $row = dbi_fetch_row($res);
    if ($row && !empty($row[0])) {
        // Output the MIME type header
        header("Content-Type: " . $row[1]);
        // Output the image
        echo $row[0];
    } else {
        // Handle error, e.g., display default image
    }
    dbi_free_result($res);
} else {
    // Handle error, e.g., display default image
}
