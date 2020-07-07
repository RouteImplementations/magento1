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
 * Shipment parser
 *
 * @category  Model
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Route_Route_Model_Sales_Order_Shipment
{

    const CUSTOM = 'custom';
    private $_shipment;

    /**
     * Get current shipment object
     *
     * @return Mage_Sales_Model_Order_Shipment
     */
    public function getShipment()
    {
        return $this->_shipment;
    }

    /**
     * Set Magento shipment object
     *
     * @param Mage_Sales_Model_Order_Shipment $shipment current shipment object
     *
     * @return $this
     */
    public function setShipment($shipment)
    {
        $this->_shipment = $shipment;
        return $this;
    }

    /**
     * Parse Shipment Track to Route object
     *
     * @param Mage_Sales_Model_Order_Shipment_Track $track track object
     *
     * @return false|string
     */
    public function getRouteObject($track)
    {
        if ($track->getParentId()) {
            $this->setShipment(Mage::getSingleton('sales/order_shipment')->load($track->getParentId()));
        }
        $shipment  = $this->getShipment();
        $data = [
            'source_order_id' => $shipment->getOrder()->getIncrementId(),
            'tracking_number' => $track->getNumber(),
            'courier_id' => $this->getCarrierName($track),
            'source_product_ids' => []
        ];
        foreach ($shipment->getAllItems() as $item) {
            $data['source_product_ids'] = array_merge(
                $data['source_product_ids'],
                array_fill(0, $item->getQty(), $item->getProductId())
            );
        }
        return json_encode($data);
    }

    /**
     * @param $track
     * @return mixed
     */
    private function getCarrierName($track)
    {
        return $track->getCarrierCode() == self::CUSTOM ? $track->getTitle() : $track->getCarrierCode();
    }
}
