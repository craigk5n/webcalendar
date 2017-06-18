<?php
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );
// .
// This file contains some convenient functions for editing rows in a table.
// You need to define the tables (typically this is done in tables.php).
// .
/*
 *
 * @param $tablear   - array that defines table (see tables.php)
   @param $fieldname - name of field
 */
function dbtable_get_field_index ( $tablear, $fieldname ) {
  global $error;

  for ( $j = 0, $cnt = count ( $tablear ); $j < $cnt; $j++ ) {
    if ( $tablear[$j]['name'] == $fieldname )
      return $j;
  }
  $error = 'dbtable_get_field_index: Invalid fieldname "' . $fieldname . '".';

  return -1;
}

/* Create a table for editing a database table entry
 *
 * @param $tablear     - array that defines table (see tables.php)
 * @param $valuesar    - array of current values
 * @param $action      - URL to post form to (or '' for display-only)
 * @param $actionlabel - Value to put on submit form ("Save", etc.)
 * @param $hidden      - array of hidden form variables (name1,value1,name2,value2,...)
 */
function dbtable_to_html ( $tablear, $valuesar, $action = '', $formname = '',
  $actionlabel = '', $hidden = '' ) {
  global $CELLBG;

  $checked = ' checked="checked"';
  $noStr = translate ( 'No' );
  $yesStr = translate ( 'Yes' );

  if ( ! is_array ( $tablear ) ) {
    return 'Error: dbtable_to_html parameter 1 is not an array!<br />' . "\n";
  }

  $ret = '
    <table>
      <tr>
        <td style="background-color:#000000;">
          <table style="border:0; width:100%;" cellspacing="1" cellpadding="2">
            <tr>
              <td style="width:100%; background-color:' . $CELLBG . ';">
                <table style="border:0; width:100%;">';
  if ( ! empty ( $action ) ) {
    $ret .= '
                  <form action="' . $action . '" method="post"'
     . ( empty ( $formname ) ? '' : ' name="' . $formname . '"' )
     . '>';
    if ( is_array ( $hidden ) ) {
      for ( $i = 0, $cnt = count ( $hidden ); $i < $cnt; $i += 2 ) {
        $ret .= '
                    <input type="hidden" name="' . $hidden[$i] . '" value="'
         . $hidden[$i + 1] . '" />';
      }
    }
  }
  for ( $i = 0, $cnt = count ( $tablear ); $i < $cnt; $i++ ) {
    if ( ! empty ( $tablear[$i]['hide'] ) )
      continue;

    if ( ! empty ( $action ) && ! empty ( $tablear[$i]['calculated'] ) )
      continue;

    if ( $tablear[$i]['type'] == 'dbdate' ) {
      // '2006-12-31'
      $y = substr ( $valuesar[$i], 0, 4 );
      $m = substr ( $valuesar[$i], 5, 2 );
      $d = substr ( $valuesar[$i], 8, 2 );
    }

    $ret .= '
                    <tr>
                      <td style="vertical-align:top;">'
     . ( empty ( $tablear[$i]['prompt'] ) ? '&nbsp;' : '<b'
       . ( empty ( $tablear[$i]['tooltip'] )
        ? '' : ' class="tooltip" title="' . $tablear[$i]['tooltip'] . '"' )
       . '>' . $tablear[$i]['prompt'] . ':</b>' ) . '</td>
                      <td style="vertical-align:top;">';
    if ( empty ( $tablear[$i]['noneditable'] ) && ! empty ( $action ) ) {
      if ( in_array ( $tablear[$i]['type'], array ( 'float', 'int', 'text' ) ) )
        $ret .= '
                        <input type="text" name="' . $tablear[$i]['name'] . '"'
         . ( empty ( $tablear[$i]['maxlength'] )
          ? '' : ' maxlength="' . $tablear[$i]['maxlength'] . '"' )
         . ( empty ( $tablear[$i]['length'] )
          ? '' : ' size="' . $tablear[$i]['length'] . '"' )
         . ( empty ( $valuesar[$i] ) ? '' : ' value="'
           . htmlspecialchars ( $valuesar[$i] ) . '"' )
         . ' />';
      elseif ( $tablear[$i]['type'] == 'boolean' )
        $ret .= '
                        <input type="radio" value="Y" name="'
         . $tablear[$i]['name'] . '"' . ( $valuesar[$i] == 'Y' ? $checked : '' )
         . '>' . $yesStr . '&nbsp;&nbsp;&nbsp;
                        <input type="radio" value="N" name="'
         . $tablear[$i]['name'] . '"' . ( $valuesar[$i] != 'Y' ? $checked : '' )
         . '>' . $noStr;
      elseif ( $tablear[$i]['type'] == 'date' )
        $ret .= date_selection ( $tablear[$i]['name'], $valuesar[$i] );
      elseif ( $tablear[$i]['type'] == 'dbdate' )
        $ret .= date_selection ( $tablear[$i]['name'],
          sprintf ( "%04d%02d%02d", $y, $m, $d ) );
      else
        $ret .= '(type ' . $tablear[$i]['type'] . ' not supported)';
    } else {
      if ( ! empty ( $valuesar[$i] ) ) {
        if ( in_array ( $tablear[$i]['type'], array ( 'float', 'int', 'text' ) ) )
          $ret .= htmlentities ( $valuesar[$i] );
        elseif ( $tablear[$i]['type'] == 'boolean' )
          $ret .= ( empty ( $valuesar[$i] ) || $valuesar[$i] == 'Y'
            ? $yesStr : $noStr );
        elseif ( $tablear[$i]['type'] == 'date' )
          $ret .= date_to_str ( $valuesar[$i] );
        elseif ( $tablear[$i]['type'] == 'dbdate' )
          $ret .= date_to_str ( sprintf ( "%04d%02d%02d", $y, $m, $d ) );
        else
          $ret .= '(type ' . $tablear[$i]['type'] . ' not supported)';
      }
    }
    $ret .= '
                      </td>
                    </tr>';
  }

  return $ret . ( empty ( $actionlabel ) ? '' : '
                    <tr>
                      <td colspan="2" style="text-align:center;"><input '
     . 'type="submit" value="' . htmlspecialchars ( $actionlabel ) . '" /></td>
                    </tr>
                  </form>' ) . '
                </table>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>';
}

/* Print rows of a table into an HTML table.
 * The first column will include (optionally) href links to a page
 * which can show further details.
 *
 * @param $tablear   - db table (defined in tables.php)
 * @param $tablename - db table name
 * @param $href      - URL (%0 will be replaced with field field 0)
 * @param $fields    - list of fields to include in table.
 * @param $keys      - array of db key fields (field tagged with "iskey" => 1)
 * @param $order     - SQL order text
 */
function dbtable_html_list ( $tablear, $tablename, $href, $fields,
  $keys, $order ) {
  global $CELLBG, $THBG, $THFG;

  if ( ! is_array ( $tablear ) )
    return 'Error: dbtable_to_html_list parameter 1 is not an array!<br />' . "\n";

  if ( ! is_array ( $fields ) )
    return 'Error: dbtable_to_html_list parameter 2 is not an array!<br />' . "\n";

  if ( ! is_array ( $keys ) )
    return 'Error: dbtable_to_html_list parameter 3 is not an array!<br />' . "\n";

  $ret = '
    <table>
      <tr>
        <td style="background-color:#000000;">
          <table style="border:0; width:100%;" cellspacing="1" cellpadding="2">
            <tr>
              <td style="width:100%; background-color:' . $CELLBG . ';">
                <table style="border:0; width:100%;">
                  <tr>'; // header
  $fieldcnt = count ( $fields );
  for ( $i = 0; $i < $fieldcnt; $i++ ) {
    $ind = dbtable_get_field_index ( $tablear, $fields[$i] );
    /*
    if ( $ind < 0 )
      echo 'Error: dbtable_html_list invalid fieldname "' . $fields[$i] . "\" $i\n";
      exit;
     */
    if ( empty ( $tablear[$ind]['hide'] ) )
      $ret .= '
                    <th style="background-color:' . $THBG . '; color:' . $THFG
       . ';">' . $tablear[$ind]['prompt'] . '</th>';
  }
  $ret .= '
                  </tr>';
  $query_params = array ();
  $sql = 'SELECT ' . $fields[0];

  for ( $i = 1; $i < $fieldcnt; $i++ ) {
    $sql .= ', ' . $fields[$i];
  }
  $sql .= ' FROM ' . $tablename . ' ';
  if ( is_array ( $keys ) && count ( $keys ) > 0 ) {
    $sql .= 'WHERE ';
    $first = 1;
    for ( $i = 0, $cnt = count ( $tablear ); $i < $cnt; $i++ ) {
      if ( ! empty ( $tablear[$i]['iskey'] ) ) {
        if ( empty ( $keys[$tablear[$i]['name']] ) ) {
          // echo 'Error: key value for ' . $tablear[$i]['name'] . ' not set.' . "\n";
          // exit;
        } else {
          if ( $first )
            $first = 0;
          else
            $sql .= ' AND ';

          $query_params[] = $keys[$tablear[$i]['name']];
          $sql .= $tablear[$i]['name'] . ' = ?';
        }
      }
    }
  }
  if ( ! empty ( $order ) )
    $sql .= ' ORDER BY ' . $order;

  $res = dbi_execute ( $sql, $query_params );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $ret .= '
                  <tr>';
      $first_href = 1;
      $first = 1;
      for ( $i = 0; $i < $fieldcnt; $i++ ) {
        // Check data type (date).
        $ind = dbtable_get_field_index ( $tablear, $fields[$i] );
        if ( empty ( $tablear[$ind]['hide'] ) ) {
          $ret .= '
                    <td style="background-color:' . $CELLBG
           . '; vertical-align:top;">';
          if ( $tablear[$ind]['type'] == 'date' )
            $val = date_to_str ( $row[$i], '', 1, 1 );
          elseif ( $tablear[$ind]['type'] == 'dbdate' )
            $val = date_to_str ( sprintf ( "%04d%02d%02d",
                substr ( $row[$i], 0, 4 ),
                substr ( $row[$i], 5, 2 ),
                substr ( $row[$i], 8, 2 ) ), '', 1, 1 );
          else
            $val = htmlentities ( $row[$i] );

          if ( $first_href && ! empty ( $href ) ) {
            $first_href = 0;
            $url = $href;
            for ( $j = count ( $fields ) - 1; $j >= 0; $j-- ) {
              $url = str_replace ( "%$j", $row[$j], $url );
            }
            $ret .= '<a href="' . $url . '">' . $val . '</a>';
          } else
            $ret .= $val;

          $ret .= '</td>';
        }
      }
      $ret .= '
                  </tr>';
    }
  } else
    dbi_error ( true );

  return $ret . '
                </table>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>';
}

