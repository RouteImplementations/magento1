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
 * User creation class
 *
 * @category  Model
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Route_Route_Model_Api_Widget extends Route_Route_Model_Api_RouteClient
{

    const API_ENDPOINT = 'asset-settings';

    /**
     * Get a thank you page widget
     *
     * @param $host
     *
     * @return mixed
     */
    public function getThankYouPageWidget($host)
    {
        $this->storable = false;
        $this->setAvoidException();
        $this->setUnauthorizedRequest();

        try {
            $response = $this
                ->setUnauthorizedRequest()
                ->getMethod(
                    $this->getRouteApiUrl(self::API_ENDPOINT . '/' . $host)
                )->execute([self::SUCCESS_HTTP_CODE]);
        }catch (Exception $e){
            Mage::log("Error while trying to get thank you page asset: " . $e->getMessage());
            Mage::logException($e);
            $logger = Mage::getSingleton('route/log_sentry');
            $logger->send($e);
            return false;
        }

        if ($response)
        {
            $response = isset($response['asset_settings']['asset_live']) && $response['asset_settings']['asset_live'] ?
                $response :
                false;
        }

        return $response;
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
