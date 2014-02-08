<?php
/**
 * Delightpdf Customisation by delight software gmbh for Magento
 *
 * DISCLAIMER
 *
 * Do not edit or add code to this file if you wish to upgrade this Module to newer
 * versions in the future.
 *
 * @category   Custom
 * @package    Delight_Delightpdf
 * @copyright  Copyright (c) 2001-2011 delight software gmbh (http://www.delightsoftware.com/)
 */

$installer = $this;
/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */

$installer->startSetup();


$installer->run("
CREATE TABLE `{$installer->getTable('delightpdf/page')}`(
  `id` int(10) unsigned NOT NULL auto_increment,
  `store_id` int(10) unsigned NULL,
  `key` varchar(50) NOT NULL default '',
  `elements` text NOT NULL default '',
  `value` varchar(50) NOT NULL default '',
  PRIMARY KEY `DELIGHTPDF_ID` (`id`),
  KEY `DELIGHTPDF_STORE` (`store_id`),
  KEY `DELIGHTPDF_KEY` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();
