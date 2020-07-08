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
 * Order parser
 *
 * @category  Model
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Route_Route_Model_Sales_Order
{

    const CANCELED = "canceled";

    private $_order;

    /**
     * Set Magento Order object
     *
     * @param Mage_Sales_Model_Order $order Order object
     *
     * @return Route_Route_Model_Sales_Order
     */
    public function setOrder($order)
    {
        $this->_order = $order;
        return $this;
    }

    /**
     * Get Magento Order object
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return $this->_order;
    }

    public function getSubtotalAmount($order)
    {
        return Mage::helper('route')->getShippableItemsSubtotal($order);
    }

    /**
     * Parse Magento order object to Route format
     *
     * @param bool $insuranceSelected if insurance is selected
     *
     * @return string
     */
    public function getRouteObject($insuranceSelected)
    {
        $address = $this->getOrder()->getShippingAddress() ?
            $this->getOrder()->getShippingAddress() :
            $this->getOrder()->getBillingAddress();

        $customerFirstName = $this->getOrder()->getCustomerFirstname() ?
            $this->getOrder()->getCustomerFirstname() : $this->getOrder()->getBillingAddress()->getFirstName();

        $customerLastName = $this->getOrder()->getCustomerLastname() ?
            $this->getOrder()->getCustomerLastname() : $this->getOrder()->getBillingAddress()->getLastname();

        $data = [
            "source_order_id" => $this->getOrder()->getIncrementId(),
            "source_order_number"  => $this->getOrder()->getId(),
            "source_created_on" => date(DATE_ATOM, strtotime($this->getOrder()->getCreatedAt())),
            "source_updated_on" => date(DATE_ATOM, strtotime($this->getOrder()->getUpdatedAt())),
            "subtotal" => floatval($this->getOrder()->getSubtotal()),
            "amount_covered" =>  floatval($this->getSubtotalAmount($this->getOrder())),
            "currency" => $this->getOrder()->getOrderCurrencyCode(),
            "taxes" => floatval($this->getOrder()->getTaxAmount()),
            "insurance_selected" => $this->isInsuranceSelected($insuranceSelected),
            "customer_details" => [
                "first_name" => $customerFirstName,
                "last_name" => $customerLastName,
                "email" => $this->getOrder()->getCustomerEmail()
            ],
            "shipping_details" => [
                "first_name" => $address->getFirstname(),
                "last_name" => $address->getLastname(),
                "street_address1" => $address->getStreet(),
                "street_address2" => $address->getStreet(),
                "city" => $address->getCity(),
                "province" => $address->getRegion(),
                "zip" => $address->getPostcode(),
                "country_code" => $address->getCountryId()
            ]
        ];

        $data = $this->_getLineItems($data);

        return json_encode($data);
    }

    /**
     * Parse items from track object
     *
     * @param array $data current parsed data
     *
     * @return mixed
     */
    private function _getLineItems($data)
    {
        foreach ($this->getOrder()->getAllItems() as $item) {
            $isProductVirtual = $item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL ? true : false;
            $product = $this->getProduct($item);
            $data["line_items"][] = [
                "delivery_method" => $isProductVirtual ? 'digital' :
                    ($this->isShippingMethodAllowed() ? 'ship_to_home' : 'ship_to_store'),
                "is_insured" => $isProductVirtual ? false :
                    ($this->isShippingMethodAllowed() ? true : false),
                "source_product_id" => $product->getId(),
                "sku" => $product->getSku(),
                "name" => $item->getName(),
                "price" => floatval($item->getPrice()),
                "quantity" => intval($item->getQtyOrdered()),
                "upc" => "",
                "image_url" => $this->_getProductImage($item)
            ];
        }
        return $data;
    }

    /**
     * Get product image url
     *
     * @param Mage_Sales_Model_Order_Item $item item to get image url
     *
     * @return mixed
     */
    private function _getProductImage($item)
    {
        try{
            return $this->getProduct($item)->getImageUrl();
        }catch (Exception $exception){
            return "";
        }
    }

    /**
     * Check if the order is recent, fresh order cannot be updated
     *
     * @return bool
     */
    private function _isNotFreshNewOrder()
    {
        $session = Mage::getSingleton('checkout/session');
        return (
            !isset($session) ||
            $session->getData('quote_id_1') != $this->getOrder()->getQuoteId()
        );
    }

    /**
     * Check if order is canceled it cannot be cancelled
     *
     * @return bool
     */
    private function _isNotCanceled()
    {
        return $this->getOrder()->getStatus() != self::CANCELED;
    }

    /**
     * Check conditions to submit order update
     *
     * @return bool
     */
    public function canSendUpdate()
    {
        return $this->_isNotFreshNewOrder() &&
            $this->_isNotCanceled() &&
            $this->getOrder()->hasDataChanges();
    }

    /**
     * @param $item
     * @return Mage_Core_Model_Abstract
     */
    private function getProduct($item)
    {
        $product = $item->getProduct();
        if (empty($product)) {
            $product = Mage::getModel('catalog/product')->load($item->getProductId());
        }
        return $product;
    }

    /**
     * @return mixed
     */
    private function isShippingMethodAllowed()
    {
        $address = $this->getOrder()->getShippingAddress() ?
            $this->getOrder()->getShippingAddress() :
            $this->getOrder()->getBillingAddress();
        return Mage::helper('route')->isShippingMethodAllowed($address->getShippingMethod());
    }

    /**
     * @param $insuranceSelected
     * @return bool
     */
    private function isInsuranceSelected($insuranceSelected)
    {
        return (Mage::helper('route')->isFullCoverage() || !$this->isShippingMethodAllowed()) ?
            false :
            !!$insuranceSelected;
    }
}
