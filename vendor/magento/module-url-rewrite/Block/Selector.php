<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\UrlRewrite\Block;

class Selector extends \Magento\Backend\Block\Template
{
    /**
     * List of available modes from source model
     * key => label
     *
     * @var array
     */
    protected $_modes;

    /**
     * @var string
     */
    protected $_template = 'selector.phtml';

    /**
     * Set block template and get available modes
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_modes = [
            'id' => __('Custom'),
            'category' => __('For category'),
            'product' => __('For product'),
            'cms_page' => __('For CMS page'),
        ];
    }

    /**
     * Available modes getter
     *
     * @return array
     */
    public function getModes()
    {
        return $this->_modes;
    }

    /**
     * Label getter
     *
     * @return \Magento\Framework\Phrase
     */
    public function getSelectorLabel()
    {
        return __('Create URL Rewrite:');
    }

    /**
     * Check whether selection is in specified mode
     *
     * @param string $mode
     * @return bool
     */
    public function isMode($mode)
    {
        return $this->getRequest()->has($mode);
    }

    /**
     * Get default mode
     *
     * @return string
     */
    public function getDefaultMode()
    {
        $keys = array_keys($this->_modes);
        return array_shift($keys);
    }
}
