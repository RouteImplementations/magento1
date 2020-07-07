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
 * Class responsible to add Route fee to total summary block
 *
 * @category  Block
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Route_Route_Block_Adminhtml_Sales_Order_Fee extends Mage_Core_Block_Template
{

    /**
     * Initialize fee totals
     *
     * @return Route_Route_Block_Adminhtml_Sales_Order_Fee
     */
    public function initTotals()
    {
        if ((float)$this->getOrder()->getBaseFeeAmount()) {
            $source = $this->getSource();
            $value = $source->getFeeAmount();
            $this->getParentBlock()->addTotal(
                new Varien_Object(
                    array('code' => 'fee',
                        'strong' => false,
                        'label' => Mage::helper('route')->getRouteLabel(),
                        'value' => $value
                    )
                )
            );
        }
        return $this;
    }

    /**
     * Get order store object
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return $this->getParentBlock()->getOrder();
    }

    /**
     * Get totals source object
     *
     * @return Mage_Sales_Model_Order
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }
}