<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/../includes/translate.php";

// Require the class under test
require_once __DIR__ . '/../includes/classes/Doc.php';

final class DocTest extends TestCase
{

    private function getSampleRow(): array
    {
        return [
            1,                  // cal_blob_id
            42,                 // cal_id
            'jdoe',             // cal_login
            'file.pdf',         // cal_name
            'A PDF file',       // cal_description
            2048,               // cal_size
            'application/pdf',  // cal_mime_type
            'A',                // cal_type
            20250101,           // cal_mod_date
            123456              // cal_mod_time
        ];
    }

    public function testConstructorAcceptsValidRow(): void
    {
        $doc = new Doc($this->getSampleRow());
        $this->assertInstanceOf(Doc::class, $doc);
    }

    public function testGettersReturnExpectedValues(): void
    {
        $doc = new Doc($this->getSampleRow());
        $this->assertEquals(1, $doc->getId());
        $this->assertEquals(42, $doc->getEventId());
        $this->assertEquals('jdoe', $doc->getLogin());
        $this->assertEquals('file.pdf', $doc->getName());
        $this->assertEquals('A PDF file', $doc->getDescription());
        $this->assertEquals(2048, $doc->getSize());
        $this->assertEquals('application/pdf', $doc->getMimeType());
        $this->assertEquals('A', $doc->getType());
        $this->assertEquals(20250101, $doc->getModDate());
        $this->assertEquals(123456, $doc->getModTime());
    }

    public function testGetSummaryContainsExpectedHtml(): void
    {
        $doc = new Doc($this->getSampleRow());
        $summary = $doc->getSummary();
        $this->assertStringContainsString('href="doc.php?blid=1"', $summary);
        $this->assertStringContainsString('file.pdf', $summary);
        $this->assertStringContainsString('A PDF file', $summary);
    }

    public function testAttachmentsEnabledAndDisabled(): void
    {
        $GLOBALS['ALLOW_ATTACH'] = 'Y';
        $this->assertTrue(Doc::attachmentsEnabled());

        $GLOBALS['ALLOW_ATTACH'] = 'N';
        $this->assertFalse(Doc::attachmentsEnabled());
    }

    public function testCommentsEnabledAndDisabled(): void
    {
        $GLOBALS['ALLOW_COMMENTS'] = 'Y';
        $this->assertTrue(Doc::commentsEnabled());

        $GLOBALS['ALLOW_COMMENTS'] = 'N';
        $this->assertFalse(Doc::commentsEnabled());
    }

    public function testGetSQLReturnsExpectedString(): void
    {
        $sql = Doc::getSQL(42, 'A');
        $this->assertStringContainsString("FROM webcal_blob", $sql);
        $this->assertStringContainsString("cal_id = 42", $sql);
        $this->assertStringContainsString("cal_type = 'A'", $sql);
    }

    public function testGetSQLForDocIdReturnsExpectedString(): void
    {
        $sql = Doc::getSQLForDocId(1);
        $this->assertStringContainsString("cal_blob_id = 1", $sql);
    }

}
