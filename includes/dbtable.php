<?php

if (preg_match("/\/includes\//", $PHP_SELF)) {
    die ("You can't access this file directly!");
}

// This file contains some convenient functions for editing rows
// in a table.
// You need to define the tables (typically this is done in tables.php).



// $tablear - array that defines table (see tables.php)
// $fieldname - name of field
function dbtable_get_field_index ( $tablear, $fieldname ) {
  global $error;
  for ( $j = 0; $j < count ( $tablear ); $j++ ) {
    if ( $tablear[$j]["name"] == $fieldname ) {
      //echo "found $fieldname $j<br />";
      return $j;
    }
  }
  $error = "dbtable_get_field_index: Invalid fieldname \"$fieldname\".";
  return -1;
}


// Create a table for editing a database table entry
// $tablear - array that defines table (see tables.php)
// $valuesar - array of current values
// $action - URL to post form to (or "" for display-only)
// $actionlabel - Value to put on submit form ("Save", etc.)
// $hidden - array of hidden form variables (name1,value1,name2,value2,...)
function dbtable_to_html ( $tablear, $valuesar, $action="", $formname="",
  $actionlabel="", $hidden="" ) {
  global $CELLBG;
  $ret = "<table style=\"border-width:0px;\" cellspacing=\"0\" cellpadding=\"0\">" .
    "<tr><td style=\"background-color:#000000;\">" .
    "<table style=\"border-width:0px; width:100%;\" cellspacing=\"1\" cellpadding=\"2\">" .
    "<tr><td style=\"width:100%; background-color:$CELLBG;\">" .
    "<table style=\"border-width:0px; width:100%;\">\n";
  if ( ! is_array ( $tablear ) ) {
    return "Error: dbtable_to_html parameter 1 is not an array!\n<br />\n";
  }
  if ( $action != "" ) {
    $ret .= "<form action=\"$action\" method=\"post\"";
    if ( $formname != "" )
      $ret .= " name=\"$formname\"";
    $ret .= ">";
    if ( is_array ( $hidden ) ) {
      for ( $i = 0; $i < count ( $hidden ); $i += 2 ) {
        $ret .= "<input type=\"hidden\" name=\"" . $hidden[$i] .
          "\" value=\"" . $hidden[$i+1] . "\" />";
      }
    }
  }
  for ( $i = 0; $i < count ( $tablear ); $i++ ) {
    if ( ! empty ( $tablear[$i]["hide"] ) )
      continue;
    if ( ! empty ( $action ) && ! empty ( $tablear[$i]["calculated"] ) )
      continue;
    $ret .= "<tr><td style=\"vertical-align:top;\">";
    if ( ! empty ( $tablear[$i]["prompt"] ) ) {
      $ret .= "<b";
      if ( ! empty ( $tablear[$i]["tooltip"] ) )
        $ret .= " class=\"tooltip\" title=\"" .  $tablear[$i]["tooltip"] . "\"";
      $ret .= ">" . $tablear[$i]["prompt"] . ":</b></td>\n";
    } else {
      $ret .= "&nbsp;</td>";
    }
    $ret .= "<td style=\"vertical-align:top;\">";
    if ( empty ( $tablear[$i]["noneditable"] ) && ! empty ( $action ) ) {
      if ( $tablear[$i]["type"] == "text" || 
        $tablear[$i]["type"] == "int" || $tablear[$i]["type"] == "float" ) {
        $ret .= "<input type=\"text\" name=\"" . $tablear[$i]["name"] .
          "\"";
        if ( ! empty ( $tablear[$i]["maxlength"] ) )
          $ret .= " maxlength=\"" .  $tablear[$i]["maxlength"] . "\"";
        if ( ! empty ( $tablear[$i]["length"] ) )
        $ret .= " size=\"" . $tablear[$i]["length"] . "\"";
        if ( ! empty ( $valuesar[$i] ) )
          $ret .= " value=\"" . htmlspecialchars ( $valuesar[$i] ) . "\"";
        $ret .= " />";
      } else if ( $tablear[$i]["type"] == "boolean" ) {
        $ret .= "<input type=\"radio\" value=\"Y\" name=\"" . $tablear[$i]["name"] .
          "\"";
        if ( $valuesar[$i] == "Y" )
          $ret .= " checked=\"checked\"";
        $ret .= "> " . translate("Yes") . "&nbsp;&nbsp;&nbsp;";
        $ret .= "<input type=\"radio\" value=\"N\" name=\"" . $tablear[$i]["name"] .
          "\"";
        if ( $valuesar[$i] != "Y" )
          $ret .= " checked=\"checked\"";
        $ret .= "> " . translate("No");
      } else if ( $tablear[$i]["type"] == "date" ) {
        $ret .= date_selection_html ( $tablear[$i]["name"], $valuesar[$i] );
      } else if ( $tablear[$i]["type"] == "dbdate" ) {
        // '2002-12-31'
        $y = substr ( $valuesar[$i], 0, 4 );
        $m = substr ( $valuesar[$i], 5, 2 );
        $d = substr ( $valuesar[$i], 8, 2 );
        $date = sprintf ( "%04d%02d%02d", $y, $m, $d );
        $ret .= date_selection_html ( $tablear[$i]["name"], $date );
      } else {
        $ret .= "(type " . $tablear[$i]["type"] . " not supported)";
      }
    } else {
      if ( ! empty ( $valuesar[$i] ) ) {
        if ( $tablear[$i]["type"] == "text" ||
          $tablear[$i]["type"] == "int" || $tablear[$i]["type"] == "float" ) {
          $ret .= htmlentities ( $valuesar[$i] );
        } else if ( $tablear[$i]["type"] == "boolean" ) {
          if ( $valuesar[$i] == "Y" || empty ( $valuesar[$i] ) )
            $ret .= translate("Yes");
          else
            $ret .= translate("No");
        } else if ( $tablear[$i]["type"] == "date" ) {
          $ret .= date_to_str ( $valuesar[$i] );
        } else if ( $tablear[$i]["type"] == "dbdate" ) {
          $y = substr ( $valuesar[$i], 0, 4 );
          $m = substr ( $valuesar[$i], 5, 2 );
          $d = substr ( $valuesar[$i], 8, 2 );
          $date = sprintf ( "%04d%02d%02d", $y, $m, $d );
          $ret .= date_to_str ( $date );
        } else {
          $ret .= "(type " . $tablear[$i]["type"] . " not supported)";
        }
      }
    }
    $ret .= "</td></tr>\n";
  }
  if ( ! empty ( $actionlabel ) )
    $ret .= "<tr><td colspan=\"2\" style=\"text-align:center;\"><input type=\"submit\"" .
      " VALUE=\"" . htmlspecialchars ( $actionlabel ) . "\" />" .
      "</td></tr></form>\n";
  $ret .= "</table>\n</td></tr></table>\n</td></tr></table>\n";

  return $ret;
}