/* Load a single row of a db table
 *
 * @param $tablear   - db table (defined in tables.php)
 * @param $tablename - db table name
 * @param $keys      - array of db key fields (field tagged with "iskey" => 1)
 */
function dbtable_load ( $tablear, $tablename, $keys ) {
  $cnt = count ( $tablear );
  $query_params = array ();
  $ret = false;
  $sql = 'SELECT ';

  if ( ! is_array ( $tablear ) ) {
    echo 'Error: dbtable_load parameter 1 is not an array!<br />' . "\n";
    exit;
  }
  if ( ! is_array ( $keys ) ) {
    echo 'Error: dbtable_load parameter 3 is not an array!<br />' . "\n";
    exit;
  }
  $first = 1;
  for ( $i = 0; $i < $cnt; $i++ ) {
    if ( $first )
      $first = 0;
    else
      $sql .= ', ';

    if ( empty ( $tablear[$i]['name'] ) ) {
      echo 'Error: dbtable_load ' . $tablename . ' field ' . $i
       . ' does not define name.' . "\n";
      exit;
    }
    $sql .= $tablear[$i]['name'];
  }
  $first = 1;
  $sql .= ' FROM ' . $tablename . ' WHERE ';
  for ( $i = 0; $i < $cnt; $i++ ) {
    if ( ! empty ( $tablear[$i]['iskey'] ) ) {
      if ( empty ( $keys[$tablear[$i]['name']] ) ) {
        // echo 'Error: key value for ' . $tablear[$i]['name'] . ' not set.' . "\n";
        // exit;
      } else {
        if ( $first )
          $first = 0;
        else
          $sql .= ' AND ';

        $query_params[] = $keys[$tablear[$i]['name']];
        $sql .= $tablear[$i]['name'] . ' = ?';
      }
    }
  }

  $res = dbi_execute ( $sql, $query_params );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $ret = array ();
      for ( $i = 0; $i < $cnt; $i++ ) {
        $ret[$i] = $row[$i];
      }
    } else
      $ret = false; // not found
    dbi_free_result ( $res );
  } else
    dbi_error ( true );

  return $ret;
}

