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
 * The main Amdinistration page which shows all PDF-Types
 *
 * @category   Custom
 * @package    Delight_Delightpdf
 * @author     delight software gmbh <info@delightsoftware.com>
 */
class Delight_Delightpdf_Block_Editor extends Mage_Adminhtml_Block_Widget {

	public function __construct() {
		parent::__construct();
		$this->setTemplate('delightpdf/form.phtml');
		$this->setId('delightpdf_edit');
	}

	protected function _prepareLayout() {
		$this->setChild('save_button',
			$this->getLayout()->createBlock('adminhtml/widget_button')->setData(array(
				'label' => Mage::helper('delightpdf')->__('Save and Continue'),
				'onclick' => 'saveForm(\''.$this->getSaveUrl().'\')',
				'class' => 'save'
		)) );
		$this->setChild('preview_button',
			$this->getLayout()->createBlock('adminhtml/widget_button')->setData(array(
				'label' => Mage::helper('delightpdf')->__('Preview this PDF'),
				'onclick' => 'saveAndPreview(\''.$this->getPreviewUrl().'\')',
				'class' => 'add-widget'
		)) );
		$this->setChild('addfooterposition_button',
			$this->getLayout()->createBlock('adminhtml/widget_button')->setData(array(
				'label' => Mage::helper('delightpdf')->__('Add a Positions-Footer Position'),
				'onclick' => 'addFixed(\''.$this->getAddfooterpositionUrl().'\')',
				'class' => 'add'
		)) );
		$this->setChild('addfixed_button',
			$this->getLayout()->createBlock('adminhtml/widget_button')->setData(array(
				'label' => Mage::helper('delightpdf')->__('Add a Fixed Section'),
				'onclick' => 'addFixed(\''.$this->getAddfixedUrl().'\')',
				'class' => 'add'
		)) );

		return parent::_prepareLayout();
	}

	public function getSaveButtonHtml() {
		return $this->getChildHtml('save_button');
	}

	public function getPreviewButtonHtml() {
		return $this->getChildHtml('preview_button');
	}

	public function getAddfixedButtonHtml() {
		return $this->getChildHtml('addfixed_button');
	}

	public function getAddfooterpositionButtonHtml() {
		return $this->getChildHtml('addfooterposition_button');
	}

	public function getAddfixedUrl() {
		return $this->getUrl('*/*/fixed', array(
			'_current'   => true,
			'back'       => 'edit',
			'tab'        => '{{tab_id}}',
			'store'      => $this->getRequest()->getParam('store'),
			'active_tab' => null
		));
	}

	public function getAddfooterpositionUrl() {
		return $this->getUrl('*/*/footerposition', array(
			'_current'   => true,
			'back'       => 'edit',
			'tab'        => '{{tab_id}}',
			'store'      => $this->getRequest()->getParam('store'),
			'active_tab' => null
		));
	}

	public function getPreviewUrl() {
		return $this->getUrl('*/*/preview', array(
			'_current'   => true,
			'back'       => 'edit',
			'tab'        => '{{tab_id}}',
			'store'      => $this->getRequest()->getParam('store'),
			'active_tab' => null
		));
	}

	public function getSaveUrl() {
		return $this->getUrl('*/*/save', array(
			'_current'   => true,
			'back'       => 'edit',
			'tab'        => '{{tab_id}}',
			'store'      => $this->getRequest()->getParam('store'),
			'active_tab' => null
		));
	}

	public function getValidationUrl() {
		return $this->getUrl('*/*/validate', array(
			'_current' => true,
			'store'    => $this->getRequest()->getParam('store')
		));
	}

	public function getHelpUrl() {
		return $this->getUrl('*/*/help', array(
			'_current' => true,
			'store'    => $this->getRequest()->getParam('store')
		));
	}

	public function getSelectedTabId() {
		return addslashes(htmlspecialchars($this->getRequest()->getParam('tab')));
	}
}
