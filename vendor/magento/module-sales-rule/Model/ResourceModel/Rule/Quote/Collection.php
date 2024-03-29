<?php
/**
 * Sales Rules resource collection model
 *
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesRule\Model\ResourceModel\Rule\Quote;

class Collection extends \Magento\SalesRule\Model\ResourceModel\Rule\Collection
{
    /**
     * Add websites for load
     *
     * @return $this
     */
    public function _initSelect()
    {
        parent::_initSelect();
        $this->addWebsitesToResult();
        return $this;
    }
}
