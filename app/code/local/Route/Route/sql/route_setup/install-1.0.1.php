<?php

/* @var $installer Mage_Core_Model_Resource_Setup */

$installer = $this;
$installer->startSetup();

$orderTable = $this->getTable('sales/order');
$quoteTable = $this->getTable('sales/quote');
$invoiceTable = $this->getTable('sales/invoice');
$creditmemoTable = $this->getTable('sales/creditmemo');
$quoteAddressTable = $this->getTable('sales/quote_address');

$connection = $installer->getConnection();

if ($connection->tableColumnExists($quoteTable, 'fee_amount') === false) $connection->addColumn($quoteTable, 'fee_amount', 'DECIMAL( 10, 2 ) NOT NULL');
if ($connection->tableColumnExists($quoteTable, 'base_fee_amount') === false) $connection->addColumn($quoteTable, 'base_fee_amount', 'DECIMAL( 10, 2 ) NOT NULL');

if ($connection->tableColumnExists($orderTable, 'fee_amount') === false) $connection->addColumn($orderTable, 'fee_amount', 'DECIMAL( 10, 2 ) NOT NULL');
if ($connection->tableColumnExists($orderTable, 'base_fee_amount') === false) $connection->addColumn($orderTable, 'base_fee_amount', 'DECIMAL( 10, 2 ) NOT NULL');

if ($connection->tableColumnExists($orderTable, 'fee_amount_refunded') === false) $connection->addColumn($orderTable, 'fee_amount_refunded', 'DECIMAL( 10, 2 ) NOT NULL');
if ($connection->tableColumnExists($orderTable, 'base_fee_amount_refunded') === false) $connection->addColumn($orderTable, 'base_fee_amount_refunded', 'DECIMAL( 10, 2 ) NOT NULL');

if ($connection->tableColumnExists($orderTable, 'fee_amount_invoiced') === false) $connection->addColumn($orderTable, 'fee_amount_invoiced', 'DECIMAL( 10, 2 ) NOT NULL');
if ($connection->tableColumnExists($orderTable, 'base_fee_amount_invoiced') === false) $connection->addColumn($orderTable, 'base_fee_amount_invoiced', 'DECIMAL( 10, 2 ) NOT NULL');

if ($connection->tableColumnExists($invoiceTable, 'fee_amount') === false) $connection->addColumn($invoiceTable, 'fee_amount', 'DECIMAL( 10, 2 ) NOT NULL');
if ($connection->tableColumnExists($invoiceTable, 'base_fee_amount') === false) $connection->addColumn($invoiceTable, 'base_fee_amount', 'DECIMAL( 10, 2 ) NOT NULL');

if ($connection->tableColumnExists($creditmemoTable, 'fee_amount') === false) $connection->addColumn($creditmemoTable, 'fee_amount', 'DECIMAL( 10, 2 ) NOT NULL');
if ($connection->tableColumnExists($creditmemoTable, 'base_fee_amount') === false) $connection->addColumn($creditmemoTable, 'base_fee_amount', 'DECIMAL( 10, 2 ) NOT NULL');

if ($connection->tableColumnExists($quoteAddressTable, 'fee_amount') === false) $connection->addColumn($quoteAddressTable, 'fee_amount', 'DECIMAL( 10, 2 ) NOT NULL');
if ($connection->tableColumnExists($quoteAddressTable, 'base_fee_amount') === false) $connection->addColumn($quoteAddressTable, 'base_fee_amount', 'DECIMAL( 10, 2 ) NOT NULL');

$installer->endSetup();