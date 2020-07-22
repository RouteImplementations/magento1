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
 * Class to provide information to route.phtml
 *
 * @category  Block
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Route_Route_Block_Additional extends Mage_Checkout_Block_Onepage_Abstract
{

    protected $jsonHelper;
    protected $routeAppHelper;
    protected $checkoutSession;
    protected $routeAppSetupHelper;

    /**
     * Route_Route_Block_Additional constructor.
     */
    public function __construct()
    {
        $this->jsonHelper = Mage::helper('core');
        $this->checkoutSession = Mage::getModel('checkout/session');
        $this->routeAppHelper = Mage::helper('route');
        $this->routeAppSetupHelper = Mage::helper('route/setup');
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
        return Mage::helper('adminhtml')->getUrl('route');
    }

    /**
     * Get controller widget check url
     *
     * @return string
     */
    public function getCheckWidgetControllerUrl()
    {
        return Mage::helper('adminhtml')->getUrl('route/index/checkWidget');
    }

    /**
     * Is route insurance route module enable
     *
     * @return bool
     */
    public function isRoutePlus()
    {
        return $this->routeAppHelper->isRoutePlus() &&
            $this->routeAppHelper->isAllowSubtotal() ;
    }

    /**
     * Is Route included on  order thank you page
     *
     * @return bool
     */
    public function isIncludesOrderThankYouPageWidget()
    {
        return $this->routeAppHelper->isIncludesOrderThankYouPageWidget();
    }

    /**
     * Get Route thank you page
     *
     * @return mixed
     */
    public function getThankYouPageWidget()
    {
        return $this->routeAppHelper->getThankYouPageWidget();
    }


    /**
     * Check if merchant is Route Plus
     *
     * @return bool
     */
    public function isFullCoverage()
    {
        return $this->routeAppHelper->isFullCoverage() &&
            $this->routeAppHelper->isAllowSubtotal() ;
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
        return (bool) $this->isRoutePlus() && $this->routeAppHelper->isShippingMethodAllowed() ? 'block' : 'none';
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

        $this->routeAppSetupHelper->setSetupCheckWidget(Route_Route_Helper_Setup::SETUP_CHECK_WIDGET_PHP);

        return $this->jsonHelper->jsonEncode(
            [
            'subtotal' => $this->getSubtotalAmount(),
            'controller' => $this->getControllerUrl(),
            'controller_widget' => $this->getCheckWidgetControllerUrl(),
            'is_enabled' => $this->isRoutePlus(),
            'is_checked' => $this->_isEnabledDefaultInsurance(),
            'route_api' => $this->_getRouteApiUrl(),
            'token' => $this->_getMerchantPublicToken(),
            'set_methods_separate' => $this->getUrl(
                'onestepcheckout/ajax/set_methods_separate'
            ),
            'shipping_method_url' => $this->getUrl(
                'onestepcheckout/index/save_shipping',
                array('_secure' => true)
            ),
            'update_shipping_payment' => Mage::getStoreConfig(
                'onestepcheckout/ajax_update/shipping_payment',
                $storeId
            ),
            'update_shipping_review' => Mage::getStoreConfig(
                'onestepcheckout/ajax_update/shipping_review',
                $storeId
            ),
            'currency' => $this->getQuote()->getQuoteCurrencyCode()
            ]
        );
    }

}
