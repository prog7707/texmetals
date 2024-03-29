<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GroupedProduct\Test\Unit\Model\ResourceModel\Product\Link;

use Magento\GroupedProduct\Model\ResourceModel\Product\Link\RelationPersister;
use Magento\Catalog\Model\ProductLink\LinkFactory;
use Magento\Catalog\Model\Product\Link;
use Magento\Catalog\Model\ResourceModel\Product\Relation;

class RelationPersisterTest extends \PHPUnit_Framework_TestCase
{
    /** @var RelationPersister|PHPUnit_Framework_MockObject_MockObject */
    private $object;

    /** @var Link */
    private $link;

    /** @var  Relation */
    private $relationProcessor;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $linkFactory = $this->getMockBuilder(LinkFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->relationProcessor = $this->getMockBuilder(Relation::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->link = $this->getMockBuilder(Link::class)
            ->setMethods(['getLinkTypeId', 'getProductId', 'getLinkedProductId'])
            ->disableOriginalConstructor()
            ->getMock();

        $linkFactory->expects($this->any())->method('create')->willReturn($this->link);

        $this->object = new RelationPersister(
            $this->relationProcessor,
            $linkFactory
        );
    }

    public function testAroundSaveProductLinks()
    {
        $subject = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Link::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->relationProcessor->expects($this->once())->method('addRelation')->with(2, 10);
        $this->assertEquals($subject, $this->object->aroundSaveProductLinks(
            $subject,
            function() use ($subject) { return $subject; },
            2,
            [['product_id' => 10]],
            3
        ));
    }

    public function testAroundDeleteProductLink()
    {
        $subject = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Link::class)
            ->disableOriginalConstructor()
            ->getMock();
        $subject->expects($this->any())->method('getIdFieldName')->willReturn('id');
        $subject->expects($this->once())->method('load')->with($this->link, 155, 'id');

        $this->link->expects($this->any())
            ->method('getLinkTypeId')
            ->willReturn(\Magento\GroupedProduct\Model\ResourceModel\Product\Link::LINK_TYPE_GROUPED);
        $this->link->expects($this->any())
            ->method('getProductId')
            ->willReturn(12);
        $this->link->expects($this->any())
            ->method('getLinkedProductId')
            ->willReturn(13);

        $this->relationProcessor->expects($this->once())->method('removeRelations')->with(12, 13);
        $this->assertEquals(
            $subject,
            $this->object->aroundDeleteProductLink(
                $subject,
                function() use ($subject) { return $subject; },
                155
            )
        );

    }
}
