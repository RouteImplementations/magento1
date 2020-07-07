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
 * Get billing information from merchant account
 *
 * @category  Model
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Route_Route_Model_Api_Billing extends Route_Route_Model_Api_RouteClient
{

    const API_ENDPOINT = 'billing';

    /**
     * Get Merchant's billing information from Route API
     *
     * @return bool|mixed
     *
     * @throws Exception
     */
    public function getBilling()
    {
        return $this->getMethod(
            $this->getRouteApiUrl(self::API_ENDPOINT)
        )->execute();

    }

    protected function isStorable()
    {
        return false;
    }

    protected function enqueueOperation()
    {
        return false;
    }
}