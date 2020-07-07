<?php
use PHPUnit\Framework\TestCase;


class Route_Route_Test_TestClient extends TestCase{

    private function hash(){
        return substr( hash('sha256', rand()),0,10);
    }

    private function generateOrder(){

        $store = Mage::app()->getStore();
        $website = Mage::app()->getWebsite();

        $store = Mage::app()->getStore();
        $website = Mage::app()->getWebsite();

// initialize sales quote object
        $quote = Mage::getModel('sales/quote')->setStoreId(1);

// set customer information
        $customer_email = "donald.langer@email.com";
        $customer_firstname = "Donald";
        $customer_lastname = "Langer";

        $billingAddress = array(
            'customer_address_id' => '',
            'prefix' => '',
            'firstname' => $customer_firstname,
            'middlename' => '',
            'lastname' => $customer_lastname,
            'suffix' => '',
            'company' => '',
            'street' => array(
                '0' => 'Thunder River Boulevard', // required
                '1' => 'Customer Address 2' // optional
            ),
            'city' => 'Teramuggus',
            'country_id' => 'US', // country code
            'region' => 'Alaska',
            'region_id' => '2',
            'postcode' => '99767',
            'telephone' => '123-456-7890',
            'fax' => '',
            'save_in_address_book' => 1
        );

        $shippingAddress = array(
            'customer_address_id' => '',
            'prefix' => '',
            'firstname' => $customer_firstname,
            'middlename' => '',
            'lastname' => $customer_lastname,
            'suffix' => '',
            'company' => '',
            'street' => array(
                '0' => 'Thunder River Boulevard', // required
                '1' => 'Customer Address 2' // optional
            ),
            'city' => 'Teramuggus',
            'country_id' => 'US',
            'region' => 'Alaska',
            'region_id' => '2',
            'postcode' => '99767',
            'telephone' => '123-456-7890',
            'fax' => '',
            'save_in_address_book' => 1
        );

// check whether the customer already registered or not
        $customer = Mage::getModel('customer/customer')->setWebsiteId($website->getId())->loadByEmail($customer_email);

        if (!$customer->getId()) {

            // create the new customer account if not registered
            $customer = Mage::getModel('customer/customer');
            $customer->setWebsiteId($website->getId())
                ->setStore($store)
                ->setFirstname($customer_firstname)
                ->setLastname($customer_lastname)
                ->setEmail($customer_email);

            try {
                $password = $customer->generatePassword();
                $customer->setPassword($password);

                // set the customer as confirmed
                $customer->setForceConfirmed(true);
                $customer->save();

                $customer->setConfirmation(null);
                $customer->save();

                // set customer address
                $customerId = $customer->getId();
                $customAddress = Mage::getModel('customer/address');
                $customAddress->setData($billingAddress)
                    ->setCustomerId($customerId)
                    ->setIsDefaultBilling('1')
                    ->setIsDefaultShipping('1')
                    ->setSaveInAddressBook('1');

                // save customer address
                $customAddress->save();

                // send new account email to customer
                $storeId = $customer->getSendemailStoreId();
                $customer->sendNewAccountEmail('registered', '', $storeId);

                // set password remainder email if the password is auto generated by magento
                $customer->sendPasswordReminderEmail();

            } catch (Exception $e) {
                Mage::logException($e);
            }
        }

// assign the customer to quote
        $quote->assignCustomer($customer);

// set currency for the quote
        $quote->setCurrency(Mage::app()->getStore()->getBaseCurrencyCode());

        $productIds = array(337 => 2, 338 => 3);

// add products to quote
        foreach($productIds as $productId => $qty) {
            $product = Mage::getModel('catalog/product')->load($productId);
            $quote->addProduct($product, $qty);
        }

// add billing address to quote
        $billingAddressData = $quote->getBillingAddress()->addData($billingAddress);

// add shipping address to quote
        $shippingAddressData = $quote->getShippingAddress()->addData($shippingAddress);

// collect shipping rates on quote
        $shippingAddressData->setCollectShippingRates(true)
            ->collectShippingRates();

// set shipping method and payment method on the quote
        $shippingAddressData->setShippingMethod('flatrate_flatrate')
            ->setPaymentMethod('checkmo');

        Mage::getSingleton('checkout/session')->setQuote($quote);

        $this->getCart()->setQuote($quote);

        $quote->setTotalsCollectedFlag(false);
// Set payment method for the quote
        $quote->getPayment()->importData(array('method' => 'cashondelivery'));

        try {
            // collect totals & save quote
            $quote->collectTotals()->save();

            // create order from quote
            $service = Mage::getModel('sales/service_quote', $quote);
            $service->submitAll();

            return $service->getOrder()->getRealOrderId();
        } catch (Exception $e) {
            Mage::logException($e);
        }

    }

