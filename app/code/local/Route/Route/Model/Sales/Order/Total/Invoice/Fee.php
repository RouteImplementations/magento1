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
 * Invoice Fee collector
 *
 * @category  Model
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Route_Route_Model_Sales_Order_Total_Invoice_Fee
    extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{
    /**
     * Collect invoice total
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice Invoice object
     *
     * @return Route_Route_Model_Sales_Order_Total_Invoice_Fee
     */
    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {
        $order = $invoice->getOrder();
        $feeAmount = $order->getFeeAmount();
        $baseFeeAmount = $order->getFeeAmount();

        $routeAppHelper = Mage::helper('route');

        $invoice->setFeeAmount($feeAmount);
        $invoice->setBaseFeeAmount($baseFeeAmount);

        if ($routeAppHelper->isTaxable()) {

            $invoice->setTaxAmount(
                $invoice->getTaxAmount() + $order->getFeeTaxAmount()
            );

            $invoice->setBaseTaxAmount(
                $invoice->getBaseTaxAmount() + $order->getBaseFeeTaxAmount()
            );

            $invoice->setFeeTaxAmount($order->getFeeTaxAmount());
            $invoice->setBaseFeeTaxAmount($order->getBaseFeeTaxAmount());

            $invoice->setGrandTotal(
                $invoice->getGrandTotal() +
                $feeAmount +
                $order->getFeeTaxAmount()
            );

            $invoice->setBaseGrandTotal(
                $invoice->getBaseGrandTotal() +
                $baseFeeAmount +
                $order->getBaseFeeTaxAmount()
            );

            return $this;
        }

        $invoice->setGrandTotal($invoice->getGrandTotal() + $feeAmount);
        $invoice->setBaseGrandTotal(
            $invoice->getBaseGrandTotal() + $baseFeeAmount
        );

        return $this;
    }
}