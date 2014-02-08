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
 * Administration-Controller
 *
 * @category   Custom
 * @package    Delight_Delightpdf
 * @author     delight software gmbh <info@delightsoftware.com>
 */
class Delight_Delightpdf_Adminhtml_EditorController extends Mage_Adminhtml_Controller_Action {

	public function indexAction() {
		$this->_indexAction();
	}

	public function validateAction() {
		$response = new Varien_Object();
		$response->setError(false);
		$this->getResponse()->setBody($response->toJson());
	}

	public function stateAction() {
		// No Idee for what this action is
	}

	public function saveAction() {
		$pdf = $this->_initPostdataPdf();
		$store = $this->getRequest()->getParam('store');
		$pdfType = $this->getRequest()->getParam('tab');
		$pdfType = substr($pdfType, strrpos($pdfType, '_')+1);

		$resource = Mage::getResourceModel('delightpdf/page_collection')
			->addFieldToFilter('store_id', $store)
			->addFieldToFilter('pdf', $pdfType)
			->load();

		// first delete everything
		foreach ($resource as $res) {
			$model = Mage::getModel('delightpdf/page');
			$model->load($res->getId());
			if ($model->getId() > 0) {
				$model->delete();
			}
		}

		if (empty($store)) {
			$file = $pdf->getPdfTemplatePath($pdfType, null);
			$d = $pdf->getDomConfig();
			$d->save($file);

		} else {
			$vals = $pdf->getSaveValues();
			foreach ($vals as $val) {
				$model = Mage::getModel('delightpdf/page')
					->setStoreId($store)
					->setPdf($pdfType)
					->setKey($val->tag)
					->setValue($val->attr)
					->setElements($val->value)
					->save();
			}
		}
		$this->_indexAction();
	}

	public function helpAction() {
		$templateName = Mage::getDesign()->getTemplateFilename('delightpdf/help.phtml', array());

		$this->getResponse()->setBody(file_get_contents($templateName));
	}

	public function fixedAction() {
		$pdf = $this->_initPostdataPdf();
		$pdf->addFixedBlock(0, 0, 1, '<element />');

		$this->_indexAction($pdf);
	}

	public function footerpositionAction() {
		$pdf = $this->_initPostdataPdf();
		$num = $pdf->numPosition('page_positions_footer') + 1;
		$pdf->setConfig('page_positions_footer_position['.$num.']_type', 'custom');

		$this->_indexAction($pdf);
	}

	public function previewAction() {
		$helper = Mage::helper('delightpdf');
		$pdf = $this->_initPostdataPdf();
		$store = $this->getRequest()->getParam('store');
		$previewType = $this->getRequest()->getParam('tab');
		$previewType = substr($previewType, strrpos($previewType, '_')+1);
		$currencyCode = Mage::app()->getStore($store)->getCurrentCurrencyCode();

		// Create a dummy-Object for the PDF
		switch ($previewType) {
			case 'invoice':
				$obj = Mage::getModel('sales/order_invoice');
				$itemModel = 'sales/order_invoice_item';
				break;
			case 'creditmemo':
				$obj = Mage::getModel('sales/order_creditmemo');
				$itemModel = 'sales/order_creditmemo_item';
				break;
			case 'shipment':
				$obj = Mage::getModel('sales/order_shipment');
				$itemModel = 'sales/order_shipment_item';
				break;
			case 'delightserial':
				$obj = Mage::getModel('sales/order_invoice');
				$itemModel = 'sales/order_invoice_item';
				break;
		}
		$obj->setState(Mage_Sales_Model_Order_Invoice::STATE_PAID);
		$obj->setDiscountAmount(12.34);
		$obj->setTaxAmount(12.34);
		$obj->setShippingAmount(12.34);
		$obj->setAdjustmentPositive(12.34);
		$obj->setAdjustmentNegative(12.34);
		$obj->setGrandTotal(12.34);
		$obj->setIncrementId('X00001234');

		// Create an Address
		$address = Mage::getModel('sales/order_address');
		$address->setCompany($helper->__('Company'));
		$address->setPrefix($helper->__('Mr.'));
		$address->setFirstname($helper->__('Firstname'));
		$address->setMiddlename($helper->__('Middlename'));
		$address->setLastname($helper->__('Lastname'));
		$address->setStreet(array($helper->__('Street 1'), $helper->__('Street 2'), $helper->__('Street 3'), $helper->__('Street 4')));
		$address->setPostcode($helper->__('Postalcode'));
		$address->setCity($helper->__('City'));
		$address->setRegion($helper->__('Region'));
		$address->setCountryId($helper->__('CC'));
		$address->getConfig()->setStore($store);

		// Create an Order
		$order = Mage::getModel('sales/order');
		$order->setStatus(Mage_Sales_Model_Order::STATE_COMPLETE);
		//$order->setStatus(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
		$order->setRealOrderId(1234);
		$order->setCreatedAt(strftime('%F %r'));
		$order->setBillingAddress($address);
		$order->setShippingAddress($address);
		$order->setStoreId($store);
		$order->setOrderCurrencyCode($currencyCode);
		$order->setIncrementId('Y00001234');
		$obj->setOrder($order);

		// Add some items
		$total = 0.0;
		for ($i = 0; $i < 20; $i++) {
			$qty = rand(1, 99);
			$subtotal = ($qty*12.34)+1.23;
			$total += $subtotal;

			$item = Mage::getModel($itemModel);
			$item->setData('id', $i+1);
			$item->setSku($helper->__('PROD-SKU-'.($i+1)));
			$item->setName(str_repeat($helper->__('Dummy-Product number '), 5).($i+1));
			$item->setData('qty', $qty);
			$item->setPrice(12.34);
			$item->setTaxAmount(1.23);
			$item->setRowTotal($subtotal);
			$item->setDescription($helper->__('Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum.'));

			$serials = array();
			$maxSerials = rand(0,5);
			for ($j = 0; $j < $maxSerials; $j++) {
				$serials[] = $helper->__('DUMMY-SERIAL-NUMBER-PREVIEW');
			}
			$item->setDelightserialNumbers($serials);
			$obj->addItem($item);
		}
		$obj->setGrandTotal($total);

		$pdf->createPdf(array($obj));
		$this->getResponse()->clearAllHeaders()
			->setHeader('Expires', '', true)
			->setHeader('Cache-Control', '', true)
			->setHeader('Pragma', '', true)
			->setHeader('Content-Type', 'application/pdf', true)
			->setHeader('Content-Disposition', 'attachment; filename="'.$previewType.'.pdf"', true)
			->setHeader('Last-Modified', date('r'), true)
			->setHeader('Accept-Ranges', 'bytes', true)
			->setBody($pdf->getPdf()->render());
	}

