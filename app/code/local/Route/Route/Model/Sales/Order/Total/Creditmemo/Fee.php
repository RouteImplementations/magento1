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
 * Creditmemo Fee collector
 *
 * @category  Model
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Route_Route_Model_Sales_Order_Total_Creditmemo_Fee
    extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{

    /**
     * Collect credit memo total
     *
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo Creditmemo object
     *
     * @return Route_Route_Model_Sales_Order_Total_Creditmemo_Fee
     */
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        if($this->isCompleteCreditmemo($creditmemo)){
            $order = $creditmemo->getOrder();
            $feeAmount = $order->getFeeAmount();
            $baseFeeAmount = $order->getFeeAmount();

            $routeAppHelper = Mage::helper('route');

            $creditmemo->setFeeAmount($feeAmount);
            $creditmemo->setBaseFeeAmount($baseFeeAmount);

            if ($routeAppHelper->isTaxable()) {

                $creditmemo->setTaxAmount(
                    $creditmemo->getTaxAmount() + $order->getFeeTaxAmount()
                );

                $creditmemo->setBaseTaxAmount(
                    $creditmemo->getBaseTaxAmount() + $order->getBaseFeeTaxAmount()
                );

                $creditmemo->setFeeTaxAmount($order->getFeeTaxAmount());
                $creditmemo->setBaseFeeTaxAmount($order->getBaseFeeTaxAmount());

                $creditmemo->setGrandTotal(
                    $creditmemo->getGrandTotal() +
                    $feeAmount +
                    $order->getFeeTaxAmount()
                );

                $creditmemo->setBaseGrandTotal(
                    $creditmemo->getBaseGrandTotal() +
                    $baseFeeAmount +
                    $order->getBaseFeeTaxAmount()
                );

                return $this;
            }

            $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $feeAmount);
            $creditmemo->setBaseGrandTotal(
                $creditmemo->getBaseGrandTotal() + $baseFeeAmount
            );
        }
        return $this;
    }

    /**
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     * @return bool
     */
    private function isCompleteCreditmemo(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        $orderItems = [];
        foreach ($creditmemo->getOrder()->getAllItems() as $item) {
            $orderItems[$item->getSku()] =  (int) $item->getQtyInvoiced() - (int) $item->getQtyRefunded();
        }
        foreach ($creditmemo->getAllItems() as $item) {
            if (isset($orderItems[$item->getSku()])) {
                if ($orderItems[$item->getSku()] != $item->getQty()) {
                    return false;
                }
            }
        }
        return true;
    }
}
