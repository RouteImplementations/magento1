<?php

/* @var $installer Mage_Core_Model_Resource_Setup */

$installer = $this;
$installer->startSetup();

$orderTable = $this->getTable('sales/order');
$orderGridTable = $this->getTable('sales/order_grid');
$quoteAddressTable = $this->getTable('sales/quote_address');

$connection = $installer->getConnection();

if ($connection->tableColumnExists($quoteAddressTable, 'route_is_insured') === false) $connection->addColumn($quoteAddressTable, 'route_is_insured', 'INT(1) NOT NULL DEFAULT 0');

if ($connection->tableColumnExists($orderGridTable, 'route_is_insured') === false) $connection->addColumn($orderGridTable, 'route_is_insured', 'INT(1) NOT NULL DEFAULT 0');

if ($connection->tableColumnExists($orderTable, 'route_is_insured') === false) $connection->addColumn($orderTable, 'route_is_insured', 'INT(1) NOT NULL DEFAULT 0');

$installer->endSetup();