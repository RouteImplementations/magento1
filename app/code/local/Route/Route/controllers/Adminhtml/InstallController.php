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


class Route_Route_Adminhtml_InstallController extends Mage_Adminhtml_Controller_Action {

    public function userAction(){
        $this->_getHelperSetup()->registerAccountToFreshNewInstallation();
        $this->_redirectReferer();
    }

    public function userLoginAction(){
        $username = $this->getRequest()->getParam('username');
        $password = $this->getRequest()->getParam('password');
        $this->_getHelperSetup()->registerAccountLogin($username, $password);
        $this->_redirectReferer();
    }

    public function merchantAction(){
        $secretUser = $this->_getHelperSetup()->getMerchantSecretToken();
        if ($this->_getHelperSetup()->registerMerchant($secretUser)) {
            $this->_getHelperSetup()->setAsInstalled();
            $this->_getHelperSetup()->setRegistrationFailedAs(Route_Route_Helper_Setup::REGISTRATION_STEP_USER_LOGIN_SUCCESS);
        }
        $this->_redirectReferer();
    }

    private function _getHelperSetup(){
        return Mage::helper('route/setup');
    }

    protected function _isAllowed()
    {
        return true;
    }
}
