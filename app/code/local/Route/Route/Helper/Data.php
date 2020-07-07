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
 * Helper to get system config, json serialization and
 * check current session is insured or not
 *
 * @category  Helper
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Route_Route_Helper_Data extends Mage_Core_Helper_Abstract
{
    const LIVE_API_URL = 'https://api.route.com/v1/';
    const SANDBOX_API_URL = "https://api-stage.route.com/v1/";

    const XML_PATH = 'insurance/route/';
    const MERCHANT_PUBLIC_TOKEN = 'merchant_public_token';
    const MERCHANT_SECRET_TOKEN = 'merchant_secret_token';
    const MERCHANT_ID = 'merchant_id';
    const INSURANCE_LABEL = 'insurance_label';
    const CHANGE_GRID = 'change_grid';
    const IS_TAXABLE = 'is_taxable';
    const PAYMENT_TAX_CLASS = 'payment_tax_class';
    const IS_SUPPORTED = 'is_supported';
    const BILLING_INFO_FILLED = 'billing_info';
    const ORDER_STATUS = 'order_status';
    const ORDER_STATUS_CANCELED = 'order_status_canceled';
    const TEST_PREFIX = 'test-';
    const DEFAULT_MAX_USD_SUBTOTAL_ALLOWED = 5000;
    const DEFAULT_MIN_USD_SUBTOTAL_ALLOWED = 0;
    const ROUTE_LABEL = 'Route Shipping Protection';
    const EXCLUDED_SHIPPING_METHODS = 'excluded_shipping_methods';
    const LATEST_VERSION_CHECK_DATE = 'latest_version_check_date';
    const LATEST_VERSION_CHECK_VERSION = 'latest_version_check_version';

    private $currentStore;

    public function __construct(){
        $this->currentStore = Mage::app()->getStore();
    }

    /**
     * Set current Store
     * @param $store
     */
    public function setCurrentStore($store){
        if (isset($store) && !is_null($store)){
            $this->currentStore = $store;
        }
    }

    /**
     * Generic method to retrieve Route Settings passing
     * the config name as param
     *
     * @param string $config configuration name
     * @param bool $escapeCache scape cache
     *
     * @return mixed
     */
    public function getConfigValue($config, $escapeCache = false)
    {
        try{
            if($escapeCache){
                return $this->readConfigByPassCache(self::XML_PATH . $config);
            }
            return Mage::getStoreConfig(
                self::XML_PATH . $config,
                $this->currentStore
            );
        }catch (Exception $exception){
            Mage::logException($exception);
            $logger = Mage::getSingleton('route/log_sentry');
            $logger->send($exception);
            return "";
        }
    }

    /**
     * Save config
     *
     * @param $config
     * @param $value
     * @param $scope
     * @param int $scopeId
     */
    public function saveConfig($config, $value)
    {
        Mage::getConfig()
            ->saveConfig(self::XML_PATH . $config, $value);
    }

    public function readConfigByPassCache($path){
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $configDataTable = Mage::getSingleton('core/resource')->getTableName("core/config_data");

        $select = $connection->select(['value'])
            ->from($configDataTable)
            ->where('path = ?', $path);
        $config = $connection->fetchRow($select);

        if (isset($config['value'])) {
            return $config['value'];
        }
        return null;
    }
    /**
     * Check if merchant is Route Plus
     *
     * @return bool Enabled or not
     */
    public function isRoutePlus()
    {
        return $this->getMerchantClient()->isRoutePlus();
    }

    /**
     * Check if merchant is Route Plus
     *
     * @return bool Enabled or not
     */
    public function isFullCoverage()
    {
        return $this->getMerchantClient()->isFullCoverage();
    }

    /**
     * It returns merchant public token as string
     *
     * @return string
     */
    public function getMerchantPublicToken() 
    {
        return $this->getConfigValue(self::MERCHANT_PUBLIC_TOKEN);
    }

    /**
     * It returns merchant secret token as string
     *
     * @return string
     */
    public function getMerchantSecretToken() 
    {
        return $this->getConfigValue(self::MERCHANT_SECRET_TOKEN);
    }

    /**
     * Check if it has test token
     *
     * @return bool
     */
    public function hasTestSecretToken(){
        return substr($this->getMerchantSecretToken(),0,strlen(self::TEST_PREFIX)) === self::TEST_PREFIX;
    }
    /**
     * Get Digital signature path
     *
     * @return string
     */
    public function getRouteLabel()
    {
        return self::ROUTE_LABEL;
    }

    /**
     * Get Route Line
     *
     * @param string @format Desired format default php array
     *
     * @return mixed
     */
    public function getRouteLine($format = ""){
        $line = [
            'label' => $this->getRouteLabel(),
            'amount' => $this->getQuote(),
            'currency' => $this->currentStore->getCurrentCurrencyCode(),
            'currency_symbol' => Mage::app()->getLocale()->currency(
                $this->currentStore->getCurrentCurrencyCode()
            )->getSymbol()
        ];

        if ($format == 'json')
            return $this->_getCoreHelper()->jsonEncode(['route'=> $line]);

        if ($format == 'html')
            return $this->_htmlFormatLine($line);

        return $line;
    }

    /**
     * Get Route line as html
     *
     * @param $line
     *
     * @return string
     */
    private function _htmlFormatLine($line){
        $amountFormatted = $this->_getCoreHelper()
            ->currency(
                $line['amount'],
                true,
                false
            );
        return "<div id=\"route\">" .
            "<span class=\"label\">{$line['label']}</span>" .
            "<span class=\"amount\">{$amountFormatted}</span>" .
            "</div>";
    }

    /**
     * Get core helper instance
     *
     * @return Mage_Core_Helper_Abstract|Mage_Core_Helper_Data
     */
    private function _getCoreHelper()
    {
        return Mage::helper('core');
    }

    /**
     * Get core helper instance
     *
     * @return string
     */
    public function getVersion()
    {
        return (string) Mage::getConfig()->getNode()->modules->Route_Route->version;
    }

    /**
     * Check default insurance functionality is enabled or not.
     *
     * @return bool Enabled or not
     */
    public function isEnabledDefaultInsurance()
    {
        return (bool) !$this->getMerchantClient()->isOptIn();
    }

    /**
     * Add Route Columns to Order grid.
     *
     * @return bool Enabled or not
     */
    public function canChangeOrderGrid()
    {
        return (bool) $this->getConfigValue(self::CHANGE_GRID);
    }

    /**
     * Checks if the current session is insured
     *
     * @return bool
     */
    public function getInsured()
    {
        $quote = Mage::getSingleton('checkout/session');
        if ($this->isAdmin()) {
            $quote = Mage::getSingleton('adminhtml/session_quote');
        }
        return  $quote->getInsured();
    }

    /**
     * Check if it should add Route Fee to summaries
     *
     * @return bool
     */
    public function canAddRouteFee(){
        return $this->getInsured() &&
            $this->isRoutePlus() &&
            $this->isAllowSubtotal() &&
            !$this->isFullCoverage() &&
            $this->isShippingMethodAllowed();
    }

    /**
     * Check if it's insured
     *
     * @return bool
     */
    public function isInsured(){
        return ($this->getInsured() && $this->isRoutePlus()) || $this->isFullCoverage();
    }

    /**
     * Get route api url
     *
     * @return string
     */
    public function getRouteApiUrl()
    {
        return self::LIVE_API_URL;
    }

    /**
     * Get quote from Route API
     *
     * @return array|string
     */
    public function getQuote()
    {
        try {
            $quote = Mage::getSingleton('checkout/cart')->getQuote();
            if ($this->isAdmin()) {
                $quote = Mage::getSingleton('adminhtml/session_quote')->getQuote();
            }
            return Mage::getSingleton('route/api_quote')->getQuote(
                $quote,
                $this->getInsured()
            );
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Check if the Route Fee is Taxable
     *
     * @return bool
     */
    public function isTaxable(){
        return (bool) $this->getConfigValue(self::IS_TAXABLE);
    }

    /**
     * Returns the tax class id from Route Fee
     *
     * @return int
     */
    public function getTaxClassId(){
        return $this->getConfigValue(self::PAYMENT_TAX_CLASS);
    }

    /**
     * Returns if billing info was checked
     *
     * @return bool
     */
    public function isBillingChecked(){
        return !is_null($this->isBillingFilled());
    }

    /**
     * Set billing info as checked or not
     *
     * @param $isFilled
     *
     * @return void
     */
    public function setBillingFilled($isFilled){
        Mage::getConfig()
            ->saveConfig(self::XML_PATH . self::BILLING_INFO_FILLED, intval($isFilled));
    }

    /**
     * Check flag if billing is filled
     *
     * @return mixed
     */
    public function isBillingFilled()
    {
        return $this->getConfigValue(self::BILLING_INFO_FILLED);
    }

    /**
     * Get order status to submit
     *
     * @return string
     */
    public function getOrderStatus()
    {
        return empty($this->getConfigValue(self::ORDER_STATUS)) ?  $this->getConfigValue(self::ORDER_STATUS) : explode(',', $this->getConfigValue(self::ORDER_STATUS));
    }


    /**
     * Get order canceled status to submit
     *
     * @return string
     */
    public function getOrderStatusCanceled()
    {
        return empty($this->getConfigValue(self::ORDER_STATUS_CANCELED)) ?  $this->getConfigValue(self::ORDER_STATUS_CANCELED) : explode(',', $this->getConfigValue(self::ORDER_STATUS_CANCELED));
    }

    /**
     * Check if it can be submitted
     *
     * @param Order $order
     *
     * @return string
     */
    public function canSubmitOrder($order)
    {
        return empty($this->getOrderStatus()) || in_array($order->getStatus(), $this->getOrderStatus());
    }

    /**
     * Check if it can be canceled
     *
     * @param Order $order
     *
     * @return string
     */
    public function canCancelOrder($order)
    {
        return empty($this->getOrderStatusCanceled()) || in_array($order->getStatus(), $this->getOrderStatusCanceled());
    }


    /**
     * Remove flags
     */
    public function deleteCheckList(){
        Mage::app()->getConfig()->deleteConfig(self::XML_PATH . self::IS_SUPPORTED);
        Mage::app()->getConfig()->deleteConfig(self::XML_PATH . self::BILLING_INFO_FILLED);
    }

    /**
     * Get Merchant Api Client
     * @return false|Mage_Core_Model_Abstract|Route_Route_Model_Api_Merchant
     */
    private function getMerchantClient()
    {
        return Mage::getSingleton('route/api_merchant');
    }

    /**
     * Check if the shipping method allows Route to appear
     *
     * @param bool $shippingMethod
     * @return bool
     * @throws Mage_Core_Model_Store_Exception
     */
    public function isShippingMethodAllowed($shippingMethod=false)
    {
        $quote = Mage::getSingleton('checkout/cart')->getQuote();
        if ($this->isAdmin()) {
            $quote = Mage::getSingleton('adminhtml/session_quote')->getQuote();
        }
        if (!$shippingMethod) {
            $shippingMethod = $quote->getShippingAddress()->getShippingMethod();
        }
        $excludedShippingMethods = $this->getExcludedShippingMethods();
        if (!$shippingMethod || !in_array($shippingMethod, $excludedShippingMethods)) {
            return true;
        }
        return false;
    }

    /**
     * Get excluded shipping methods
     *
     * @return string
     */
    public function getExcludedShippingMethods()
    {
        return empty($this->getConfigValue(self::EXCLUDED_SHIPPING_METHODS)) ?
            [] :
            explode(',', $this->getConfigValue(self::EXCLUDED_SHIPPING_METHODS));
    }

    /**
     * Check cart subtotal, hide if subtotal above allowed
     *
     * @return bool
     */
    public function isAllowSubtotal()
    {
        $quoteResponse = $this->getQuoteResponse();

        $currentUsdSubtotal = 0;
        if (isset($quoteResponse['subtotal_usd'])) {
            if ($quoteResponse['subtotal_usd'] > 0) {
                $currentUsdSubtotal = $quoteResponse['subtotal_usd'];
            }
        }
        $maxUsdSubtotal = self::DEFAULT_MAX_USD_SUBTOTAL_ALLOWED;
        if (isset($quoteResponse['coverage_upper_limit'])) {
            if ($quoteResponse['coverage_upper_limit'] > 0) {
                $maxUsdSubtotal = $quoteResponse['coverage_upper_limit'];
            }
        }
        $minUsdSubtotal = self::DEFAULT_MIN_USD_SUBTOTAL_ALLOWED;
        if (isset($quoteResponse['coverage_lower_limit'])) {
            if ($quoteResponse['coverage_lower_limit'] > 0) {
                $minUsdSubtotal = $quoteResponse['coverage_lower_limit'];
            }
        }

        return ($minUsdSubtotal < $currentUsdSubtotal) && ($currentUsdSubtotal < $maxUsdSubtotal);
    }

    /**
     * @return mixed
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getQuoteResponse()
    {
        $quote = Mage::getSingleton('checkout/cart')->getQuote();
        if ($this->isAdmin()) {
            $quote = Mage::getSingleton('adminhtml/session_quote')->getQuote();
        }
        return Mage::getSingleton('route/api_quote')->getQuoteResponse(
            $quote,
            $this->getInsured()
        );
    }

    /**
     * @return bool
     * @throws Mage_Core_Model_Store_Exception
     */
    public function isAdmin()
    {
        if (Mage::app()->getStore()->isAdmin() || Mage::getDesign()->getArea() == 'adminhtml') return true;
        return false;
    }

    /**
     *  Get Quote/Order subtotal for shippable items only
     *
     * @param $obj Mage_Sales_Model_Quote || Mage_Sales_Model_Order
     * @return float|int
     */
    public function getShippableItemsSubtotal($obj)
    {
        $subtotal = $obj->getSubtotal();
        if ($subtotal == 0) {
            $subtotal = $obj->getShippingAddress()->getSubtotal();
        }
        $items = $obj->getAllItems();
        foreach ($items as $item) {
            if ($item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL) {
                $qty = $item->getQty() ?  $item->getQty() : $item->getQtyOrdered();
                $productPrice =  $item->getPrice() * $qty;
                $subtotal =  $subtotal - $productPrice;
            }
        }
        return $subtotal;
    }

    /**
     * Returns last time we perform a version check on admin page
     *
     * @return int
     */
    public function getLatestVersionCheckDate()
    {
        return $this->getConfigValue(self::LATEST_VERSION_CHECK_DATE);
    }

    /**
     * Returns version check on admin page
     *
     * @return int
     */
    public function getLatestVersionCheckVersion()
    {
        return $this->getConfigValue(self::LATEST_VERSION_CHECK_VERSION);
    }

}
