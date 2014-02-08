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
 * Order-Model override
 *
 * @category   Custom
 * @package    Delight_Delightpdf
 * @author     delight software gmbh <info@delightsoftware.com>
 */
class Delight_Delightpdf_Model_Order extends Mage_Sales_Model_Order {

	/**
	 * Send Customer-Notification EMails for the first invoice
	 * TODO: Send also notifications for partial-paid invoices but only for the ones created as last
	 */
	protected function _sendInvoiceEmail() {
	    $payment = $this->getPayment();
    	if (!$payment) Mage::log('This Order has no Payment:', Zend_Log::WARN);

    	$invoice = $payment ? $payment->getCreatedInvoice() : false;
    	if (!$invoice) {
    		Mage::log('Payment does not have a created Invoice:', Zend_Log::WARN);
    		foreach ($this->getInvoiceCollection() as $invoice) {
    			break;
    		}
    	}
    	if ($invoice) {
    		$inform = Mage::getStoreConfig('catalog/delightpdf/inform_customer');
			$inform_pending = Mage::getStoreConfig('catalog/delightpdf/inform_customer_pending');
			$paid = ($invoice->getState() == Mage_Sales_Model_Order_Invoice::STATE_PAID);
			$pending = (($this->getState() == Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) || ($this->getState() == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT));
			if (($inform && $paid && !$pending) || ($inform && $inform_pending && $pending)) {
    			$invoice->sendEmail();
			}
    	}
	}

	/**
	 * Sending email with order data
	 *
	 * @return Delight_Delightpdf_Model_Order
	 * @override
	 */
	public function sendNewOrderEmail()
	{
    	if (!Mage::helper('delightpdf')->isPrevious15()) {
    		$result = parent::sendNewOrderEmail();
    		$this->_sendInvoiceEmail();
    		return $result;
    	}

		if (!Mage::helper('sales')->canSendNewOrderEmail($this->getStore()->getId())) {
			return $this;
		}

		// @var $translate Mage_Core_Model_Translate
		$translate = Mage::getSingleton('core/translate');
		$translate->setTranslateInline(false);

		$paymentBlock = Mage::helper('payment')
			->getInfoBlock($this->getPayment())
			->setIsSecureMode(true);

		$paymentBlock->getMethod()
			->setStore($this->getStore()
			->getId());

		// @var $mailTemplate Mage_Core_Model_Email_Template
		$mailTemplate = Mage::getModel('core/email_template');

		$copyTo = $this->_getEmails(self::XML_PATH_EMAIL_COPY_TO);
		$copyMethod = Mage::getStoreConfig(self::XML_PATH_EMAIL_COPY_METHOD, $this->getStoreId());
		if ($copyTo && $copyMethod == 'bcc') {
			foreach ($copyTo as $email) {
				$mailTemplate->addBcc($email);
			}
		}

		if ($this->getCustomerIsGuest()) {
			$template = Mage::getStoreConfig(self::XML_PATH_EMAIL_GUEST_TEMPLATE, $this->getStoreId());
			$customerName = $this->getBillingAddress()->getName();
		} else {
			$template = Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE, $this->getStoreId());
			$customerName = $this->getCustomerName();
		}

		// BEGIN: DELIGHT SOFTWARE GMBH
		$pdfInvoice = Mage::getStoreConfig('catalog/delightpdf/pdf_invoice');
		if ($pdfInvoice == 'attach') {
			$invoice = $this->prepareInvoice();
			$pdf = Mage::getModel('delightpdf/order_pdf_invoice')->getPdf(array($invoice));
			$attachment = $mailTemplate->getMail()->createAttachment($pdf->render());
			$attachment->type = 'application/pdf';
			$attachment->filename = 'Rechnung.pdf';
		}
		// END: DELIGHT SOFTWARE GMBH


		$sendTo = array(array('email' => $this->getCustomerEmail(), 'name' => $customerName));
		if ($copyTo && $copyMethod == 'copy') {
			foreach ($copyTo as $email) {
				$sendTo[] = array('email' => $email, 'name' => null);
			}
		}

