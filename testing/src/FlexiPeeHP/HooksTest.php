<?php

namespace Test\FlexiPeeHP;

use FlexiPeeHP\Hooks;
use FlexiPeeHP\Changes;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2016-05-24 at 14:37:54.
 */
class HooksTest extends FlexiBeeRWTest
{
    /**
     * @var Hooks
     */
    protected $object;

    /**
     * Changes API enabler/disabler
     * @var Changes
     */
    protected $changes;

    /**
     * Onetime Hook for tests
     * @var string 
     */
    public $testHookName = null;

    public function __construct($name = null, array $data = array(),
                                $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->testHookName = 'http://localhost/'.\Ease\Sand::randomString().'webhook.php';
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new Hooks();
    }

    /**
     * @covers FlexiPeeHP\Hooks::register
     */
    public function testRegister()
    {
        $this->changes = new Changes();
        $this->changes->enable();
        $this->object->setDataValue('skipUrlTest', 'true');
        $result        = $this->object->register($this->testHookName);
        $this->assertTrue($result);
        $result2       = $this->object->register($this->testHookName);
        $this->assertFalse($result2);
    }

    /**
     * @covers FlexiPeeHP\Hooks::getFlexiData
     * @depends testRegister
     */
    public function testGetFlexiData()
    {
        $flexidata = $this->object->getFlexiData();
        $this->assertArrayHasKey(0, $flexidata);
        $this->assertArrayHasKey('id', $flexidata[0]);
    }

    /**
     * @covers FlexiPeeHP\Hooks::recordExists
     * @depends testRegister
     */
    public function testRecordExists()
    {
        $this->assertNull($this->object->recordExists());
    }

    /**
     * @covers FlexiPeeHP\Hooks::refresh
     * @depends testRegister
     */
    public function testRefresh()
    {
        $hooks = $this->object->getAllFromFlexibee();
        $this->assertTrue($this->object->refresh(current(end($hooks))));
    }

    /**
     * @covers FlexiPeeHP\Hooks::unregister
     * @depends testRegister
     */
    public function testUnregister()
    {
        $hooks = $this->object->getAllFromFlexibee();
        $this->assertTrue($this->object->unregister(current(end($hooks))));
    }

    /**
     * Disable ChangesAPI
     */
    protected function tearDown()
    {
        
    }

}
