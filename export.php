<?php
/**
 * Page Description:
 * This page will present the user with forms for exporting calendar events.
 *
 * Input Parameters:
 * None
 */
include_once 'includes/init.php';
include_once 'includes/xcal.php';

if ( empty ( $login ) || $login == '__public__' ) {
  // do not allow public access
  do_redirect ( empty ( $STARTVIEW ) ? 'month.php' : "$STARTVIEW" );
  exit;
}

load_user_categories();

$datem = date ( 'm' );
$dateY = date ( 'Y' );
$yearAgo = time () - 365 * 24 * 3600;
$dateYearAgo = date('Ymd', $yearAgo);
$selected = ' selected="selected" ';

print_header('', '', 'onload="updateDateFields();"' );
echo '<h2>' . translate ( 'Export' ) . '</h2>
    <form action="export_handler.php" method="post" name="exportform" id="exportform">
      ' . print_form_key() . '
      <table class="table">
        <tr>
          <td><label for="exformat">' . translate ( 'Export format' )
 . ':</label></td>
          <td>' . generate_export_select ( 'toggel_catfilter' ) . '
          </td>
        </tr>';

if ( is_array ( $categories ) ) {
  echo '
        <tr id="catfilter">
          <td><label for="cat_filter">' . translate ( 'Categories' )
   . ':</label></td>
          <td>
            <select name="cat_filter" id="cat_filter">
              <option value=""' . $selected . '>' . translate ( 'All' )
   . '</option>';

  foreach ( $categories as $K => $V ) {
    if ( $K > 0 )
      echo '
              <option value="' . $K . '">' . htmlentities ( $V['cat_name'] ) . '</option>';
  }

  echo '
            </select>
          </td>
        </tr>';
}
// Only include layers if they are enabled.
$dateYmd = date ( 'Ymd' );
echo ( ! empty ( $LAYERS_STATUS ) && $LAYERS_STATUS == 'Y' ? '
        <tr>
          <td>&nbsp;</td>
          <td>
            <input type="checkbox" name="include_layers" id="include_layers" '
   . 'value="y" />
            <label for="include_layers">' . translate ( 'Include all layers' )
   . '</label>
          </td>
        </tr>'
  : '' ) . '
        <tr>
          <td>&nbsp;</td>
          <td>
            <input type="checkbox" name="include_deleted" id="include_deleted" '
 . 'value="y" />
            <label for="include_deleted">'
 . translate ( 'Include deleted entries' ) . '</label>
          </td>
        </tr>
        <tr>
          <td>&nbsp;</td>
          <td>
            <input type="checkbox" name="use_all_dates" id="exportall" '
 . 'value="y" checked="checked" onclick="toggle_datefields( \'dateArea\', this );" />
            <label for="exportall">' . translate ( 'Export all dates' )
 . '</label>
          </td>
        </tr>
        <tr>
          <td colspan="2">
            <table id="dateArea">
              <tr>
                <td><label>' . translate ( 'Start date' ) . ':</label></td>
                <td>' . date_selection ( 'from', $dateYmd ) . '</td>
              </tr>
              <tr>
                <td><label>' . translate ( 'End date' ) . ':</label></td>
                <td>' . date_selection ( 'end', $dateYmd ) . '</td>
              </tr>
              <tr>
                <td><label>' . translate ( 'Modified since' ) . ':</label></td>
                <td>' . date_selection ( 'mod', $dateYearAgo )  . '</td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td colspan="2"><input class="btn btn-primary" type="submit" value="'
 . translate ( 'Export' ) . '" /></td>
        </tr>
      </table>
    </form>';
?>
<script>
  function updateDateFields () {
    var displayAll = $('#exportall')[0].checked;
    if (displayAll) {
      $('#dateArea').show();
    } else {
      $('#dateArea').hide();
    }
  }
  
  function toggle_datefields( name, ele ) {
    updateDateFields();
  }

  function toggel_catfilter() {
    if ( $('#exformat option:selected').index() == 0 ) {
      // ICalendar
      $('#catfilter').show();
    } else {
      $('#catfilter').hide();
    }
  }
</script>
<?php echo print_trailer (); ?>
