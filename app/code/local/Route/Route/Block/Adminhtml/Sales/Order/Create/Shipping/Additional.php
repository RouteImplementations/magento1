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

class Route_Route_Block_Adminhtml_Sales_Order_Create_Shipping_Additional extends Mage_Checkout_Block_Onepage_Abstract
{

    protected $jsonHelper;
    protected $routeAppHelper;

    /**
     * Route_Route_Block_Additional constructor.
     */
    public function __construct()
    {
        $this->jsonHelper = Mage::helper('core');
        $this->checkoutSession = Mage::getModel('adminhtml/session_quote');
        $this->routeAppHelper = Mage::helper('route');
    }

    /**
     * Method to check if the current session is insured
     *
     * @return bool
     */
    public function isInsured()
    {
        return $this->checkoutSession->getInsured();
    }

    /**
     * Get current quote subtotal
     *
     * @return float
     */
    public function getSubtotalAmount()
    {
        return Mage::helper('route')->getShippableItemsSubtotal($this->checkoutSession->getQuote());
    }

    /**
     * Get controller insurance url
     *
     * @return string
     */
    public function getControllerUrl()
    {
        return Mage::helper("adminhtml")->getUrl("route/insured");
    }

    /**
     * Is route insurance route module enable
     *
     * @return bool
     */
    public function isRoutePlus()
    {
        return $this->routeAppHelper->isRoutePlus() &&
            $this->routeAppHelper->isAllowSubtotal() &&
            $this->routeAppHelper->isShippingMethodAllowed();
    }

    /**
     * Check if merchant is Route Plus
     *
     * @return bool
     */
    public function isFullCoverage()
    {
        return $this->routeAppHelper->isFullCoverage() &&
            $this->routeAppHelper->isAllowSubtotal() &&
            $this->routeAppHelper->isShippingMethodAllowed();
    }
    /**
     * Get Route API Url
     *
     * @return string
     */
    private function _getRouteApiUrl()
    {
        return $this->routeAppHelper->getRouteApiUrl();
    }

    /**
     * Get public token from configurations
     *
     * @return string
     */
    private function _getMerchantPublicToken()
    {
        return $this->routeAppHelper->getMerchantPublicToken();
    }

    /**
     * Check if the insurance is checked by default
     *
     * @return bool
     */
    private function _isEnabledDefaultInsurance()
    {
        if(is_null($this->checkoutSession->getInsured())) {
            return $this->routeAppHelper->isEnabledDefaultInsurance();
        }
        return $this->checkoutSession->getInsured();
    }

    /**
     * Returns css property to display block or not
     *
     * @return string
     */
    public function getDisplay()
    {
        return (bool) $this->isRoutePlus() ? 'block' : 'none';
    }

    /**
     * Returns json with default setting info
     *
     * @return false|string
     */
    public function isEnabledDefaultInsuranceEncoded()
    {
        return json_encode((bool) $this->_isEnabledDefaultInsurance());
    }

    public function getQuote()
    {
        return $this->checkoutSession->getQuote();
    }

    /**
     * Returns array of configuration
     *
     * @return string
     *
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getConfig()
    {
        $storeId = Mage::app()->getStore(true)->getStoreId();

        return $this->jsonHelper->jsonEncode(
            [
                'subtotal' => $this->getSubtotalAmount(),
                'controller' => $this->getControllerUrl(),
                'is_enabled' => $this->isRoutePlus(),
                'is_checked' => $this->_isEnabledDefaultInsurance(),
                'route_api' => $this->_getRouteApiUrl(),
                'token' => $this->_getMerchantPublicToken(),
                'currency' => $this->getQuote()->getQuoteCurrencyCode()
            ]
        );
    }

}

