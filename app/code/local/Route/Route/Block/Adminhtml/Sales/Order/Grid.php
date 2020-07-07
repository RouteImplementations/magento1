<?php
/**
 * A Route Magento Extension that adds secure shipping
 * insurance to your orders
 *
 * Php version 5.6^
 *
 * @category  
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */

/**
 * Add to extra columns to Magento admin
 *
 * Php version 5.6^
 *
 * @category
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Route_Route_Block_Adminhtml_Sales_Order_Grid
    extends Mage_Adminhtml_Block_Sales_Order_Grid {


    /**
     * Add new two columns to order grid
     *
     * @return Mage_Adminhtml_Block_Sales_Order_Grid|void
     */
    protected function _prepareColumns()
    {
        if (Mage::helper('route')->canChangeOrderGrid()) {

            $this->addColumnAfter('route_is_insured', array(
                'header'=> Mage::helper('sales')->__('Route Insurance'),
                'width' => '80px',
                'index' => 'route_is_insured',
                'type'  => 'options',
                'renderer' => 'route/adminhtml_widget_grid_column_renderer_insured',
                'options'   => array(
                    1 => 'Yes',
                    0 => 'No',
                ),
            ),'status');

            $this->addColumnAfter('fee_amount', array(
                'header'=> Mage::helper('sales')->__('Route Charge'),
                'width' => '80px',
                'filter'    => false,
                'sortable'  => false,
                'type'  => 'currency',
                'index' => 'fee_amount',
                'currency' => 'order_currency_code',
            ),'route_is_insured');
        }
        parent::_prepareColumns();
    }

}