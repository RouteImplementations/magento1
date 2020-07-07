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


class Route_Route_Adminhtml_InsuredController extends Mage_Adminhtml_Controller_Action {
    /**
     * Main method that receive the param to define if the quote is insured or not
     *
     * @return void
     */
    public function indexAction()
    {
        $routeInsurance = $this->getRequest()->getParam('is_route_insured');
        $checkoutSession = Mage::getSingleton('adminhtml/session_quote');

        $checkoutSession->setInsured(
            filter_var($routeInsurance, FILTER_VALIDATE_BOOLEAN)
        );
        $this->getResponse()->setBody(
            $this->_getCoreHelper()->jsonEncode(
                ['success' => $checkoutSession->getInsured()]
            )
        );
    }

    protected function _isAllowed()
    {
        return true;
    }
}