// Print rows of a table into an HTML table.  The first column will
// include (optionally) href links to a page which can show further 
// details.
// $tablear - db table (defined in tables.php)
// $tablename - db table name
// $href - URL (%0 will be replaced with field field 0)
// $fields - list of fields to include in table.
// $keys - array of db key fields (field tagged with "iskey" => 1)
// $order - SQL order text
function dbtable_html_list ( $tablear, $tablename, $href, $fields,
  $keys, $order ) {
  global $THBG, $THFG, $CELLBG;
  if ( ! is_array ( $tablear ) )
    return "Error: dbtable_to_html_list parameter 1 is not an array!\n<br />\n";
  if ( ! is_array ( $fields ) )
    return "Error: dbtable_to_html_list parameter 2 is not an array!\n<br />\n";
  if ( ! is_array ( $keys ) )
    return "Error: dbtable_to_html_list parameter 3 is not an array!\n<br />\n";
  $ret = "<table style=\"border-width:0px;\" cellspacing=\"0\" cellpadding=\"0\">" .
    "<tr><td style=\"background-color:#000000;\">" .
    "<table style=\"border-width:0px; width:100%;\" cellspacing=\"1\" cellpadding=\"2\">" .
    "<tr><td style=\"width:100%; background-color:$CELLBG;\">" .
    "<table style=\"border-width:0px; width:100%;\">\n";
  // header
  $ret .= "<tr>";
  for ( $i = 0; $i < count ( $fields ); $i++ ) {
    $ind = dbtable_get_field_index ( $tablear, $fields[$i] );
/*
    if ( $ind < 0 )
      echo "Error: dbtable_html_list invalid fieldname \"$fields[$i]\" $i\n"; exit;
*/
    if ( empty ( $tablear[$ind]["hide"] ) )
      $ret .= "<th style=\"background-color:$THBG; color:$THFG;\">" .
        $tablear[$ind]["prompt"] .
        "</th>";
  }
  $ret .= "</tr>\n";
  $sql = "SELECT " . $fields[0];
  for ( $i = 1; $i < count ( $fields ); $i++ ) {
    $sql .= ", " . $fields[$i];
  }
  $sql .= " FROM " . $tablename . " ";
  if ( is_array ( $keys ) && count ( $keys ) > 0 ) {
    $sql .= "WHERE ";
    $first = 1;
    for ( $i = 0; $i < count ( $tablear ); $i++ ) {
      if ( ! empty ( $tablear[$i]["iskey"] ) ) {
        if ( empty ( $keys[$tablear[$i]["name"]] ) ) {
          //echo "Error: key value for " . $tablear[$i]["name"] . " not set.\n";
          //exit;
        } else {
          if ( $first )
            $first = 0;
          else
            $sql .= " AND ";
          $sql .= $tablear[$i]["name"] . " = " ;
          if ( $tablear[$i]["type"] == "int" ||
            $tablear[$i]["type"] == "float" || $tablear[$i]["type"] == "date" )
            $sql .= $keys[$tablear[$i]["name"]];
          else
            $sql .= "'" . $keys[$tablear[$i]["name"]] . "'";
        }
      }
    }
  }
  if ( ! empty ( $order ) )
    $sql .= " ORDER BY " . $order;
  //echo "SQL: $sql<P>\n";
  $res = dbi_query ( $sql );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $ret .= "<tr>";
      $first_href = 1;
      $first = 1;
      for ( $i = 0; $i < count ( $fields ); $i++ ) {
        // check data type (date)
        $ind = dbtable_get_field_index ( $tablear, $fields[$i] );
        if ( empty ( $tablear[$ind]["hide"] ) ) {
          $ret .= "<td style=\"background-color:$CELLBG; vertical-align:top;\">";
          if ( $tablear[$ind]["type"] == "date" )
            $val = date_to_str ( $row[$i], "", 1, 1 );
          else if ( $tablear[$ind]["type"] == "dbdate" ) {
            $y = substr ( $row[$i], 0, 4 );
            $m = substr ( $row[$i], 5, 2 );
            $d = substr ( $row[$i], 8, 2 );
            $date = sprintf ( "%04d%02d%02d", $y, $m, $d );
            $val = date_to_str ( $date, "", 1, 1 );
          } else
            $val = htmlentities ( $row[$i] );
          if ( $first_href && ! empty ( $href ) ) {
            $first_href = 0;
            $url = $href;
            for ( $j = count ( $fields ) - 1; $j >= 0; $j-- ) {
              $url = str_replace ( "%$j", $row[$j], $url );
            }
            $ret .= "<a href=\"$url\">" . $val . "</a>";
          } else {
            $ret .= $val;
          }
          $ret .= "</td>";
        }
      }
      $ret .= "</tr>\n";
    }
  } else {
    echo translate("Database error") . ": " . dbi_error (); exit;
  }
  $ret .= "</table>\n</td></tr></table>\n</td></tr></table>\n";
  return $ret;
}



