<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Checkout\Test\Constraint;

use Magento\Catalog\Test\Fixture\CatalogProductSimple;
use Magento\Checkout\Test\Fixture\Cart;
use Magento\Checkout\Test\Fixture\Cart\Items;
use Magento\Checkout\Test\Page\CheckoutCart;
use Magento\Mtf\Constraint\AbstractAssertForm;
use Magento\Mtf\Fixture\FixtureInterface;

/**
 * Class AssertSubtotalInShoppingCart
 * Assert that subtotal total in the shopping cart is equals to expected total from data set
 */
class AssertSubtotalInShoppingCart extends AbstractAssertForm
{
    /**
     * Assert that subtotal total in the shopping cart is equals to expected total from data set
     *
     * @param CheckoutCart $checkoutCart
     * @param Cart $cart
     * @param boolean $requireReload
     * @return void
     */
    public function processAssert(CheckoutCart $checkoutCart, Cart $cart, $requireReload = true)
    {
        if ($requireReload) {
            $checkoutCart->open();
        }

        /** @var Items $sourceProducts */
        $sourceProducts = $cart->getDataFieldConfig('items')['source'];
        $products = $sourceProducts->getProducts();
        $items = $cart->getItems();
        $productsData = [];
        $cartData = [];

        foreach ($items as $key => $item) {
            /** @var CatalogProductSimple $product */
            $product = $products[$key];
            $productName = $product->getName();
            /** @var FixtureInterface $item */
            $checkoutItem = $item->getData();
            $cartItem = $checkoutCart->getCartBlock()->getCartItem($product);

            $productsData[$productName] = [
                'subtotal' => $checkoutItem['subtotal'],
            ];
            $cartData[$productName] = [
                'subtotal' => $cartItem->getSubtotalPrice(),
            ];
        }

        $error = $this->verifyData($productsData, $cartData, true);
        \PHPUnit_Framework_Assert::assertEmpty($error, $error);
    }

    /**
     * Returns a string representation of the object.
     *
     * @return string
     */
    public function toString()
    {
        return 'Subtotal in the shopping cart equals to expected total from data set.';
    }
}
