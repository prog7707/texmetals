<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Ui\DataProvider;

use Magento\Framework\Data\Collection;

/**
 * AddFilterToCollection interface
 */
interface AddFilterToCollectionInterface
{
    /**
     * @param Collection $collection
     * @param string $field
     * @param string|null $condition
     * @return void
     */
    public function addFilter(Collection $collection, $field, $condition = null);
}
