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
 * Observer
 *
 * @category  Model
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Route_Route_Model_Observer
{

    const CANCELED = "canceled";
    const RESEND_ORDERS_ISSUE_BEGIN = '20200201000000';
    const RESEND_ORDERS_LAST_PROCESSED_ORDER = 'last_processed_order';
    const RESEND_ORDERS_SIZE = 100;

    private $_jsonHelper;
    private $_checkoutSession;
    private $_routeAppHelper;
    private $_routeAppSetupHelper;
    private $_orderClient;
    private $_shipmentClient;

    /**
     * Route_Route_Model_Observer constructor
     */
    public function __construct()
    {
        $this->_jsonHelper = Mage::helper('core');
        $this->_checkoutSession = Mage::getSingleton('checkout/session');
        $this->_routeAppHelper = Mage::helper('route');
        $this->_routeAppSetupHelper = Mage::helper('route/setup');
        $this->_orderClient = Mage::getModel('route/api_order');
        $this->_shipmentClient = Mage::getModel('route/api_shipment');
    }

    /**
     * When order is placed we need to recalculate Order Tax over Route Fee
     *
     * @param Mage_Admin_Model_Observer $observer Observer object
     *
     * @return $this
     *
     * @throws Exception
     */
    public function orderComplete($observer)
    {
        $order = $observer->getEvent()->getOrder();
        $this->_setupCheck($order->getStore());
        if ($this->_routeAppHelper->isTaxable() && $order->getRouteIsInsured()) {
            $this->saveOrderTax($order);
        }
        $this->_checkoutSession->setInsured(null);
        return $this;
    }

    private function _setupCheck($store)
    {
        if ($this->_routeAppSetupHelper->canSendCompatibilityReport()) {
            /**
             * API CALL for incompatibility test
             */
            $api = Mage::getSingleton('route/api_compatibility');
            $api->send($this->_routeAppSetupHelper->prepareCompatibilityData($store));
        }
    }

    /**
     * When order is shipped submit it to Route API
     *
     * @param Mage_Admin_Model_Observer $observer Observer object
     *
     * @return $this
     *
     * @throws Exception
     */
    public function salesOrderShipmentSaveAfter($observer)
    {
        $shipment = $observer->getEvent()->getShipment();
        if ($this->hasRouteOrderId($shipment->getOrder()) && !$shipment->getAvoidResend()) {
            $this->_shipmentClient->postShipment($shipment);
        }
        return $this;
    }

    /**
     * Observer unitary track changes like edit / remove track numbers
     *
     * @param Mage_Admin_Model_Observer $observer Observer object
     *
     * @return $this
     */
    public function salesOrderShipmentTrackSaveAfter($observer)
    {
        $track = $observer->getEvent()->getTrack();
        if ($this->hasRouteOrderId($track->getShipment()->getOrder()) && !$track->getAvoidResend()) {
            $this->_shipmentClient->postTracking($track);
        }
        return $this;
    }

    /**
     * Before remove track from Magento, remove it from Route API
     *
     * @param $observer
     *
     * @return $this
     */
    public function beforeRemoveTrack($observer){

        $track = $observer->getEvent()->getTrack();
        $this->_shipmentClient->cancelShipment($track);

        return $this;
    }

    /**
     * This method is responsible to send
     *
     *  Order Creation
     *  Order Updates
     *  Order Cancellation
     *  Order Closed
     *  Shipment Creation
     *
     * @param Mage_Admin_Model_Observer $observer Observer object
     *
     * @return $this
     *
     * @throws Exception
     */
    public function salesOrderSave($observer)
    {
        $routeOrders = $observer->getEvent()->getOrder() ?
            [$observer->getEvent()->getOrder()] :
            $observer->getEvent()->getOrders();

        if (!empty($routeOrders)) {
            foreach ($routeOrders as $routeOrder) {
                if($this->_routeAppHelper->canCancelOrder($routeOrder)){
                    $this->_orderClient
                        ->postOrderCancel($routeOrder);
                }

                if($this->_routeAppHelper->canSubmitOrder($routeOrder) && !$routeOrder->getAvoidResend()) {

                    if ($this->hasRouteOrderId($routeOrder)) {
                        $this->_orderClient
                            ->postUpdateOrder(
                                $routeOrder,
                                $routeOrder->getRouteIsInsured()
                            );
                        return $this;

                    }

                    $completeOrder = $this->_orderClient->postOrder(
                        $routeOrder,
                        $routeOrder->getRouteIsInsured() // get stored value
                    );

                    $routeOrderId = isset($completeOrder['id']) ? $completeOrder['id'] : null;

                    $routeOrder
                        ->setRouteOrderId($routeOrderId);

                    if ($routeOrder->getShipmentsCollection() && $routeOrderId){
                        foreach ($routeOrder->getShipmentsCollection() as $shipment){
                            $this->_shipmentClient->postShipment($shipment);
                        }
                    }

                    if($routeOrder->getQuote()){
                        $routeOrder->getQuote()
                            ->getBillingAddress()
                            ->setRouteOrderId($routeOrderId);
                        return $this;

                    }

                    $routeOrder
                        ->setAvoidResend(true)
                        ->save();
                }
            }
        }

        return $this;
    }

    public function sanitizeFallbackOperations()
    {
        $this->clearFallbackOperations();
        $operationCollection = Mage::getModel('route/api_operation_fallback')
            ->getCollection();
        $operationCollection->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns(
            array(
                'entity_id' => 'main_table.entity_id',
                'COUNT(entity_id)' => 'COUNT(main_table.entity_id)',
                'type' => 'main_table.type',
                'COUNT(type)' => 'COUNT(main_table.type)',
                'operation' => 'main_table.operation',
                'COUNT(operation)' => 'COUNT(main_table.operation)'
                ))
            ->group(array('main_table.entity_id', 'main_table.type', 'main_table.operation'))
            ->having('COUNT(entity_id) > 1')
            ->having('COUNT(type) > 1')
            ->having('COUNT(operation) > 1');
        
        foreach ($operationCollection as $operation) {
            $collection = Mage::getModel('route/api_operation_fallback')
                ->getCollection()
                ->addFieldToFilter('entity_id', ['eq' => $operation->getEntityId()]);
            $lastId = $collection->getLastItem()->getId();
            foreach ($collection as $item) {
                if ($item->getId() != $lastId) {
                    $item->delete();
                }
            }
        }
    }

    public function clearFallbackOperations()
    {
        Mage::log("clearFallbackOperations process started");

        $operationCollection = Mage::getModel('route/api_operation_fallback')
            ->getCollection()
            ->getCompletedOperations();
        $operationCollection->walk('delete');

        Mage::log("retryFallbackOperations process done");
    }

    public function retryFallbackOperations()
    {

        Mage::log("retryFallbackOperations process started");

        $operationCollection = Mage::getModel('route/api_operation_fallback')
            ->getCollection()
            ->getPendingOperations();

        foreach ($operationCollection as $operation) {
            Mage::log(
                "Trying to re-execute operation: " . $operation->getOperation() .
                "\n Entity_id: " . $operation->getEntityId());
            $operation->retry();
        }

        Mage::log("retryFallbackOperations process done");
    }

    public function resendOrdersOperations()
    {
        Mage::log("resendOrdersOperations process started");
        $lastProcessedOne = $this->_routeAppHelper->getConfigValue(self::RESEND_ORDERS_LAST_PROCESSED_ORDER, true);

        foreach ($this->getPendingOrders($lastProcessedOne) as $order) {
            $lastProcessedOne = $order->getId();
            if ($this->_routeAppHelper->canSubmitOrder($order) && !$this->hasRouteOrderId($order)) {
                $completeOrder = $this->_orderClient->postOrder($order, $order->getRouteIsInsured());
                $routeOrderId = isset($completeOrder['id']) ? $completeOrder['id'] : null;
                $order
                    ->setRouteOrderId($routeOrderId);
                if ($order->getShipmentsCollection() && $routeOrderId){
                    foreach ($order->getShipmentsCollection() as $shipment){
                        $this->_shipmentClient->postShipment($shipment);
                    }
                }
                if ($order->getQuote()) {
                    $order->getQuote()
                        ->getBillingAddress()
                        ->setRouteOrderId($routeOrderId);
                }
                $order
                    ->setAvoidResend(true)
                    ->save();
            }
        }

        $this->_routeAppHelper->saveConfig(self::RESEND_ORDERS_LAST_PROCESSED_ORDER, $lastProcessedOne);

        Mage::log("resendOrdersOperations process done");
    }

    public function resendShipmentsOperationsHourly(){
        $date = new \DateTime();
        $date->sub(new \DateInterval('PT1H'));
        $this->resendShipmentsOperations($date);
    }

    public function resendShipmentsOperationsWeekly(){
        $date = new \DateTime();
        $date->sub(new \DateInterval('P7D'));
        $this->resendShipmentsOperations($date);
    }

    public function resendShipmentsOperations($timeRange)
    {
        Mage::log("resendShipmentsOperations process started");

        foreach ($this->getPendingTracks($timeRange) as $track) {

            if (!$track->getRouteOrderId()) {
                $order = Mage::getSingleton('sales/order')->load($track->getOrderId());
                if($this->_routeAppHelper->canSubmitOrder($order) && !$this->hasRouteOrderId($order)) {
                    $completeOrder = $this->_orderClient->postOrder(
                        $order,
                        $order->getRouteIsInsured() // get stored value
                    );

                    $routeOrderId = isset($completeOrder['id']) ? $completeOrder['id'] : null;

                    $order
                        ->setRouteOrderId($routeOrderId)
                        ->setAvoidResend(true)
                        ->save();
                }
            }
            $this->_shipmentClient->postTracking($track);
        }

        Mage::log("resendShipmentsOperations process done");
    }

    /**
     * @param int $lastProcessedOne
     *
     * @return sales/order collection
     */
    private function getPendingOrders($lastProcessedOne = 0)
    {
        $date = \DateTime::createFromFormat('YmdHis', self::RESEND_ORDERS_ISSUE_BEGIN);

        $ordersCollection = Mage::getModel('sales/order')->getCollection();

        if (!empty($this->_routeAppHelper->getOrderStatus())) {
            $ordersCollection->addFieldToFilter('status', [
                'in' => $this->_routeAppHelper->getOrderStatus()
            ]);
        }

        if (!empty($lastProcessedOne)) {
            $ordersCollection->addFieldToFilter('entity_id', [
                'gt' => $lastProcessedOne
            ]);
        }

        $ordersCollection->addFieldToFilter('status', [
            'nin' => $this->_routeAppHelper->getOrderStatusCanceled()
        ])->addFieldToFilter('created_at', [
            'gt' => $date->format('YmdHis')
        ])->addFieldToFilter('route_order_id', [
            ['null' => true],
            ['eq' => ''],
            ['eq' => 0]
        ])->addFieldToFilter('route_is_insured', '1')
            ->setPageSize(self::RESEND_ORDERS_SIZE);
        
        return $ordersCollection;
    }

    private function getPendingTracks($date)
    {
        $tracksCollection = Mage::getModel('sales/order_shipment_track')->getCollection();
        $tracksCollection->join(array('order'=> 'sales/order'),
            'order.entity_id=main_table.order_id',
            array('status'=>'status', 'route_is_insured' => 'route_is_insured', 'route_order_id' => 'route_order_id'),
            null,
            'left');

        if (!empty($this->_routeAppHelper->getOrderStatus())) {
            $tracksCollection->addFieldToFilter('status', [
                'in' => $this->_routeAppHelper->getOrderStatus()
            ]);
        }

        $tracksCollection->addFieldToFilter('status', [
            'nin' => $this->_routeAppHelper->getOrderStatusCanceled()
        ])->addFieldToFilter('main_table.created_at', [
            'gt' => $date->format('YmdHis')
        ])->addFieldToFilter('main_table.route_shipment_id', [
            ['null' => true],
            ['eq' => ''],
            ['eq' => '0']
        ])->addFieldToFilter('route_is_insured', '1');

        return $tracksCollection;

    }

    public function resetChecklist()
    {
        $this->_routeAppHelper->deleteCheckList();
    }

    public function registration(){
        Mage::log("Check if registration is necessary");
        if ($this->_getHelperSetup()->isFreshNewInstallation()) {
            Mage::log("Fresh new installation - trying to auto setup");
            $this->_getHelperSetup()->registerAccountToFreshNewInstallation();
            return;
        }
        Mage::log("Registration not necessary");
    }

    private function _getHelperSetup(){
        return Mage::helper('route/setup');
    }

    /**
     * Save order tax information regarding Route Fee
     *
     * @param $order
     */
    private function saveOrderTax($order)
    {
        $calc = Mage::getSingleton('tax/calculation');

        $addressTaxRequest = $calc->getRateRequest(
            $order->getShippingAddress(),
            $order->getBillingAddress(),
            $order->getCustomerTaxClassId(),
            $order->getStore()
        );

        //this step prevent tax row to be omitted
        $priority = count($order->getAppliedTaxes()) + 1;

        $addressTaxRequest->setProductClassId($this->_routeAppHelper->getTaxClassId());
        $appliedRates = $calc->getAppliedRates($addressTaxRequest);

        foreach ($appliedRates as $appliedRate) {
            foreach ($appliedRate['rates'] as $rate) {
                $hidden = (isset($appliedRate['hidden']) ? $appliedRate['hidden'] : 0);
                $data = array(
                    'order_id' => $order->getId(),
                    'code' => $rate['code'],
                    'title' => $rate['title'],
                    'hidden' => $hidden,
                    'percent' => $rate['percent'],
                    'priority' => $priority,
                    'position' => $rate['position'],
                    'amount' => $order->getFeeTaxAmount(),
                    'base_amount' => $order->getBaseFeeTaxAmount(),
                    'process' => isset($rate['process']) ? $rate['process'] : '',
                    'base_real_amount' => $order->getBaseFeeTaxAmount(),
                );
                Mage::getModel('tax/sales_order_tax')->setData($data)->save();
            }
        }
    }

    /**
     * @param $routeOrder
     * @return bool
     */
    private function hasRouteOrderId($order)
    {
        if(!empty($order->getRouteOrderId())) {
            return true;
        }

        $routeOrder = $this->_orderClient->getOrder($order);

        if($routeOrder && isset($routeOrder['id'])){
            $order
                ->setRouteOrderId($routeOrder['id'])
                ->setAvoidResend(true)
                ->save();
            return true;
        }

        return false;
    }

    /**
     * Add custom handle for admin create order process to overwrite ShipperHQ templates
     *
     * @param Varien_Event_Observer $observer
     */
    public function checkShipperHQHandle(Varien_Event_Observer $observer)
    {
        if (Mage::helper('core')->isModuleEnabled('Shipperhq_Shipper')) {
            $observer->getEvent()->getLayout()->getUpdate()
                ->addHandle('shipperhq_extend');
        }
    }

}
