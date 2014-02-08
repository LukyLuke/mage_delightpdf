<?php

class Delight_Delightpdf_Model_Core_Mailer extends Mage_Core_Model_Email_Template_Mailer {

	/**
	 * Send all emails from email list
	 *
	 * @see Mage_Core_Model_Email_Template_Mailer::send()
	 * @return Mage_Core_Model_Email_Template_Mailer
	 */
	public function send() {
		$emailTemplate = Mage::getModel('core/email_template');
        // Send all emails from corresponding list
        while (!empty($this->_emailInfos)) {

        	// begin: added by delight software gmbh
       		$params = $this->getTemplateParams();
        	if ((Mage::getStoreConfig('catalog/delightpdf/pdf_invoice') == 'attach') && array_key_exists('invoice', $params)) {
				$pdf = Mage::getModel('sales/order_pdf_invoice')->getPdf(array($params['invoice']));
       			$attachment = $emailTemplate->getMail()->createAttachment($pdf->render());
				$attachment->type = 'application/pdf';
				$attachment->filename = Mage::helper('delightpdf')->__('Invoice.pdf');

        	} else if ((Mage::getStoreConfig('catalog/delightpdf/pdf_packingslip') == 'attach') && array_key_exists('shipment', $params)) {
				$pdf = Mage::getModel('sales/order_pdf_shipment')->getPdf(array($params['shipment']));
       			$attachment = $emailTemplate->getMail()->createAttachment($pdf->render());
				$attachment->type = 'application/pdf';
				$attachment->filename = Mage::helper('delightpdf')->__('PackingSlip.pdf');

        	} else if ((Mage::getStoreConfig('catalog/delightpdf/pdf_creditmemo') == 'attach') && array_key_exists('creditmemo', $params)) {
				$pdf = Mage::getModel('sales/order_pdf_creditmemo')->getPdf(array($params['creditmemo']));
       			$attachment = $emailTemplate->getMail()->createAttachment($pdf->render());
				$attachment->type = 'application/pdf';
				$attachment->filename = Mage::helper('delightpdf')->__('Creditmemo.pdf');

        	} else if ((Mage::getStoreConfig('catalog/delightpdf/pdf_order') == 'attach') && array_key_exists('order', $params)) {
        		$order = Mage::getModel('sales/order')->loadByIncrementId($params['order']->getIncrementId());
        		$invoice = array();
        		if (!$order->hasInvoices()) {
					$invoice[] = $order->prepareInvoice()->register();
        		} else {
        			foreach ($order->getInvoiceCollection() as $iv) {
        				$invoice[] = $iv;
        			}
        		}
				$pdf = Mage::getModel('delightpdf/order_pdf_invoice')->getPdf($invoice);
				$attachment = $emailTemplate->getMail()->createAttachment($pdf->render());
				$attachment->type = 'application/pdf';
				$attachment->filename = Mage::helper('delightpdf')->__('Invoice.pdf');
        	}
        	// end: added by delight software gmbh

            $emailInfo = array_pop($this->_emailInfos);
            // Handle "Bcc" recepients of the current email
            $emailTemplate->addBcc($emailInfo->getBccEmails());
            // Set required design parameters and delegate email sending to Mage_Core_Model_Email_Template
            $emailTemplate->setDesignConfig(array('area' => 'frontend', 'store' => $this->getStoreId()))
                ->sendTransactional(
                $this->getTemplateId(),
                $this->getSender(),
                $emailInfo->getToEmails(),
                $emailInfo->getToNames(),
                $this->getTemplateParams(),
                $this->getStoreId()
            );
        }
        return $this;
	}

}
