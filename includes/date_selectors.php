<?php
/**
 * Month/Week/Year date selectors.
 *
 * Shared by the Bootstrap top menu (includes/menu.php) and by print_trailer()
 * (includes/init.php) so the selectors can be placed at the top or the bottom
 * of the page. Which one is used depends on the MENU_DATE_TOP setting, and on
 * MENU_ENABLED: with the top menu switched off there is no navbar to hold the
 * selectors, so they always fall through to the trailer.
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license https://gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL
 * @package WebCalendar
 */
defined('_ISVALID') or die('You cannot access this file directly!');

/**
 * Generates the Month, Week and Year dropdowns.
 *
 * @return string  HTML for a navbar list holding the three dropdowns.
 */
function date_selectors_html()
{
  global $thisday, $thismonth, $thisyear;

  $d = (empty($thisday) ? date('d') : $thisday);
  $m = (empty($thismonth) ? date('m') : $thismonth);
  $y = (empty($thisyear) ? date('Y') : $thisyear);

  return '<ul class="navbar-nav flex-row mxr-auto">' . "\n"
    . date_selector_month($m, $y)
    . date_selector_week($d, $m, $y)
    . date_selector_year($y)
    . "</ul>\n";
}

/**
 * Generates the Month dropdown: the tail of last year, all of this year and
 * the start of next year.
 *
 * A dropdown-submenu version covering more years was attempted and abandoned
 * because Bootstrap v4 does not position the submenu correctly. See the
 * commented-out blocks removed from includes/menu.php in the commit that
 * created this file, and the related TODO in print_trailer().
 *
 * @param int $thismonth  Month currently being viewed (1-12)
 * @param int $thisyear   Year currently being viewed
 *
 * @return string  HTML for a single navbar dropdown.
 */
function date_selector_month($thismonth, $thisyear)
{
  $ret = date_selector_open('Month', 'dateSelectorMonth');

  // Last 4 months of the prior year.
  $ret .= '<h6 class="dropdown-header">' . ($thisyear - 1) . "</h6>\n";
  for ($i = 9; $i <= 12; $i++) {
    $ret .= date_selector_item('month.php',
      sprintf('%04d%02d01', $thisyear - 1, $i), month_name($i - 1));
  }
  // All of this year, with the current month in bold.
  $ret .= '<div class="dropdown-divider"></div>' . "\n"
    . '<h6 class="dropdown-header">' . $thisyear . "</h6>\n";
  for ($i = 1; $i <= 12; $i++) {
    $name = month_name($i - 1);
    if ($i == $thismonth)
      $name = '<b>' . $name . '</b>';

    $ret .= date_selector_item('month.php',
      sprintf('%04d%02d01', $thisyear, $i), $name);
  }
  // First 3 months of next year.
  $ret .= '<div class="dropdown-divider"></div>' . "\n"
    . '<h6 class="dropdown-header">' . ($thisyear + 1) . "</h6>\n";
  for ($i = 1; $i <= 3; $i++) {
    $ret .= date_selector_item('month.php',
      sprintf('%04d%02d01', $thisyear + 1, $i), month_name($i - 1));
  }

  return $ret . date_selector_close();
}

/**
 * Generates the Week dropdown: 5 weeks before and 9 weeks after the week
 * currently being viewed.
 *
 * @param int $d  Day currently being viewed
 * @param int $m  Month currently being viewed
 * @param int $y  Year currently being viewed
 *
 * @return string  HTML for a single navbar dropdown.
 */
function date_selector_week($d, $m, $y)
{
  global $DISPLAY_WEEKENDS;

  $ret = date_selector_open('Week', 'dateSelectorWeek');

  $lastDay = ($DISPLAY_WEEKENDS == 'N' ? 4 : 6);
  $thisdate = date('Ymd', mktime(0, 0, 0, $m, $d, $y));
  $wkstart = get_weekday_before($y, $m, $d);
  for ($i = -5; $i <= 9; $i++) {
    $twkstart = bump_local_timestamp($wkstart, 0, 0, 0, 0, 7 * $i, 0);
    $twkend = bump_local_timestamp($twkstart, 0, 0, 0, 0, $lastDay, 0);
    // Skip weeks that fall outside the range we can represent.
    if ($twkstart <= 0 || $twkend >= 2146021200)
      continue;

    $dateSYmd = date('Ymd', $twkstart);
    $dateEYmd = date('Ymd', $twkend);
    $name = (!empty($GLOBALS['PULLDOWN_WEEKNUMBER'])
      && $GLOBALS['PULLDOWN_WEEKNUMBER'] == 'Y'
      ? '(' . date('W', $twkstart + 86400) . ')&nbsp;&nbsp;' : '')
      . sprintf('%s - %s',
        date_to_str($dateSYmd, '__mon__ __dd__', false, true),
        date_to_str($dateEYmd, '__mon__ __dd__', false, true));
    if ($thisdate >= $dateSYmd && $thisdate <= $dateEYmd)
      $name = '<b>' . $name . '</b>';

    $ret .= date_selector_item('week.php', $dateSYmd, $name);
  }

  return $ret . date_selector_close();
}

/**
 * Generates the Year dropdown: 5 years either side of the year currently
 * being viewed.
 *
 * @param int $thisyear  Year currently being viewed
 *
 * @return string  HTML for a single navbar dropdown.
 */
function date_selector_year($thisyear)
{
  $ret = date_selector_open('Year', 'dateSelectorYear');

  for ($i = -5; $i <= 5; $i++) {
    $name = $thisyear + $i;
    if ($i == 0)
      $name = '<b>' . $name . '</b>';

    $ret .= date_selector_item('year.php',
      sprintf('%04d0101', $thisyear + $i), $name);
  }

  return $ret . date_selector_close();
}

/**
 * Generates the opening tags of one navbar dropdown.
 *
 * @param string $label  Untranslated dropdown label
 * @param string $id     Unique element id, used to label the dropdown menu
 *
 * @return string  HTML
 */
function date_selector_open($label, $id)
{
  return '<li class="nav-item dropdown">' . "\n"
    . '<a class="nav-link dropdown-toggle" href="#" id="' . $id . '"'
    . ' data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'
    . translate($label) . "</a>\n"
    . '<ul class="dropdown-menu" aria-labelledby="' . $id . '">' . "\n";
}

/**
 * Generates the closing tags of one navbar dropdown.
 *
 * @return string  HTML
 */
function date_selector_close()
{
  return "</ul>\n</li>\n";
}

/**
 * Generates one entry within a date selector dropdown.
 *
 * @param string $page  Page to link to (month.php, week.php or year.php)
 * @param string $date  Target date in YYYYMMDD format
 * @param string $name  Text of the link (may contain markup)
 *
 * @return string  HTML
 */
function date_selector_item($page, $date, $name)
{
  global $login, $user;

  return '<li><a class="dropdown-item" href="' . $page . '?date=' . $date
    . (!empty($user) && $user != $login
      ? '&amp;user=' . htmlspecialchars($user) : '')
    . '">' . $name . "</a></li>\n";
}
?>
