<?php
/**
 * Dynamic CSS styles used in WebCalendar.
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @package WebCalendar
 *
 *                         HOW TO READ THIS DOCUMENT
 *
 * There are two main parts to every CSS style: 'selector' & 'declaration'.
 *   EXAMPLE:
 *     body {
 *       color: red;
 *     }
 * The selector in the example above is 'body',
 * while its declaration is 'color: red;'.
 * Each declaration has two parts: 'property' & 'value'.
 *
 * In the example above, there is only one declaraion ("color: red;").
 * For that declaration, the PROPERTY is "color" and the VALUE is "red".
 *
 * NOTE: Each property must be followed by a colon (:),
 *       and each value must be followed by a semi-colon (;).
 *
 * Each selector can contain multiple declarations.
 *   EXAMPLE:
 *     body {
 *       background: black;
 *       color: red;
 *       font-size: 12px;
 *     }
 * In the example above, there are three declarations:
 *   background: black;
 *   color: red;
 *   font-size: 12px;
 *
 * NOTE: The declarations for a given style must be contained
 *       within curly brackets ({ }).
 *
 *                  PHP VARIABLES USED TO STYLE WEBCALENDAR
 *
 * BGCOLOR      - background-color for the page
 * CELLBG       - background-color for normal cells
 *                (not weekends, today, or any other types of cells)
 * FONTS        - default font-family
 * H2COLOR      - text color for text within h2 tags
 * MYEVENTS     - text color for users' events
 * OTHERMONTHBG - background-color for cells that belong to other month
 * POPUP_BG     - background-color for event popups
 * POPUP_FG     - text color for event popups
 * TABLEBG      - background-color for tables
 *                (typically used when the table also has cellspacing,
 *                thereby creating a border effect)
 * TEXTCOLOR    - default text color
 * THBG         - background-color for table headers
 * THFG         - text color for table headers
 * TODAYCELLBG  - background-color for cells that make up today's date
 * WEEKENDBG    - background-color for cells that make up the weekend
 *
 *           SOME OF THE CSS IDS AND CLASSES USED IN WEBCALENDAR
 *  #programname         - link to webcalendar site
 *                         NOTE: modifying this can make this link disappear
 *  #viewd .main th      - participants cell
 *  #year.minical        - contains ALL months
 *                         NOTE: display: block; here
 *                         keeps the caption vertically close to the day names
 *  .dayofmonth          - numerical date links in main calendars
 *  .dayofmonth:hover    - numerical date links in main calendars on hover
 *  .entry               - links to entries/events
 *  .entry img
 *  .layerentry img
 *  .unapprovedentry img - event (or bullet) icon;
 *                         NOTE: must be defined AFTER the .entry, .layerentry,
 *                         and .unapprovedentry classes.
 *  .layerentry          - links to entries/events on layers
 *  .main                - most display pages use this for calendar content
 *  .main td.hasevents   - only use HASEVENTSBG if it differs from CELLBG
 *  .minical             - styles for minicalendars
 *  .minical caption     - really big number above the minicalendar in day.php
 *  .minical th          - formats the day name (i.e. Sun, Mon, etc) in minicals
 *  .minitask            - table appearing in small task window
 *  .new                 - new event icon (i.e. '+' symbol)
 *  .next img            - right arrow images
 *  .prev img            - left arrow images
 *  .printer             - printer-friendly links
 *  .standard            - standard table mainly in prefs.php & admin.php
 *  .unapprovedentry     - links to unapproved entries/events
 *  .weeknumber          - week number in monthview & such
 *  a                    - links that don't have a specific class must be
 *                         defined BEFORE all other link-related classes for
 *                         those classes to work.
 *  img.color            - transparent images used for visual color-selection
 *  img.help             - question mark img linking to help sections
 *
 * display: none; is unhidden by includes/css/print_styles.css
 * for printer-friendly pages and where else needed.
 *
 *                            PHP FUNCTION CALLS
 * A special function, background_css(), will allow the dynamic creation of
 * gradient images to be used for the background of that selector. The image
 * file will be created and cached (if enabled) for faster processing and the
 * url will be returned for inclusion into the final CSS file.
 *   Example: background_css( $GLOBALS['CELLBG'], 50 );
 *   Yields : background: #FFFFFF url( images/cache/FFFFFF-50.png ) repeat-x;
 *
 *                          CSS CACHING AND VIEWING
 * A caching scheme has been implemented to improve performance and reduce
 * download payloads. This file is now called from a helper file called
 * 'css_cacher.php'. Its function is to control cache expiration and compress
 * the data if possible.
 *
 * To view the current CSS definitions from your browser, simply run
 *   http://yourserver/css_cacher.php
 * The resulting file will contain the color and layout preferences for the
 * logged in user or the default values if not logged in.
 *
 * Each page in WebCalendar is assigned a unique ID. This unique ID is
 * determined by taking the name of the page & removing any underscores (_).
 *   Example: edit_entry.php
 *   Results: <body id="editentry">
 */

