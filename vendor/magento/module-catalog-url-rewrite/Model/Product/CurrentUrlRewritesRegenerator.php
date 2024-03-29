<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogUrlRewrite\Model\Product;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Magento\UrlRewrite\Model\OptionProvider;
use Magento\CatalogUrlRewrite\Model\ObjectRegistry;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator;
use Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CurrentUrlRewritesRegenerator
{
    /** @var Product */
    protected $product;

    /** @var ObjectRegistry */
    protected $productCategories;

    /** @var UrlFinderInterface */
    protected $urlFinder;

    /** @var ProductUrlPathGenerator */
    protected $productUrlPathGenerator;

    /** @var UrlRewriteFactory */
    protected $urlRewriteFactory;

    /**
     * @param UrlFinderInterface $urlFinder
     * @param ProductUrlPathGenerator $productUrlPathGenerator
     * @param UrlRewriteFactory $urlRewriteFactory
     */
    public function __construct(
        UrlFinderInterface $urlFinder,
        ProductUrlPathGenerator $productUrlPathGenerator,
        UrlRewriteFactory $urlRewriteFactory
    ) {
        $this->urlFinder = $urlFinder;
        $this->productUrlPathGenerator = $productUrlPathGenerator;
        $this->urlRewriteFactory = $urlRewriteFactory;
    }

    /**
     * Generate list based on current rewrites
     *
     * @param int $storeId
     * @param Product $product
     * @param ObjectRegistry $productCategories
     * @return UrlRewrite[]
     */
    public function generate($storeId, Product $product, ObjectRegistry $productCategories)
    {
        $this->product = $product;
        $this->productCategories = $productCategories;

        $currentUrlRewrites = $this->urlFinder->findAllByData(
            [
                UrlRewrite::STORE_ID => $storeId,
                UrlRewrite::ENTITY_ID => $this->product->getId(),
                UrlRewrite::ENTITY_TYPE => ProductUrlRewriteGenerator::ENTITY_TYPE,
            ]
        );

        $urlRewrites = [];
        foreach ($currentUrlRewrites as $currentUrlRewrite) {
            $category = $this->retrieveCategoryFromMetadata($currentUrlRewrite);
            if ($category === false) {
                continue;
            }
            $url = $currentUrlRewrite->getIsAutogenerated()
                ? $this->generateForAutogenerated($currentUrlRewrite, $storeId, $category)
                : $this->generateForCustom($currentUrlRewrite, $storeId, $category);
            $urlRewrites = array_merge($urlRewrites, $url);
        }

        $this->product = null;
        $this->productCategories = null;
        return $urlRewrites;
    }

    /**
     * @param UrlRewrite $url
     * @param int $storeId
     * @param Category|null $category
     * @return array
     */
    protected function generateForAutogenerated($url, $storeId, $category)
    {
        if (!$this->product->getData('save_rewrites_history')) {
            return [];
        }
        $targetPath = $this->productUrlPathGenerator->getUrlPathWithSuffix($this->product, $storeId, $category);
        if ($url->getRequestPath() === $targetPath) {
            return [];
        }
        return [
            $this->urlRewriteFactory->create()
                ->setEntityType(ProductUrlRewriteGenerator::ENTITY_TYPE)
                ->setEntityId($this->product->getId())
                ->setRequestPath($url->getRequestPath())
                ->setTargetPath($targetPath)
                ->setRedirectType(OptionProvider::PERMANENT)
                ->setStoreId($storeId)
                ->setDescription($url->getDescription())
                ->setIsAutogenerated(0)
                ->setMetadata($url->getMetadata())
        ];
    }

    /**
     * @param UrlRewrite $url
     * @param int $storeId
     * @param Category|null $category
     * @return array
     */
    protected function generateForCustom($url, $storeId, $category)
    {
        $targetPath = $url->getRedirectType()
            ? $this->productUrlPathGenerator->getUrlPathWithSuffix($this->product, $storeId, $category)
            : $url->getTargetPath();
        if ($url->getRequestPath() === $targetPath) {
            return [];
        }
        return [
            $this->urlRewriteFactory->create()
                ->setEntityType(ProductUrlRewriteGenerator::ENTITY_TYPE)
                ->setEntityId($this->product->getId())
                ->setRequestPath($url->getRequestPath())
                ->setTargetPath($targetPath)
                ->setRedirectType($url->getRedirectType())
                ->setStoreId($storeId)
                ->setDescription($url->getDescription())
                ->setIsAutogenerated(0)
                ->setMetadata($url->getMetadata())
        ];
    }

    /**
     * @param UrlRewrite $url
     * @return Category|null|bool
     */
    protected function retrieveCategoryFromMetadata($url)
    {
        $metadata = $url->getMetadata();
        if (isset($metadata['category_id'])) {
            $category = $this->productCategories->get($metadata['category_id']);
            return $category === null ? false : $category;
        }
        return null;
    }
}
