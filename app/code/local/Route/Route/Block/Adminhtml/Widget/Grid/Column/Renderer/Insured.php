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
 * Change visualization from value to Yes or No
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
class Route_Route_Block_Adminhtml_Widget_Grid_Column_Renderer_Insured
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    /**
     * Replace value by Yes/No
     *
     * @param Varien_Object $row
     *
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $value = (int)$row->getData($this->getColumn()->getIndex());
        return $value > 0 ?
            Mage::helper('adminhtml')->__('Yes') :
            Mage::helper('adminhtml')->__('No');
    }
}