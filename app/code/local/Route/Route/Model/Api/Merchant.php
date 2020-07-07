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
 * Merchant creation class
 *
 * @category  Model
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Route_Route_Model_Api_Merchant extends Route_Route_Model_Api_RouteClient
{

    const API_ENDPOINT = 'merchants';

    private $merchant;
    /**
     * Create merchant account
     *
     * @param $data
     * @param $merchantSecretKey
     *
     * @return mixed
     */
    public function createMerchant($data, $merchantSecretKey){
        try{
            Mage::log("Trying to create Merchant Account using the following params " . print_r($data,1));
            $response = $this
                ->setUnauthorizedRequest()
                ->addHeader('token', 'token: '. $merchantSecretKey)
                ->postMethod(
                    $this->getRouteApiUrl(self::API_ENDPOINT),
                    Mage::helper('core')->jsonEncode($data)
                )
                ->execute([parent::CREATED_HTTP_CODE, parent::SUCCESS_HTTP_CODE]);

            if($this->hasConflicted()){
                Mage::log("Merchant already exists.");
            } else if($this->hasFailed()) {
                Mage::log("Merchant account creation has failed" . print_r($this->getInfo(),1));
            } else {
                Mage::log("Successfully Merchant account creation.");
            }

            return $response;
        }catch (Exception $e){
            Mage::log("Merchant creation has failed reason: " . $e->getMessage());
            Mage::logException($e);
            return false;
        }
    }


    /**
     * Update merchant account
     *
     * @param $data
     * @param $merchantId
     *
     * @return mixed
     */
    public function updateMerchant($data, $merchantId){
        try{
            Mage::log("Trying to update Merchant Account using the following params " . print_r($data,1));

            $response = $this
                ->postMethod(
                    $this->getRouteApiUrl(self::API_ENDPOINT . '/' . $merchantId),
                    Mage::helper('core')->jsonEncode(array_merge($data, ['create_tokens' => true]))
                )
                ->execute([parent::CREATED_HTTP_CODE, parent::SUCCESS_HTTP_CODE]);

            if ($this->hasFailed()) {
                Mage::log("Merchant account update has failed" . print_r($this->getInfo(),1));
            } else {
                Mage::log("Successfully Merchant account update.");
            }

            return $response;
        }catch (Exception $e){
            Mage::log("Merchant update has failed reason: " . $e->getMessage());
            Mage::logException($e);
            $logger = Mage::getSingleton('route/log_sentry');
            $logger->send($e);
            return false;
        }
    }

    /**
     * Get merchant data
     *
     * @return mixed
     */
    public function getMerchant(){
        if(!is_null($this->merchant)){
            return $this->merchant;
        }
        try{
            $response = $this
                ->getMethod(
                    $this->getRouteApiUrl(self::API_ENDPOINT )
                )
                ->execute(self::SUCCESS_HTTP_CODE);

            foreach ($response as $merchant) {
                $this->merchant = $merchant;
                return $merchant;
            }
            return false;
        }catch (Exception $e){
            Mage::logException($e);
            return false;
        }
    }

    /**
     * Check if merchant is Route Plus
     *
     * @return bool
     */
    public function isRoutePlus(){
        $response = $this->getMerchant();
        return  isset($response['has_route_plus']) && $response['has_route_plus'];
    }


    /**
     * Check if merchant is Full Coverage
     *
     * @return bool
     */
    public function isFullCoverage(){
        $response = $this->getMerchant();
        return $this->isRoutePlus() && isset($response['merchant_preferences']['merchant_supplied_insurance']) && $response['merchant_preferences']['merchant_supplied_insurance'];
    }

    /**
     * Check if it's is opt-in
     *
     * @return bool
     */
    public function isOptIn(){
        $response = $this->getMerchant();
        return isset($response['merchant_preferences']['opt_in']) && $response['merchant_preferences']['opt_in'];
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
