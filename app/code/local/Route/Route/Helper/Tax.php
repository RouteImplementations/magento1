<?php
/**
 * A Route Magento Extension that adds secure shipping
 * insurance to your orders
 *
 * Php version 5.6
 *
 * @category  Block
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */

/**
 * Rewrite tax helper to fix invoice and credit memo calculation by item
 *
 * @category  Helper
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Route_Route_Helper_Tax extends Mage_Tax_Helper_Data
{
    /**
     * Get calculated taxes for each tax class
     *
     * This method returns array with format:
     * array(
     *  $index => array(
     *      'tax_amount'        => $taxAmount,
     *      'base_tax_amount'   => $baseTaxAmount,
     *      'hidden_tax_amount' => $hiddenTaxAmount,
     *      'title'             => $title,
     *      'percent'           => $percent
     *  )
     * )
     *
     * @param Mage_Sales_Model_Order $source
     * @return array
     */
    public function getCalculatedTaxes($source)
    {

        if (!$source->getRouteIsInsured()){
            return parent::getCalculatedTaxes($source);
        }

        if ($this->_getFromRegistry('current_invoice')) {
            $current = $this->_getFromRegistry('current_invoice');
        } elseif ($this->_getFromRegistry('current_creditmemo')) {
            $current = $this->_getFromRegistry('current_creditmemo');
        } else {
            $current = $source;
        }

        $taxClassAmount = array();
        if ($current && $source) {

            $rates = $this->_getTaxRateSubtotals($source);
            foreach ($rates['items'] as $rate) {
                $taxClassId = $rate['tax_id'];
                $taxClassAmount[$taxClassId]['tax_amount'] = $rate['amount'];
                $taxClassAmount[$taxClassId]['base_tax_amount'] = $rate['base_amount'];
                $taxClassAmount[$taxClassId]['title'] = $rate['title'];
                $taxClassAmount[$taxClassId]['percent'] = $rate['percent'];
            }

            foreach ($taxClassAmount as $key => $tax) {
                if ($tax['tax_amount'] == 0 && $tax['base_tax_amount'] == 0) {
                    unset($taxClassAmount[$key]);
                }
            }

            $taxClassAmount = array_values($taxClassAmount);
        }

        return $taxClassAmount;
    }
}
