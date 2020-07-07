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
 
class Route_Route_Model_Resource_Api_Operation_Fallback extends Mage_Core_Model_Resource_Db_Abstract
{

    protected function _construct()
    {
        $this->_init('route/route_operation_fallback', 'operation_id');
    }

}