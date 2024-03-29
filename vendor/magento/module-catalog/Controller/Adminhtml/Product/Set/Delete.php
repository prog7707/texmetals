<?php
/**
 *
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Catalog\Controller\Adminhtml\Product\Set;

class Delete extends \Magento\Catalog\Controller\Adminhtml\Product\Set
{
    /**
     * @var \Magento\Eav\Api\AttributeSetRepositoryInterface
     */
    protected $attributeSetRepository;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Eav\Api\AttributeSetRepositoryInterface $attributeSetRepository
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Eav\Api\AttributeSetRepositoryInterface $attributeSetRepository
    ) {
        parent::__construct($context, $coreRegistry);
        $this->attributeSetRepository = $attributeSetRepository;
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $setId = $this->getRequest()->getParam('id');
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $this->attributeSetRepository->deleteById($setId);
            $this->messageManager->addSuccess(__('The attribute set has been removed.'));
            $resultRedirect->setPath('catalog/*/');
        } catch (\Exception $e) {
            $this->messageManager->addError(__('We can\'t delete this set right now.'));
            $resultRedirect->setUrl($this->_redirect->getRedirectUrl($this->getUrl('*')));
        }
        return $resultRedirect;
    }
}
