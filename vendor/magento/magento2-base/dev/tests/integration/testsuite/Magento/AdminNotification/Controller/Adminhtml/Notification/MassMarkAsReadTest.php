<?php
/***
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminNotification\Controller\Adminhtml\Notification;

class MassMarkAsReadTest extends \Magento\TestFramework\TestCase\AbstractBackendController
{
    public function setUp()
    {
        $this->resource = 'Magento_AdminNotification::mark_as_read';
        $this->uri = 'backend/admin/notification/massmarkasread';
        parent::setUp();
    }
}
