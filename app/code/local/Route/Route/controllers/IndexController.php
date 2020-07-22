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
 * Main controller to receive and define the quote as insured or not
 *
 * @category  Controller
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Route_Route_IndexController extends Mage_Core_Controller_Front_Action
{

    /**
     * Main method that receive the param to define if the quote is insured or not
     *
     * @return void
     */
    public function indexAction()
    {
        $routeInsurance = $this->getRequest()->getParam('is_route_insured');
        $checkoutSession = Mage::getSingleton('checkout/session');
        $checkoutSession->setInsured(
            filter_var($routeInsurance, FILTER_VALIDATE_BOOLEAN)
        );
        $routeAppSetupHelper = Mage::helper('route/setup');
        $routeAppSetupHelper->setSetupCheckWidget(Route_Route_Helper_Setup::SETUP_CHECK_WIDGET_JS);
        $this->getResponse()->setBody(
            $this->_getCoreHelper()->jsonEncode(
                ['success' => $checkoutSession->getInsured()]
            )
        );
    }

    /**
     * Method to check if we should show/hide widget on checkout page
     *
     * @throws Mage_Core_Model_Store_Exception
     */
    public function checkWidgetAction()
    {
        $response = ['show_widget' => true];
        $shippingMethod = $this->getRequest()->getParam('shipping_method');
        if (strpos($shippingMethod, '_') === false) {
            $shippingMethod = $shippingMethod . '_' . $shippingMethod;
        }
        if (!$this->_getRouteHelper()->isShippingMethodAllowed($shippingMethod)) {
            $response = ['show_widget' => false];
        }
        $this->getResponse()->setBody(
            $this->_getCoreHelper()->jsonEncode(
                $response
            )
        );
    }

    /**
     * Update orders
     *
     * @return void
     *
     * @throws Exception
     */
    public function updateAction()
    {
        $token = $this->getRequest()->getParam('secret');
        $updatedOrders = [];

        if ($token === $this->_getRouteHelper()->getMerchantPublicToken()) {
            $orders = explode(',', $this->getRequest()->getParam('orders'));
            if (!empty($orders)) {
                $routeOrderParser = Mage::getModel('route/sales_order');
                $routeOrderClient = Mage::getModel('route/api_order');
                foreach ($orders as $incrementId) {
                    $order = Mage::getModel('sales/order')
                        ->loadByIncrementId($incrementId);
                    if ($order->getId() > 0) {
                        $routeOrderParser->setOrder($order);
                        $routeOrderClient->postOrder(
                            $routeOrderParser,
                            !!$order->getRouteFee() > 0
                        );
                        $updatedOrders[] = $incrementId;
                    }
                }

            }
        }

        $this->getResponse()->setBody(
            $this->_getCoreHelper()->jsonEncode(
                [
                    'updated' => $updatedOrders,
                    'count' => count($updatedOrders)
                ]
            )
        );
    }

    /**
     * Action to render current Route Status
     *
     * @return Zend_Controller_Response_Abstract
     *
     * @throws Exception
     */
    public function routeLineAction(){
        $format = $this->getRequest()->getParam('format');

        if(!in_array($format, ["", "json", "html"]))
            Mage::throwException("Invalid format");

        return $this->getResponse()->setBody(
            $this->_getRouteHelper()->getRouteLine($format)
        );
    }

    /**
     * Get Route helper instance
     *
     * @return Mage_Core_Helper_Abstract|Route_Route_Helper_Data
     */
    private function _getRouteHelper()
    {
        return Mage::helper('route');
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
     * Get setup helper instance
     *
     * @return Mage_Core_Helper_Abstract|Mage_Core_Helper_Data
     */
    private function _getSetupHelper()
    {
        return Mage::helper('route/setup');
    }

    public function configAction(){
        $message = '';
        $secretToken = $this->getRequest()->getParam('secret_token');
        $secretToken = isset($secretToken) ? $secretToken : false;
        $action = $this->getRequest()->getParam('action');
        $action  = isset($action) ? $this->getRequest()->getParam('action') : false;

        if ($secretToken != $this->_helper->getMerchantSecretToken() || !$action) {
            return;
        }

        switch ($action) {
            case 'modify_last_processed_order':
                $orderProcessId = $this->getRequest()->getParam(Route_Route_Model_Observer::RESEND_ORDERS_LAST_PROCESSED_ORDER);
                $orderProcessId = isset($orderProcessId) ?
                    filter_var($orderProcessId, FILTER_SANITIZE_NUMBER_INT) :
                    false;
                $this->_getRouteHelper()->saveConfig(Route_Route_Model_Observer::RESEND_ORDERS_LAST_PROCESSED_ORDER, $orderProcessId);
                $message = "Last Process Order: " . $orderProcessId;
                break;
        }

        $content = [
            'message' => $message
        ];

        return $this->getResponse()->setBody(
            $this->_getCoreHelper()->jsonEncode($content)
        );
    }

    public function statusAction(){
        $publicToken = !empty($this->_getRouteHelper()->getMerchantPublicToken()) ?
            $this->_encodeToken($this->_getRouteHelper()->getMerchantPublicToken()) :
            false;
        $secretKey =  !empty($this->_getRouteHelper()->getMerchantSecretToken()) ?
            $this->_encodeToken($this->_getRouteHelper()->getMerchantSecretToken()) :
            false;
        $content = [
            'version' => $this->_getRouteHelper()->getVersion(),
            'public_token' => $publicToken,
            'secret_key' => $secretKey,
            'default_setting' => $this->_getRouteHelper()
                ->isEnabledDefaultInsurance(),
            'route_plus' => $this->_getRouteHelper()->isRoutePlus(),
            'is_full' => $this->_getRouteHelper()->isFullCoverage(),
            'module_enabled' => $this->_getRouteHelper()->isModuleEnabled(),
            'is_insured' => $this->_getRouteHelper()->getQuote() > 0,
            'failed_transactions' => $this->failedPendingTransactions(),
            'fresh_new_installation' => $this->_getSetupHelper()->isFreshNewInstallation(),
            'date' => (new DateTime()),
            'is_taxable' => $this->_getRouteHelper()->isTaxable(),
            'include_order_thank_you_page_widget' => $this->_getRouteHelper()->isIncludesOrderThankYouPageWidget(),
            'payment_tax_class' => $this->_getRouteHelper()->getTaxClassId(),
            'order_status' => $this->_getRouteHelper()->getOrderStatus(),
            'order_status_canceled' => $this->_getRouteHelper()->getOrderStatusCanceled(),
            'excluded_shipping_methods' => $this->_getRouteHelper()->getExcludedShippingMethods(),
            'last_processed_order' => $this->_getRouteHelper()->getConfigValue(Route_Route_Model_Observer::RESEND_ORDERS_LAST_PROCESSED_ORDER )
        ];

        return $this->getResponse()->setBody(
            $this->_getCoreHelper()->jsonEncode($content)
        );
    }

    private function _encodeToken($token)
    {
        return substr($token, 0, 5) . '...' . substr($token, -5);
    }

    public function failedPendingTransactions(){
        return Mage::getModel('route/api_operation_fallback')
            ->getCollection()
            ->getPendingOperations()
            ->count();
    }
}
