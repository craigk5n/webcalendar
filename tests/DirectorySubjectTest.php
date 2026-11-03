<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WebCalendar\Services\DirectorySubject;

require_once __DIR__ . '/../app/Services/DirectorySubject.php';

final class DirectorySubjectTest extends TestCase
{
  public function testDirectoryReplacementAndAuthorityChangesProduceDifferentSubjects(): void
  {
    $entry = ['dn' => 'uid=alice,dc=example', 'entryuuid' => ['count' => 1, 0 => 'original-entry']];
    $subject = DirectorySubject::fromEntry($entry, 'directory-one');
    self::assertNotNull($subject);
    self::assertSame($subject, DirectorySubject::fromEntry($entry, 'directory-one'));
    $entry['entryuuid'][0] = 'replacement-entry';
    self::assertNotSame($subject, DirectorySubject::fromEntry($entry, 'directory-one'));
    $entry['entryuuid'][0] = 'original-entry';
    self::assertNotSame($subject, DirectorySubject::fromEntry($entry, 'directory-two'));
  }

  public function testBinaryActiveDirectoryGuidIsSupported(): void
  {
    $entry = ['objectguid' => ['count' => 1, 0 => "\0\x80synthetic-guid\xff"]];
    self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', DirectorySubject::fromEntry($entry, 'ad'));
  }

  public function testDnAndUsernameAreNotAcceptedAsImmutableIdentifiers(): void
  {
    self::assertNull(DirectorySubject::fromEntry(['dn' => 'uid=alice,dc=example', 'uid' => ['count' => 1, 0 => 'alice']], 'directory'));
    self::assertNull(DirectorySubject::fromEntry(['entryuuid' => ['count' => 1, 0 => '']], 'directory'));
    self::assertNull(DirectorySubject::fromEntry(['entryuuid' => ['count' => 2, 0 => 'one', 1 => 'two']], 'directory'));
  }
}