/* Delete a single row of a db table
 *
 * @param $tablear   - db table (defined in tables.php)
 * @param $tablename - db table name
 * @param $keys      - array of db key fields (field tagged with "iskey" => 1)
 */
function dbtable_delete ( $tablear, $tablename, $keys ) {
  $cnt = count ( $tablear );
  $ret = false;

  if ( ! is_array ( $tablear ) ) {
    echo 'Error: dbtable_delete parameter 1 is not an array!<br />' . "\n";
    exit;
  }
  if ( ! is_array ( $keys ) ) {
    echo 'Error: dbtable_delete parameter 3 is not an array!<br />' . "\n";
    exit;
  }
  $first = 1;
  $query_params = array ();
  $sql = 'DELETE FROM ' . $tablename . ' WHERE ';
  for ( $i = 0; $i < $cnt; $i++ ) {
    if ( ! empty ( $tablear[$i]['iskey'] ) ) {
      if ( empty ( $keys[$tablear[$i]['name']] ) )
        // echo 'Error: key value for ' . $tablear[$i]['name'] . ' not set.' . "\n";
        // exit;
        continue;
      else {
        if ( $first )
          $first = 0;
        else
          $sql .= ' AND ';

        $query_params[] = $keys[$tablear[$i]['name']];
        $sql .= $tablear[$i]['name'] . ' = ?';
      }
    }
  }

  if ( ! dbi_execute ( $sql, $query_params ) )
    dbi_error ( true );

  return $ret;
}

