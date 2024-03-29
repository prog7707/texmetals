<?php
namespace Snmportal\Pdfprint\Controller\Adminhtml\Template;

use Snmportal\Pdfprint\Controller\Adminhtml\AbstractMassStatus;

/**
 * Class MassEnable
 */
class MassEnable extends AbstractMassStatus
{
    /**
     * Field id
     */
    const ID_FIELD = 'template_id';

    /**
     * Resource collection
     *
     * @var string
     */
    protected $collection = 'Snmportal\Pdfprint\Model\ResourceModel\Template\Collection';

    /**
     * Page model
     *
     * @var string
     */
    protected $model = 'Snmportal\Pdfprint\Model\Template';

    /**
     * Page enable status
     *
     * @var boolean
     */
    protected $status = true;
}
