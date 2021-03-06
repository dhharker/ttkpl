<?php

require_once 'PHPUnit/Framework.php';

require_once dirname(__FILE__) . '/../../../lib/core/misc_base.php';

/**
 * Test class for taUtils.
 * Generated by PHPUnit on 2011-05-04 at 12:09:34.
 */
class caur extends taUtils {

}

class taUtilsTest extends PHPUnit_Framework_TestCase {

    /**
     * @var taUtils
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        $this->object = new caur;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {

    }

    public function testInvWeightedMean() {
        $values = array (100, 200, 300, 400);
        $invWeights = array (
            array (1, 1, 1, 1),
            array (1e100, 1e100, 1, 1),
            array (1, 1, 1e100, 1e100),
        );
        $answers = array (
            250.0,
            350.0,
            150.0,
        );
        foreach ($invWeights as $i => $in)
            $this->assertEquals ((float) $answers[$i], round ($this->object->invWeightedMean($values, $in), 10), "Results are wrong!");

    }

    public function testWeightedMean() {
        $values = array (100, 200, 300, 400);
        $weights = array (
            array (1, 1, 1, 1),
            array (1, 1, 2e100, 2e100),
            array (-1, 1, 2e100, 2e100),
            array (2, 4, 6, 8),
        );
        $answers = array (
            250.0,
            350.0,
            350.0,
            300.0
        );
        foreach ($weights as $i => $in)
            $this->assertEquals ($answers[$i], round ($this->object->weightedMean($values, $in), 10) , "Results are wrong!");

    }

    public function testFilenameFromCrap() {
        // Remove the following lines when you implement this test.
        $str = '';
        $pass = '_-_0123456789_abcdefghijklmnopqrstuvwxyz_abcdefghijklmnopqrstuvwxyz_';
        for ($i = 0; $i <= 255; $i++)
            $str .= chr ($i);
        $this->assertEquals ($pass, $this->object->filenameFromCrap($str));
    }

}
?>
