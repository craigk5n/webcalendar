/*
 * $Id$
 */

package us.k5n.webcalendar;

/**
  * Defines the API for receiving an event list once it is
  * returned from the server.
  */
public interface EventDisplayer {

  public void storeEvents ( EventList events );

}

