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
 * Order integration
 *
 * @category  Model
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Route_Route_Model_Api_Order extends Route_Route_Model_Api_RouteClient
{

    const API_ENDPOINT = 'orders';
    const API_CANCEL_SUFFIX = 'cancel';

    private $storable = true;
    private $orderObj;

    /**
     * Submit fresh order created to Route API
     *
     * @param Mage_Sales_Model_Order $order     Route Order Object
     * @param bool                   $isInsured if order is insured
     *
     * @return bool|mixed
     *
     * @throws Exception
     */
    public function postOrder($order, $isInsured)
    {

        $this->setStore($order->getStore());

        $this->orderObj = Mage::getModel('route/sales_order')
            ->setOrder($order);

        $this->setOperation('postOrder');

        $completeOrder = $this->postMethod(
            $this->getRouteApiUrl(self::API_ENDPOINT),
            $this->orderObj->getRouteObject($isInsured)
        )->execute([parent::CREATED_HTTP_CODE, parent::SUCCESS_HTTP_CODE]);

        $this->log(
            "Route: Order " .
            (isset($completeOrder['id']) ?
                "submitted successfully for order id '" . $completeOrder['id'] .
                "' and order number '" . $completeOrder['order_number'] . "'" :
                "not submitted")
        );

        return $completeOrder;
    }

    /**
     * Submit order canceling to Route API
     *
     * @param Mage_Sales_Model_Order $orderObj Route Order Object
     *
     * @return bool|mixed
     *
     * @throws Exception
     */
    public function postOrderCancel($orderObj)
    {
        if ($this->orderExists($orderObj)) {
            $this->setStore($orderObj->getStore());

            $this->orderObj = Mage::getModel('route/sales_order')
                ->setOrder($orderObj);

            $this->setOperation('postOrderCancel');

            $cancelResponse = $this->postMethod(
                $this->getRouteApiUrl(
                    self::API_ENDPOINT . '/' .
                    $this->orderObj->getOrder()->getIncrementId() . '/' .
                    self::API_CANCEL_SUFFIX
                )
            )->execute([parent::CREATED_HTTP_CODE, parent::SUCCESS_HTTP_CODE, parent::NOT_FOUND_HTTP_CODE]);

            $this->log(
                "Route: Order " .
                (
                isset($cancelResponse['id']) ?
                    "successfully canceled  for order id '" . $cancelResponse['id'] .
                    "' and order number '" . $cancelResponse['order_number'] . "'" :
                    "not canceled"
                )
            );
            return $cancelResponse;
        }
        return true;
    }

    /**
     * Submit order update to Route API
     *
     * @param Mage_Sales_Model_Order $orderObj  Route Order Object
     * @param bool                   $isInsured if order is insured
     *
     * @return bool|mixed
     *
     * @throws Exception
     */
    public function postUpdateOrder($orderObj, $isInsured)
    {

        $this->setStore($orderObj->getStore());

        $this->orderObj = Mage::getModel('route/sales_order')
            ->setOrder($orderObj);

        if ($this->orderObj->canSendUpdate()) {
            $this->setOperation('postUpdateOrder');

            $updateResponse = $this->postMethod(
                $this->getRouteApiUrl(
                    self::API_ENDPOINT . '/' . $this->orderObj->getOrder()->getIncrementId()
                ),
                $this->orderObj->getRouteObject($isInsured)
            )->execute([parent::CREATED_HTTP_CODE, parent::SUCCESS_HTTP_CODE]);

            $this->log(
                "Route: Order " .
                (
                isset($updateResponse['id']) ?
                    "updated successfully for order id '" . $updateResponse['id'] .
                    "' and order number '" . $updateResponse['order_number'] . "'" :
                    "not updated"
                )
            );

            return $updateResponse;
        }

        return false;
    }

    /**
     * Get Order from Route API
     *
     * @param Mage_Sales_Model_Order $orderObj  Route Order Object
     *
     * @return bool|mixed
     *
     * @throws Exception
     */
    public function getOrder($orderObj)
    {
        $this->storable = false;
        $this->setAvoidException();
        $routeOrder = $this->getMethod(
            $this->getRouteApiUrl(
                self::API_ENDPOINT . '/' . $orderObj->getIncrementId()
            )
        )->execute([self::SUCCESS_HTTP_CODE, self::NOT_FOUND_HTTP_CODE]);

        return $routeOrder;
    }

    public function orderExists($order){
        if($order->getRouteOrderId()){
            return true;
        }

        return !empty($this->getOrder($order));
    }

    protected function isStorable()
    {
        return $this->storable;
    }

    protected function enqueueOperation()
    {
        try{
            if(!$this->isRetrying()){
                Mage::getModel('route/api_operation_fallback')
                    ->setEntityId($this->orderObj->getOrder()->getIncrementId())
                    ->setType(self::API_ENDPOINT)
                    ->setOperation($this->getOperation())
                    ->setStatus(Route_Route_Model_Api_Operation_Fallback::STATUS_PENDING)
                    ->save();
            }
        }catch (Exception $e){
            $this->log('Error while trying to store operation, please check exception log.');
            Mage::logException($e);
            $logger = Mage::getSingleton('route/log_sentry');
            $logger->send($e);
        }
    }
}