    private function getCart(){
        return Mage::getSingleton('checkout/cart');
    }

    private function getSession(){
        return Mage::getSingleton('checkout/session');
    }

    public function testQuoteWithInsurance(){
        $cart = $this->getCart();
        $cart->init();
        $cart->save();
        $this->getSession()->setInsured(1);
        $incrementId = $this->generateOrder();
        $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
        $this->assertTrue(((float) $order->getFeeAmount()) > 0);
    }

    public function testGetBillingStatus(){
        $billing = Mage::getModel('route/api_billing')->getBilling();
        $this->assertEquals($billing, []);
    }

    public function testGetRouteObject(){
        $incrementId = '302000010';
        $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
        $routeObject = Mage::getModel('route/sales_order')->setOrder($order)->getRouteObject(true);
        $routeParsedObject = json_decode($routeObject, true);
        $this->assertEquals($routeParsedObject['source_order_id'],$incrementId);
        $this->assertTrue(strlen($routeParsedObject['currency']) == 3);
    }

    public function testSendOrder(){
        $incrementId = '302000012';
        $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
        $order->setIncrementId( rand(303000010,304000010));

        $completeOrder = Mage::getModel('route/api_order')->postOrder(
            $order,
            (
                Mage::helper('route')->isRoutePlus() &&
                !!$order->getFeeAmount()
            )
        );
        $this->assertTrue(isset($completeOrder['order_number']));
    }


    public function testUpdateOrder(){
        $incrementId = '302000012';
        $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);

        $order->setIncrementId( rand(303000010,304000010));

        $cancelOrder = Mage::getModel('route/api_order')->postOrderCancel($order);

        $this->assertTrue(isset($cancelOrder['order_number']));
    }

    public function testFallbackCollection(){
        $this->assertEmpty(Mage::getModel('route/api_operation_fallback')->getCollection()->count());
    }

    public function testFallbackRetry(){
        Mage::getModel('route/observer')->retryFallbackOperations();
    }

    public function testGetQuote(){
        $quote = Mage::getModel('sales/quote');
        $quote->setQuoteCurrencyCode("USD");
        $quote->setSubtotal(123);
        $quoteResponse = Mage::getModel('route/api_quote')->getQuote($quote , 1);
        $this->assertEquals(1.23, $quoteResponse);
    }

    public function testGetQuoteWithDifferentCurrency(){
        $quote = Mage::getModel('sales/quote');
        $quote->setQuoteCurrencyCode("BRL");
        $quote->setSubtotal(600);
        $quoteResponse = Mage::getModel('route/api_quote')->getQuoteResponse($quote , 1);
        $this->assertEquals(6.0, $quoteResponse['insurance_price']);
    }

    public function testSentryClientException(){
        try{
            Mage::throwException('Unsuccessful connection with Route API. Returned code: ' . 500);
        }catch (Exception $e){
            $this->assertTrue($e instanceof  Exception);
            $logger = Mage::getSingleton('route/log_sentry');
            $logger->send($e);
        }
    }

    public function testGetDealSizeAsString(){
        $this->assertEquals(gettype(Mage::helper('route/setup')->getDealSize()), "string");
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


    private function createObserverObj()
    {
        $observer = $this->createMock('Mage_Admin_Model_Observer');

        $observer->expects($this->once())
            ->method('getEvent')
            ->willReturn($this->createEventObj());

        return $observer;
    }


    private function createEventObj()
    {
        $event = $this->createMock('Varien_Event_Observer');

        $event->expects($this->once())
            ->method('getShipment')
            ->willReturn($this->createSimpleShipmentMock());

        return $event;
    }


    public function testCreateShipment(){

        $return = Mage::getModel('route/observer')->salesOrderShipmentSaveAfter($this->createObserverObj());
        $this->assertTrue($return instanceof Route_Route_Model_Observer);
    }

}
