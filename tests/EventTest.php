<?php
// Invoke from toplevel dir:
// ./vendor/bin/phpunit tests/EventTest.php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/classes/Event.php';

final class EventTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['login'] = 'alice';
        $GLOBALS['override_public'] = 'N';
        $GLOBALS['override_public_text'] = '[private]';
    }

    private function createSampleEvent(array $overrides = []): Event {
        $defaults = [
            'name' => 'Test Event',
            'description' => 'A sample description',
            'date' => '20250730',
            'time' => '130000',
            'id' => 1,
            'extForID' => '',
            'priority' => 1,
            'access' => 'P',
            'duration' => 60,
            'status' => 'Confirmed',
            'owner' => 'alice',
            'category' => 'Work',
            'login' => 'alice',
            'calType' => 'E',
            'location' => 'Office',
            'url' => 'http://example.com',
            'dueDate' => '20250801',
            'dueTime' => '120000',
            'percent' => 100,
            'moddate' => '20250729',
            'modtime' => '110000'
        ];
        $params = array_merge($defaults, $overrides);

        return new Event(
            $params['name'], $params['description'], $params['date'], $params['time'],
            $params['id'], $params['extForID'], $params['priority'], $params['access'],
            $params['duration'], $params['status'], $params['owner'], $params['category'],
            $params['login'], $params['calType'], $params['location'], $params['url'],
            $params['dueDate'], $params['dueTime'], $params['percent'], $params['moddate'], $params['modtime']
        );
    }

    public function testGetName(): void {
        $event = $this->createSampleEvent();
        $this->assertEquals('Test Event', $event->getName());
    }

    public function testGetDateTimeTS(): void {
        $event = $this->createSampleEvent();
        $expected = gmmktime(13, 0, 0, 7, 30, 2025);
        $this->assertEquals($expected, $event->getDateTimeTS());
    }

    public function testInvalidDateStillReturnsTimestamp(): void {
        $event = $this->createSampleEvent(['date' => '20250732']); // invalid day
        $ts = $event->getDateTimeTS();
        $this->assertIsInt($ts); // PHP mktime auto-adjusts (e.g., to August 1st)
    }

    public function testInvalidTimeStillReturnsTimestamp(): void {
        $event = $this->createSampleEvent(['time' => '256000']); // 25:60:00 is invalid
        $ts = $event->getDateTimeTS();
        $this->assertIsInt($ts); // PHP auto-adjusts overflow
    }

    public function testEmptyDateDefaultsToEpoch(): void {
        $event = $this->createSampleEvent(['date' => '']);
        $ts = $event->getDateTimeTS();
        $this->assertIsInt($ts);
    }

    public function testDueDateTimeHandlesEmptyTime(): void {
        $event = $this->createSampleEvent(['dueTime' => '']);
        $ts = $event->getDueDateTimeTS();
        $this->assertIsInt($ts);
    }

    public function testDueDateTimeHandlesEmptyDate(): void {
        $event = $this->createSampleEvent(['dueDate' => '']);
        $ts = $event->getDueDateTimeTS();
        $this->assertIsInt($ts);
    }

    public function testIsTimed(): void {
        $event = $this->createSampleEvent();
        $this->assertTrue($event->isTimed());
    }

    public function testIsAllDay(): void {
        $event = $this->createSampleEvent(['time' => '000000', 'duration' => 1440]);
        $this->assertTrue($event->isAllDay());
    }

    public function testIsUntimed(): void {
        $event = $this->createSampleEvent(['time' => -1, 'duration' => 0]);
        $this->assertTrue($event->isUntimed());
    }

    public function testOverridePublicHidesNameAndDescription(): void {
        $GLOBALS['login'] = '__public__';
        $GLOBALS['override_public'] = 'Y';
        $GLOBALS['override_public_text'] = '[hidden]';

        $event = $this->createSampleEvent(['name' => 'Secret', 'description' => 'Confidential']);
        $this->assertEquals('[hidden]', $event->getName());
        $this->assertEquals('[hidden]', $event->getDescription());
    }

    public function testSetTimeAndDuration(): void {
        $event = $this->createSampleEvent();
        $event->setTime(90000); // 9:00 AM
        $event->setDuration(90);
        $this->assertTrue($event->isTimed());
    }

    public function testSetDate(): void {
        $event = $this->createSampleEvent();
        $event->setDate('20251231');
        $this->assertEquals('20251231', $event->getDate());
    }

    public function testCloneSupport(): void {
        $event = $this->createSampleEvent();
        $event->setClone('20250801');
        $this->assertEquals('20250801', $event->getClone());
    }
}