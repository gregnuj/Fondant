<?php
namespace Fondant\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\TestSuite\TestCase;
use Fondant\Controller\Component\FondantComponent;

/**
 * Fondant\Controller\Component\FondantComponent Test Case
 */
class FondantComponentTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \Fondant\Controller\Component\FondantComponent
     */
    public $FondantComponent;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $registry = new ComponentRegistry();
        $this->FondantComponent = new FondantComponent($registry);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->FondantComponent);

        parent::tearDown();
    }

    /**
     * Test initial setup
     *
     * @return void
     */
    public function testInitialization()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
