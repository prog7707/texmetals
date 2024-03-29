<?php
namespace Snmportal\Pdfprint\Plugin\Tax\Sales\Pdf;


class Tax
{
    /**
     * @var \Magento\Tax\Model\Config
     */
    protected $_taxConfig;

    protected $helperBlock;
    public function __construct(
        \Magento\Tax\Model\Config $taxConfig
    ) {
        $this->_taxConfig = $taxConfig;
    }
    protected function _sortTax($a, $b)
    {
        if (!isset($a['percent']) ) {
            return 1;
        }
        if (!isset($b['percent']) ) {
            return -1;
        }

        if ($a['percent'] == $b['percent']) {
            return 0;
        }

        return $a['percent'] > $b['percent'] ? 1 : -1;
    }

    public function aroundGetTotalsForDisplay(
        \Magento\Tax\Model\Sales\Pdf\Tax $subject,
        \Closure $proceed
    ) {
        $totals = $proceed();
        if ( !count($totals) )
            return $totals;

        $template = $subject->getSource()->getAuItPrintTemplate();
        if ( !$template || !$template->getData('table_totals_use_custom') || $template->getData('table_taxrenderer_default'))
        {
            return $totals;
        }

        if ( $template->getData('table_tax_full_summary') ) {
            if ( count($totals) == 1)
                $totals = array_merge($totals, $subject->getFullTaxInfo());
        }
        if ( !$template->getData('table_tax_all') )
        {
            if ( count($totals) > 1 )
            {
                $tt= [];
                foreach ( $totals as $total)
                {
                    if ( isset($total['percent']) )
                    {
                        $tt[]=$total;
                    }
                }
                $totals =$tt;
            }
        }
        if ( $template->getData('table_tax_all') == 2)
        {
            if ( count($totals) == 2 )
            {
                $tt= [];
                foreach ( $totals as $total)
                {
                    if ( isset($total['percent']) )
                    {
                        $tt[]=$total;
                    }
                }
                $totals =$tt;
            }
        }


//        $totals = array_merge($totals, parent::getTotalsForDisplay());

        $title = __($subject->getTitle());
        usort($totals, [$this, '_sortTax']);
        foreach ( $totals as &$total)
        {
            if ( isset($total['percent']) )
            {
                $percent = $total['percent'];
                $percent =sprintf('%s', $percent + 0);
                $percent = $percent ? ' (' . $percent . '%)' : '';
                $total['label'] = $title . $percent . ':';
            }
        }
        return $totals;
/*
        return $proceed();

        $def = $proceed();


        $fontSize = $subject->getFontSize() ? $subject->getFontSize() : 7;

        if ( $subject->getAmount() )
        {
            $totalForDisplay =[];
            $totals = [];
            if ( $template->getData('table_tax_all') )
                $totalForDisplay = $this->_getTotalsForDisplay($order,$subject);
            if ( $template->getData('table_tax_full_summary') ) {
                //$totals = $subject->getFullTaxInfo();
                if ( !count($totals) )
                {
                    // OLD DATA Bug Fix
                    $taxClassAmount=[];
                    foreach ( $subject->getSource()->getItemsCollection() as $item )
                    {
                        if ( $item->getParentItem() ) continue;

                        $taxPercent = $item->getTaxPercent();
                        if ( !$taxPercent && $item->getOrderItem())
                            $taxPercent = $item->getOrderItem()->getTaxPercent();

                        if ( !isset($taxClassAmount[$taxPercent]) )
                            $taxClassAmount[$taxPercent]=0;
                        $taxClassAmount[$taxPercent] += $item->getTaxAmount();
                    }
                    $title = __($subject->getTitle());
                    foreach ( $taxClassAmount as $percent => $amount)
                    {
                        $percent =sprintf('%s', $percent + 0);
                        $percent = $percent ? ' (' . $percent . '%)' : '';
                        $totals[]=[
                            'percent' => $percent,
                            'amount' => $subject->getAmountPrefix() . $order->formatPriceTxt($amount),
                            'label' => $title . $percent . ':',
                            'font_size' => $fontSize,
                        ];
                    }
                }
            }
            $totals = array_merge($totals, $totalForDisplay);
            return $totals;
        }
        return $proceed();
*/
    }

    public function getFullTaxInfo()
    {
        $fontSize = $this->getFontSize() ? $this->getFontSize() : 7;
        $taxClassAmount = $this->_taxHelper->getCalculatedTaxes($this->getOrder());
        if (!empty($taxClassAmount)) {
            foreach ($taxClassAmount as &$tax) {
                $percent = $tax['percent'] ? ' (' . $tax['percent'] . '%)' : '';
                $tax['amount'] = $this->getAmountPrefix() . $this->getOrder()->formatPriceTxt($tax['tax_amount']);
                $tax['label'] = __($tax['title']) . $percent . ':';
                $tax['font_size'] = $fontSize;
            }
        } else {
            /** @var $orders \Magento\Tax\Model\ResourceModel\Sales\Order\Tax\Collection */
            $orders = $this->_taxOrdersFactory->create();
            $rates = $orders->loadByOrder($this->getOrder())->toArray();
            $fullInfo = $this->_taxCalculation->reproduceProcess($rates['items']);
            $tax_info = [];

            if ($fullInfo) {
                foreach ($fullInfo as $info) {
                    if (isset($info['hidden']) && $info['hidden']) {
                        continue;
                    }

                    $_amount = $info['amount'];

                    foreach ($info['rates'] as $rate) {
                        $percent = $rate['percent'] ? ' (' . $rate['percent'] . '%)' : '';

                        $tax_info[] = [
                            'amount' => $this->getAmountPrefix() . $this->getOrder()->formatPriceTxt($_amount),
                            'label' => __($rate['title']) . $percent . ':',
                            'font_size' => $fontSize,
                        ];
                    }
                }
            }
            $taxClassAmount = $tax_info;
        }

        return $taxClassAmount;
    }

    protected function _getTotalsForDisplay($order,$subject)
    {
        $amount = $order->formatPriceTxt($subject->getAmount());
        if ($subject->getAmountPrefix()) {
            $amount = $subject->getAmountPrefix() . $amount;
        }

        $title = __($subject->getTitle());
        if ($subject->getTitleSourceField()) {
            $label = $title . ' (' . $subject->getTitleDescription() . '):';
        } else {
            $label = $title . ':';
        }

        $fontSize = $subject->getFontSize() ? $subject->getFontSize() : 7;
        $total = ['amount' => $amount, 'label' => $label, 'font_size' => $fontSize];
        return [$total];
    }

}
