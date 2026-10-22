<?php
/**
 * The cal_type values written to webcal_entry_log.
 *
 * These were defined inline in WebCalendar::_initFunctions(), which only the
 * web pages and the scripts under tools/ ever run. bin/webcal.php loads
 * configuration and the database layer without it, so import_data() reached
 * LOG_CREATE and died with "Undefined constant", and `user reset-password`
 * had to pass the literal 'u' with a comment explaining why. One copy,
 * included from both.
 *
 * They are single characters because that is the width of the column; the
 * letters are not mnemonic and must not be changed, since every existing row
 * is already recorded with them.
 *
 * @package WebCalendar
 */

// _initFunctions() may run after a command line entry point has already
// included this, and define() on an existing constant is a warning.
if (!defined('LOG_APPROVE')) {
  define('LOG_APPROVE',       'A');
  define('LOG_APPROVE_J',     'P');
  define('LOG_APPROVE_T',     'H');
  define('LOG_ATTACHMENT',    'T');
  define('LOG_COMMENT',       'M');
  define('LOG_CREATE',        'C');
  define('LOG_CREATE_J',      'I');
  define('LOG_CREATE_T',      'G');
  define('LOG_DELETE',        'D');
  define('LOG_DELETE_J',      'V');
  define('LOG_DELETE_T',      'L');
  define('LOG_LOGIN_FAILURE', 'x');
  define('LOG_NEWUSER_EMAIL', 'E');
  define('LOG_NEWUSER_FULL',  'F');
  define('LOG_NOTIFICATION',  'N');
  define('LOG_REJECT',        'X');
  define('LOG_REJECT_J',      'Q');
  define('LOG_REJECT_T',      'J');
  define('LOG_REMINDER',      'R');
  define('LOG_UPDATE',        'U');
  define('LOG_UPDATE_J',      'S');
  define('LOG_UPDATE_T',      'K');
  define('LOG_USER_ADD',      'a');
  define('LOG_USER_DELETE',   'd');
  define('LOG_USER_UPDATE',   'u');
  define('SECURITY_VIOLATION', 'Z');
  define('LOG_SYSTEM',        'Y');
}
