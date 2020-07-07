<?php
/**
 * A Route Magento Extension that adds secure shipping
 * insurance to your orders
 *
 * Php version 5.6
 *
 * @category  Helper
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */

/**
 * Helper setup create new User and Merchant account
 *
 * @category  Helper
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Route_Route_Helper_Setup extends Route_Route_Helper_Data
{

    const REGISTRATION_STEP = 'registration_step';
    const FAILED_REGISTRATION = 'failed_registration';
    const MAGENTO = 'magento1';
    const REGISTRATION_FIXED = '0';
    const REGISTRATION_STEP_USER_LOGIN_SUCCESS = '1-200';

    const FAILED_REGISTRATION_STEP_USER = '1';
    const FAILED_REGISTRATION_STEP_USER_DUPLICATED = '1-409';
    const FAILED_REGISTRATION_STEP_USER_LOGIN_FAILED = '1-401';
    const FAILED_REGISTRATION_STEP_USER_ACTIVATION = '2';
    const FAILED_REGISTRATION_STEP_MERCHANT = '3';
    const FAILED_REGISTRATION_STEP_MERCHANT_DUPLICATED = '3-409';
    const REDIRECTED = '2';
    const MODULE_INSTALLED = '1';
    const TRANS_EMAIL_IDENT_GENERAL_NAME = 'trans_email/ident_general/name';
    const TRANS_EMAIL_IDENT_GENERAL_EMAIL = 'trans_email/ident_general/email';
    const ACTIVATION_LINK = 'activation_link';

    const SETUP_CHECK_WIDGET_INSTALLATION_DATE = 'setup_check_widget_installation_date';
    const SETUP_CHECK_WIDGET_API_CALL = 'setup_check_widget_api_call';
    const SETUP_CHECK_WIDGET_PHP = 'setup_check_widget_php';
    const SETUP_CHECK_WIDGET_JS = 'setup_check_widget_js';

    /**
     * Register User and Merchant Account
     * To fresh new installation
     *
     * @return bool
     */
    public function registerAccountToFreshNewInstallation()
    {
        $userClient = $this->getUserClient();
        $responseUserCreation = $userClient->createUser(
            $this->getUserData()
        );

        if ($userClient->hasFailed()) {
            $this->setRegistrationFailedAs(
                $userClient->hasConflicted() ?
                    self::FAILED_REGISTRATION_STEP_USER_DUPLICATED :
                    self::FAILED_REGISTRATION_STEP_USER
            );
            $this->cleanCache();
            return false;
        }

        if ($this->registerUser($responseUserCreation)) {

            $this->setAsInstalled();

            $this->setRegistrationFailedAs(self::REGISTRATION_FIXED);

            $this->cleanCache();

            return true;
        }
    }

    /**
     * Register User and Merchant Account by login action
     * @param $username
     * @param $password
     *
     * @return bool
     */
    public function registerAccountLogin($username, $password)
    {
        $userClient = $this->getUserClient();
        $responseUserLogin = $userClient->login($username, $password);

        if ($userClient->hasFailed()) {
            $this->setRegistrationFailedAs(
                self::FAILED_REGISTRATION_STEP_USER_LOGIN_FAILED
            );
            $this->cleanCache();
            return false;
        }

        if ($this->registerUser($responseUserLogin, true)) {

            $this->setAsInstalled();

            $this->setRegistrationFailedAs(self::REGISTRATION_STEP_USER_LOGIN_SUCCESS);

            $this->cleanCache();

            return true;
        }
    }

    /**
     * Activate User Account
     *
     * @return bool
     */
    public function activateAccount()
    {
        try {
            $userActivationClient = $this->getUserClient();
            $activationResponse = $userActivationClient->activateAccount($this->getCurrentUserEmail());
            if (!$activationResponse) {
                $this->setRegistrationFailedAs(self::FAILED_REGISTRATION_STEP_USER_ACTIVATION);
                $this->cleanCache();
                return false;
            }
            $this->setActivationLink($activationResponse['set_password_url'], 'default', $this->getCurrentStoreId());
        } catch (Exception $e) {
            $this->setRegistrationFailedAs(self::FAILED_REGISTRATION_STEP_USER_ACTIVATION);
            $this->cleanCache();
            return false;
        }

        $this->setRegistrationFailedAs(self::REGISTRATION_FIXED);

        return true;

    }

    /**
     * Register user data
     *
     * @param $responseUser
     * @param $isActive
     *
     * @return bool
     */
    public function registerUser($responseUser, $isActive = false)
    {
        try {
            $this->setSecretToken($responseUser['token'], 'default', $this->getCurrentStoreId());
        } catch (Exception $exception) {
            Mage::log('Exception while trying to store the user token: ' . $exception->getMessage());
            $logger = Mage::getSingleton('route/log_sentry');
            $logger->send($exception);
            $this->setRegistrationFailedAs(self::FAILED_REGISTRATION_STEP_USER);
            return false;
        }

        $this->setRegistrationFailedAs(self::REGISTRATION_FIXED);

        $this->cleanCache();

        if (!$isActive) {
            $this->activateAccount();
        }

        return $this->registerMerchant($responseUser['token']);
    }

    /**
     * Register merchant account to all available stores
     *
     * @param $userToken
     *
     * @return bool
     */
    public function registerMerchant($userToken = '')
    {
        Mage::log("Creating Merchant account by Store");
        if (empty($userToken)) {
            Mage::log("Using stored merchant token");
        }
        $defaultStoreWasSet = false;

        $domains = [];

        foreach ((array) Mage::app()->getStores() as $id => $store) {

            $merchantClient = $this->getMerchantClient();
            $currentMerchant = $this->prepareMerchantData($store);

            if (in_array($currentMerchant['store_domain'], $domains)) {
                continue;
            }

            $domains[] = $currentMerchant['store_domain'];

            $merchantCreationResponse = $merchantClient
                ->createMerchant(
                    $currentMerchant,
                    $userToken
                );

            if ($merchantClient->hasFailed()) {
                $this->setRegistrationFailedAs(
                    $merchantClient->hasConflicted() ?
                        self::FAILED_REGISTRATION_STEP_MERCHANT_DUPLICATED :
                        self::FAILED_REGISTRATION_STEP_MERCHANT);
                $this->cleanCache();
                return false;
            }

            //TODO Currently when we try to create merchant that already exists it's returning 200 http code success
            //TODO So we need try to update it with current platform
            if ($merchantClient->isSuccess()) {

                if (!$this->merchantCanBeUpdated($merchantCreationResponse)) {
                    $this->setRegistrationFailedAs(self::FAILED_REGISTRATION_STEP_MERCHANT);
                    $this->cleanCache();
                    return false;
                }

                //TODO This is happening when we try to update merchant that user doesn't own
                //TODO So we need try to recreate it with additional slash at the store's domain end
                $currentMerchant['store_domain'] = $currentMerchant['store_domain'] . '/';
                $merchantCreationResponse = $merchantClient
                    ->createMerchant(
                        $currentMerchant,
                        $userToken
                    );

                if ($merchantClient->hasFailed()) {
                    $this->setRegistrationFailedAs(
                        $merchantClient->hasConflicted() ?
                            self::FAILED_REGISTRATION_STEP_MERCHANT_DUPLICATED :
                            self::FAILED_REGISTRATION_STEP_MERCHANT);
                    $this->cleanCache();
                    return false;
                }

            }

            //TODO It'll just happen if merchant creation returns 201
            if (!$defaultStoreWasSet) {
                Mage::log("Creating Merchant account to default store");
                $this->setPublicToken(
                    $merchantCreationResponse['public_api_key'], 'default'
                );

                $this->setSecretToken(
                    $merchantCreationResponse['prod_api_secret'], 'default'
                );
                $defaultStoreWasSet = true;
            }

            Mage::log("Creating Merchant account to specific stores");

            $this->setPublicToken(
                $merchantCreationResponse['public_api_key'], 'stores', $store->getId()
            );

            $this->setSecretToken(
                $merchantCreationResponse['prod_api_secret'], 'stores', $store->getId()
            );

        }

        return true;
    }

    /**
     * Double check column type
     * Old installation was creating columns with wrong type value
     * This will make sure they can be fixed else it'll warning
     * the admin user about the incompatibility
     *
     * @return bool
     */
    public function doubleCheckColumnType()
    {

        if ($this->isDbSupportChecked()) {
            return !$this->isDbSupported();
        }

        $connection = Mage::getSingleton('core/resource');
        $tables = [
            $connection->getTableName('sales/order') => [
                'fee_amount', 'base_fee_amount', 'fee_amount_refunded',
                'base_fee_amount_refunded', 'fee_amount_invoiced', 'base_fee_amount_invoiced'
            ],
            $connection->getTableName('sales/quote') => ['fee_amount', 'base_fee_amount'],
            $connection->getTableName('sales/invoice') => ['fee_amount', 'base_fee_amount'],
            $connection->getTableName('sales/creditmemo') => ['fee_amount', 'base_fee_amount'],
            $connection->getTableName('sales/quote_address') => ['fee_amount', 'base_fee_amount']
        ];

        $connection = $connection->getConnection('core_read');

        foreach ($tables as $table => $columns) {
            $tableColumns = $connection->describeTable($table);
            foreach ($columns as $column) {
                if (
                    isset($tableColumns[$column]) && isset($tableColumns[$column]['SCALE'])
                    && $tableColumns[$column]['SCALE'] < 2
                ) {
                    $this->setDbSupported(false);
                    return true;
                }
            }
        }

        $this->setDbSupported(true);

        return false;
    }

    /**
     * Check if it's fresh installation
     *
     * @return bool
     */
    public function isFreshNewInstallation()
    {
        return
            $this->getCurrentAdminSession()->isLoggedIn() &&
            is_null($this->registrationStep()) &&
            !$this->hasValidMerchantTokens();
    }

    /**
     * Check if it's has valid Merchants
     *
     * @return bool
     */
    public function hasValidMerchantTokens()
    {
        $isValid = $this->hasMerchantTokens() &&
            !empty($this->getMerchantClient()->getMerchant());
        Mage::log($isValid ? "Merchant has valid tokens." : "Merchant has invalid tokens.");
        return $isValid;
    }

    /**
     * Check if it's has Merchant Tokens
     *
     * @return bool
     */
    private function hasMerchantTokens()
    {
        return !empty($this->getMerchantPublicToken()) &&
            !empty($this->getMerchantSecretToken());
    }

    /**
     * Set as installed successful.
     *
     * @return void
     */
    public function setAsInstalled()
    {
        $this->registerStep(self::MODULE_INSTALLED);
        $this->setSetupCheckWidget(self::SETUP_CHECK_WIDGET_INSTALLATION_DATE, time());
    }

    /**
     * Save configuration
     *
     * @param $config
     * @param $value
     * @param string $scope
     * @param int $scopeId
     */
    public function saveConfig($config, $value, $scope = 'default', $scopeId = 0)
    {
        Mage::getConfig()
            ->saveConfig(
                parent::XML_PATH . $config,
                $value,
                $scope,
                $scopeId
            );
    }

    /**
     * Set registration failed
     *
     * @param $status
     *
     * @return void
     */
    public function setRegistrationFailedAs($status)
    {
        Mage::log($status > 0 ? "Setup failed at step " . $status :
            "Setup was successful");
        $this->saveConfig(self::FAILED_REGISTRATION, $status);
    }

    /**
     * Get registration failed
     *
     * @return string
     */
    public function getFailedRegistration()
    {
        return $this->getConfigValue(self::FAILED_REGISTRATION);
    }

    /**
     * If it's installation has failed at user step creation step
     *
     * @return bool
     */
    public function hasUserCreationFailed()
    {
        return
            $this->getFailedRegistration() == self::FAILED_REGISTRATION_STEP_USER || $this->getFailedRegistration() == self::FAILED_REGISTRATION_STEP_USER_LOGIN_FAILED;
    }

    /**
     * If it's installation has failed at Merchant creation step
     *
     * @return bool
     */
    public function hasMerchantCreationFailed()
    {
        return $this->getFailedRegistration() == self::FAILED_REGISTRATION_STEP_MERCHANT;
    }

    /**
     * If it's installation has failed at user step creation step
     *
     * @return bool
     */
    public function hasUserCreationConflicted()
    {
        return $this->getFailedRegistration() == self::FAILED_REGISTRATION_STEP_USER_DUPLICATED;
    }

    /**
     * If it's installation has failed at Merchant creation step
     *
     * @return bool
     */
    public function hasMerchantCreationConflicted()
    {
        return $this->getFailedRegistration() == self::FAILED_REGISTRATION_STEP_MERCHANT_DUPLICATED;
    }

    /**
     * Check if user login has succeed
     *
     * @return bool
     */
    public function hasUserLoginSucceed()
    {
        return $this->getFailedRegistration() == self::REGISTRATION_STEP_USER_LOGIN_SUCCESS;
    }

    /**
     * Set token generated after user creation
     *
     * @param string $token
     * @param string $scope
     * @param string $storeId
     *
     * @return void
     */
    public function setPublicToken($token, $scope = 'default', $storeId = '0')
    {
        $this->saveConfig(self::MERCHANT_PUBLIC_TOKEN,
            $token,
            $scope,
            $storeId
        );
    }

    /**
     * Set token generated after user creation
     *
     * @param string $token
     * @param string $scope
     * @param string $storeId
     *
     * @return void
     */
    public function setSecretToken($token, $scope = 'default', $storeId = '0')
    {
        Mage::log('New secret token has set: ' . $token);
        $this->saveConfig(self::MERCHANT_SECRET_TOKEN,
            $token,
            $scope,
            $storeId
        );
    }

    /**
     * Activation Link
     *
     * @param string $activationLink
     * @param string $scope
     * @param string $storeId
     *
     * @return void
     */
    public function setActivationLink($activationLink, $scope = 'default', $storeId = '0')
    {
        Mage::log('Activation link: ' . $activationLink);
        $this->saveConfig(self::ACTIVATION_LINK,
            $activationLink,
            $scope,
            $storeId
        );
    }

    /**
     * Get activation link
     *
     * @return mixed
     */
    public function getActivationLink()
    {
        return $this->getConfigValue(self::ACTIVATION_LINK);
    }

    /**
     * Generates temporary password to account creation
     *
     * @return string
     */
    private function generateTempPassword()
    {
        return "pass" . substr(hash('sha256', rand()), 0, 10);
    }

    /**
     * Prepare User Data
     *
     * @return mixed
     */
    private function getUserData()
    {
        $userData = [];
        $userData['name'] = $this->getCurrentUsername();
        $userData['password'] = $this->generateTempPassword();
        $userData['platform_id'] = self::MAGENTO;
        $userData['phone'] = $this->getGeneralPhoneNumber();
        $userData['primary_email'] = $this->getCurrentUserEmail();
        return $userData;
    }

    /**
     * Prepare Merchant Data
     * @param $store
     * @return mixed
     */
    private function prepareMerchantData($store)
    {
        $merchantData = [];
        $merchantData['platform_id'] = self::MAGENTO;
        $merchantData['store_domain'] = parse_url($store->getBaseUrl(), PHP_URL_HOST);
        $merchantData['store_name'] = $store->getName();
        $merchantData['country'] = $this->getCountryByStore($store);
        $merchantData['deal_size_order_count'] = $this->getDealSize();
        $merchantData['currency'] = $store->getCurrentCurrencyCode();
        $merchantData['source'] = self::MAGENTO;
        return $merchantData;
    }

    /**
     * Prepare Merchant Data
     * @param $store
     * @return mixed
     */
    public function prepareCompatibilityData($store)
    {
        $widgetPhpFlag = $this->getSetupCheckWidget(self::SETUP_CHECK_WIDGET_PHP);
        $widgetJsFlag = $this->getSetupCheckWidget(self::SETUP_CHECK_WIDGET_JS);
        $creationDate = $this->getSetupCheckWidget(self::SETUP_CHECK_WIDGET_INSTALLATION_DATE);

        $successInstallation = $widgetPhpFlag && $widgetJsFlag;

        $data = [];
        $data['php_flag'] = $widgetPhpFlag ? $widgetPhpFlag : 0;
        $data['js_flag'] = $widgetJsFlag ? $widgetJsFlag : 0;
        $data['success_installation'] = $successInstallation;
        $data['install_date'] = $creationDate;
        $data['store_domain'] = parse_url($store->getBaseUrl(), PHP_URL_HOST);
        $data['platform_id'] = self::MAGENTO;
        $data['subject'] = $successInstallation ? 'Success Installation' : 'Installation Issues';
        $data['modules'] = Mage::getConfig()->getNode()->modules;
        return $data;
    }

    public function canSendCompatibilityReport(){
        $apiCalled = $this->getSetupCheckWidget(self::SETUP_CHECK_WIDGET_API_CALL);
        $creationDate = $this->getSetupCheckWidget(self::SETUP_CHECK_WIDGET_INSTALLATION_DATE);

        return (time() > $creationDate && !$apiCalled);
    }
    /**
     * @return false|Mage_Core_Model_Abstract|Route_Route_Model_Api_Merchant
     */
    private function getMerchantClient()
    {
        return Mage::getModel('route/api_merchant');
    }

    /**
     * @return false|Mage_Core_Model_Abstract|Route_Route_Model_Api_User
     */
    private function getUserClient()
    {
        return Mage::getModel('route/api_user');
    }

    /**
     * @return Mage_Admin_Model_Session|Mage_Core_Model_Abstract
     */
    private function getCurrentAdminSession()
    {
        return Mage::getSingleton('admin/session');
    }

    /**
     * Get General Phone from settings
     *
     * @return string
     */
    private function getGeneralPhoneNumber()
    {
        try {
            return Mage::getStoreConfig(
                Mage_Core_Model_Store::XML_PATH_STORE_STORE_PHONE,
                Mage::app()->getStore()
            );
        } catch (Exception $e) {
            return "";
        }
    }

    /**
     * Get current user First Name
     *
     * @return string
     */
    private function getCurrentUserName()
    {
        try {
            $name = $this->getCurrentAdminSession()->getUser()->getFirstname();
            if (empty($name)) {
                $name = Mage::getStoreConfig(self::TRANS_EMAIL_IDENT_GENERAL_NAME, Mage::app()->getStore());
            }
            return $name;
        } catch (Exception $e) {
            return "";
        }
    }

    /**
     * Get current user email
     *
     * @return string
     */
    public function getCurrentUserEmail()
    {
        try {
            $email = $this->getCurrentAdminSession()->getUser()->getEmail();
            if (!empty($email)) {
                $email = Mage::getStoreConfig(self::TRANS_EMAIL_IDENT_GENERAL_EMAIL, Mage::app()->getStore());
            }
            return $email;
        } catch (Exception $e) {
            return "";
        }
    }

    /**
     * Returns if the database support was checked.
     *
     * @param $supported
     *
     * @return void
     */
    public function setDbSupported($supported)
    {
        $this->saveConfig(self::IS_SUPPORTED, intval($supported));
    }

    /**
     * Check flag if db is compatible
     *
     * @return mixed
     */
    public function isDbSupported()
    {
        return $this->getConfigValue(self::IS_SUPPORTED);
    }

    /**
     * Returns if the database support was checked.
     *
     * @return bool
     */
    public function isDbSupportChecked()
    {
        return !is_null($this->isDbSupported());
    }

    /**
     * Clean Cache Config
     */
    private function cleanCache()
    {
        Mage::log("Trying to clean caches");
        return Mage::app()->cleanCache() &&
            Mage::app()->getCacheInstance()->flush();
    }

    /**
     * @param $store
     * @return mixed
     */
    private function getCountryByStore($store)
    {
        return Mage::getStoreConfig('general/country/default', $store->getId());
    }

    /**
     * @return mixed
     */
    public function registrationStep()
    {
        return $this->getConfigValue(self::REGISTRATION_STEP, true);
    }

    /**
     * Set config as redirected.
     *
     * @return bool
     */
    public function setRedirected()
    {
        $this->registerStep(self::REDIRECTED);
        return true;
    }

    /**
     * @return mixed
     */
    public function wasRedirected()
    {
        return $this->registrationStep() == self::REDIRECTED;
    }

    /**
     * check if it's installed
     *
     * @return bool
     */
    public function isInstalled()
    {
        return $this->registrationStep() == self::MODULE_INSTALLED;
    }

    /**
     * @param $step
     * @return mixed
     */
    public function registerStep($step)
    {
        return $this->saveConfig(self::REGISTRATION_STEP, $step);
    }

    /**
     * @return int
     * @throws Mage_Core_Model_Store_Exception
     */
    private function getCurrentStoreId()
    {
        return Mage::app()->getStore()->getId();
    }

    /**
     * Check if merchant can be updated
     * @param $merchantCreationResponse
     * @return bool
     */
    public function merchantCanBeUpdated($merchantCreationResponse)
    {
        return (isset($merchantCreationResponse['platform_id']) && strtolower($merchantCreationResponse['platform_id']) == 'email') ||
            (isset($merchantCreationResponse['status']) && strtolower($merchantCreationResponse['status']) != 'active');
    }

    public function getDealSize(){
        $currentDate = new DateTime();
        $monthAgo = $currentDate->sub(new DateInterval("P1M"));
        $orderCollection = Mage::getModel('sales/order')
            ->getCollection()
            ->addAttributeToFilter('created_at', ['gteq' => $monthAgo->format('YmdHis')]);
        return strval($orderCollection->count());
    }


    /**
     * Set setup check widget
     *
     * @param $config
     * @param bool $value
     */
    public function setSetupCheckWidget($config, $value=false)
    {
        if (!$this->getConfigValue($config, true)) {
            $value = $value ? $value : 1;
            $this->saveConfig($config, $value);
        }
    }

    /**
     * Get setup check widget config
     *
     * @param $config
     * @return mixed
     */
    public function getSetupCheckWidget($config)
    {
        return $this->getConfigValue($config, true);
    }

}
