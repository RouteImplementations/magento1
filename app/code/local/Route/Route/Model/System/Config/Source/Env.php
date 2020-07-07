<?php

class Route_Route_Model_System_Config_Source_Env
{
    const LIVE_ENV = 1;
    const STAGING_ENV = 0;

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => self::LIVE_ENV, 'label' => Mage::helper('adminhtml')->__('Live')),
            array('value' => self::STAGING_ENV, 'label' => Mage::helper('adminhtml')->__('Staging')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            self::LIVE_ENV => Mage::helper('adminhtml')->__('Live'),
            self::STAGING_ENV => Mage::helper('adminhtml')->__('Staging'),
        );
    }
}