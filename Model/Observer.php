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

/**
 * Event-Observer
 *
 * @category   Custom
 * @package    Delight_Delightpdf
 * @author     delight software gmbh <info@delightsoftware.com>
 */
class Delight_Delightpdf_Model_Observer {

	/**
	 * Remove all Store-Entries from the Database before a store gets deleted
	 *
	 * @param   Varien_Object $observer
	 * @return  Delight_Delightpdf_Model_Observer
	 */
	public function storeDeleteBefore($observer) {
		$store = $observer->getEvent()->getStore();
		$res = Mage::getResourceModel('delightpdf/page_collection')->addFieldToFilter('store_id', $store->getStoreId());
		foreach ($res as $r) {
			$r->delete();
		}
		return $this;
	}

}