/* Add a row into a table (SQL insert)
 *
 * @param $tablear   - db table (defined in tables.php)
 * @param $tablename - db table name
 * @param $valuesar  - array of values
 */
function dbtable_add ( $tablear, $tablename, $valuesar ) {
  global $error;

  $query_params = array ();
  $ret = false;
  $sql = 'INSERT INTO ' . $tablename . ' (';
  if ( ! is_array ( $tablear ) ) {
    echo 'Error: dbtable_add parameter 1 is not an array!<br />' . "\n";
    exit;
  }
  if ( ! is_array ( $valuesar ) ) {
    echo 'Error: dbtable_add parameter 3 is not an array!<br />' . "\n";
    exit;
  }
  $first = 1;
  for ( $i = 0; $i < count ( $tablear ); $i++ ) {
    if ( $first )
      $first = 0;
    else
      $sql .= ', ';

    if ( empty ( $tablear[$i]['name'] ) ) {
      echo 'Error: dbtable_load ' . $tablename . ' field ' . $i
       . ' does not define name.' . "\n";
      exit;
    }
    $sql .= $tablear[$i]['name'];
  }
  $first = 1;
  $sql .= ' ) VALUES (';
  for ( $i = 0, $cnt = count ( $tablear ); $i < $cnt; $i++ ) {
    if ( $first ) {
      $first = 0;
      $sql .= '?';
    } else
      $sql .= ', ?';

    $query_params[] = ( empty ( $valuesar[$i] ) ? null : $valuesar[$i] );
  }
  $sql .= ' )';

  if ( ! dbi_execute ( $sql, $query_params ) ) {
    // Shouldn't happen... complain if it does.
    $error = db_error ();
    return false;
  }
  return true;
}

/* Update a row in a table (SQL update)
 *
 * @param $tablear   - db table (defined in tables.php)
 * @param $tablename - db table name
 * @param $valuesar  - array of values
 */
function dbtable_update ( $tablear, $tablename, $valuesar ) {
  global $error;

  $query_params = array ();
  $sql = 'UPDATE ' . $tablename . ' SET';
  if ( ! is_array ( $tablear ) ) {
    echo 'Error: dbtable_update parameter 1 is not an array!<br />' . "\n";
    exit;
  }
  if ( ! is_array ( $valuesar ) ) {
    echo 'Error: dbtable_update parameter 3 is not an array!<br />' . "\n";
    exit;
  }
  $first = 1;
  for ( $i = 0; $i < count ( $tablear ); $i++ ) {
    if ( ! empty ( $tablear[$i]['iskey'] ) )
      continue;

    if ( $first )
      $first = 0;
    else
      $sql .= ', ';

    if ( empty ( $tablear[$i]['name'] ) ) {
      echo 'Error: dbtable_update ' . $tablename . ' field ' . $i
       . ' does not define name.' . "\n";
      exit;
    }
    $query_params[] = ( empty ( $valuesar[$i] ) ? null : $valuesar[$i] );
    $sql .= ' ' . $tablear[$i]['name'] . ' = ?';
  }
  $first = 1;
  $sql .= ' WHERE';
  for ( $i = 0; $i < count ( $tablear ); $i++ ) {
    if ( empty ( $tablear[$i]['iskey'] ) )
      continue;

    if ( $first )
      $first = 0;
    else
      $sql .= ' AND';

    if ( empty ( $valuesar[$i] ) ) {
      echo 'Error: you must set field ' . $i . ' (' . $tablear[$i]['name']
       . ') by hand. Cannot be empty.';
      exit;
    }
    $query_params[] = $valuesar[$i];
    $sql .= ' ' . $tablear[$i]['name'] . ' = ?';
  }

  if ( ! dbi_execute ( $sql, $query_params ) ) {
    // Shouldn't happen... complain if it does.
    $error = db_error ();
    return false;
  }
  return true;
}

/* Generate a new ID
 */
function dbtable_genid ( $tablename, $field ) {
  $ret = 1;
  $sql = 'SELECT MAX( ' . $field . ' ) FROM ' . $tablename;
  $res = dbi_execute ( $sql );

  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) )
      $ret = $row[0] + 1;

    dbi_free_result ( $res );
  }
  return $ret;
}

/* Convert an array of db values (with index values 0,1,2,...
 * into an associative array (with index values of table column names).
 *
 * @param $tablear  - db table (defined in tables.php)
 * @param $valuesar - array of values
 */
function dbtable_build_name_index ( $tablear, $valuesar ) {
  $ret = array ();
  for ( $i = 0, $cnt = count ( $tablear ); $i < $cnt; $i++ ) {
    $ret[$tablear[$i]['name']] = $valuesar[$i];
  }
  return $ret;
}

?>
