<?php

/*
 * Regression tests for GitHub issue #703 -- "LDAP multiple calls to unbind".
 *
 * includes/user-ldap.php used to keep the LDAP link in a global $ds that was
 * shared by user_search_dn(), user_load_variables(), user_get_users() and
 * get_admins(). Because user_load_variables() calls get_admins() while its own
 * link is still open, the nested call overwrote the caller's $ds with a second
 * link and then closed it. The caller's trailing ldap_close($ds) therefore ran
 * against an already-closed link.
 *
 * On PHP 8 that is fatal: ldap_connect() returns an LDAP\Connection object
 * (it used to return a resource) and closing one twice throws
 * "Error: LDAP connection has already been closed". The @ operator does not
 * suppress a thrown Error, so LDAP logins died with an HTTP 500.
 *
 * The fix makes connect_and_bind() return the link so every caller owns a
 * local one. These tests exercise the real functions from user-ldap.php
 * against a fake LDAP layer, so they need neither an LDAP server nor the
 * php-ldap extension.
 *
 * Note: strict_types is deliberately not declared here -- the code under test
 * is legacy and untyped, and eval()'d code inherits the caller's setting.
 */

namespace WebCalendarTest\LdapStub;

use PHPUnit\Framework\TestCase;

// user_get_users() sorts with the string callable 'sort_users', which resolves
// in the global namespace.
require_once __DIR__ . '/../includes/functions.php';

/** Stand-in for PHP 8's LDAP\Connection object. */
final class FakeLdapLink
{
  public $id;
  public $closed = false;

  public function __construct($id)
  {
    $this->id = $id;
  }
}

/** Records what the code under test did to the directory. */
final class FakeLdap
{
  public static $opened = 0;
  /** @var array<int,int> link id => number of times ldap_close() was called */
  public static $closeCounts = [];
  public static $bindSucceeds = true;
  public static $tlsSucceeds = true;
  public static $adminMembers = [];

  public static function reset()
  {
    self::$opened = 0;
    self::$closeCounts = [];
    self::$bindSucceeds = true;
    self::$tlsSucceeds = true;
    self::$adminMembers = ['jdoe'];
  }

  public static function leakedLinks()
  {
    return self::$opened - count(self::$closeCounts);
  }

  public static function doubleClosedLinks()
  {
    return array_keys(array_filter(self::$closeCounts, function ($n) {
      return $n > 1;
    }));
  }
}

// ---------------------------------------------------------------------------
// Fake LDAP layer. Unqualified calls inside this namespace resolve here before
// falling back to the global (real) functions, which is what lets us drive the
// unmodified code in user-ldap.php.
// ---------------------------------------------------------------------------

function function_exists($name)
{
  // user-ldap.php guards on this before doing anything; keep the tests
  // runnable on builds without the php-ldap extension.
  return $name === 'ldap_connect' ? true : \function_exists($name);
}

function ldap_connect($host = null, $port = null)
{
  return new FakeLdapLink(++FakeLdap::$opened);
}

function ldap_close($link)
{
  if (!$link instanceof FakeLdapLink) {
    throw new \Error('ldap_close(): argument is not an LDAP connection');
  }
  // Mirrors PHP 8 behaviour, which is the whole point of issue #703.
  FakeLdap::$closeCounts[$link->id] =
    (FakeLdap::$closeCounts[$link->id] ?? 0) + 1;
  if ($link->closed) {
    throw new \Error('LDAP connection has already been closed');
  }
  $link->closed = true;
  return true;
}

function ldap_bind($link, $dn = null, $password = null)
{
  require_open($link);
  return FakeLdap::$bindSucceeds;
}

function ldap_set_option($link, $option, $value)
{
  require_open($link);
  return true;
}

function ldap_start_tls($link)
{
  require_open($link);
  return FakeLdap::$tlsSucceeds;
}

function ldap_error($link)
{
  return 'fake ldap error';
}

