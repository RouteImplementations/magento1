<?php
use PHPUnit\Framework\TestCase;

class Route_Route_Test_TestShipmentParser extends TestCase {
    /** @var Mage_Sales_Model_Order_Shipment_Track $track*/
    private $track;

    /** @var Mage_Sales_Model_Order_Shipment $shipment*/
    private $shipment;

    /** @var Route_Route_Model_Sales_Order_Shipment $shipmentParser */
    private $shipmentParser;

    public function setUp()
    {
        $this->shipmentParser = Mage::getSingleton('route/sales_order_shipment');

        /** Mock Order Shipment Track*/
        //Add a magic method to the list of mocked class methods
        $methods = \array_merge(
            \get_class_methods('Mage_Sales_Model_Order_Shipment_Track'),
            ['getCarrierCode'],
            ['getTitle']
        );
        $this->track = $this->getMockBuilder('Mage_Sales_Model_Order_Shipment_Track')
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();

        $this->track->expects($this->once())
            ->method('getNumber')
            ->willReturn('TRACK_TEST_1234');
    }

    private function createOrderMock()
    {
        /** Mock Order */
        //Add a magic method to the list of mocked class methods
        $methods = \array_merge(
            \get_class_methods('Mage_Sales_Model_Order'),
            ['getIncrementId']
        );
        $order = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();
        $order->expects($this->once())->method('getIncrementId')->willReturn('100000007');
        return $order;
    }

    private function createSimpleShipmentMock()
    {
        $shipment = $this->createMock('Mage_Sales_Model_Order_Shipment');

        $shipment->expects($this->once())
            ->method('getOrder')
            ->willReturn($this->createOrderMock());

        $shipment->expects($this->once())
            ->method('getAllItems')
            ->willReturn($this->getItems());

        return $shipment;
    }

    private function createMultipleShipmentMock()
    {
        $shipment = $this->createMock('Mage_Sales_Model_Order_Shipment');

        $shipment->expects($this->once())
            ->method('getOrder')
            ->willReturn($this->createOrderMock());

        $shipment->expects($this->once())
            ->method('getAllItems')
            ->willReturn($this->getMultipleItems());

        return $shipment;

    }

    private function getMultipleItems()
    {
        /** Mock Order Shipment Item */
        //Add a magic method to the list of mocked class methods
        $methods = \array_merge(
            \get_class_methods('Mage_Sales_Model_Order_Shipment_Item'),
            ['getProductId'],
            ['getQty']
        );

        $item = $this->getMockBuilder('Mage_Sales_Model_Order_Shipment_Item')
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();
        $item->expects($this->once())->method('getProductId')->willReturn('10003');
        $item->expects($this->once())->method('getQty')->willReturn(2);
        return array_merge($this->getItems(), [$item]);
    }

    private function getItems()
    {
        /** Mock Order Shipment Item */
        //Add a magic method to the list of mocked class methods
        $methods = \array_merge(
            \get_class_methods('Mage_Sales_Model_Order_Shipment_Item'),
            ['getProductId'],
            ['getQty']
        );

        $item = $this->getMockBuilder('Mage_Sales_Model_Order_Shipment_Item')
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();
        $item->expects($this->once())->method('getProductId')->willReturn('10002');
        $item->expects($this->once())->method('getQty')->willReturn(1);
        return [$item];
    }


    public function testSimpleShipment()
    {
        $expected = [
            'source_order_id' => '100000007',
            'tracking_number' => 'TRACK_TEST_1234',
            'courier_id' => 'usps',
            'source_product_ids' => ["10002"]
        ];

        $shipment = $this->createSimpleShipmentMock();

        $this->track->expects($this->any())
            ->method('getCarrierCode')
            ->willReturn('usps');

        $this->shipmentParser->setShipment($shipment);

        $this->assertEquals(json_encode($expected), $this->shipmentParser->getRouteObject($this->track));
    }

    public function testMultipleItemsShipment()
    {
        $expected = [
            'source_order_id' => '100000007',
            'tracking_number' => 'TRACK_TEST_1234',
            'courier_id' => 'usps',
            'source_product_ids' => ["10002", "10003", "10003"]
        ];

        $shipment = $this->createMultipleShipmentMock();

        $this->track->expects($this->any())
            ->method('getCarrierCode')
            ->willReturn('usps');

        $this->shipmentParser->setShipment($shipment);

        $this->assertEquals(json_encode($expected), $this->shipmentParser->getRouteObject($this->track));
    }

    public function testCustomCarrierShipment()
    {
        $expected = [
            'source_order_id' => '100000007',
            'tracking_number' => 'TRACK_TEST_1234',
            'courier_id' => 'Title',
            'source_product_ids' => ["10002"]
        ];
        $shipment = $this->createSimpleShipmentMock();

        $this->track->expects($this->any())
            ->method('getCarrierCode')
            ->willReturn('custom');

        $this->track->expects($this->any())
            ->method('getTitle')
            ->willReturn('Title');

        $this->shipmentParser->setShipment($shipment);

        $this->assertEquals(json_encode($expected), $this->shipmentParser->getRouteObject($this->track));
    }
}
