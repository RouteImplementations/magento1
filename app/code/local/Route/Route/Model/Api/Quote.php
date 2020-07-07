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
 * Quote integration
 *
 * @category  Model
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Route_Route_Model_Api_Quote extends Route_Route_Model_Api_RouteClient
{

    const API_ENDPOINT = 'quote';

    /**
     * Main method that get quote value
     *
     * @param Mage_Sales_Model_Quote $quoteObj  Quote object
     * @param bool                   $isInsured if it's insured or not
     *
     * @return array
     *
     * @throws Exception
     */
    public function getQuote($quoteObj, $isInsured)
    {
        $response = $this->getQuoteResponse($quoteObj, $isInsured);

        return $response['insurance_price'];
    }

    /**
     * Main method that get quote api response or execute fallback method
     *
     * @param Mage_Sales_Model_Quote $quoteObj  Quote object
     * @param bool                   $isInsured if it's insured or not
     *
     * @return array
     *
     * @throws Exception
     */
    public function getQuoteResponse($quoteObj, $isInsured = false)
    {
        try {
            $response = $this->getMethod(
                $this->getRouteApiUrl(self::API_ENDPOINT),
                [
                    'subtotal' => $this->_getShippableItemsSubtotal($quoteObj),
                    'taxes' => 0,
                    'currency' => $this->getCurrentCurrencyCode($quoteObj),
                    'selected_insurance' => is_null($isInsured) ? false : $isInsured
                ]
            )->execute();

        }catch (Exception $exception){
            $response = $this->_getFallbackProtectionAmount($this->_getShippableItemsSubtotal($quoteObj));
        }

        return $response;
    }
    /**
     * Fallback Method
     *
     * @param float $subtotal subtotal to calculate fee
     *
     * @return array
     */
    private function _getFallbackProtectionAmount($subtotal)
    {
        return [
            'insurance_price' => ($subtotal * 1 / 100),
            'subtotal_usd' => $subtotal
        ];
    }

    /**
     *
     * Get shippable items subtotal
     *
     * @param Mage_Sales_Model_Quote $quoteObj Quote object
     * 
     * @return mixed
     */
    private function _getShippableItemsSubtotal($quoteObj)
    {
        return Mage::helper('route')->getShippableItemsSubtotal($quoteObj);
    }

    /**
     * Get subtotal from quote object or its address
     *
     * @param Mage_Sales_Model_Quote $quoteObj Quote object
     *
     * @return mixed
     */
    private function _getSubtotal($quoteObj)
    {
        return $quoteObj->getSubtotal() > 0 ?
            $quoteObj->getSubtotal() :
            $quoteObj->getShippingAddress()->getSubtotal();
    }

    protected function isStorable()
    {
        return false;
    }

    protected function enqueueOperation()
    {
        return false;
    }

    /**
     * @param $quoteObj
     * @return string
     */
    protected function getCurrentCurrencyCode($quoteObj)
    {
        return is_null($quoteObj->getQuoteCurrencyCode()) ? 'USD' : $quoteObj->getQuoteCurrencyCode();
    }
}
