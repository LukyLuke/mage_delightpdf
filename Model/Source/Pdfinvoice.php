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
 * PDF-Attachment Chooser for Configuration
 *
 * @category   Custom
 * @package    Delight_Delightpdf
 * @author     delight software gmbh <info@delightsoftware.com>
 */
class Delight_Delightpdf_Model_Source_Pdfinvoice
{
    public function toOptionArray()
    {
        return array(
        	array('value' => 'disabled', 'label' => Mage::helper('delightpdf')->__('No PDF Attachment')),
            array('value' => 'attach', 'label' => Mage::helper('delightpdf')->__('Attach PDF to the Email'))
        );
    }
}
