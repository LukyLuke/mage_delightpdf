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
 * Tabs on the lrft side which shows all PDF-Types
 *
 * @category   Custom
 * @package    Delight_Delightpdf
 * @author     delight software gmbh <info@delightsoftware.com>
 */
class Delight_Delightpdf_Block_Tabs extends Mage_Adminhtml_Block_Widget_Tabs {

	protected $_pdfBlock = 'delightpdf/tab_pdf';

	public function __construct() {
		parent::__construct();
		$this->setTemplate('delightpdf/tabs.phtml');
		$this->setId('delightpdf_info_tabs');
		$this->setDestElementId('delightpdf_edit_form');
		$this->setTitle(Mage::helper('delightpdf')->__('Custom PDFs'));
	}

	protected function _prepareLayout() {
		$this->setChild('store_switcher',
            $this->getLayout()->createBlock('adminhtml/store_switcher')
                ->setSwitchUrl($this->getUrl('*/*/*', array('_current'=>true, '_query'=>false, 'store'=>null)))
        );

		$this->addTab('invoice', array(
			'label' => Mage::helper('delightpdf')->__('Invoice'),
			'content' => $this->getLayout()
				->createBlock($this->_pdfBlock)
				->setPdfType('invoice')
				->setPdfTemplate($this->getPdfTemplate())
				->toHtml()
		));

		$this->addTab('creditmemo', array(
			'label' => Mage::helper('delightpdf')->__('Creditmemo'),
			'content' => $this->getLayout()
				->createBlock($this->_pdfBlock)
				->setPdfType('creditmemo')
				->setPdfTemplate($this->getPdfTemplate())
				->toHtml()
		));

		$this->addTab('shipment', array(
			'label' => Mage::helper('delightpdf')->__('Shipment'),
			'content' => $this->getLayout()
				->createBlock($this->_pdfBlock)
				->setPdfType('shipment')
				->setPdfTemplate($this->getPdfTemplate())
				->toHtml()
		));

		// Add this Tab only if DelightSerials is available
		// Maybe there is a better way than catch an Exception...
		try {
			$serials = Mage::helper('delightserial');
			$this->addTab('delightserial', array(
				'label' => Mage::helper('delightpdf')->__('Delight Serials'),
				'content' => $this->getLayout()
					->createBlock($this->_pdfBlock)
					->setPdfType('delightserial')
					->setPdfTemplate($this->getPdfTemplate())
					->toHtml()
			));
			unset($serials);
		} catch(Exception $e) { }

		return parent::_prepareLayout();
	}

    public function getStoreSwitcherHtml() {
		return $this->getChildHtml('store_switcher');
	}

}
