<?php
namespace Fondant\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\TestSuite\TestCase;
use Fondant\Controller\Component\JqueryUiComponent;

/**
 * Fondant\Controller\Component\JqueryUiComponent Test Case
 */
class JqueryUiComponentTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \Fondant\Controller\Component\JqueryUiComponent
     */
    public $JqueryUi;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $registry = new ComponentRegistry();
        $this->JqueryUi = new JqueryUiComponent($registry);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->JqueryUi);

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
