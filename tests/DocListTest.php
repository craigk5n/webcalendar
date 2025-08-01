<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/classes/Doc.php';
require_once __DIR__ . '/../includes/classes/DocList.php';

final class DocListTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['ALLOW_ATTACH'] = 'Y';
        $GLOBALS['ALLOW_COMMENTS'] = 'Y';

        if (!function_exists('dbi_execute')) {
            function dbi_execute($sql) {
                return [
                    [101, 42, 'alice', 'doc1.txt', 'Attachment 1', 512, 'text/plain', 'A', 20250801, 90000],
                    [102, 42, 'bob', 'doc2.txt', 'Attachment 2', 1024, 'text/plain', 'A', 20250802, 103000],
                ];
            }

            function dbi_fetch_row(&$res) {
                return array_shift($res);
            }

            function dbi_free_result(&$res) {
                $res = null;
            }
        }
        if(!function_exists("die_miserable_death")) {
            function die_miserable_death($message) {
                echo "$message\n";
            }
        }
    }

    public function testConstructorCreatesCorrectSize(): void {
        $list = new DocList(42, 'A');
        $this->assertEquals(2, $list->getSize());
    }

    public function testGetDocReturnsValidDoc(): void {
        $list = new DocList(42, 'A');
        $doc = $list->getDoc(0);
        $this->assertInstanceOf(Doc::class, $doc);
    }

    public function testGetDocOutOfBoundsReturnsNull(): void {
        $list = new DocList(42, 'A');
        $this->assertNull($list->getDoc(-1));
        $this->assertNull($list->getDoc(100)); // beyond range
    }

    public function testGetEventIdReturnsExpected(): void {
        $list = new DocList(42, 'A');
        $this->assertEquals(42, $list->getEventId());
    }

    public function testInvalidDocListTypeDies(): void {
        $this->expectOutputRegex('/Invalid DocList type/');
        @new DocList(42, 'X');
    }
}
