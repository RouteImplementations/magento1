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

if ($connection->tableColumnExists($quoteTable, 'fee_tax_amount') === false) $connection->addColumn($quoteTable, 'fee_tax_amount', 'DECIMAL( 10, 2 ) NOT NULL');
if ($connection->tableColumnExists($quoteTable, 'base_fee_tax_amount') === false) $connection->addColumn($quoteTable, 'base_fee_tax_amount', 'DECIMAL( 10, 2 ) NOT NULL');

if ($connection->tableColumnExists($orderTable, 'fee_tax_amount') === false) $connection->addColumn($orderTable, 'fee_tax_amount', 'DECIMAL( 10, 2 ) NOT NULL');
if ($connection->tableColumnExists($orderTable, 'base_fee_tax_amount') === false) $connection->addColumn($orderTable, 'base_fee_tax_amount', 'DECIMAL( 10, 2 ) NOT NULL');

if ($connection->tableColumnExists($orderTable, 'fee_tax_amount_refunded') === false) $connection->addColumn($orderTable, 'fee_tax_amount_refunded', 'DECIMAL( 10, 2 ) NOT NULL');
if ($connection->tableColumnExists($orderTable, 'base_fee_tax_amount_refunded') === false) $connection->addColumn($orderTable, 'base_fee_tax_amount_refunded', 'DECIMAL( 10, 2 ) NOT NULL');

if ($connection->tableColumnExists($orderTable, 'fee_tax_amount_invoiced') === false) $connection->addColumn($orderTable, 'fee_tax_amount_invoiced', 'DECIMAL( 10, 2 ) NOT NULL');
if ($connection->tableColumnExists($orderTable, 'base_fee_tax_amount_invoiced') === false) $connection->addColumn($orderTable, 'base_fee_tax_amount_invoiced', 'DECIMAL( 10, 2 ) NOT NULL');

if ($connection->tableColumnExists($invoiceTable, 'fee_tax_amount') === false) $connection->addColumn($invoiceTable, 'fee_tax_amount', 'DECIMAL( 10, 2 ) NOT NULL');
if ($connection->tableColumnExists($invoiceTable, 'base_fee_tax_amount') === false) $connection->addColumn($invoiceTable, 'base_fee_tax_amount', 'DECIMAL( 10, 2 ) NOT NULL');

if ($connection->tableColumnExists($creditmemoTable, 'fee_tax_amount') === false) $connection->addColumn($creditmemoTable, 'fee_tax_amount', 'DECIMAL( 10, 2 ) NOT NULL');
if ($connection->tableColumnExists($creditmemoTable, 'base_fee_tax_amount') === false) $connection->addColumn($creditmemoTable, 'base_fee_tax_amount', 'DECIMAL( 10, 2 ) NOT NULL');

if ($connection->tableColumnExists($quoteAddressTable, 'fee_tax_amount') === false) $connection->addColumn($quoteAddressTable, 'fee_tax_amount', 'DECIMAL( 10, 2 ) NOT NULL');
if ($connection->tableColumnExists($quoteAddressTable, 'base_fee_tax_amount') === false) $connection->addColumn($quoteAddressTable, 'base_fee_tax_amount', 'DECIMAL( 10, 2 ) NOT NULL');

$installer->endSetup();