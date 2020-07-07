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
 * Sentry log integration
 *
 * @category  Model
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Route_Route_Model_Log_Sentry extends Route_Route_Model_Api_RouteClient
{
    const SENTRY_KEY= '05fdb60c616546499f8c30a239539521';
    const SENTRY_PROJECT_ID = '2048186';
    const SENTRY_LOG_CONTEXT_LINES = 10;
    private $_jsonParser;
    private $_helper;
    private $_event = false;
    private $_extraData = [];

    /**
     * Client constructor
     */
    public function __construct()
    {
        $this->_helper = Mage::helper('route');
        $this->_jsonParser = Mage::helper('core');
        parent::__construct();
    }

    /**
     *  Set Extra Data
     * @param $extraData
     */
    public function setExtraData($extraData)
    {
        $this->_extraData = $extraData;
    }

    /**
     * Send log event to Sentry
     *
     * @param $event
     * @param $extraData
     * 
     * @return bool|string
     * @throws Exception
     */
    public function send($event, $extraData = []) {
        $this->_event = $event;
        $this->_extraData = $extraData;
        $this->_setSentryAuth();
        $this->setAvoidException();
        $log = $this->postMethod(
            $this->_getSentryApiUrl(),
            $this->_jsonParser->jsonEncode($this->_prepareEvent())
        )->execute([self::CREATED_HTTP_CODE, self::SUCCESS_HTTP_CODE]);
        return $log;
    }

    /**
     * Prepare event in array
     *
     * @return array
     */
    private function _prepareEvent() {
        $eventArray = array();
        $eventArray['message'] = (string)$this->_event->getMessage();
        $eventArray['platform'] = 'php';
        switch(strtolower(get_class($this->_event))) {
            case 'error':
            case 'exception':
                $reportLevel = strtolower(get_class($this->_event));
                break;
            default:
                $reportLevel = 'debug';
                break;
        }
        $eventArray['type'] = (string)$this->_event->getMessage();
        $eventArray['tags'] = [
            ['module', 'Route Module'],
            ['module.version', (string) $this->_helper->getVersion()],
            ['url', Mage::getBaseUrl()],
            ['php.version', phpversion()],
            ['report.level', $reportLevel],
            ['http.server', $_SERVER['SERVER_SOFTWARE']],
            ['magento.version', Mage::getVersion()]
        ];

        $eventArray['exception'] = [
            'values' => [
                [
                    'type' => ucfirst($eventArray['type']),
                    'module' => 'Route Magento 1 Integration',
                    'value' => $eventArray['message'],
                    'stacktrace' => [
                        'frames' => $this->_prepareStackTrace()
                    ]
                ]
            ]
        ];

        $eventArray['extra']['url'] = Mage::getBaseUrl();

        if (!empty($this->_extraData)) {
            $eventArray['extra']['extraData'] = $this->_extraData;
        }

        return $eventArray;
    }

    /**
     * Receive error or exception traces, return formatted array for Sentry
     *
     * @return array
     */
    private function _prepareStackTrace() {
        $tracesArray = array();
        foreach (array_reverse($this->_event->getTrace()) as $trace) {
            if ($this->_isValidTrace($trace)) {
                $traceArray = array();
                $traceArray['filename'] = $trace['file'];
                $traceArray['abs_path'] = $trace['file'];
                $traceArray['lineno'] = (int)$trace['line'];
                $traceArray['function'] = $trace['function'];
                $traceArray['context_line'] = $trace['function'];
                $traceArray['pre_context'][0] = "";
                $traceArray['post_context'][0] = "";

                $sourceCodeExcerpt = $this->getSourceCodeExcerpt($traceArray['filename'],
                    $traceArray['lineno'],
                    self::SENTRY_LOG_CONTEXT_LINES);

                if (isset($sourceCodeExcerpt['context_line'])) {
                    $traceArray['context_line'] = $sourceCodeExcerpt['context_line'];
                }

                if (isset($sourceCodeExcerpt['pre_context'])) {
                    $traceArray['pre_context'] = $sourceCodeExcerpt['pre_context'];
                }
                if (isset($sourceCodeExcerpt['post_context'])) {
                    $traceArray['post_context'] = $sourceCodeExcerpt['post_context'];
                }
                $traceArray['in_app'] = false;
                $tracesArray[] = $traceArray;
            }
        }
        return $tracesArray;
    }

    /**
     * Check if is a valid trace
     * @param $trace
     * @return bool
     */
    private function _isValidTrace($trace) {
        if (isset($trace['file']) && isset($trace['line']) && isset($trace['function'])) {
            if (!empty($trace['file']) && !empty($trace['line']) && !empty($trace['function'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gets an excerpt of the source code around a given line.
     *
     * @param $path            The file path
     * @param $lineNumber      The line to centre about
     * @param $maxLinesToFetch The maximum number of lines to fetch
     */
    protected function getSourceCodeExcerpt($path,$lineNumber, $maxLinesToFetch)
    {
        if (@!is_readable($path) || !is_file($path)) {
            return [];
        }

        $frame = [
            'pre_context' => [],
            'context_line' => '',
            'post_context' => [],
        ];

        $target = max(0, ($lineNumber - ($maxLinesToFetch + 1)));
        $currentLineNumber = $target + 1;

        try {
            $file = new \SplFileObject($path);
            $file->seek($target);

            while (!$file->eof()) {
                /** @var string $line */
                $line = $file->current();
                $line = rtrim($line, "\r\n");

                if ($currentLineNumber == $lineNumber) {
                    $frame['context_line'] = $line;
                } elseif ($currentLineNumber < $lineNumber) {
                    $frame['pre_context'][] = $line;
                } elseif ($currentLineNumber > $lineNumber) {
                    $frame['post_context'][] = $line;
                }

                ++$currentLineNumber;

                if ($currentLineNumber > $lineNumber + $maxLinesToFetch) {
                    break;
                }

                $file->next();
            }
        } catch (\Exception $exception) {
            // Do nothing, if any error occurs while trying to get the excerpts
            // it's not a drama
        }

        return $frame;
    }


    /**
     * Get Sentry API endpoint
     *
     * @return string
     */
    private function _getSentryApiUrl() {
        return 'https://sentry.io/api/' . self::SENTRY_PROJECT_ID . '/store/';
    }

    /**
     * Set Sentry Auth if not exist yet
     */
    private function _setSentryAuth() {
        $headers = $this->getHeaders();
        if (!isset($headers['X-Sentry-Auth'])) {
            parent::addHeader('X-Sentry-Auth','X-Sentry-Auth: ' . $this->_getSentryAuth());
        }
    }


    /**
     * Get Sentry Auth
     *
     * @return string
     */
    private function _getSentryAuth() {
        $sentryAuth = 'Sentry sentry_version=7, sentry_key=';
        $sentryAuth.= self::SENTRY_KEY;
        $sentryAuth.= ', sentry_client=raven-bash/0.1';
        return $sentryAuth;
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
