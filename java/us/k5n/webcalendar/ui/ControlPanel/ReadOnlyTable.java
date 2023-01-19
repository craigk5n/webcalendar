package us.k5n.webcalendar.ui.ControlPanel;

import java.awt.Color;
import java.awt.Component;
import java.awt.event.MouseEvent;
import java.awt.event.MouseListener;
import java.util.Vector;

import javax.swing.JTable;
import javax.swing.table.TableCellRenderer;

/**
 * Overide methods of JTable to prevent cell editing. We also override cell
 * colors to display an entire row in the selected color rather than just a
 * single cell.
 *
 * @author Craig Knudsen
 */
public class ReadOnlyTable extends JTable implements MouseListener {
  int highlightedRow = -1;
  Color evenForeground, evenBackground, oddForeground, oddBackground;
  Color selectedForeground, selectedBackground;
  Color lightGray;
  private int numColumns;

  public Component prepareRenderer ( TableCellRenderer renderer, int rowIndex,
      int vColIndex ) {
    Component c = super.prepareRenderer ( renderer, rowIndex, vColIndex );
    if (rowIndex == highlightedRow) {
      c.setBackground ( selectedBackground );
      c.setForeground ( selectedForeground );
    } else if (rowIndex % 2 == 0) {
      c.setBackground ( evenBackground );
      c.setForeground ( evenForeground );
    } else {
      // If not shaded, match the table's background
      c.setBackground ( oddBackground );
      c.setForeground ( oddForeground );
    }
    return c;
  }

  public ReadOnlyTable ( int rows, int cols ) {
    super ( rows, cols );
    setCellSelectionEnabled ( false );
    setRowSelectionAllowed ( true );
    this.addMouseListener ( this );
    numColumns = cols;
    initColors ();
  }

  public ReadOnlyTable ( Vector rowData, Vector colNames ) {
    super ( rowData, colNames );
    setCellSelectionEnabled ( false );
    setRowSelectionAllowed ( true );
    this.addMouseListener ( this );
    numColumns = colNames.size ();
    initColors ();
  }

  public void updateTableData ( Vector rowData ) {
    for (int i = 0; i < rowData.size (); i++) {
      Vector row = (Vector)rowData.elementAt ( i );
      for (int col = 0; col < numColumns; col++) {
        Object o = rowData.elementAt ( col );
        super.setValueAt ( o, i, col );
      }
    }
  }

  private void initColors () {
    evenForeground = Color.BLACK;
    evenBackground = new Color ( 230, 230, 230 );
    oddForeground = Color.BLACK;
    oddBackground = getBackground ();
    selectedForeground = Color.BLACK;
    selectedBackground = Color.YELLOW;
  }

  public boolean isCellEditable ( int row, int col ) {
    return false;
  }

  /**
   * Track mouse clicks so we can paint an entire row as selected when the user
   * selects a cell.
   */
  public void mouseClicked ( MouseEvent e ) {
    highlightedRow = this.rowAtPoint ( e.getPoint () );
    repaint ();
  }

  public int getSelectedRow () {
    return highlightedRow;
  }

  public int[] getSelectedRows () {
    if (highlightedRow < 0)
      return null;
    int[] ret = new int[1];
    ret[0] = highlightedRow;
    return ret;
  }

  public void mouseEntered ( MouseEvent e ) {
  }

  public void mouseExited ( MouseEvent e ) {
  }

  public void mousePressed ( MouseEvent e ) {
  }

  public void mouseReleased ( MouseEvent e ) {
  }

}
