<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Test class for \Magento\Weee\Model\Config
 */
namespace Magento\Weee\Test\Unit\Model;

use \Magento\Weee\Model\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the methods that rely on the ScopeConfigInterface object to provide their return values
     *
     * @param string $method
     * @param string $path
     * @param bool $configValue
     * @param bool $expectedValue
     * @dataProvider dataProviderScopeConfigMethods
     */
    public function testScopeConfigMethods($method, $path, $configValue, $expectedValue)
    {
        $scopeConfigMock = $this->getMockForAbstractClass('Magento\Framework\App\Config\ScopeConfigInterface');
        $scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null)
            ->will($this->returnValue($configValue));
        $scopeConfigMock->expects($this->any())
            ->method('isSetFlag')
            ->with($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null)
            ->will($this->returnValue($configValue));

        $taxData = $this->getMock('Magento\Tax\Helper\Data', [], [], '', false);

        /** @var \Magento\Weee\Model\Config */
        $model = new Config($scopeConfigMock, $taxData);
        $this->assertEquals($expectedValue, $model->{$method}());
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function dataProviderScopeConfigMethods()
    {
        return [
            [
                'getPriceDisplayType',
                Config::XML_PATH_FPT_DISPLAY_PRODUCT_VIEW,
                true,
                true,
            ],
            [
                'getListPriceDisplayType',
                Config::XML_PATH_FPT_DISPLAY_PRODUCT_LIST,
                true,
                true
            ],
            [
                'getSalesPriceDisplayType',
                Config::XML_PATH_FPT_DISPLAY_SALES,
                true,
                true
            ],
            [
                'getEmailPriceDisplayType',
                Config::XML_PATH_FPT_DISPLAY_EMAIL,
                true,
                true
            ],
            [
                'includeInSubtotal',
                Config::XML_PATH_FPT_INCLUDE_IN_SUBTOTAL,
                true,
                true
            ],
            [
                'isTaxable',
                Config::XML_PATH_FPT_TAXABLE,
                true,
                true
            ],
            [
                'isEnabled',
                Config::XML_PATH_FPT_ENABLED,
                true,
                true
            ]
        ];
    }
}
