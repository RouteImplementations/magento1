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
 * Generic Route API client
 *
 * @category  Model
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
abstract class Route_Route_Model_Api_RouteClient
{

    const LIVE_API_URL = 'https://api.route.com/v1/';

    const SANDBOX_API_URL = "https://api-stage.route.com/v1/";
    const SUCCESS_HTTP_CODE = 200;
    const CREATED_HTTP_CODE = 201;
    const CONFLICT_HTTP_CODE = 409;
    const NOT_FOUND_HTTP_CODE = 404;
    const DEFAULT_TIMEOUT = 10;
    const SENTRY_TIMEOUT = 5;
    const GET_METHOD = "GET";
    const POST_METHOD = "POST";

    private $_client;
    private $_jsonParser;
    private $_helper;
    private $_auth = true;
    private $_header = [];
    private $_failed = false;
    private $_lastInfo = false;
    private $_avoidException = false;
    private $_method;

    private $_data = [];
    private $_url = '';

    protected $operation;
    protected $isRetrying = false;

    /**
     * @return mixed
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * @param mixed $operation
     */
    public function setOperation($operation)
    {
        $this->operation = $operation;
    }

    /**
     * Client constructor
     */
    public function __construct()
    {
        $this->_helper = Mage::helper('route');
        $this->_jsonParser = Mage::helper('core');
    }

    /**
     * Change store if needed;
     *
     * @param $store
     * @return $this
     */
    public function setStore($store){
        if (isset($store)) {
            $this->_helper->setCurrentStore($store);
        }
        return $this;
    }

    /**
     * Initialize rest client to Route API defining basic headers
     *
     * @return void
     *
     * @throws Exception
     */
    public function initClient()
    {
        $this->_client = curl_init();

        if (!$this->_avoidException) {
            curl_setopt($this->_client, CURLOPT_TIMEOUT, self::DEFAULT_TIMEOUT);
        } else {
            curl_setopt($this->_client, CURLOPT_TIMEOUT, self::SENTRY_TIMEOUT);
        }

        $this->addHeader('Content-Type', 'Content-Type: application/json');

        if ($this->_auth) {
            $this->addHeader('token','token: ' . $this->_helper->getMerchantSecretToken());
        }

        curl_setopt($this->_client, CURLOPT_HTTPHEADER, $this->getHeaders());
    }

    /**
     * Set authentication as not required
     *
     * @return Route_Route_Model_Api_RouteClient
     */
    public function setUnauthorizedRequest()
    {
        $this->_auth = false;
        return $this;
    }

    /**
     * Add extra header
     *
     * @param $name
     * @param $header
     *
     * @return Route_Route_Model_Api_RouteClient
     */
    public function addHeader($name, $header)
    {
        $this->_header[$name] = $header;
        return $this;
    }

    /**
     * Get headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->_header;
    }

    /**
     * Get Route Api Url
     *
     * @param string $path path to be concatenated to the base url
     *
     * @return string
     */
    public function getRouteApiUrl($path)
    {
        return self::LIVE_API_URL . $path;
    }

    /**
     * Build query string based on array passed by param
     *
     * @param array $params params to be converted in query string
     *
     * @return string
     */
    public function buildQuery($params)
    {
        return http_build_query($params);
    }

    /**
     * Get current client if it exists or create a new one
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function getClient()
    {
        if (empty($this->_client)) {
            $this->initClient();
        }
        return $this->_client;
    }

    /**
     * Prepare GET http Method to be executed
     *
     * @param string $url  url to be reached
     * @param null   $data data to be passed as param
     *
     * @return Route_Route_Model_Api_RouteClient
     *
     * @throws Exception
     */
    protected function getMethod($url, $data = null)
    {
        $queryString = isset($data) ? '?' . $this->buildQuery($data) : '';
        return $this->_prepareHttpMethod($url . $queryString, self::GET_METHOD, $data);
    }

    /**
     * Prepare POST http Method to be executed
     *
     * @param string $url  url to be reached
     * @param array  $data data to be passed as param
     *
     * @return Route_Route_Model_Api_RouteClient
     *
     * @throws Exception
     */
    protected function postMethod($url, $data = [])
    {
        return $this->_prepareHttpMethod($url, self::POST_METHOD, $data);
    }

    /**
     * Prepare http method
     *
     * @param string $url    base url
     * @param string $method method to be executed
     * @param array  $data   data to be passed as param
     *
     * @return Route_Route_Model_Api_RouteClient
     *
     * @throws Exception
     */
    private function _prepareHttpMethod($url, $method, $data = [])
    {
        $this->_method = $method;
        $this->_data = $data;
        $this->_url = $url;

        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false
        );

        if ($method == self::POST_METHOD) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $data;
            $this->_data = json_decode($data);
        } elseif ($method == self::GET_METHOD) {
            $options[CURLOPT_HTTPGET] = true;
        }

        curl_setopt_array($this->getClient(), $options);

        return $this;
    }

    /**
     * Executed prepared method
     *
     * @param array $successCode code expected to success
     *
     * @return bool|string
     *
     * @throws Exception
     */
    protected function execute($successCode = [self::SUCCESS_HTTP_CODE])
    {
        try {
            $this->setFailed(false);
            $response = curl_exec($this->getClient());
            $info = $this->getInfo();

            if(!is_array($successCode))
                $successCode = [$successCode];

            if (in_array($info['http_code'], $successCode)) {
                return json_decode($response, true);
            }

            Mage::throwException('Unsuccessful connection with Route API. Returned code: ' . $info['http_code']);
        } catch (Exception $e) {
            if($this->isStorable()){
                $this->enqueueOperation();
            }
            Mage::logException($e);
            if (!$this->_avoidException) {
                Mage::getSingleton('route/log_sentry')->send($e,
                [
                    'params' => $this->getData(),
                    'method' => $this->getCurrentMethod(),
                    'endpoint' => $this->getUrl(),
                ]);
            }
        } finally {
            $this->_close();
        }
        $this->setFailed(true);
        return false;
    }

    /**
     * Get info from curl request
     *
     * @return mixed
     *
     * @throws Exception
     */
    protected function getInfo()
    {
        if (is_null($this->_client)) {
            return $this->_lastInfo;
        }
        return curl_getinfo($this->getClient());
    }

    /**
     * Close current curl connection
     *
     * @return mixed
     *
     * @throws Exception
     */
    private function _close()
    {
        $this->_lastInfo = $this->getInfo();
        curl_close($this->getClient());
        $this->_client = null;
    }

    public function hasConflicted() {
        $info = $this->getInfo();
        return $info['http_code'] == self::CONFLICT_HTTP_CODE;
    }

    public function isSuccess() {
        $info = $this->getInfo();
        return $info['http_code'] == self::SUCCESS_HTTP_CODE;
    }

    public function hasCreated() {
        $info = $this->getInfo();
        return $info['http_code'] == self::CREATED_HTTP_CODE;
    }

    public function hasFailed() {
        return $this->_failed;
    }

    public function setFailed($failed) {
        return $this->_failed = $failed;
    }

    abstract protected function isStorable();

    abstract protected function enqueueOperation();

    public function isRetrying()
    {
        return $this->isRetrying;
    }

    public function setRetrying()
    {
        $this->isRetrying = true;
    }

    /**
     * Log messages
     *
     * @param $msg
     */
    protected function log($msg)
    {
        Mage::log($msg);
    }

    public function setAvoidException()
    {
        $this->_avoidException = true;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->_url;
    }

    /**
     * @return string
     */
    public function getCurrentMethod()
    {
        return $this->_method;
    }

}
