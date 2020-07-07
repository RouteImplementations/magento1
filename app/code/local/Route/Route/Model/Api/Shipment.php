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
 * Shipment integration
 *
 * @category  Model
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Route_Route_Model_Api_Shipment extends Route_Route_Model_Api_RouteClient
{

    const API_ENDPOINT = 'shipments';

    private $_shipmentObj;

    private $storable = true;

    /**
     * Submit shipment creation to Route API
     *
     * @param Mage_Sales_Model_Order_Shipment $shipment shipment object
     *
     * @return bool|string
     *
     * @throws Exception
     */
    public function postShipment($shipment)
    {
        foreach ($shipment->getAllTracks() as $track) {
          $this->postTracking($track);
        }
    }

    /**
     * Submit shipment creation to Route API
     *
     * @param Mage_Sales_Model_Order_Shipment_Track $track track object
     *
     * @return bool|string
     *
     * @throws Exception
     */
    public function postTracking($track)
    {
        $this->setOperation('postTracking');

        $this->setStore($track->getStore());

        $shipmentRoute = Mage::getModel('route/sales_order_shipment');

        if (!$this->shipmentExists($track)) {

            $shipmentResponse = $this->postMethod(
                $this->getRouteApiUrl(self::API_ENDPOINT),
                $shipmentRoute->getRouteObject($track)
            )->execute([parent::CREATED_HTTP_CODE, parent::SUCCESS_HTTP_CODE]);

            $this->log(
                "Route: Shipment " .
                (
                isset($shipmentResponse['id']) ?
                    "submitted successfully for order id '" . $shipmentResponse['id'] .
                    "' and tracking number '" . $shipmentResponse['tracking_number'] . "'" :
                    "not submitted"
                )
            );

            $shipmentRouteId = isset($shipmentResponse['id']) ? $shipmentResponse['id'] : null;

            if($shipmentRouteId){
                $track
                    ->setRouteShipmentId($shipmentRouteId)
                    ->setAvoidResend(true)
                    ->save();
            }
        }

    }

    /**
     * Cancel Shipment at Route API
     *
     * @param Mage_Sales_Model_Order_Shipment_Track $track
     *
     * @return bool
     *
     * @throws Exception
     */
    public function cancelShipment($track){

        if ($this->shipmentExists($track)) {

            $this->setOperation('cancelShipment');

            $this->setStore($track->getStore());

            $this->postMethod(
                $this->getRouteApiUrl(self::API_ENDPOINT . '/' . $track->getNumber() . '/cancel')
            )->execute([parent::SUCCESS_HTTP_CODE, parent::SUCCESS_HTTP_CODE]);

            $this->log("Route: Shipment Cancel " . $track->getNumber() . ($this->hasFailed() ? " has failed" : " has succeed"));
        }

        return true;
    }

    public function shipmentExists($track){
        if($track->getRouteShipmentId()){
            return true;
        }

        $shipmentResponse = $this->getShipment($track->getNumber());

        $shipmentRouteId = isset($shipmentResponse['id']) ? $shipmentResponse['id'] : null;

        if($shipmentRouteId){
            $track
                ->setRouteShipmentId($shipmentRouteId)
                ->setAvoidResend(true)
                ->save();
        }

        return !empty($shipmentRouteId);
    }

    public function getShipment($trackNumber){

        $this->storable = false;

        $this->setAvoidException();

        return $this->getMethod(
            $this->getRouteApiUrl(
                self::API_ENDPOINT . '/' . $trackNumber
            )
        )->execute([self::SUCCESS_HTTP_CODE, self::NOT_FOUND_HTTP_CODE]);
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
                    ->setEntityId($this->_shipmentObj->getId())
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
