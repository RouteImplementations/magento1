<?php
use PHPUnit\Framework\TestCase;


class Route_Route_Test_TestThankYouPage extends TestCase {

    private function _getRouteModelWidgetMock($returnValue)
    {
        $mock = $this->getMockForAbstractClass( //mock generator made for abstract classes
            'Route_Route_Model_Api_Widget',     // the fully qualified name of the abstract class to mock
            [],                                 // no parameters for the class constructor
            '',                                 // no new name for the class to mock
            true,                               // do not call the original constructor
            true,                               // call original clone
            true,                               // call autoload
            ['execute'] );                      // mock the execute method
        $mock->expects($this->once())->method('execute')
            ->will($this->returnValue($returnValue));
        return $mock;
    }

    public function testHasThankYouPageAsset()
    {

        $returnValue= <<<html
{"store_domain":"domain.test","platform_id":"magento1","asset_settings":{"asset_live":true,"asset_content":{"raw_html":"<div id='asset-container'><a href=https://route.onelink.me/gibK?pid=Merchants&af_web_dp=https://route.com&c=null&af_adset=static target='_blank' id='asset-track-button'><img id='asset-icon' src='https://route-cdn.s3.amazonaws.com/route-widget-shopify/images/thank-you-asset/Icon-Tracking.png' alt='tracking-icon' />Track Your Order with <span id='asset-uppercase-text'>&nbsp;Route</span></a></div>","css_url":"https://route-cdn.s3.amazonaws.com/route-widget-shopify/images/thank-you-asset/static-asset.css"}}}
html;
        $mock = $this->_getRouteModelWidgetMock($returnValue);
        $response = $mock->getThankYouPageWidget('domain.test');

        //check content of return
        $this->assertEquals($response['asset_settings']['asset_live'], true);
        $this->assertEquals($response['asset_settings']['asset_content']['css_url'], 'https://route-cdn.s3.amazonaws.com/route-widget-shopify/images/thank-you-asset/static-asset.css');
    }

    public function testCannotReadPropertyThankYouPageAsset()
    {
        $returnValue= <<<html
{"err":"Cannot read property 'asset_live' of null","message":"Cannot read property 'asset_live' of null"}
html;
        $mock = $this->_getRouteModelWidgetMock($returnValue);
        $response = $mock->getThankYouPageWidget('domain.test');

        //check content of return
        $this->assertEquals($response, false);
    }

    public function testDomainNotExistThankYouPageAsset()
    {
        $returnValue= <<<html
{"err":"NOT_FOUND","message":"Store domain not found"}
html;
        $mock = $this->_getRouteModelWidgetMock($returnValue);
        $response = $mock->getThankYouPageWidget('domain.test');

        //check content of return
        $this->assertEquals($response, false);
    }

    public function testDisableThankYouPageAsset()
    {
        $returnValue= <<<html
{"store_domain":"asdasd","platform_id":"api","asset_settings":{"asset_live":false,"asset_content":{}}}
html;
        $mock = $this->_getRouteModelWidgetMock($returnValue);
        $response = $mock->getThankYouPageWidget('domain.test');

        //check content of return
        $this->assertEquals($response, false);
    }


}
