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
 * Custom Fieldset on Administration
 *
 * @category   Custom
 * @package    Delight_Delightpdf
 * @author     delight software gmbh <info@delightsoftware.com>
 */
class Delight_Delightpdf_Block_Tab_Fieldset extends Mage_Adminhtml_Block_System_Config_Form_Fieldset {

	/**
	 * Enter description here...
	 *
	 * @param Varien_Data_Form_Element_Abstract $element
	 * @return string
	 */
	protected function _getHeaderHtml($element) {
		$default = !$this->getRequest()->getParam('website') && !$this->getRequest()->getParam('store');

		$html = '<div class="entry-edit-head collapseable fieldset-wide"><a id="' . $element->getHtmlId() . '-head" href="#" onclick="Fieldset.toggleCollapse(\'' . $element->getHtmlId() . '\', \'' . $this->getUrl('*/*/state') . '\'); return false;">' . $element->getLegend() . '</a></div>';
		$html .= '<input id="' . $element->getHtmlId() . '-state" name="config_state[' . $element->getId() . ']" type="hidden" value="' . (int)$this->_getCollapseState($element) . '" />';
		$html .= '<fieldset class="config collapseable" id="' . $element->getHtmlId() . '">';
		$html .= '<legend>' . $element->getLegend() . '</legend>';

		if ($element->getComment()) {
			$html .= '<div class="comment">' . $element->getComment() . '</div>';
		}

		// field label column
		$html .= '<table cellspacing="0" class="form-list"><colgroup class="label" /><colgroup class="value" style="width:100%;" />';
		if (!$default) {
			$html .= '<colgroup class="use-default" />';
		}
		$html .= '<colgroup class="scope-label" /><colgroup class="" /><tbody>';

		return $html;
	}
}
