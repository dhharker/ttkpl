<?php

require_once 'PHPUnit/Framework.php';

require_once dirname(__FILE__) . '/../../../lib/core/magic_glue.php';

/**
 * Test class for MicroEnvironmentalRecord.
 * Generated by PHPUnit on 2011-05-07 at 15:36:43.
 */
class MicroEnvironmentalRecordTest extends PHPUnit_Framework_TestCase {

    /**
     * @var MicroEnvironmentalRecord
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        $this->object = new MicroEnvironmentalRecord;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {

    }

    /**
     * @todo Implement testAt().
     */
    public function testAt() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testBy().
     */
    public function testBy() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testDeposited().
     */
    public function testDeposited() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testExcavated().
     */
    public function testExcavated() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testMoved().
     */
    public function testMoved() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testIsLocated().
     */
    public function testIsLocated() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement test_interpolateFromToAt().
     */
    public function test_interpolateFromToAt() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement test_setTimeContext().
     */
    public function test_setTimeContext() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     *
     *
     *
     *
     */


    public function test_when() {

        $test = array (
            '2510AD' => '',
            '1999 a.d.' => '',
            'ad 1950' => '',
            'Bce100' => '',
            '1000 BC' => '',
            '-61 b.p' => '-61',
            //'THEN' => '', <-- needs its own test
            '1 year ago' => 0,
            '10 yrs. ago' => 0,

        );
        foreach ($test as $when => $expected) {
            $this->assertEquals ($expected, $this->object->_when($when), "Date input '$when' misunderstood");
        }
    }

    /**
     * @todo Implement test_desc().
     */
    public function test_desc() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement test_tAliasEvent().
     */
    public function test_tAliasEvent() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

}
?>
