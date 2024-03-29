<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\View\Asset\PreProcessor;

use Magento\Framework\View\Asset\PreProcessorInterface;

/**
 * Interface AlternativeSourceInterface
 */
interface AlternativeSourceInterface extends PreProcessorInterface
{
    /**
     * Get extensions names of alternatives
     *
     * @return string[]
     */
    public function getAlternativesExtensionsNames();
}