defined( '_ISVALID' ) or die( 'You cannot access this file directly!' );

/* CSS Variables (Custom Properties) are currently available in all modern browsers. Even IE > 9!

  See http://www.caniuse.com/#search=css%20variables
  and https://developer.mozilla.org/en-US/docs/Web/CSS/Using_CSS_variables

  So, as long as this file, "styles.php" with PHP variables, gets called before
  "styles.css" they should all work. Allowing "styles.css" to cache without the
  need to go through "css_cacher.php". */

/* ":root" matches top-level, usually "html". */
?>
:root {
<?php /* Leave font-size as "px" here and "rem" should scale properly everywhere else. */ ?>
  --def-font-size: 16px;
  --def-font-family: sans-serif;

  --box-default-color: #888888;

  --bgcolor: <?php echo $GLOBALS['BGCOLOR']?>;
  --bgimage: <?php echo$GLOBALS['BGIMAGE']; ?>;
  --bgrepeat: <?php echo$GLOBALS['BGREPEAT']; ?>;
  --captions: <?php echo$GLOBALS['CAPTIONS']; ?>;
  --cellbg: <?php echo$GLOBALS['CELLBG']; ?>;
  --fonts: <?php echo$GLOBALS['FONTS']; ?>;
  --h2color: <?php echo$GLOBALS['H2COLOR']; ?>;
  --haseventsbg: <?php echo$GLOBALS['HASEVENTSBG']; ?>;
  --minicalfont: <?php echo$GLOBALS['MINICALFONT']; ?>;
  --minicalwidth: <?php echo$GLOBALS['MINICALWIDTH']; ?>;
  --myevents: <?php echo$GLOBALS['MYEVENTS']; ?>;
  --nextmonthbg: <?php echo$GLOBALS['NEXTMONTHBG']; ?>;
  --othermonthbg: <?php echo$GLOBALS['OTHERMONTHBG']; ?>;
  --popupfg: <?php echo$GLOBALS['POPUP_FG']; ?>;
  --popupbg: <?php echo$GLOBALS['POPUP_BG']; ?>;
  --prevmonthbg: <?php echo$GLOBALS['PREVMONTHBG']; ?>;
  --tablebg: <?php echo$GLOBALS['TABLEBG']; ?>;
  --textcolor: <?php echo$GLOBALS['TEXTCOLOR']; ?>;
  --thbg: <?php echo$GLOBALS['THBG']; ?>;
  --thfg: <?php echo$GLOBALS['THFG']; ?>;
  --todaycellbg: <?php echo$GLOBALS['TODAYCELLBG']; ?>;
  --weekendbg: <?php echo$GLOBALS['WEEKENDBG']; ?>;
  --weeknumber: <?php echo$GLOBALS['WEEKNUMBER']; ?>;
}
<?php
// TODO: I think these two, among others, may be too specific.
// Do they really need "#month"?
// And, instead of IDs "#nextmonth" and "#prevmonth", would classes ".next" and ".prev" work?
if ( $DISPLAY_TASKS != 'Y' ) { ?>
#month #nextmonth {
  float: right;
}
#month #prevmonth {
  float: left;
}
<?php } ?>

#minicalendar table {
  width: <?php echo empty( $GLOBALS['MINICALWIDTH'] )
  ? '10em' : $GLOBALS['MINICALWIDTH']; ?>;
}

