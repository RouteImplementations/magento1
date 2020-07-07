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
class Route_Route_Model_Api_User extends Route_Route_Model_Api_RouteClient
{

    const API_ENDPOINT = 'users';
    const API_ENDPOINT_ACTIVATE_ACCOUNT = 'activate_account';
    const API_ENDPOINT_LOGIN = 'login';

    /**
     * Create user if it's not configured yet
     *
     * @param $data
     *
     * @return mixed
     */
    public function createUser($data){
        try{

            Mage::log("Trying to create new User Account with params: " . print_r($data, 1));

            $response = $this->setUnauthorizedRequest()
                ->postMethod(
                    $this->getRouteApiUrl(self::API_ENDPOINT),
                    Mage::helper('core')->jsonEncode($data)
                )
                ->execute([parent::CREATED_HTTP_CODE, parent::SUCCESS_HTTP_CODE]);

            if($this->hasConflicted()){
                Mage::log("User already exists.");
            } else if($this->hasFailed()) {
                Mage::log("User account creation has failed" . print_r($this->getInfo(),1));
            } else {
                Mage::log("Successfully user account creation.");
            }
            return $response;

        }catch (Exception $e){
            Mage::log("User creation has failed reason: " . $e->getMessage());
            Mage::logException($e);
            return false;
        }
    }

    /**
     * Activate Account to User successful user creation
     *
     * @param $email
     *
     * @return mixed
     */
    public function activateAccount($email){
        try{

            Mage::log("Trying to create new User Account with params: " . print_r($email, 1));

            $response = $this->setUnauthorizedRequest()
                ->postMethod(
                    $this->getRouteApiUrl(self::API_ENDPOINT_ACTIVATE_ACCOUNT),
                    Mage::helper('core')->jsonEncode(['email' => $email])
                )
                ->execute([parent::CREATED_HTTP_CODE, parent::SUCCESS_HTTP_CODE]);

            if($this->hasFailed()) {
                Mage::log("User account activation has failed" . print_r($this->getInfo(),1));
            } else {
                Mage::log("Successfully user account activation.");
            }
            return $response;

        }catch (Exception $e){
            Mage::log("User account activation has failed reason: " . $e->getMessage());
            Mage::logException($e);
            return false;
        }
    }

    /**
     * User login
     *
     * @param $username
     * @param $password
     *
     * @return mixed
     */
    public function login($username, $password){
        try{

            Mage::log("Trying to login user account with params: " . print_r($username, 1));

            $response = $this->setUnauthorizedRequest()
                ->postMethod(
                    $this->getRouteApiUrl(self::API_ENDPOINT_LOGIN),
                    Mage::helper('core')->jsonEncode(
                        [
                            "username" => $username,
                            "password" => $password
                        ]
                    )
                )
                ->execute([parent::CREATED_HTTP_CODE, parent::SUCCESS_HTTP_CODE]);

            if($this->hasFailed()) {
                Mage::log("Login has failed" . print_r($this->getInfo(),1));
            } else {
                Mage::log("Successfully user login.");
            }

            return $response;

        }catch (Exception $e){
            Mage::log("User account activation has failed reason: " . $e->getMessage());
            Mage::logException($e);
            return false;
        }
    }

    public function isStorable()
    {
        return false;
    }

    public function enqueueOperation()
    {
        return false;
    }


}
