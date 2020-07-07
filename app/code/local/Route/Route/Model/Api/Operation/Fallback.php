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

/**
 * Fallback class to execute Queued operation
 *
 * Php version 7.0^
 *
 * @category  Model
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Route_Route_Model_Api_Operation_Fallback extends Mage_Core_Model_Abstract
{
    const STATUS_PENDING = 0;
    const STATUS_DONE = 1;
    const STATUS_EXCEED_LIMIT = 2;
    const RETRY_LIMIT = 3;

    protected $entities = [
        'orders' => 'sales/order',
        'shipments' => 'sales/order_shipment'
    ];

    protected $operation = [
        'orders' => 'route/api_order',
        'shipments' => 'route/api_shipment'
    ];

    protected function _construct()
    {
        $this->_init('route/api_operation_fallback');
    }

    public function exceedRetryLimit()
    {
        return $this->getRetry() >= self::RETRY_LIMIT;
    }

    public function isPending()
    {
        return $this->getStatus() == self::STATUS_PENDING;
    }

    public function save()
    {
        $id = $this->getId();
        if (empty($id)) {
            $this->setCreatedAt(now());
            $this->setRetry(0);
        }
        $this->setUpdatedAt(now());
        parent::save();
    }

    public function retry()
    {
        if ($this->exceedRetryLimit()) {
            $this->setStatus(self::STATUS_EXCEED_LIMIT);
        } else {

            $operationObj = $this->getOperationObject();
            $operationObj->setRetrying();

            if(method_exists($operationObj, $this->getOperation())){
                $response = call_user_func_array(
                    array(
                        $operationObj, $this->getOperation()
                    ), $this->getParams()
                );

                if (!empty($response)) {
                    $this->setStatus(self::STATUS_DONE);
                }
            }

            $this->setRetry($this->getRetry() + 1);
        }

        $this->save();
    }

    public function getEntity()
    {
        $model = Mage::getModel($this->entities[$this->getType()]);

        switch ($this->getType()){
            case Route_Route_Model_Api_Order::API_ENDPOINT:
                return $model->loadByIncrementId($this->getEntityId());
            case Route_Route_Model_Api_Shipment::API_ENDPOINT:
                return $model->load($this->getEntityId());
            default:
                return false;
        }
    }

    public function getOperationObject(){
        return Mage::getModel($this->operation[$this->getType()]);
    }

    public function getParams(){
        switch ($this->getType()){
            case Route_Route_Model_Api_Order::API_ENDPOINT:
                return [$this->getEntity(), !!$this->getEntity()->getRouteFee() > 0];
            case Route_Route_Model_Api_Shipment::API_ENDPOINT:
                return [$this->getEntity()];
            default:
                return false;
        }

    }
}