// Load a single row of a db table
// $tablear - db table (defined in tables.php)
// $tablename - db table name
// $keys - array of db key fields (field tagged with "iskey" => 1)
function dbtable_load ( $tablear, $tablename, $keys ) {
  $ret = false;
  $sql = "SELECT ";
  if ( ! is_array ( $tablear ) ) {
    echo "Error: dbtable_load parameter 1 is not an array!\n<br />\n";
    exit;
  }
  if ( ! is_array ( $keys ) ) {
    echo "Error: dbtable_load parameter 3 is not an array!\n<br />\n";
    exit;
  }
  $first = 1;
  for ( $i = 0; $i < count ( $tablear ); $i++ ) {
    if ( $first )
      $first = 0;
    else
      $sql .= ", ";
    if ( empty ( $tablear[$i]["name"] ) ) {
      echo "Error: dbtable_load $tablename field $i does not define name.\n";
      exit;
    }
    $sql .= $tablear[$i]["name"];
  }
  $sql .= " FROM " . $tablename . " WHERE ";
  $first = 1;
  for ( $i = 0; $i < count ( $tablear ); $i++ ) {
    if ( ! empty ( $tablear[$i]["iskey"] ) ) {
      if ( empty ( $keys[$tablear[$i]["name"]] ) ) {
        //echo "Error: key value for " . $tablear[$i]["name"] . " not set.\n";
        //exit;
      } else {
        if ( $first )
          $first = 0;
        else
          $sql .= " AND ";
        $sql .= $tablear[$i]["name"] . " = " ;
        if ( $tablear[$i]["type"] == "int" ||
          $tablear[$i]["type"] == "float" || $tablear[$i]["type"] == "date" )
          $sql .= $keys[$tablear[$i]["name"]];
        else
          $sql .= "'" . $keys[$tablear[$i]["name"]] . "'";
      }
    }
  }
  //echo "SQL: $sql <br /><br />\n";
  $res = dbi_query ( $sql );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $ret = array ();
      for ( $i = 0; $i < count ( $tablear ); $i++ ) {
        $ret[$i] = $row[$i];
      }
    } else {
      $ret = false; // not found
    }
    dbi_free_result ( $res );
  } else {
    echo translate("Database error") . ": " . dbi_error (); exit;
  }

  return $ret;
}



