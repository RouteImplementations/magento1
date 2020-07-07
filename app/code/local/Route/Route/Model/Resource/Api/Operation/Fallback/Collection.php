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
 
class Route_Route_Model_Resource_Api_Operation_Fallback_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{

    protected function _construct()
    {
        $this->_init('route/api_operation_fallback');
    }

    public function getPendingOperations(){
        return
            $this->addFieldToFilter('retry', ['lteq' => Route_Route_Model_Api_Operation_Fallback::RETRY_LIMIT])
            ->addFieldToFilter('status', ['eq' => Route_Route_Model_Api_Operation_Fallback::STATUS_PENDING]);
    }

    public function getCompletedOperations(){
        return
            $this->addFieldToFilter('status', ['in' => [
                Route_Route_Model_Api_Operation_Fallback::STATUS_DONE,
                Route_Route_Model_Api_Operation_Fallback::STATUS_EXCEED_LIMIT
            ]]);
    }
}
