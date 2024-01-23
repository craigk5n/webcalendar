<?php
require_once 'includes/init.php';

$icon_max_size = '6000';
$icon_path = 'wc-icons/';


function updateIconBlob($catId, $iconData, $iconMimeType) {
  // Update the icon binary data in the database.
  dbi_update_blob(
      'webcal_categories',
      'cat_icon_blob',
      "cat_id = $catId",
      $iconData
  );

  // Update the MIME type of the icon in the database.
  dbi_execute(
      'UPDATE webcal_categories SET cat_icon_mime = ? WHERE cat_id = ?',
      [$iconMimeType, $catId]
  );
}

// Does the category belong to the user?
$is_my_event = false;
$id = getValue('id');
$catname = getValue('catname');
// prohibit any html in category name (including <script>)
$catname = strip_tags($catname);
$catcolor = getValue('catcolor');
$isglobal = getValue('isglobal');
$delIcon = getPostValue('delIcon');
if (empty($id))
  $is_my_event = true; // New event.
else {
  $res = dbi_execute('SELECT cat_id, cat_owner FROM webcal_categories
  WHERE cat_id = ?', [$id]);
  if ($res) {
    $row = dbi_fetch_row($res);

    $is_my_event = ($row[0] == $id && $row[1] == $login ||
      (empty($row[1]) && $is_admin));

    dbi_free_result($res);
  } else
    $error = db_error();
}

if (!empty($_FILES['FileName']))
  $file = $_FILES['FileName'];

// Make sure we clear $file if no file was uploaded.
if (!empty($file['tmp_name']) && $file['tmp_name'] == 'none')
  $file = '';

if (!$is_my_event)
  $error = print_not_auth();

$delete = getPostValue('delete');
if (empty($error) && !empty($delete)) {
  // Delete this category.
  if (!dbi_execute(
    'DELETE FROM webcal_categories
    WHERE cat_id = ? AND ( cat_owner = ?'
      . ($is_admin ? ' OR cat_owner IS NULL )' : ' )'),
    [$id, $login]
  )) {
    $error = db_error();
  }

  if (!dbi_execute(
    'DELETE FROM webcal_entry_categories
    WHERE cat_id = ? AND ( cat_owner = ?'
      . ($is_admin ? ' OR cat_owner = '' )' : ' )'),
    [$id, $login]
  )) {
    $error = db_error();
  }
} else if (empty($error) && empty($catname)) {
  $error = translate('Category name is required');
} else if (empty($error)) {
  if (!empty($id)) {
    # Update (don't let them change global status).
    if (!dbi_execute(
      'UPDATE webcal_categories
      SET cat_name = ?, cat_color = ? WHERE cat_id = ?',
      [$catname, $catcolor, $id]
    ))
      $error = db_error();
  } else {
    // Add new category.
    // Get new id.
    $res = dbi_execute('SELECT MAX( cat_id ) FROM webcal_categories');
    if ($res) {
      $row = dbi_fetch_row($res);
      $id = $row[0] + 1;
      dbi_free_result($res);
      // Set catowner to NULL for global category
      $catowner = ($is_admin ? ($isglobal == 'Y' ? null : $login) : $login);
      if (!dbi_execute(
        'INSERT INTO webcal_categories ( cat_id, cat_owner,
        cat_name, cat_color ) VALUES ( ?, ?, ?, ? )',
        [$id, $catowner, $catname, $catcolor]
      ))
        $error = db_error();
    } else
      $error = db_error();
  }
  if (empty($delIcon) && (!empty($ENABLE_ICON_UPLOADS) && $ENABLE_ICON_UPLOADS == 'Y' || $is_admin)) {
    // Save icon if uploaded.
    if (!empty($file['tmp_name'])) {
        if (($file['type'] == 'image/gif' || $file['type'] == 'image/png')
            && $file['size'] <= $icon_max_size) {
            // Get binary data of the icon.
            $iconData = file_get_contents($file['tmp_name']);
            // Update the icon data and MIME type in the database.
            updateIconBlob($id, $iconData, $file['type']);
        } else if ($file['size'] > $icon_max_size) {
            $error = translate('File size exceeds maximum.');
        } else if (
            $file['type'] != 'image/gif' &&
            $file['type'] != 'image/png'
        ) {
            $error = translate('File is not a GIF or PNG image') . ': '
                . $file['type'];
        }
    }
    // Copy icon if local file specified.
    $urlname = getPostvalue('urlname');
    if (!empty($urlname) && file_exists($icon_path . $urlname)) {
        // Get binary data of the icon.
        $iconData = file_get_contents($icon_path . $urlname);
        // Determine the MIME type based on the file extension.
        $iconMimeType = (preg_match('/.(gif|GIF)$/', $urlname)) ? 'image/gif' : 'image/png';
        // Update the icon data and MIME type in the database.
        updateIconBlob($id, $iconData, $iconMimeType);
    }
  }
}

if (empty($error))
  do_redirect('category.php');

print_header();
echo print_error($error) . print_trailer();
