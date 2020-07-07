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
 * Collect totals and include Route Fee if it's checked
 *
 * @category  Model
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Route_Route_Model_Sales_Quote_Address_Total_Fee
    extends Mage_Sales_Model_Quote_Address_Total_Abstract
{

    /**
     * Collect totals process.
     *
     * @param Mage_Sales_Model_Quote_Address $address current quote address object
     *
     * @return Mage_Sales_Model_Quote_Address_Total_Abstract
     */
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        parent::collect($address);

        try {
            $routeAppHelper = Mage::helper('route');

            $this->_setAmount(0);
            $this->_setBaseAmount(0);
            $items = $this->_getAddressItems($address);
            if (!count($items)) {
                return $this;
            }

            $address->setRouteIsInsured($routeAppHelper->isInsured());

            if ($routeAppHelper->canAddRouteFee()) {
                $balance = $routeAppHelper->getQuote();

                $address->setFeeAmount($balance);
                $address->setBaseFeeAmount($balance);

                if($routeAppHelper->isTaxable()){
                    $calc = Mage::getSingleton('tax/calculation');

                    $addressTaxRequest = $calc->getRateRequest(
                            $address,
                            $address->getQuote()->getBillingAddress(),
                            $address->getQuote()->getCustomerTaxClassId(),
                            $address->getQuote()->getStore()
                    );

                    $addressTaxRequest->setProductClassId($routeAppHelper->getTaxClassId());

                    $rate = $calc->getRate($addressTaxRequest);
                    $taxAmount = $calc->calcTaxAmount($balance, $rate, false, true);
                    $baseTaxAmount = $calc->calcTaxAmount($balance, $rate, false, true);

                    $address->setTaxAmount($address->getTaxAmount() + $taxAmount);
                    $address->setBaseTaxAmount($address->getBaseTaxAmount() + $baseTaxAmount);

                    $address->setFeeTaxAmount($taxAmount);
                    $address->setBaseFeeTaxAmount($baseTaxAmount);

                    $address->setGrandTotal($address->getGrandTotal() + $taxAmount);
                    $address->setBaseGrandTotal($address->getBaseGrandTotal() + $baseTaxAmount);
                }

                $address->setTotalAmount('fee', $balance);
                $address->setBaseTotalAmount('fee', $balance);

            }
        } catch (Exception $e) {
            Mage::logException($e);
            $logger = Mage::getSingleton('route/log_sentry');
            $logger->send($e);
        }
    }

    /**
     * Fetch (Retrieve data as array)
     *
     * @param Mage_Sales_Model_Quote_Address $address current quote address
     *
     * @return array
     */
    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        $amount = $address->getFeeAmount();
        if ($amount != 0) {
            $address->addTotal(
                array(
                'code' => $this->getCode(),
                'title' => Mage::helper('route')->getRouteLabel(),
                'value' => $amount,
                )
            );
        }
        return $this;
    }
}