		foreach ($sendTo as $recipient) {
			$mailTemplate->setDesignConfig(
				array(
					'area' => 'frontend',
					'store' => $this->getStoreId())
				)->sendTransactional(
					$template,
					Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $this->getStoreId()),
					$recipient['email'],
					$recipient['name'],
					array(
						'order' => $this,
						'billing' => $this->getBillingAddress(),
						'payment_html' => $paymentBlock->toHtml()
					)
				);
		}

		$translate->setTranslateInline(true);

		// BEGIN: DELIGHT SOFTWARE GMBH
		$this->_sendInvoiceEmail();
		// END: DELIGHT SOFTWARE GMBH
		return $this;
	}

	/**
	 * Sending email with order update information
	 *
	 * @return Mage_Sales_Model_Order
	 */
	public function sendOrderUpdateEmail($notifyCustomer = true, $comment = '')
	{
		$notifyCustomer = $notifyCustomer || Mage::getStoreConfig('catalog/delightpdf/inform_customer');
    	if (!Mage::helper('delightpdf')->isPrevious15()) {
    		return parent::sendOrderUpdateEmail($notifyCustomer, $comment);
    	}

		if (!Mage::helper('sales')->canSendOrderCommentEmail($this->getStore()->getId())) {
			return $this;
		}

		$copyTo = $this->_getEmails(self::XML_PATH_UPDATE_EMAIL_COPY_TO);
		$copyMethod = Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_COPY_METHOD, $this->getStoreId());
		if (!$notifyCustomer && !$copyTo) {
			return $this;
		}

		// set design parameters, required for email (remember current)
		$currentDesign = Mage::getDesign()->setAllGetOld(array('store' => $this->getStoreId(), 'area' => 'frontend', 'package' => Mage::getStoreConfig('design/package/name', $this->getStoreId())));

		$translate = Mage::getSingleton('core/translate');
		/* @var $translate Mage_Core_Model_Translate */
		$translate->setTranslateInline(false);

		$sendTo = array();

		$mailTemplate = Mage::getModel('core/email_template');

		if ($this->getCustomerIsGuest()) {
			$template = Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_GUEST_TEMPLATE, $this->getStoreId());
			$customerName = $this->getBillingAddress()->getName();
		} else {
			$template = Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_TEMPLATE, $this->getStoreId());
			$customerName = $this->getCustomerName();
		}

		//// BEGIN: DELIGHT SOFTWARE GMBH
		$pdfInvoice = Mage::getStoreConfig('catalog/delightpdf/pdf_invoice');
		if ($pdfInvoice == 'attach') {
			$invoice = $this->prepareInvoice();
			$pdf = Mage::getModel('delightpdf/order_pdf_invoice')->getPdf(array($invoice));
			$attachment = $mailTemplate->getMail()->createAttachment($pdf->render());
			$attachment->type = 'application/pdf';
			$attachment->filename = 'Rechnung.pdf';
		}
		//// END: DELIGHT SOFTWARE GMBH

		if ($notifyCustomer) {
			$sendTo[] = array('name' => $customerName, 'email' => $this->getCustomerEmail());
			if ($copyTo && $copyMethod == 'bcc') {
				foreach ($copyTo as $email) {
					$mailTemplate->addBcc($email);
				}
			}

		}

		if ($copyTo && ($copyMethod == 'copy' || !$notifyCustomer)) {
			foreach ($copyTo as $email) {
				$sendTo[] = array('name' => null, 'email' => $email);
			}
		}

		foreach ($sendTo as $recipient) {
			$mailTemplate->setDesignConfig(array('area' => 'frontend', 'store' => $this->getStoreId()))->sendTransactional($template, Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_IDENTITY, $this->getStoreId()), $recipient['email'], $recipient['name'], array('order' => $this, 'billing' => $this->getBillingAddress(), 'comment' => $comment));
		}

		$translate->setTranslateInline(true);

		// revert current design
		Mage::getDesign()->setAllGetOld($currentDesign);

		return $this;
	}

}
