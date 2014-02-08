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
 * Fieldrenderer on Administration page
 *
 * @category   Custom
 * @package    Delight_Delightpdf
 * @author     delight software gmbh <info@delightsoftware.com>
 */
class Delight_Delightpdf_Block_Tab_Field extends Mage_Adminhtml_Block_System_Config_Form_Field {
    public function render(Varien_Data_Form_Element_Abstract $element) {
    	$html = parent::render($element);
    	$html = str_replace('<td class="use-default">', '<td class="use-default" style="white-space:nowrap;">', $html);
    	$html = str_replace('<td class="scope-label">', '<td class="scope-label" style="white-space:nowrap;">', $html);
    	if ($element->getData('required')) {
    		$html = str_replace('</label>', ' <span class="required">*</span></label>', $html);
    	}
		return $html;
	}
}
