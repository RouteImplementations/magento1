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
 * Add Route Fee block after shipping block on Checkout Page
 *
 * @category  Block
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Route_Route_Block_Checkout_Onepage_Shipping_Method_Additional
    extends Mage_Checkout_Block_Onepage_Shipping_Method_Additional
{

    protected $routeAppHelper;

    /**
     * Route_Route_Block_Checkout_Onepage_Shipping_Method_Additional constructor.
     */
    public function __construct()
    {
        $this->routeAppHelper = Mage::helper('route');
        parent::__construct();
    }

    /**
     * Checks if merchant public token is set
     *
     * @return bool
     */
    private function _hasMerchantPublicToken()
    {
        return !empty($this->routeAppHelper->getMerchantPublicToken());
    }

    /**
     * Processing block html after rendering
     *
     * @param string $html html block
     *
     * @return string
     */
    protected function _afterToHtml($html)
    {
        if ($this->_hasMerchantPublicToken() 
            && $block = $this->getLayout()->createBlock('route/additional')
        ) {
            $html = $block->setTemplate('route/route.phtml')->toHtml().$html;
        }
        return $html;
    }

}