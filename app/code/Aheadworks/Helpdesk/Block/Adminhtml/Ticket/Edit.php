<?php
/**
* Copyright 2016 aheadWorks. All rights reserved.
* See LICENSE.txt for license details.
*/

namespace Aheadworks\Helpdesk\Block\Adminhtml\Ticket;

/**
 * Class Edit
 * @package Aheadworks\Helpdesk\Block\Adminhtml\Ticket
 */
class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'Aheadworks_Helpdesk';
        $this->_controller = 'adminhtml_ticket';

        parent::_construct();

        $this->buttonList->remove('delete');
        $this->buttonList->remove('reset');
        $this->buttonList->remove('save');
    }

}
