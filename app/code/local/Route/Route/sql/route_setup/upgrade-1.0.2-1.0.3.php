<?php

/* @var $installer Mage_Core_Model_Resource_Setup */

$installer = $this;
$installer->startSetup();

$orderGridTable = $installer->getTable('sales/order_grid');

$connection = $installer->getConnection();

if ($connection->tableColumnExists($orderGridTable, 'fee_amount') === false)
    $connection->addColumn($orderGridTable, 'fee_amount', 'DECIMAL( 10, 2 ) NOT NULL');
if ($connection->tableColumnExists($orderGridTable, 'base_fee_amount') === false)
    $connection->addColumn($orderGridTable, 'base_fee_amount', 'DECIMAL( 10, 2 ) NOT NULL');


$installer->endSetup();