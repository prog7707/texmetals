<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Message\Test\Unit;

use Magento\Framework\Message\MessageInterface;

/**
 * \Magento\Framework\Message\Success test case
 */
class SuccessTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Framework\Message\Success
     */
    protected $model;

    protected function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $objectManager->getObject('Magento\Framework\Message\Success');
    }

    public function testGetType()
    {
        $this->assertEquals(MessageInterface::TYPE_SUCCESS, $this->model->getType());
    }
}