function ldap_free_result($result)
{
  return true;
}

function ldap_search($link, $base, $filter, $attributes = [])
{
  require_open($link);
  return ['filter' => $filter];
}

function ldap_get_entries($link, $result)
{
  require_open($link);

  // The admin-group lookup in get_admins() searches "(memberuid=*)".
  if (strpos($result['filter'], 'memberuid') !== false) {
    $members = ['count' => count(FakeLdap::$adminMembers)];
    foreach (FakeLdap::$adminMembers as $i => $member) {
      $members[$i] = $member;
    }
    return ['count' => 1, 0 => ['memberuid' => $members]];
  }

  return ['count' => 1, 0 => [
    'dn' => 'uid=jdoe,dc=example,dc=org',
    'uid' => [0 => 'jdoe'],
    'sn' => [0 => 'Doe'],
    'givenname' => [0 => 'John'],
    'cn' => [0 => 'John Doe'],
    'mail' => [0 => 'jdoe@example.org'],
  ]];
}

function require_open($link)
{
  if ($link->closed) {
    throw new \Error('LDAP connection has already been closed');
  }
}

// ---------------------------------------------------------------------------
// Load the real user-ldap.php into this namespace so its unqualified ldap_*
// calls bind to the fakes above.
// ---------------------------------------------------------------------------

$ldapSource = file_get_contents(__DIR__ . '/../includes/user-ldap.php');
$stripped = 0;
$ldapSource = preg_replace('/^<\?php/', '', $ldapSource, 1, $stripped);
if ($stripped !== 1) {
  throw new \RuntimeException('user-ldap.php: could not strip opening tag');
}
$ldapSource = preg_replace('/\?>\s*\z/', '', $ldapSource);
$ldapSource = preg_replace("/^defined \\( '_ISVALID' \\).*$/m", '', $ldapSource, 1, $stripped);
if ($stripped !== 1) {
  throw new \RuntimeException('user-ldap.php: could not strip _ISVALID guard');
}
$ldapSource = preg_replace("/^include_once 'auth-settings.php';$/m", '', $ldapSource, 1, $stripped);
if ($stripped !== 1) {
  throw new \RuntimeException('user-ldap.php: could not strip auth-settings include');
}

// user-ldap.php lower-cases these two at file scope; seed them so evaluating
// the file scope does not read undefined variables.
$ldap_admin_group_attr = 'memberUid';
$ldap_admin_group_type = 'posixGroup';
eval('namespace WebCalendarTest\LdapStub;' . $ldapSource);

final class LdapConnectionLifecycleTest extends TestCase
{
  protected function setUp(): void
  {
    FakeLdap::reset();

    foreach ([
      'error' => '',
      'ldap_server' => 'localhost',
      'ldap_port' => '389',
      'ldap_start_tls' => false,
      'set_ldap_version' => false,
      'ldap_version' => 3,
      'ldap_admin_dn' => '',
      'ldap_admin_pwd' => '',
      'ldap_base_dn' => 'dc=example,dc=org',
      'ldap_login_attr' => 'uid',
      'ldap_user_filter' => '(objectClass=person)',
      'ldap_user_attr' => ['uid', 'sn', 'givenname', 'cn', 'mail'],
      'ldap_admin_group_name' => 'cn=admins,dc=example,dc=org',
      'ldap_admin_group_attr' => 'memberuid',
      'ldap_admin_group_type' => 'posixgroup',
      'NONUSER_PREFIX' => '',
      'PUBLIC_ACCESS' => 'N',
      'PUBLIC_ACCESS_FULLNAME' => 'Public Access',
      // Both caches are request-scoped globals; clear them between tests.
      'cached_admins' => [],
      'cached_user_var' => [],
    ] as $name => $value) {
      $GLOBALS[$name] = $value;
    }
  }

