<?php
/**
 * A Route Magento Extension that adds secure shipping
 * insurance to your orders
 *
 * Php version 7.0^
 *
 * @category  
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
 
class Route_Route_Model_Paypal_Cart extends Mage_Paypal_Model_Cart {

    /**
     * Render and get totals
     * If the totals are invalid for any reason, they will be merged into one amount (subtotal is utilized for it)
     * An option to substract discount from the subtotal is available
     *
     * @param bool $mergeDiscount
     * @return array
     */
    public function getTotals($mergeDiscount = false)
    {
        $totals = parent::getTotals($mergeDiscount);
        $routeAppHelper = Mage::helper('route');
        if ($routeAppHelper->canAddRouteFee()) {
            $balance = $routeAppHelper->getQuote();
            $totals[self::TOTAL_SUBTOTAL] -= $balance;
            $totals[self::TOTAL_SHIPPING] += $balance;
        }
        return $totals;
    }

}