// Delete a single row of a db table
// $tablear - db table (defined in tables.php)
// $tablename - db table name
// $keys - array of db key fields (field tagged with "iskey" => 1)
function dbtable_delete ( $tablear, $tablename, $keys ) {
  $ret = false;
  if ( ! is_array ( $tablear ) ) {
    echo "Error: dbtable_delete parameter 1 is not an array!\n<br />\n";
    exit;
  }
  if ( ! is_array ( $keys ) ) {
    echo "Error: dbtable_delete parameter 3 is not an array!\n<br />\n";
    exit;
  }
  $sql = "DELETE FROM $tablename WHERE ";
  $first = 1;
  for ( $i = 0; $i < count ( $tablear ); $i++ ) {
    if ( ! empty ( $tablear[$i]["iskey"] ) ) {
      if ( empty ( $keys[$tablear[$i]["name"]] ) ) {
        //echo "Error: key value for " . $tablear[$i]["name"] . " not set.\n";
        //exit;
        continue;
      } else {
        if ( $first )
          $first = 0;
        else
          $sql .= " AND ";
        $sql .= $tablear[$i]["name"] . " = " ;
        if ( $tablear[$i]["type"] == "int" ||
          $tablear[$i]["type"] == "float" || $tablear[$i]["type"] == "date" )
          $sql .= $keys[$tablear[$i]["name"]];
        else
          $sql .= "'" . $keys[$tablear[$i]["name"]] . "'";
      }
    }
  }
  //echo "SQL: $sql <br /><br />\n";
  if ( ! dbi_query ( $sql ) ) {
    echo translate("Database error") . ": " . dbi_error (); exit;
  }

  return $ret;
}


