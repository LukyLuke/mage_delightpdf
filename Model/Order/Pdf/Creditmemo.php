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
 * Creditmemo PDF-Model Override
 *
 * @category   Custom
 * @package    Delight_Delightpdf
 * @author     delight software gmbh <info@delightsoftware.com>
 */
class Delight_Delightpdf_Model_Order_Pdf_Creditmemo extends Mage_Sales_Model_Order_Pdf_Creditmemo {

	public function getPdf($creditmemos = array()) {
		$pdfType = Mage::getStoreConfig('catalog/delightpdf/pdf_type');

		if ($pdfType == 'custom') {
			$this->_beforeGetPdf();

			$pdfTemplate = new Delight_Delightpdf_Model_Order_Pdf_Template_Pdf();
			$pdfTemplate->parseTemplate('creditmemo', $creditmemos[0]->getStoreId());
			$pdfTemplate->createPdf($creditmemos);

			$this->_afterGetPdf();
			return $pdfTemplate->getPdf();

		} else {
			return parent::getPdf($creditmemos);
		}
	}

}
?>