  private function assertLinksBalanced(): void
  {
    self::assertSame(
      [],
      FakeLdap::doubleClosedLinks(),
      'An LDAP link was closed more than once (issue #703).'
    );
    self::assertSame(
      0,
      FakeLdap::leakedLinks(),
      'An LDAP link was opened but never closed.'
    );
  }

  /**
   * The issue #703 crash: user_load_variables() calls get_admins() while its
   * own link is open. Before the fix this threw
   * "LDAP connection has already been closed" and LDAP login returned a 500.
   */
  public function testLoginPathDoesNotCloseTheSameLinkTwice(): void
  {
    $loaded = user_load_variables('jdoe', 'i703');

    self::assertTrue($loaded);
    self::assertSame('John Doe', $GLOBALS['i703fullname']);
    self::assertSame('jdoe@example.org', $GLOBALS['i703email']);
    // 'Y' only if the nested get_admins() call actually ran and returned.
    self::assertSame('Y', $GLOBALS['i703is_admin']);
    self::assertGreaterThan(
      1,
      FakeLdap::$opened,
      'Expected a nested get_admins() lookup on its own link.'
    );
    $this->assertLinksBalanced();
  }

  public function testNonAdminLoginStillResolves(): void
  {
    FakeLdap::$adminMembers = ['someone-else'];

    self::assertTrue(user_load_variables('jdoe', 'i703b'));
    self::assertSame('N', $GLOBALS['i703bis_admin']);
    $this->assertLinksBalanced();
  }

  /**
   * The fix: the link is handed back to the caller instead of being published
   * through a shared global, so nested calls cannot clobber it.
   */
  public function testConnectAndBindReturnsTheLinkNotABoolean(): void
  {
    $ds = connect_and_bind();

    self::assertInstanceOf(FakeLdapLink::class, $ds);
    self::assertArrayNotHasKey(
      'ds',
      $GLOBALS,
      'connect_and_bind() must not publish the link as a global.'
    );

    ldap_close($ds);
    $this->assertLinksBalanced();
  }

  public function testConnectAndBindClosesTheLinkWhenBindFails(): void
  {
    FakeLdap::$bindSucceeds = false;

    self::assertFalse(connect_and_bind());
    self::assertSame('Invalid Admin login for LDAP Server', $GLOBALS['error']);
    $this->assertLinksBalanced();
  }

  public function testConnectAndBindClosesTheLinkWhenTlsFails(): void
  {
    $GLOBALS['ldap_start_tls'] = true;
    FakeLdap::$tlsSucceeds = false;

    self::assertFalse(connect_and_bind());
    self::assertSame('Could not start TLS for LDAP connection', $GLOBALS['error']);
    $this->assertLinksBalanced();
  }

  public function testUserGetUsersClosesEveryLinkExactlyOnce(): void
  {
    $users = user_get_users();

    self::assertCount(1, $users);
    self::assertSame('jdoe', $users[0]['cal_login']);
    self::assertSame('Y', $users[0]['cal_is_admin']);
    $this->assertLinksBalanced();
  }

  /**
   * user_get_users() declared `global $PUBLIC_ACCESS_FULLNAM` (missing the
   * trailing E), so the public entry's fullname resolved to an undefined
   * local instead of the configured value.
   */
  public function testPublicAccessUserGetsTheConfiguredFullname(): void
  {
    $GLOBALS['PUBLIC_ACCESS'] = 'Y';

    $users = user_get_users(true);

    self::assertCount(1, $users);
    self::assertSame('__public__', $users[0]['cal_login']);
    self::assertSame('Public Access', $users[0]['cal_fullname']);
  }

  public function testUserSearchDnClosesItsOwnLink(): void
  {
    self::assertSame('uid=jdoe,dc=example,dc=org', user_search_dn('jdoe'));
    $this->assertLinksBalanced();
  }

  public function testGetAdminsClosesItsOwnLink(): void
  {
    self::assertSame(['jdoe'], get_admins());
    $this->assertLinksBalanced();
  }
}