	protected function _indexAction(Delight_Delightpdf_Model_Order_Pdf_Template_Pdf $pdf = null) {
		$this->loadLayout();
		$this->_setActiveMenu('system/delightpdf');
		$this->_addBreadcrumb(Mage::helper('delightpdf')->__('Delight PDF-Attachments'), Mage::helper('delightpdf')->__('Delight PDF-Attachments'));
		$this->_addContent($this->getLayout()->createBlock('delightpdf/editor')->setData('action', $this->getUrl('*/delightpdf_pdf/save')));
		$this->_addLeft($this->getLayout()->createBlock('delightpdf/tabs', '', array('pdf_template'=>$pdf)));
		$this->_addJs($this->getLayout()->createBlock('adminhtml/template')->setTemplate('delightpdf/js.phtml'));
		$this->renderLayout();
	}

	protected function _initPostdataPdf() {
		$store = $this->getRequest()->getParam('store');
		$pdfType = $this->getRequest()->getParam('tab');
		$pdfType = substr($pdfType, strrpos($pdfType, '_')+1);
		$saveData = $this->getRequest()->getPost('delightpdf_'.$pdfType);

		$pdf = Mage::getModel('delightpdf/order_pdf_template_pdf');
		if (is_array($saveData)) {
			foreach ($saveData as $var => $val) {
				if (is_array($val) && array_key_exists('inherit', $val) && ($val['inherit'] == 1)) {
					if (substr($var, 0, 6) == 'fixed_') {
						$var = preg_replace('/^fixed_(\d+)/smie', '"page_fixed[".((int)${1}+1)."]"', $var);
					} else if (substr($var, 0, 22) == 'page_positions_footer_') {
						//$var = preg_replace('/^page_positions_footer_([a-z]+)/smi', 'page_positions_footer_position[@type="${1}"]', $var);
						$var = preg_replace('/^page_positions_footer_(\d+)/smie', '"page_positions_footer_position[".(${1}+1)."]"', $var);
					}
					$pdf->setAsInherited($var);
				}
			}
		}

		$pdf->parseTemplate($pdfType, $store);
		if (is_array($saveData)) {
			foreach ($saveData as $var => $val) {
				if (is_array($val) && array_key_exists('value', $val)) {
					if (substr($var, 0, 6) == 'fixed_') {
						$var = preg_replace('/^fixed_(\d+)/smie', '"page_fixed[".((int)${1}+1)."]"', $var);
					} else if (substr($var, 0, 22) == 'page_positions_footer_') {
						//$var = preg_replace('/^page_positions_footer_([a-z]+)/smi', 'page_positions_footer_position[@type="${1}"]', $var);
						$var = preg_replace('/^page_positions_footer_(\d+)/smie', '"page_positions_footer_position[".(${1}+1)."]"', $var);
					}
					$pdf->setConfig($var, utf8_encode($val['value']));
				}
			}
		}

		return $pdf;
	}

}

?>