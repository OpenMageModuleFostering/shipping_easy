<?php

/**
 * 
 *  adding an order level attribute for Shipping Easy module in order Module
 * 
 * 
 */
$installer = new Mage_Catalog_Model_Resource_Eav_Mysql4_Setup('sales_setup');
$installer->startSetup();
$installer->getConnection()->addColumn($installer->getTable('sales/order'), 'is_exported', "TINYINT(1) UNSIGNED DEFAULT '0'");

$installer->addAttribute('order', 'is_exported', array(
    'type' => 'int',
    'label' => 'Exported to Shipping Easy',
    'input' => 'select',
    'source' => 'eav/entity_attribute_source_boolean',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'required' => false,
    'default' => '0'
));


$installer->endSetup();
?>