// Add a row into a table (SQL insert)
// $tablear - db table (defined in tables.php)
// $tablename - db table name
// $valuesar - array of values
function dbtable_add ( $tablear, $tablename, $valuesar ) {
  global $error;
  $ret = false;
  $sql = "INSERT INTO " . $tablename . " (";
  if ( ! is_array ( $tablear ) ) {
    echo "Error: dbtable_add parameter 1 is not an array!\n<br />\n";
    exit;
  }
  if ( ! is_array ( $valuesar ) ) {
    echo "Error: dbtable_add parameter 3 is not an array!\n<br />\n";
    exit;
  }
  $first = 1;
  for ( $i = 0; $i < count ( $tablear ); $i++ ) {
    if ( $first )
      $first = 0;
    else
      $sql .= ", ";
    if ( empty ( $tablear[$i]["name"] ) ) {
      echo "Error: dbtable_load $tablename field $i does not define name.\n";
      exit;
    }
    $sql .= $tablear[$i]["name"];
  }
  $sql .= " ) VALUES (";
  $first = 1;
  for ( $i = 0; $i < count ( $tablear ); $i++ ) {
    if ( $first )
      $first = 0;
    else
      $sql .= ", ";
    if ( empty ( $valuesar[$i] ) )
      $sql .= "NULL";
    else if ( $tablear[$i]["type"] == "date" ||
      $tablear[$i]["type"] == "int" )
      $sql .= $valuesar[$i];
    else
      $sql .= "'" . $valuesar[$i] . "'";
  }
  $sql .= " )";
  //echo "SQL: $sql <P>\n";
  if ( ! dbi_query ( $sql ) ) {
    // Shouldn't happen... complain if it does.
    $error = translate("Database error") . ": " . dbi_error ();
    return false;
  }
  return true;
}


// Update a row in a table (SQL update)
// $tablear - db table (defined in tables.php)
// $tablename - db table name
// $valuesar - array of values
function dbtable_update ( $tablear, $tablename, $valuesar ) {
  global $error;
  $sql = "UPDATE " . $tablename . " SET";
  if ( ! is_array ( $tablear ) ) {
    echo "Error: dbtable_update parameter 1 is not an array!\n<br />\n";
    exit;
  }
  if ( ! is_array ( $valuesar ) ) {
    echo "Error: dbtable_update parameter 3 is not an array!\n<br />\n";
    exit;
  }
  $first = 1;
  for ( $i = 0; $i < count ( $tablear ); $i++ ) {
    if ( ! empty ( $tablear[$i]["iskey"] ) )
      continue;
    if ( $first )
      $first = 0;
    else
      $sql .= ", ";
    if ( empty ( $tablear[$i]["name"] ) ) {
      echo "Error: dbtable_update $tablename field $i does not define name.\n";
      exit;
    }
    $sql .= " " . $tablear[$i]["name"] . " = ";
    if ( empty ( $valuesar[$i] ) ) {
      $sql .= "NULL";
    } else if ( $tablear[$i]["type"] == "int" || 
      $tablear[$i]["type"] == "date" ) {
      $sql .= $valuesar[$i];
    } else
      $sql .= "'" . $valuesar[$i] . "'";
  }
  $sql .= " WHERE";
  $first = 1;
  for ( $i = 0; $i < count ( $tablear ); $i++ ) {
    if ( empty ( $tablear[$i]["iskey"] ) )
      continue;
    if ( $first )
      $first = 0;
    else
      $sql .= " AND";
    if ( empty ( $valuesar[$i] ) ) {
      echo "Error: you must set field $i (" . $tablear[$i]["name"] .
        ") by hand.  Cannot be empty.";
      exit;
    }
    $sql .= " " . $tablear[$i]["name"] . " = '" . $valuesar[$i] . "'";
  }
  //echo "SQL: $sql <P>\n";
  if ( ! dbi_query ( $sql ) ) {
    // Shouldn't happen... complain if it does.
    $error = translate("Database error") . ": " . dbi_error ();
    return false;
  }
  return true;
}


// Generate a new ID
function dbtable_genid ( $tablename, $field ) {
  $ret = 1;

  $sql = "SELECT MAX(" . $field . ") FROM " . $tablename;
  $res = dbi_query ( $sql );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $ret = $row[0] + 1;
    }
    dbi_free_result ( $res );
  }
  return $ret;
}

// Convert an array of db values (with index values 0,1,2,... into
// an associative array (with index values of table column names).
// $tablear - db table (defined in tables.php)
// $valuesar - array of values
function dbtable_build_name_index ( $tablear, $valuesar ) {
  $ret = array ();
  for ( $i = 0; $i < count ( $tablear ); $i++ ) {
    $ret[$tablear[$i]["name"]] = $valuesar[$i];
  }
  return $ret;
}
?>
