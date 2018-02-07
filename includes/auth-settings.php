<?php
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );

//	User management and authentication scheme parameters.


//  Global user management `whishes' (some may be enabled/disabled by specific
// authentication schemes.
//  Note: some external authentication schemes supporting user deletion
// only purges local (i.e.: webcal tables) user data. Real user deletion
// should be performed at the authentication scheme level if needed.

$user_can_update_password =	true;
$admin_can_add_user =		true;
$admin_can_delete_user =	true;
$admin_can_disable_user =	false;


switch ($user_inc) {

case 'user.php':	// Scheme: web or HTTP. Password in webcal_user table

	// No module-specific settings.

	break;

case 'user-ldap.php':	// Scheme: LDAP. Password in LDAP.
	//------ LDAP General Server Settings ------//
	//
	// Name or address of the LDAP server
	//  For SSL/TLS use 'ldaps://localhost'
	$ldap_server = 'localhost';

	// Port LDAP listens on (default 389)
	$ldap_port = '389';

	// Use TLS for the connection (not the same as ldaps://)
	$ldap_start_tls = false;

	// If you need to set LDAP_OPT_PROTOCOL_VERSION
	$set_ldap_version = false;
	$ldap_version = '3'; // (usually 3)

	// base DN to search for users
	$ldap_base_dn = 'ou=people,dc=company,dc=com';

	// The ldap attribute used to find a user (login).
	// E.g., if you use cn,  your login might be "Jane Smith"
	//       if you use uid, your login might be "jsmith"
	$ldap_login_attr = 'uid';

	// Account used to bind to the server and search for information.
	// This user must have the correct rights to perform search.
	// If left empty the search will be made in anonymous.
	//
	// *** We do NOT recommend storing the root LDAP account info here ***
	$ldap_admin_dn = '';  // user DN
	$ldap_admin_pwd = ''; // user password

	//------ Admin Group Settings ------//
	//
	// A group name (complete DN) to find users with admin rights
	$ldap_admin_group_name = 'cn=webcal_admin,ou=group,dc=company,dc=com';

	// What type of group do we want (posixgroup, groupofnames,
	// groupofuniquenames)
	$ldap_admin_group_type = 'posixgroup';

	// The LDAP attribute used to store member of a group
	$ldap_admin_group_attr = 'memberuid';

	//------ LDAP Filter Settings ------//
	//
	// LDAP filter used to limit search results and login authentication
	$ldap_user_filter = '(objectclass=person)';

	// Attributes to fetch from LDAP and corresponding user variables in the
	// application. Do change according to your LDAP Schema
	$ldap_user_attr = [
	  // LDAP attribute   //WebCalendar variable
	  'uid',              //login
	  'sn',               //lastname
	  'givenname',        //firstname
	  'cn',               //fullname
	  'mail'              //email
  ];

	break;

case 'user-nis.php':	// Scheme: NIS. Password in NIS.
	// $user_external_group = 100;	// Enable to limit to a group.
	$user_external_email = 'domain.com';
	break;

case 'user-imap.php':	// Scheme IMAP. Password verified by IMAP.
	// Allow auto-creation of WebCalendar Accounts for fully
	//  authenticated users.
	$allow_auto_create = true;

	// 'auth_imap' configuration settings "borrowed" from the Meeting
	//   Room Booking System
	//  https://sourceforge.net/projects/mrbs
	//  GNU General Public License (GPL)
	$imap_host = 'yourserver.com';	// Where is the IMAP server.
	$imap_port = '143';		// The IMAP server port.
	break;

case 'user-app-joomla.php':	// Scheme: Joomla. User must be logged in.
	// Directory that contains the joomla configuration.php file (with
	//  trailing slash).
	$app_path = '/usr/local/www/data/joomla/';

	// Set the group id(s) of the joomla group(s) you want to
	//  be webcal admins.
	// Default is set to the 'Super Administrator' and
	//  'Administrator' groups.
	// Groups in core_acl_aro_groups table.
	$app_admin_gid = ['24','25'];
	break;

case 'user-app-postnuke.php':	// Scheme: PostNuke. User must be logged in.
	// Location of postnuke config.php file (with trailing slash).
	$app_path = '/usr/local/www/data/postnuke/';

	// URL to postnuke (with trailing slash).
	$app_url = 'http://'.$_SERVER['SERVER_NAME'].'/postnuke/';

	// Table Prefix.
	$pn_table_prefix = 'pn_';

	// Set the group id of the postnuke group you want to be webcal admins.
	// Default is set to the postnuke 'Admins' group.
	$pn_admin_gid = '2';
	break;
}
?>
