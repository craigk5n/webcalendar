package us.k5n.webcalendar.ui.ControlPanel;

import java.awt.Color;
import java.awt.Component;
import java.util.Vector;

import javax.swing.JTable;
import javax.swing.table.TableCellRenderer;

/**
 * Overide methods of JTable to prevent cell editing.
 * 
 * @author Craig Knudsen
 * @version $Id$
 */
public class ReadOnlyTable extends JTable {
  int highlightedRow = -1;
  Color lightGray;

  public Component prepareRenderer ( TableCellRenderer renderer, int rowIndex,
      int vColIndex ) {
    Component c = super.prepareRenderer ( renderer, rowIndex, vColIndex );
    if (rowIndex == highlightedRow) {
      c.setBackground ( Color.blue );
      c.setForeground ( Color.white );
    } else if (rowIndex % 2 == 0) {
      c.setBackground ( lightGray );
      c.setForeground ( Color.black );
    } else {
      // If not shaded, match the table's background
      c.setBackground ( getBackground () );
      c.setForeground ( Color.black );
    }
    return c;
  }

  public ReadOnlyTable ( int rows, int cols ) {
    super ( rows, cols );
    lightGray = new Color ( 220, 220, 220 );
  }

  public ReadOnlyTable ( Vector rowData, Vector colNames ) {
    super ( rowData, colNames );
    lightGray = new Color ( 220, 220, 220 );
  }

  public boolean isCellEditable ( int row, int col ) {
    return false;
  }

}
