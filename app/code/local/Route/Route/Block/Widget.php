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
 * Class to render Route Widget as Magento Widget
 *
 * @category  Block
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Route_Route_Block_Widget
    extends Mage_Core_Block_Abstract implements Mage_Widget_Block_Interface
{
    /**
     * Override this method in descendants to produce html
     *
     * @return string
     */
    protected function _toHtml() 
    {
        $html = '';
        if ($block = $this->getLayout()->createBlock('route/additional') ) {
            $html .= $block->setTemplate(
                'route/route.phtml'
            )->toHtml();
        }
        return $html;
    }
}