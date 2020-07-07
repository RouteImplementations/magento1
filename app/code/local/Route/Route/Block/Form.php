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
 * Form class
 *
 * @category  Block
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Route_Route_Block_Form extends Mage_Checkout_Block_Onepage_Abstract
{

    protected $jsonHelper;
    protected $routeAppHelper;
    protected $scopeConfig;
    protected $checkoutSession;

    /**
     * Form constructor
     */
    public function __construct()
    {
        $this->checkoutSession = Mage::getModel('checkout/session');
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
     * Get controller insurance url
     *
     * @return string
     */
    public function getControllerUrl()
    {
        return Mage::helper('adminhtml')->getUrl('route');
    }

    /**
     * Is route insurance route module enable
     *
     * @return bool
     */
    public function isRouteEnabled()
    {
        return $this->routeAppHelper->isRoutePlus();
    }
}
