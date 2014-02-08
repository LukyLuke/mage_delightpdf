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
 * Delightserial PDF-Model override
 *
 * @category   Custom
 * @package    Delight_Delightpdf
 * @author     delight software gmbh <info@delightsoftware.com>
 */
class Delight_Delightpdf_Model_Order_Pdf_Serial extends Delight_Delightserial_Model_Product_Pdf_Serial {

	public function getPdf($payments = array()) {
		$pdfType = Mage::getStoreConfig('catalog/delightpdf/pdf_type');

		if ($pdfType == 'custom') {
			$this->_beforeGetPdf();

			// we need a special $payments-array
			$invoices = array();
			foreach ($payments as $payment) {
				$invoices[] = $payment->getOrder()->prepareInvoice();
			}

			$pdfTemplate = new Delight_Delightpdf_Model_Order_Pdf_Template_Pdf();
			$pdfTemplate->parseTemplate('delightserial', $invoices[0]->getStoreId());
			$pdfTemplate->createPdf($invoices);

			$this->_afterGetPdf();
			return $pdfTemplate->getPdf();

		} else {
			return parent::getPdf($payments);
		}
	}

}
?>