<?php
/**
 *
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Magento\Catalog\Test\Unit\Model\Indexer\Product\Flat\Action;

class EraserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $connection;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $indexerHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManager;

    /**
     * @var \Magento\Catalog\Model\Indexer\Product\Flat\Action\Eraser
     */
    protected $model;

    protected function setUp()
    {
        $resource = $this->getMock('Magento\Framework\App\ResourceConnection', [], [], '', false);
        $this->connection = $this->getMock('Magento\Framework\DB\Adapter\AdapterInterface');
        $resource->expects($this->any())->method('getConnection')->will($this->returnValue($this->connection));
        $this->indexerHelper = $this->getMock(
            'Magento\Catalog\Helper\Product\Flat\Indexer',
            [],
            [], '', false
        );
        $this->indexerHelper->expects($this->any())->method('getTable')->will($this->returnArgument(0));
        $this->indexerHelper->expects($this->any())->method('getFlatTableName')->will($this->returnValueMap([
            [1, 'store_1_flat'],
            [2, 'store_2_flat'],
        ]));

        $this->storeManager = $this->getMock('Magento\Store\Model\StoreManagerInterface');
        $this->model = new \Magento\Catalog\Model\Indexer\Product\Flat\Action\Eraser(
            $resource,
            $this->indexerHelper,
            $this->storeManager
        );
    }

    public function testRemoveDeletedProducts()
    {
        $productsToDeleteIds = [1, 2];
        $select = $this->getMock('\Magento\Framework\Db\Select', [], [], '', false);
        $select->expects($this->once())->method('from')->with('catalog_product_entity')->will($this->returnSelf());
        $select->expects($this->once())->method('where')->with('entity_id IN(?)', $productsToDeleteIds)
            ->will($this->returnSelf());
        $products = [['entity_id' => 2]];
        $statement = $this->getMock('\Zend_Db_Statement_Interface');
        $statement->expects($this->once())->method('fetchAll')->will($this->returnValue($products));
        $this->connection->expects($this->once())->method('query')->with($select)
            ->will($this->returnValue($statement));
        $this->connection->expects($this->once())->method('select')->will($this->returnValue($select));
        $this->connection->expects($this->once())->method('delete')
            ->with('store_1_flat', ['entity_id IN(?)' => [1]]);

        $this->model->removeDeletedProducts($productsToDeleteIds, 1);
    }

    public function testDeleteProductsFromStoreForAllStores()
    {
        $store1 = $this->getMock('Magento\Store\Model\Store', [], [], '', false);
        $store1->expects($this->any())->method('getId')->will($this->returnValue(1));
        $store2 = $this->getMock('Magento\Store\Model\Store', [], [], '', false);
        $store2->expects($this->any())->method('getId')->will($this->returnValue(2));
        $this->storeManager->expects($this->once())->method('getStores')
            ->will($this->returnValue([$store1, $store2]));
        $this->connection->expects($this->at(0))->method('delete')
            ->with('store_1_flat', ['entity_id IN(?)' => [1]]);
        $this->connection->expects($this->at(1))->method('delete')
            ->with('store_2_flat', ['entity_id IN(?)' => [1]]);

        $this->model->deleteProductsFromStore(1);
    }
}
