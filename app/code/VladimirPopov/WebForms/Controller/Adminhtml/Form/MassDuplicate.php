<?php
/**
 * @author      Vladimir Popov
 * @copyright   Copyright © 2017 Vladimir Popov. All rights reserved.
 */

namespace VladimirPopov\WebForms\Controller\Adminhtml\Form;

use Magento\Authorization\Model\RulesFactory;
use Magento\Authorization\Model;
use Magento\Backend\App\Action;
use Magento\Backend\Model\Authorization;
use VladimirPopov\WebForms\Controller\Adminhtml\AbstractMassDuplicate;
use Magento\Framework\Controller\ResultFactory;


/**
 * Class MassDuplicate
 */
class MassDuplicate extends AbstractMassDuplicate
{
    const REDIRECT_URL = 'webforms/form/index';

    const MODEL = 'VladimirPopov\WebForms\Model\Form';

    protected $roleLocator;

    protected $_rulesFactory;

    protected $_rulesCollectionFactory;

    protected $_aclBuilder;

    protected $_cache;

    protected $authSession;

    public function __construct(
        Action\Context $context,
        Authorization\RoleLocator $roleLocator,
        RulesFactory $rulesFactory,
        \Magento\Authorization\Model\ResourceModel\Rules\CollectionFactory $rulesCollectionFactory,
        \Magento\Framework\Acl\Builder $aclBuilder,
        \Magento\Framework\Acl\CacheInterface $cache,
        \Magento\Backend\Model\Auth\Session $authSession
    )
    {
        $this->roleLocator = $roleLocator;
        $this->_rulesFactory = $rulesFactory;
        $this->_rulesCollectionFactory = $rulesCollectionFactory;
        $this->_aclBuilder = $aclBuilder;
        $this->_cache = $cache;
        $this->authSession = $authSession;
        parent::__construct($context);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('VladimirPopov_WebForms::manage_forms');
    }
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $Ids = $this->getRequest()->getParam(static::ID_FIELD);
        if (!is_array($Ids) || empty($Ids)) {
            $this->messageManager->addErrorMessage(__('Please select item(s).'));
        } else {
            try {
                foreach ($Ids as $id) {
                    $item = $this->_objectManager->get(static::MODEL)->load($id);
                    $newForm = $item->duplicate();
                    // update role permissions
                    if (!$this->_authorization->isAllowed('Magento_Backend::all')) {
                        $this->_rulesFactory->create()->setData([
                            'role_id' => $this->roleLocator->getAclRoleId(),
                            'resource_id' => 'VladimirPopov_WebForms::form' . $newForm->getId(),
                            'permission' => 'allow'
                        ])->save();
                    }
                }
                // refresh ACL
                $this->_cache->clean();
                $this->authSession->setAcl($this->_aclBuilder->getAcl());
                $this->messageManager->addSuccessMessage(
                    __('A total of %1 record(s) have been duplicated.', count($Ids))
                );
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath(static::REDIRECT_URL, $this->redirect_params);
    }
}