<?php if (  $MENU_ENABLED == 'N' ) { ?>
#dateselector form {
  border-top: 0.0625em solid <?php echo $GLOBALS['TABLEBG'];?>;
}
<?php } ?>

<?php if ( ! empty ( $GLOBALS['BGIMAGE'] ) ) { ?>
body {
  background-image: url( '<php echo $GLOBALS['BGIMAGE'];?>' );
  background-repeats: '<?php echo$GLOBALS['BGREPEAT'];?>' );
}
.popup {
  <?php echo background_css( $GLOBALS['POPUP_BG'], 200 ); ?>
}
<?php } ?>
.main th,
.main th.weekend {
  <?php echo background_css( $GLOBALS['THBG'], 15 );?>
}
.main td {
  <?php echo background_css( $GLOBALS['CELLBG'], 100 ); ?>
}
.main td.weekend {
  <?php echo background_css( $GLOBALS['WEEKENDBG'], 100 ); ?>
}
<?php if  ( $GLOBALS['HASEVENTSBG'] != $GLOBALS['CELLBG'] ) { ?>
.main td.hasevents {
  <?php echo background_css( $GLOBALS['HASEVENTSBG'], 100 ); ?>
}
<?php } ?>
.main td.othermonth {
  <?php echo background_css( $GLOBALS['OTHERMONTHBG'], 100 ); ?>
}
.main td.today, #datesel td #today {
  <?php echo background_css( $GLOBALS['TODAYCELLBG'], 100 ); ?>
}
#admin .main td.weekcell,
#month .main td.weekcell,
#pref .main td.weekcell,
#viewl .main td.weekcell {
  <?php echo background_css( $GLOBALS['THBG'], 50 ); ?>
}
.glance td,
.note {
  <?php echo background_css( $GLOBALS['CELLBG'], 50 );?>
}
#viewt .main th.weekend {
  <?php echo background_css( $GLOBALS['WEEKENDBG'], 15 );?>
}
#login table,
#register table {
  <?php echo background_css( $GLOBALS['CELLBG'], 200 );?>
}
#securityAuditNotes {
  <?php echo background_css( $GLOBALS['CELLBG'], 150 );?>
}
#viewt td.entry {
  <?php echo background_css( $GLOBALS['THBG'], 10 );?>
}
#minicalendar th,
#minicalendar td {
  font-size: <?php echo ( empty( $GLOBALS['MINICALFONT'] )
  ? '0.6875em' : $GLOBALS['MINICALFONT'] ); ?>;
}
<?php if ( $DISPLAY_WEEKENDS == 'N' ) { ?>
#viewt .main tr.weekend,
.main th.weekend,
.main td.weekend,
.minical th.weekend,
.minical td.weekend {
  display: none;
}
<?php } ?>

<?php
if ( $CATEGORIES_ENABLED === 'Y' ) {
  // Need to load user variables so that $is_admin is set before we load
  // categories.
  user_load_variables ( $user, '' );
  load_user_categories ();

  // Default color is $MYEVENTS.  Add a bogus array 'none' element for it.
  $categories['none'] = ['cat_color' => $MYEVENTS];
  foreach ( $categories as $catId => $cat ) {
    if ( $catId == 0 || $catId == -1 )
      continue;

    $color = $cat['cat_color'];
    $fg = '#000000';

    if ( $catId < 0 )
      $catId = 0 - $catId;

    $rgb = [255, 255, 255];

    if ( ! empty ( $color ) ) {
      if ( preg_match ( "/#(.)(.)(.)(.)(.)(.)/", $color ) ) {
        $rgb = html2rgb ( $color );

        // If red+green+blue is less than 50%, then we will
        // assume this color is dark enough to use a white text foreground.
        if ( $rgb[0] + $rgb[1] + $rgb[2] < 384 ) {
          $fg = '#FFFFFF';
        }
      }
    // Gradient
      echo "#combo .cat_ {$catId} { background-color: $color; border: 1px outset $color; color: $fg }\n#month2 .cat_ {$catId} { color: $color }\n";
    }
  }
}
?>
