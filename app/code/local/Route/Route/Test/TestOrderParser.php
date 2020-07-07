<?php
use PHPUnit\Framework\TestCase;

class Route_Route_Test_TestOrderParser extends TestCase {

    /** @var OrderInterface  $order*/
    private $order;
    /** @var \Route\Route\Model\Sales\Order  $orderParser*/
    private $orderParser;

    public function setUp()
    {
        $this->orderParser = Mage::getSingleton('route/sales_order');
    }

    private function createCustomerLoggedInOrderMock()
    {
        $order = $this->createSimpleOrderMock();

        $order->expects($this->exactly(2))
            ->method('getCustomerFirstname')
            ->willReturn('Firstson');

        $order->expects($this->exactly(2))
            ->method('getCustomerLastname')
            ->willReturn('Lastson');

        //$order->expects($this->once())
        $order->expects($this->exactly(2))
            ->method('getShippingAddress')
            ->willReturn($this->getShippingAddress());

        $order->expects($this->once())
            ->method('getAllItems')
            ->willReturn($this->getItems());

        return $order;
    }

    private function createCustomerLoggedInOrderWithMultipleItemsMock()
    {
        $order = $this->createSimpleOrderMock();

        $order->expects($this->exactly(2))
            ->method('getCustomerFirstname')
            ->willReturn('Firstson');

        $order->expects($this->exactly(2))
            ->method('getCustomerLastname')
            ->willReturn('Lastson');

        //$order->expects($this->once())
        $order->expects($this->exactly(2))
            ->method('getShippingAddress')
            ->willReturn($this->getShippingAddress());

        $order->expects($this->once())
            ->method('getAllItems')
            ->willReturn($this->getMultipleItems());

        return $order;
    }

    private function createSimpleOrderMock()
    {
        /** Mock Order */
        //Add a magic method to the list of mocked class methods
        $methods = \array_merge(
            \get_class_methods('Mage_Sales_Model_Order'),
            ['getIncrementId'],
            ['getSubtotal'],
            ['getOrderCurrencyCode'],
            ['getTaxAmount'],
            ['getCustomerEmail'],
            ['getCustomerFirstname'],
            ['getCustomerLastname']
        );
        $order = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();

        $order->expects($this->once())
            ->method('getIncrementId')
            ->willReturn(100000012);

        $order->expects($this->once())
            ->method('getId')
            ->willReturn(12);

        $order->expects($this->once())
            ->method('getSubtotal')
            ->willReturn(123.00);

        $order->expects($this->once())
            ->method('getOrderCurrencyCode')
            ->willReturn('USD');

        $order->expects($this->once())
            ->method('getTaxAmount')
            ->willReturn(0);

        $order->expects($this->once())
            ->method('getCustomerEmail')
            ->willReturn('email@test.com');

        return $order;
    }

    private function getShippingAddress()
    {
        /** Mock Order Address */
        //Add a magic method to the list of mocked class methods
        $methods = \array_merge(
            \get_class_methods('Mage_Sales_Model_Order_Address'),
            ['getFirstname'],
            ['getLastname'],
            ['getCity'],
            ['getPostcode'],
            ['getCountryId']
        );
        $address = $this->getMockBuilder('Mage_Sales_Model_Order_Address')
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();
        $address->expects($this->once())
            ->method('getFirstname')
            ->willReturn('Firstson');
        $address->expects($this->once())
            ->method('getLastname')
            ->willReturn('Lastson');
        $address->expects($this->exactly(2))
            ->method('getStreet')
            ->willReturn('66 Doctor Doe St', 'Ap 111');
        $address->expects($this->once())
            ->method('getCity')
            ->willReturn('San Diego');
        $address->expects($this->once())
            ->method('getRegion')
            ->willReturn('California');
        $address->expects($this->once())
            ->method('getPostcode')
            ->willReturn(92122);
        $address->expects($this->once())
            ->method('getCountryId')
            ->willReturn(1);
        return $address;
    }

    private function getMultipleItems()
    {
        $item = $this->getItems();

        /** Mock Order Item */
        //Add a magic method to the list of mocked class methods
        $methods = \array_merge(
            \get_class_methods('Mage_Sales_Model_Order_Item'),
            ['getPrice'],
            ['getQtyOrdered'],
            ['getName']
        );
        $item2 = $this->getMockBuilder('Mage_Sales_Model_Order_Item')
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();
        $item2->expects($this->exactly(2))
            ->method('getProduct')
            ->willReturn($this->getProduct2());
        $item2->expects($this->once())
            ->method('getPrice')
            ->willReturn(15.0);
        $item2->expects($this->once())
            ->method('getQtyOrdered')
            ->willReturn(2);
        $item2->expects($this->once())
            ->method('getName')
            ->willReturn('Test Product 2');

        return array_merge($item, [$item2]);
    }

