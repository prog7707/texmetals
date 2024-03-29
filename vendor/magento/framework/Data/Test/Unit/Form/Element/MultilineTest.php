<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Data\Test\Unit\Form\Element;

class MultilineTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Framework\Data\Form\Element\Multiline
     */
    protected $element;

    /**
     * @var \Magento\Framework\Data\Form\Element\Factory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $elementFactory;

    /**
     * @var \Magento\Framework\Data\Form\Element\CollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $collectionFactory;

    /**
     * @var \Magento\Framework\Escaper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $escaper;

    protected function setUp()
    {
        $this->elementFactory = $this->getMockBuilder('Magento\Framework\Data\Form\Element\Factory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionFactory = $this->getMockBuilder('Magento\Framework\Data\Form\Element\CollectionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->escaper = $this->getMockBuilder('Magento\Framework\Escaper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->element = new \Magento\Framework\Data\Form\Element\Multiline(
            $this->elementFactory,
            $this->collectionFactory,
            $this->escaper
        );
    }

    /**
     * @param mixed $value
     * @param int $index
     * @param string $resultValue
     * @return void
     * @dataProvider dataProviderValues
     */
    public function testGetEscapedValue($value, $index, $resultValue)
    {
        $this->element->setValue($value);

        $result = $this->element->getEscapedValue($index);
        $this->assertEquals($resultValue, $result);
    }

    /**
     * @return array
     */
    public function dataProviderValues()
    {
        return [
            ["", 0, ""],
            ["string1", 0, "string1"],
            ["string1\nstring2", 0, "string1"],
            ["string1\nstring2", 1, "string2"],
            ["string1\nstring2", 2, null],
            [null, 0, null],
        ];
    }
}
