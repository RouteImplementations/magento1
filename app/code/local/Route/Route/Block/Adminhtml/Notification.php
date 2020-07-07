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
 * Class to control billing notification
 *
 * @category  Block
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Route_Route_Block_Adminhtml_Notification extends Mage_Adminhtml_Block_Template
{

    const BILLING_DASHBOARD_URL = "https://dashboard.route.com/";
    const PORTAL_URL = 'https://dashboard.route.com/login?redirect=onboarding';
    const VERSION_UPDATE_URI = 'https://route.com/merchants/install/';
    const VERSION_CHECK_ENDPOINT = 'https://route-cdn.s3.amazonaws.com/route-magento-extension/magento1-version.json';

    /**
     * Check if it need to be notified
     *
     * @return bool
     */
    public function canShow()
    {
        if ($this->_getHelper()->isBillingChecked() && $this->_getHelper()->isBillingFilled()) {
            return false;
        }

        try {
            $isBillingEmpty = empty(Mage::getModel('route/api_billing')->getBilling());
            $this->_getHelper()->setBillingFilled(!$isBillingEmpty);
        } catch (Exception $e) {
            return true;
        }
    }

    /**
     * Check if there is a module update available
     *
     * @return bool
     * @throws Exception
     */
    public function versionUpdateAvailable()
    {
        $latestVersion = $this->_getLatestVersion();
        if (version_compare($this->_getHelper()->getVersion(), $latestVersion) < 0) {
            return true;
        }
        return false;
    }

    /**
     * Get latest version available
     *
     * @return string
     * @throws Exception
     */
    public function getVersionUpdate()
    {
        return $this->_getLatestVersion();
    }

    /**
     * Get latest version from endpoint, save into database
     *
     * @return mixed
     * @throws Exception
     */
    private function _getLatestVersion()
    {
        $version = $this->_getHelper()->getLatestVersionCheckVersion();

        if ($this->_shouldPerformApiCall()) {
            $version = $this->_getMostRecentVersion();
            $this->_getHelper()->saveConfig(Route_Route_Helper_Data::LATEST_VERSION_CHECK_VERSION, $version);
            $currentDate = new \DateTime();
            $this->_getHelper()->saveConfig(Route_Route_Helper_Data::LATEST_VERSION_CHECK_DATE, $currentDate->format('Y-m-d'));
        }

        return $version;
    }

    /**
     * Parse module version from endpoint
     *
     * @return string
     */
    private function _getMostRecentVersion()
    {
        try{
            $versionJson = file_get_contents(self::VERSION_CHECK_ENDPOINT);
            if (!empty($versionJson)) {
                $decodedVersion = json_decode($versionJson, true);
                return $decodedVersion['latest_version'];
            }
        }catch(\Exception $exception){
            return $this->_getHelper()->getLatestVersionCheckVersion();
        }
    }

    /**
     * Check if we should make API call to get latest version based on date
     *
     * @return bool
     * @throws Exception
     */
    private function _shouldPerformApiCall()
    {
        $currentDate = new \DateTime();
        $latestRequest = $this->_getHelper()->getLatestVersionCheckDate() ?
            new \DateTime($this->_getHelper()->getLatestVersionCheckDate()) :
            false;
        if (!$latestRequest || ($currentDate->format('Y-m-d')!==$latestRequest->format('Y-m-d'))) {
            return true;
        }
        return false;
    }

    /**
     * Get version update URI
     *
     * @return string
     */
    public function getVersionUpdateUri()
    {
        return self::VERSION_UPDATE_URI;
    }

    /**
     * Check if the database has wrong column type else
     * let the admin user knows about it
     *
     * @return bool
     */
    public function checkDatabaseSupport()
    {
        return $this->_getHelperSetup()->doubleCheckColumnType();
    }

    /**
     * If it's fresh new Installation it'll trigger
     * User and Merchant creation account
     *
     * @return bool
     */
    public function canShowRedirectAlert()
    {
        return ($this->hasToBeRedirected() ||
            $this->hasMerchantCreationConflicted()) &&
            $this->_getHelperSetup()->setRedirected();
    }

    /**
     * Get message according to installation step and status
     *
     * @return bool
     */
    public function getMessage()
    {

        if ($this->hasUserCreationConflicted()) {
            return "Your user account already exists. We're redirecting you to Route website to finalize your account setup... ";
        }

        return "You're almost there! We're redirecting you to Route website to finalize your account setup... ";


    }

    /**
     * Get message according to installation step and status
     *
     * @return bool
     */
    public function getUsername()
    {
        return $this->_getHelperSetup()->getCurrentUserEmail();
    }

    /**
     * Check if user creation has failed by conflict reason
     *
     * @return bool
     */
    public function hasUserCreationFailed()
    {
        return $this->_getHelperSetup()->hasUserCreationFailed() && !$this->_getHelperSetup()->hasValidMerchantTokens();
    }

    /**
     * Check if user creation has failed by conflict reason
     *
     * @return bool
     */
    public function hasUserCreationConflicted()
    {
        return $this->_getHelperSetup()->hasUserCreationConflicted() && !$this->_getHelperSetup()->hasValidMerchantTokens();
    }

    /**
     * Check if merchant creation has failed by conflict reason
     *
     * @return bool
     */
    public function hasMerchantCreationFailed()
    {
        return $this->_getHelperSetup()->hasMerchantCreationFailed() && !$this->_getHelperSetup()->hasValidMerchantTokens();
    }

    /**
     * If it's installation has failed
     *
     * @return bool
     */
    public function hasMerchantCreationConflicted()
    {
        return $this->_getHelperSetup()->hasMerchantCreationConflicted() && !$this->_getHelperSetup()->hasValidMerchantTokens();
    }

    /**
     * Check if user has logged in
     *
     * @return bool
     */
    public function hasUserLoginSucceed()
    {
        return $this->_getHelperSetup()->hasUserLoginSucceed();
    }

    /**
     * Get billing dashboard url
     *
     * @return string
     */
    public function getBillingDashboardUrl()
    {
        return self::BILLING_DASHBOARD_URL;
    }

    /**
     * Get Route helper
     *
     * @return mixed
     */
    private function _getHelper()
    {
        return Mage::helper('route');
    }

    /**
     * Get setup helper
     *
     * @return mixed
     */
    private function _getHelperSetup()
    {
        return Mage::helper('route/setup');
    }

    /**
     * Ger dashboard url to redirect
     */
    public function getRoutePortalUrl()
    {
        if ($this->hasUserLoginSucceed()) {
            return self::PORTAL_URL;
        }

        return $this->_getHelperSetup()->getActivationLink();
    }

    /**
     * Ger recreate user url
     *
     * @return mixed
     */
    public function tryRecreateUser()
    {
        return Mage::helper("adminhtml")->getUrl("route/install/user");
    }

    /**
     * Ger recreate merchant url
     *
     * @return mixed
     */
    public function tryRecreateMerchant()
    {
        return Mage::helper("adminhtml")->getUrl("route/install/merchant");
    }

    /**
     * Ger login url
     *
     * @return mixed
     */
    public function userLoginAction()
    {
        return Mage::helper("adminhtml")->getUrl("route/install/userLogin");
    }

    /**
     * @return bool
     */
    public function hasToBeRedirected()
    {
        return $this->_getHelperSetup()->isInstalled()
            && !$this->_getHelperSetup()->wasRedirected();
    }
}
