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
 * PDF-Types for the Configuration
 *
 * @category   Custom
 * @package    Delight_Delightpdf
 * @author     delight software gmbh <info@delightsoftware.com>
 */
class Delight_Delightpdf_Model_Source_Pdftypes
{
    public function toOptionArray()
    {
        return array(
        	array('value' => 'disabled', 'label' => Mage::helper('delightpdf')->__('Use default PDFs')),
            array('value' => 'custom', 'label' => Mage::helper('delightpdf')->__('Use customized PDFs'))
        );
    }
}
