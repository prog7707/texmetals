<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Migration\Handler\Settings;

class TemplateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return void
     */
    public function testHandle()
    {
        $templateOldFashion = 'old_update_email_template';
        $templateNewStyle = 'new_update_email_template';
        $fieldName = 'value';
        /** @var \Migration\ResourceModel\Record|\PHPUnit_Framework_MockObject_MockObject $recordToHandle */
        $recordToHandle = $this->getMock(
            'Migration\ResourceModel\Record',
            ['getValue', 'setValue', 'getFields'],
            [],
            '',
            false
        );
        $recordToHandle->expects($this->once())->method('getValue')->with($fieldName)->willReturn($templateOldFashion);
        $recordToHandle->expects($this->once())->method('setValue')->with($fieldName, $templateNewStyle);
        $recordToHandle->expects($this->once())->method('getFields')->will($this->returnValue([$fieldName]));
        $oppositeRecord = $this->getMockBuilder('Migration\ResourceModel\Record')
            ->disableOriginalConstructor()
            ->getMock();
        $oppositeRecord->expects($this->once())->method('getValue')->with($fieldName)->willReturn($templateNewStyle);
        $handler = new \Migration\Handler\Settings\Template();
        $handler->setField($fieldName);
        $handler->handle($recordToHandle, $oppositeRecord);
    }
}