    private function getItems()
    {
        /** Mock Order Item */
        //Add a magic method to the list of mocked class methods
        $methods = \array_merge(
            \get_class_methods('Mage_Sales_Model_Order_Item'),
            ['getPrice'],
            ['getQtyOrdered'],
            ['getName']
        );
        $item = $this->getMockBuilder('Mage_Sales_Model_Order_Item')
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();
        $item->expects($this->exactly(2))
            ->method('getProduct')
            ->willReturn($this->getProduct());
        $item->expects($this->once())
            ->method('getPrice')
            ->willReturn(10.00);
        $item->expects($this->once())
            ->method('getQtyOrdered')
            ->willReturn(3);
        $item->expects($this->once())
            ->method('getName')
            ->willReturn('Test Product');

        return [$item];
    }

    private function getProduct()
    {
        $product = $this->getMockBuilder('Mage_Catalog_Model_Product')
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->once())
            ->method('getId')
            ->willReturn(123);
        $product->expects($this->once())
            ->method('getSku')
            ->willReturn('we-123');
        $product->expects($this->once())
            ->method('getImageUrl')
            ->willReturn('http://localhost/image/product.jpg');

        return $product;
    }

    private function getProduct2()
    {
        $product = $this->getMockBuilder('Mage_Catalog_Model_Product')
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->once())
            ->method('getId')
            ->willReturn(124);
        $product->expects($this->once())
            ->method('getSku')
            ->willReturn('we-1234');
        $product->expects($this->once())
            ->method('getImageUrl')
            ->willReturn('http://localhost/image/product.jpg');

        return $product;
    }

    private function createGuestUserOrderMock()
    {
        $order = $this->createSimpleOrderMock();

        /** Mock Order Address */
        //Add a magic method to the list of mocked class methods
        $methods = \array_merge(
            \get_class_methods('Mage_Sales_Model_Order_Address'),
            ['getFirstname'],
            ['getLastname']
        );
        $orderAddressMock = $this->getMockBuilder('Mage_Sales_Model_Order_Address')
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();

        $order->expects($this->once())
            ->method('getCustomerFirstname')
            ->willReturn(null);

        $order->expects($this->once())
            ->method('getCustomerLastname')
            ->willReturn(null);

        $orderAddressMock->expects($this->once())
            ->method('getFirstname')
            ->willReturn('Guest');

        $orderAddressMock->expects($this->once())
            ->method('getLastname')
            ->willReturn('User');

        $order->expects($this->exactly(2))
            ->method('getBillingAddress')
            ->willReturn($orderAddressMock);

        $order->expects($this->once())
            ->method('getAllItems')
            ->willReturn($this->getItems());

        //$order->expects($this->once())
        $order->expects($this->exactly(2))
            ->method('getShippingAddress')
            ->willReturn($this->getGuestShippingAddress());

        return $order;
    }

    private function getGuestShippingAddress()
    {
        /** Mock Order Address */
        //Add a magic method to the list of mocked class methods
        $methods = \array_merge(
            \get_class_methods('Mage_Sales_Model_Order_Address'),
            ['getFirstname'],
            ['getLastname'],
            ['getCity'],
            ['getPostcode'],
            ['getCountryId']
        );
        $address = $this->getMockBuilder('Mage_Sales_Model_Order_Address')
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();
        $address->expects($this->once())
            ->method('getFirstname')
            ->willReturn('Guest');
        $address->expects($this->once())
            ->method('getLastname')
            ->willReturn('User');
        $address->expects($this->exactly(2))
            ->method('getStreet')
            ->willReturn('66 Doctor Doe St', 'Ap 111');
        $address->expects($this->once())
            ->method('getCity')
            ->willReturn('San Diego');
        $address->expects($this->once())
            ->method('getRegion')
            ->willReturn('California');
        $address->expects($this->once())
            ->method('getPostcode')
            ->willReturn(92122);
        $address->expects($this->once())
            ->method('getCountryId')
            ->willReturn(1);
        return $address;
    }

    public function testGetRouteObjectWithOrderInsured()
    {
        $expected = [
            'source_order_id' => 100000012,
            'source_order_number' => 12,
            'source_created_on' => '1970-01-01T00:00:00+00:00',
            'source_updated_on' => '1970-01-01T00:00:00+00:00',
            'subtotal' => 123.0,
            'currency' => 'USD',
            'taxes' => 0.0,
            'insurance_selected' => true,
            'customer_details' => [
                'first_name' => 'Firstson',
                'last_name' => 'Lastson',
                'email' => 'email@test.com'
            ],
            'shipping_details' => [
                'first_name' => 'Firstson',
                'last_name' => 'Lastson',
                'street_address1' => '66 Doctor Doe St',
                'street_address2' => 'Ap 111',
                'city' => 'San Diego',
                'province' => 'California',
                'zip' => 92122,
                'country_code' => 1
            ],
            'line_items' => [
                [
                    'source_product_id' => 123,
                    'sku' => 'we-123',
                    'name' => 'Test Product',
                    'price' => 10.0,
                    'quantity' => 3,
                    'upc' => '',
                    'image_url' => 'http://localhost/image/product.jpg',
                ]

            ]

        ];

        $order = $this->createCustomerLoggedInOrderMock();

        $this->orderParser->setOrder($order);

        $this->assertEquals(json_encode($expected), $this->orderParser->getRouteObject(1));
    }

    public function testGetRouteObjectWithOrderNotInsured()
    {
        $expected = [
            'source_order_id' => 100000012,
            'source_order_number' => 12,
            'source_created_on' => '1970-01-01T00:00:00+00:00',
            'source_updated_on' => '1970-01-01T00:00:00+00:00',
            'subtotal' => 123.0,
            'currency' => 'USD',
            'taxes' => 0.0,
            'insurance_selected' => false,
            'customer_details' => [
                'first_name' => 'Firstson',
                'last_name' => 'Lastson',
                'email' => 'email@test.com'
            ],
            'shipping_details' => [
                'first_name' => 'Firstson',
                'last_name' => 'Lastson',
                'street_address1' => '66 Doctor Doe St',
                'street_address2' => 'Ap 111',
                'city' => 'San Diego',
                'province' => 'California',
                'zip' => 92122,
                'country_code' => 1
            ],
            'line_items' => [
                [
                    'source_product_id' => 123,
                    'sku' => 'we-123',
                    'name' => 'Test Product',
                    'price' => 10.0,
                    'quantity' => 3,
                    'upc' => '',
                    'image_url' => 'http://localhost/image/product.jpg',
                ]

            ]

        ];

        $order = $this->createCustomerLoggedInOrderMock();

        $this->orderParser->setOrder($order);

        $this->assertEquals(json_encode($expected), $this->orderParser->getRouteObject(0));
    }

    public function testGetRouteObjectWithGuestUser()
    {
        $expected = [
            'source_order_id' => 100000012,
            'source_order_number' => 12,
            'source_created_on' => '1970-01-01T00:00:00+00:00',
            'source_updated_on' => '1970-01-01T00:00:00+00:00',
            'subtotal' => 123.0,
            'currency' => 'USD',
            'taxes' => 0.0,
            'insurance_selected' => false,
            'customer_details' => [
                'first_name' => 'Guest',
                'last_name' => 'User',
                'email' => 'email@test.com'
            ],
            'shipping_details' => [
                'first_name' => 'Guest',
                'last_name' => 'User',
                'street_address1' => '66 Doctor Doe St',
                'street_address2' => 'Ap 111',
                'city' => 'San Diego',
                'province' => 'California',
                'zip' => 92122,
                'country_code' => 1
            ],
            'line_items' => [
                [
                    'source_product_id' => 123,
                    'sku' => 'we-123',
                    'name' => 'Test Product',
                    'price' => 10.0,
                    'quantity' => 3,
                    'upc' => '',
                    'image_url' => 'http://localhost/image/product.jpg',
                ]

            ]

        ];

        $order = $this->createGuestUserOrderMock();

        $this->orderParser->setOrder($order);

        $this->assertEquals(json_encode($expected), $this->orderParser->getRouteObject(0));
    }

    public function testGetRouteObjectWithMultipleItems()
    {
        $expected = [
            'source_order_id' => 100000012,
            'source_order_number' => 12,
            'source_created_on' => '1970-01-01T00:00:00+00:00',
            'source_updated_on' => '1970-01-01T00:00:00+00:00',
            'subtotal' => 123.0,
            'currency' => 'USD',
            'taxes' => 0.0,
            'insurance_selected' => false,
            'customer_details' => [
                'first_name' => 'Firstson',
                'last_name' => 'Lastson',
                'email' => 'email@test.com'
            ],
            'shipping_details' => [
                'first_name' => 'Firstson',
                'last_name' => 'Lastson',
                'street_address1' => '66 Doctor Doe St',
                'street_address2' => 'Ap 111',
                'city' => 'San Diego',
                'province' => 'California',
                'zip' => 92122,
                'country_code' => 1
            ],
            'line_items' => [
                [
                    'source_product_id' => 123,
                    'sku' => 'we-123',
                    'name' => 'Test Product',
                    'price' => 10.0,
                    'quantity' => 3,
                    'upc' => '',
                    'image_url' => 'http://localhost/image/product.jpg',
                ],
                [
                    'source_product_id' => 124,
                    'sku' => 'we-1234',
                    'name' => 'Test Product 2',
                    'price' => 15.0,
                    'quantity' => 2,
                    'upc' => '',
                    'image_url' => 'http://localhost/image/product.jpg',
                ]

            ]

        ];

        $order = $this->createCustomerLoggedInOrderWithMultipleItemsMock();

        $this->orderParser->setOrder($order);

        $this->assertEquals(json_encode($expected), $this->orderParser->getRouteObject(0));
    }
}