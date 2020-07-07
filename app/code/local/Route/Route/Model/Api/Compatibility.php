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
 * Compatiblity API integration
 *
 * @category  Model
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */

class Route_Route_Model_Api_Compatibility extends Route_Route_Model_Api_RouteClient
{
    const API_ENDPOINT = 'partner-integration/plugin-status';

    /**
     * Send compatibility report
     *
     * @param $data
     * @return mixed
     */
    public function send($data)
    {
        try{
            Mage::log("Trying to send Compatibility report using the following params " . print_r($data,1));
            $response = $this
                ->postMethod(
                    $this->getRouteApiUrl(self::API_ENDPOINT),
                    Mage::helper('core')->jsonEncode($data)
                )
                ->execute([parent::CREATED_HTTP_CODE, parent::SUCCESS_HTTP_CODE]);
            if ($this->hasFailed()) {
                Mage::log("Compatibility API has failed " . print_r($this->getInfo(),1));
            } else {
                Mage::log("Successfully sent compatibility report.");
                $routeAppSetupHelper = Mage::helper('route/setup');
                $routeAppSetupHelper->setSetupCheckWidget(Route_Route_Helper_Setup::SETUP_CHECK_WIDGET_API_CALL);
            }
            return $response;
        }catch (Exception $e){
            Mage::log("Compatibility API has failed reason: " . $e->getMessage());
            Mage::logException($e);
            $logger = Mage::getSingleton('route/log_sentry');
            $logger->send($e);
            return false;
        }
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
