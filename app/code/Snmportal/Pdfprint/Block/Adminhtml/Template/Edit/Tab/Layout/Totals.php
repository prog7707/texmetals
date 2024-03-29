<?php
namespace  Snmportal\Pdfprint\Block\Adminhtml\Template\Edit\Tab\Layout;
/**
 * Adminhtml tier price item renderer
 */
use Magento\Backend\Block\Widget;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

class Totals extends Widget implements RendererInterface
{
    /**
     * Form element instance
     *
     * @var \Magento\Framework\Data\Form\Element\AbstractElement
     */
    protected $_element;

    /**
     * Form element instance
     *
     * @var \Snmportal\Pdfprint\Model\Template
     */
    protected $_modelTemplate;

    /**
     * @var string
     */
    protected $_template = 'instance/edit/totals.phtml';

    /**
     * @var \Magento\Sales\Model\Order\Pdf\Config
     */
    protected $_pdfConfig;

    public function __construct(\Magento\Backend\Block\Template\Context $context,
                                \Magento\Sales\Model\Order\Pdf\Config $config,
                                array $data = [])
    {
        $this->_pdfConfig = $config;
        parent::__construct($context, $data);
    }

    /**
     * Prepare global layout
     * Add "Add " button to layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            ['label' => __('Add Column'), 'onclick' => 'return '.$this->getJsName().'Control.addItem()', 'class' => 'add']
        );
        $button->setName('add_'.$this->getJsName().'_item_button');
        $this->setChild('add_button', $button);
        return parent::_prepareLayout();
    }
    public function getReadOnly()
    {
        return false;
    }
    public function canManageOptionDefaultOnly()
    {
        return false;
    }

    public function getJsName()
    {
        if ( !$this->getData('js_name') )
        {
            $this->setData('js_name',uniqid('jsctrl_'));
        }
        return $this->getData('js_name');
    }
    public function getValues()
    {
        $values = [];
        $data = $this->getElement()->getValue();

        if (is_array($data)) {
            $values = $this->_sortValues($data);
        }
        return $values;
    }

    public function setModelTemplate($template)
    {
        $this->_modelTemplate= $template;
        return $this;
    }
    protected function _sortTotalsList($a, $b)
    {
        if (!isset($a['sort_order']) || !isset($b['sort_order'])) {
            return 0;
        }

        if ($a['sort_order'] == $b['sort_order']) {
            return 0;
        }

        return $a['sort_order'] > $b['sort_order'] ? 1 : -1;
    }

    public function getOptions()
    {
        $values = [];
        $values[]=array('label'=>__('Only if useful'),'value'=>'candisplay');
        $values[]=array('label'=>__('Always'),'value'=>'always');
        return $values;
    }
    public function getTypeOption()
    {
        $values = [];
        $totals = $this->_pdfConfig->getTotals();
        usort($totals, [$this, '_sortTotalsList']);
        foreach ($totals as $totalInfo) {

            $values[]=array('label'=>$totalInfo['title'],'value'=>$totalInfo['source_field']);
        }
        return $values;
    }

    /**
     * Sort values
     *
     * @param array $data
     * @return array
     */
    protected function _sortValues($data)
    {
        //usort($data, [$this, '_sortTierPrices']);
        return $data;
    }

    /**
     * Retrieve 'add group price item' button HTML
     *
     * @return string
     */
    public function getAddButtonHtml()
    {
        return $this->getChildHtml('add_button');
    }

    /**
     * Render HTML
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {

        $this->setElement($element);
        return $this->toHtml();
    }


}
