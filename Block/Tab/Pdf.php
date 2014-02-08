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
 * A Administratin entry
 *
 * @category   Custom
 * @package    Delight_Delightpdf
 * @author     delight software gmbh <info@delightsoftware.com>
 */
class Delight_Delightpdf_Block_Tab_Pdf extends Mage_Adminhtml_Block_Catalog_Form {
    const SCOPE_DEFAULT = 'default';
    const SCOPE_WEBSITES = 'websites';
    const SCOPE_STORES   = 'stores';

	protected $pdf;
	protected $_scopeLabels;
	protected $_defaultFieldRenderer;
	protected $_elementsComment;
	protected $_valuesComment;

	public function __construct() {
		parent::__construct();
		$this->_scopeLabels = array(
			self::SCOPE_DEFAULT  => Mage::helper('adminhtml')->__('[GLOBAL]'),
			self::SCOPE_WEBSITES => Mage::helper('adminhtml')->__('[WEBSITE]'),
			self::SCOPE_STORES   => Mage::helper('adminhtml')->__('[STORE VIEW]'),
		);
		$this->_elementsComment = Mage::helper('delightpdf')->__('Positions and sizes must be in Points. One Point is 1/72 inch or 2.54/72 mm (%.4f inch / %.4f mm). <a href="%s">Click here for more Information</a>', 1/72, 2.54/72, '#" onclick="return showHelp();');
		$this->_valuesComment = Mage::helper('delightpdf')->__('Value must be in Points. One Point is 1/72 inch or 2.54/72 mm (%.4f inch / %.4f mm)', 1/72, 2.54/72);
		$this->_defaultFieldRenderer = Mage::getBlockSingleton('delightpdf/tab_field');
		$this->_defaultFieldRenderer->setForm($this);
	}

    public function getScope() {
        $scope = $this->getData('scope');
        if (is_null($scope)) {
        	$store = $this->getRequest()->getParam('store');
            if (!is_null($store)) {
                $scope = self::SCOPE_STORES;
            } else {
                $scope = self::SCOPE_DEFAULT;
            }
            $this->setScope($scope);
        }

        return $scope;
    }

    public function getScopeLabel() {
		return $this->_scopeLabels[self::SCOPE_STORES];
    }

    public function getScopeId() {
        $scopeId = $this->getData('scope_id');
        if (is_null($scopeId)) {
        	$store = $this->getRequest()->getParam('store');
            if (!is_null($store)) {
                $scopeId = Mage::app()->getStore($store)->getId();
            } else {
                $scopeId = '';
            }
            $this->setScopeId($scopeId);
        }
        return $scopeId;
    }

    public function canUseDefaultValue() {
        return ($this->getScope() == self::SCOPE_STORES);
    }

	/**
	 * Disable loading Wysiwyg and Prepare layout
	 */
	protected function _prepareLayout() {
		parent::_prepareLayout();
		$this->getLayout()->getBlock('head')->setCanLoadTinyMce(false);
	}


	protected function _prepareForm() {
		$type = $this->getPdfType();
		if (!empty($type)) {
			$form = new Varien_Data_Form();
			$this->loadPdfTemplate();

			Mage::register('delightpdf_'.$type, $this);
			$form->setDataObject($this);

			$this->addPageFields( $this->addFieldset($form, 'page', 'Page Settings') );
			$this->addPageHeaderFields( $this->addFieldset($form, 'pageheader', 'Page Header') );
			$this->addPageFooterFields( $this->addFieldset($form, 'pagefooter', 'Page Footer') );

			for ($i = 0; $i < $this->pdf->numFixed('page'); $i++) {
				$this->addFixedBlockFields( $this->addFieldset($form, 'fixed_'.$i, 'Fixed Block #'.($i+1)), $i );
			}

			$this->addPositionsFields( $this->addFieldset($form, 'page_positions_settings', 'Positions Settings') );
			$this->addPositionsHeaderFields( $this->addFieldset($form, 'page_positions_header', 'Positions Header') );
			$this->addPositionsPositionsFields( $this->addFieldset($form, 'page_positions_positions', 'Positions') );
			$this->addPositionsSummaryFields( $this->addFieldset($form, 'page_positions_summary', 'Positions Summary') );
			$this->addPositionsFooterFields( $this->addFieldset($form, 'page_positions_footer', 'Positions Footer') );

			$values = Mage::registry('delightpdf_'.$type)->getData();

			Mage::dispatchEvent('delight_delightpdf_prepare_form', array('form' => $form));

			$form->addValues($values);
			$form->setFieldNameSuffix('delightpdf_'.$type);
			$this->setForm($form);
		}
	}

	protected function loadPdfTemplate() {
        $store = $this->getRequest()->getParam('store');
		$this->pdf = $this->getPdfTemplate();
		if (is_null($this->pdf)) {
			$this->pdf = Mage::getModel('delightpdf/order_pdf_template_pdf');
			$this->pdf->parseTemplate($this->getPdfType(), $store);
		}

		// Page-Settings Values
		// pdf->page
		$pageFormat = explode('_', $this->pdf->getFormat('page'));
		$this->setData('page_size', $pageFormat[0]);
		$this->setData('page_orientation', isset($pageFormat[1]) ? $pageFormat[1] : 'portrait');
		$this->setData('page_padding', $this->pdf->getPadding('page'));

		// Page-Header Values
		// pdf->page->header
		$this->setData('page_header_x', $this->pdf->getX('header', 'page'));
		$this->setData('page_header_y', $this->pdf->getY('header', 'page'));
		$this->setData('page_header_cdata', $this->pdf->getCDATA('header', 'page'));

		// Page-Footer Values
		// pdf->page->footer
		$this->setData('page_footer_x', $this->pdf->getX('footer', 'page'));
		$this->setData('page_footer_y', $this->pdf->getY('footer', 'page'));
		$this->setData('page_footer_cdata', $this->pdf->getCDATA('footer', 'page'));

		// Positions Values
		// pdf->page->positions
		$this->setData('page_positions_x', $this->pdf->getX('positions', 'page'));
		$this->setData('page_positions_y', $this->pdf->getY('positions', 'page'));

		// Positions-Header Values
		// pdf->page->positions->header
		$this->setData('page_positions_header_margin', $this->pdf->getMargin('header', 'page_positions'));
		$this->setData('page_positions_header_cdata', $this->pdf->getCDATA('header', 'page_positions'));

		// Positions-Position Values
		// pdf->page->positions->position
		$this->setData('page_positions_position_margin', $this->pdf->getMargin('position', 'page_positions'));
		$this->setData('page_positions_position_cdata', $this->pdf->getCDATA('position', 'page_positions'));

		// Positions-Summary Values
		// pdf->page->positions->summary
		$this->setData('page_positions_summary_margin', $this->pdf->getMargin('summary', 'page_positions'));
		$this->setData('page_positions_summary_cdata', $this->pdf->getCDATA('summary', 'page_positions'));

		// Positions-Footer Values
		// pdf->page->positions->footer
		$this->setData('page_positions_footer_margin', $this->pdf->getMargin('footer', 'page_positions'));

		// Positions-Footer types
		// pdf->page->positions->footer->position[@type='(discount|tax|shipping|adjustmentfee|adjustmentrefund|grandtotal)']
		// pdf->page->positions->footer->position[x]
		for ($i = 0; $i < $this->pdf->numPosition('page_positions_footer'); $i++) {
			$this->setData('page_positions_footer_'.$i.'_margin', $this->pdf->getMargin('position', 'page_positions_footer', $i));
			$this->setData('page_positions_footer_'.$i.'_type', $this->pdf->getType('position', 'page_positions_footer', $i));
			$this->setData('page_positions_footer_'.$i.'_cdata', $this->pdf->getCDATA('position', 'page_positions_footer', $i));

			//$this->setData('page_positions_footer_'.$type.'_margin', $this->pdf->getMargin('position', 'page_positions_footer', $i));
			//$this->setData('page_positions_footer_'.$type.'_cdata', $this->pdf->getCDATA('position', 'page_positions_footer', $i));
		}

		// Fixed-Field Values
		// pdf->page->fixed[x]
		for ($i = 0; $i < $this->pdf->numFixed('page'); $i++) {
			$this->setData('fixed_'.$i.'_x', $this->pdf->getX('fixed', 'page', $i));
			$this->setData('fixed_'.$i.'_y', $this->pdf->getY('fixed', 'page', $i));
			$this->setData('fixed_'.$i.'_page', $this->pdf->getPage('fixed', 'page', $i));
			$this->setData('fixed_'.$i.'_cdata', $this->pdf->getCDATA('fixed', 'page', $i));
		}
	}

	protected function addFieldset(Varien_Data_Form $form, $name, $title) {
		return $form->addFieldset('delightpdf_'.$name.'_'.$this->getPdfType(), array(
			'legend' => Mage::helper('delightpdf')->__($title),
		))->setRenderer(Mage::getBlockSingleton('delightpdf/tab_fieldset'));
	}

	protected function addPageFields(Varien_Data_Form_Element_Fieldset $fieldset) {
		$fieldset->addField('page_size', 'select', array(
			'name' => 'page_size[value]',
			'label' => Mage::helper('delightpdf')->__('Page format'),
			'comment' => 'a4 = 595x842 points / letter = 612x792 points',
			'required' => true,
			'values' => array(
				'a4' => Mage::helper('delightpdf')->__('A4'),
				'letter' => Mage::helper('delightpdf')->__('Letter')
			),
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_size')
		))->setRenderer($this->_defaultFieldRenderer);

		$fieldset->addField('page_orientation', 'select', array(
			'name' => 'page_orientation[value]',
			'label' => Mage::helper('delightpdf')->__('Page orientation'),
			'required' => true,
			'values' => array(
				'portrait' => Mage::helper('delightpdf')->__('Portrait'),
				'landscape' => Mage::helper('delightpdf')->__('Landscape')
			),
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_orientation')
		))->setRenderer($this->_defaultFieldRenderer);

		$fieldset->addField('page_padding', 'text', array(
			'name' => 'page_padding[value]',
			'label' => Mage::helper('delightpdf')->__('Page border'),
			'comment' => $this->_valuesComment,
			'required' => false,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_padding')
		))->setRenderer($this->_defaultFieldRenderer);
	}

	protected function addPageHeaderFields(Varien_Data_Form_Element_Fieldset $fieldset) {
		$fieldset->addField('page_header_x', 'text', array(
			'name' => 'page_header_x[value]',
			'label' => Mage::helper('delightpdf')->__('Left X-Position'),
			'comment' => $this->_valuesComment,
			'required' => false,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_header_x')
		))->setRenderer($this->_defaultFieldRenderer);

		$fieldset->addField('page_header_y', 'text', array(
			'name' => 'page_header_y[value]',
			'label' => Mage::helper('delightpdf')->__('Top Y-Position from Bottom'),
			'comment' => $this->_valuesComment,
			'required' => false,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_header_y')
		))->setRenderer($this->_defaultFieldRenderer);

		$fieldset->addField('page_header_cdata', 'textarea', array(
			'name' => 'page_header_cdata[value]',
			'label' => Mage::helper('delightpdf')->__('Elements'),
			'comment' => $this->_elementsComment,
			'style' => 'height:250px;width:100%;',
			'required' => false,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_header_cdata')
		))->setRenderer($this->_defaultFieldRenderer);
	}

	protected function addPageFooterFields(Varien_Data_Form_Element_Fieldset $fieldset) {
		$fieldset->addField('page_footer_x', 'text', array(
			'name' => 'page_footer_x[value]',
			'label' => Mage::helper('delightpdf')->__('Left X-Position'),
			'comment' => $this->_valuesComment,
			'required' => false,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_footer_x')
		))->setRenderer($this->_defaultFieldRenderer);

		$fieldset->addField('page_footer_y', 'text', array(
			'name' => 'page_footer_y[value]',
			'label' => Mage::helper('delightpdf')->__('Top Y-Position from Bottom'),
			'comment' => $this->_valuesComment,
			'required' => false,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_footer_y')
		))->setRenderer($this->_defaultFieldRenderer);

		$fieldset->addField('page_footer_cdata', 'textarea', array(
			'name' => 'page_footer_cdata[value]',
			'label' => Mage::helper('delightpdf')->__('Elements'),
			'comment' => $this->_elementsComment,
			'style' => 'height:250px;width:100%;',
			'required' => false,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_footer_cdata')
		))->setRenderer($this->_defaultFieldRenderer);
	}

	protected function addFixedBlockFields(Varien_Data_Form_Element_Fieldset $fieldset, $num) {
		$fieldset->addField('fixed_'.$num.'_x', 'text', array(
			'name' => 'fixed_'.$num.'_x[value]',
			'label' => Mage::helper('delightpdf')->__('Left X-Position'),
			'comment' => $this->_valuesComment,
			'required' => false,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_fixed['.($num+1).']_x')
		))->setRenderer($this->_defaultFieldRenderer);

		$fieldset->addField('fixed_'.$num.'_y', 'text', array(
			'name' => 'fixed_'.$num.'_y[value]',
			'label' => Mage::helper('delightpdf')->__('Top Y-Position from Bottom'),
			'comment' => $this->_valuesComment,
			'required' => false,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_fixed['.($num+1).']_y')
		))->setRenderer($this->_defaultFieldRenderer);

		$fieldset->addField('fixed_'.$num.'_page', 'text', array(
			'name' => 'fixed_'.$num.'_page[value]',
			'label' => Mage::helper('delightpdf')->__('Show on Page'),
			'comment' => Mage::helper('delightpdf')->__('Can be a Pagenumber, "first", "last", "each" or "all"'),
			'required' => false,
			'value' => 'first,each,all,last,PageNumber',
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_fixed['.($num+1).']_page')
		))->setRenderer($this->_defaultFieldRenderer);

		$fieldset->addField('fixed_'.$num.'_cdata', 'textarea', array(
			'name' => 'fixed_'.$num.'_cdata[value]',
			'label' => Mage::helper('delightpdf')->__('Elements'),
			'comment' => $this->_elementsComment,
			'style' => 'height:250px;width:100%;',
			'required' => false,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_fixed['.($num+1).']_cdata')
		))->setRenderer($this->_defaultFieldRenderer);
	}

	protected function addPositionsFields(Varien_Data_Form_Element_Fieldset $fieldset) {
		$fieldset->addField('page_positions_x', 'text', array(
			'name' => 'page_positions_x[value]',
			'label' => Mage::helper('delightpdf')->__('Left X-Position'),
			'comment' => $this->_valuesComment,
			'required' => true,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_positions_x')
		))->setRenderer($this->_defaultFieldRenderer);

		$fieldset->addField('page_positions_y', 'text', array(
			'name' => 'page_positions_y[value]',
			'label' => Mage::helper('delightpdf')->__('Top Y-Position from Bottom'),
			'comment' => $this->_valuesComment,
			'required' => true,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_positions_y')
		))->setRenderer($this->_defaultFieldRenderer);

		/*$fieldset->addField('page_positions_x2', 'text', array(
			'name' => 'page_positions_x2[value]',
			'label' => Mage::helper('delightpdf')->__('Right X-Position'),
			'comment' => $this->_valuesComment,
			'required' => true,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_positions_x2')
		))->setRenderer($this->_defaultFieldRenderer);

		$fieldset->addField('page_positions_y2', 'text', array(
			'name' => 'page_positions_y2[value]',
			'label' => Mage::helper('delightpdf')->__('Bottom Y-Position from Bottom'),
			'comment' => $this->_valuesComment,
			'required' => true,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_positions_y2')
		))->setRenderer($this->_defaultFieldRenderer);*/
	}

	protected function addPositionsHeaderFields(Varien_Data_Form_Element_Fieldset $fieldset) {
		$fieldset->addField('page_positions_header_margin', 'text', array(
			'name' => 'page_positions_header_margin[value]',
			'label' => Mage::helper('delightpdf')->__('Margin to Positions'),
			'comment' => $this->_valuesComment,
			'required' => true,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_positions_header_margin')
		))->setRenderer($this->_defaultFieldRenderer);

		$fieldset->addField('page_positions_header_cdata', 'textarea', array(
			'name' => 'page_positions_header_cdata[value]',
			'label' => Mage::helper('delightpdf')->__('Elements'),
			'comment' => $this->_elementsComment,
			'style' => 'height:250px;width:100%;',
			'required' => true,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_positions_header_cdata')
		))->setRenderer($this->_defaultFieldRenderer);
	}

	protected function addPositionsSummaryFields(Varien_Data_Form_Element_Fieldset $fieldset) {
		$fieldset->addField('page_positions_summary_margin', 'text', array(
			'name' => 'page_positions_summary_margin[value]',
			'label' => Mage::helper('delightpdf')->__('Margin to Positions'),
			'comment' => $this->_valuesComment,
			'required' => false,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_positions_summary_margin')
		))->setRenderer($this->_defaultFieldRenderer);

		$fieldset->addField('page_positions_summary_cdata', 'textarea', array(
			'name' => 'page_positions_summary_cdata[value]',
			'label' => Mage::helper('delightpdf')->__('Elements'),
			'comment' => $this->_elementsComment,
			'style' => 'height:250px;width:100%;',
			'required' => false,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_positions_summary_cdata')
		))->setRenderer($this->_defaultFieldRenderer);
	}

	protected function addPositionsPositionsFields(Varien_Data_Form_Element_Fieldset $fieldset) {
		$fieldset->addField('page_positions_position_margin', 'text', array(
			'name' => 'page_positions_position_margin[value]',
			'label' => Mage::helper('delightpdf')->__('Margin between Positions'),
			'comment' => $this->_valuesComment,
			'required' => false,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_positions_position_margin')
		))->setRenderer($this->_defaultFieldRenderer);

		$fieldset->addField('page_positions_position_cdata', 'textarea', array(
			'name' => 'page_positions_position_cdata[value]',
			'label' => Mage::helper('delightpdf')->__('Elements'),
			'comment' => $this->_elementsComment,
			'style' => 'height:250px;width:100%;',
			'required' => false,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_positions_position_cdata')
		))->setRenderer($this->_defaultFieldRenderer);
	}

	protected function addPositionsFooterFields(Varien_Data_Form_Element_Fieldset $fieldset) {
		$fieldset->addField('page_positions_footer_margin', 'text', array(
			'name' => 'page_positions_footer_margin[value]',
			'label' => Mage::helper('delightpdf')->__('Margin to Positions-Summary'),
			'comment' => $this->_valuesComment,
			'required' => false,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_positions_footer_margin')
		))->setRenderer($this->_defaultFieldRenderer);

		for ($i = 0; $i < $this->pdf->numPosition('page_positions_footer'); $i++) {
			$type = $this->pdf->getType('position', 'page_positions_footer', $i);

			$fieldset->addField('page_positions_footer_'.$i.'_type', 'select', array(
				'name' => 'page_positions_footer_'.$i.'_type[value]',
				'label' => Mage::helper('delightpdf')->__('Section type'),
				'required' => true,
				'values' => array(
					'custom' => Mage::helper('delightpdf')->__('Custom'),
					'discount' => Mage::helper('delightpdf')->__('Discount'),
					'tax' => Mage::helper('delightpdf')->__('Tax'),
					'shipping' => Mage::helper('delightpdf')->__('Shipping'),
					'adjustmentrefund' => Mage::helper('delightpdf')->__('Adjustment Refund'),
					'adjustmentfee' => Mage::helper('delightpdf')->__('Adjustment Fee'),
					'grandtotal' => Mage::helper('delightpdf')->__('Grandtotal')
				),
				'scope' => $this->getScope(),
				'scope_id' => $this->getScopeId(),
				'scope_label' => $this->getScopeLabel(),
				'can_use_default_value' => $this->canUseDefaultValue(),
				'inherit' => $this->pdf->isInherited('page_positions_footer_position['.$i.']_type')
			))->setRenderer($this->_defaultFieldRenderer);

			$fieldset->addField('page_positions_footer_'.$i.'_margin', 'text', array(
				'name' => 'page_positions_footer_'.$i.'_margin[value]',
				'label' => Mage::helper('delightpdf')->__('Margin below this Section'),
				'comment' => $this->_valuesComment,
				'required' => false,
				'scope' => $this->getScope(),
				'scope_id' => $this->getScopeId(),
				'scope_label' => $this->getScopeLabel(),
				'can_use_default_value' => $this->canUseDefaultValue(),
				'inherit' => $this->pdf->isInherited('page_positions_footer_position['.$i.']_margin')
			))->setRenderer($this->_defaultFieldRenderer);

			$fieldset->addField('page_positions_footer_'.$i.'_cdata', 'textarea', array(
				'name' => 'page_positions_footer_'.$i.'_cdata[value]',
				'label' => Mage::helper('delightpdf')->__('Elements for this Section'),
				'comment' => $this->_elementsComment,
				'style' => 'height:250px;width:100%;',
				'required' => false,
				'scope' => $this->getScope(),
				'scope_id' => $this->getScopeId(),
				'scope_label' => $this->getScopeLabel(),
				'can_use_default_value' => $this->canUseDefaultValue(),
				'inherit' => $this->pdf->isInherited('page_positions_footer_position['.$i.']_cdata')
			))->setRenderer($this->_defaultFieldRenderer);
		}

		/*
		// Discount
		$fieldset->addField('page_positions_footer_discount_margin', 'text', array(
			'name' => 'page_positions_footer_discount_margin[value]',
			'label' => Mage::helper('delightpdf')->__('Margin below'),
			'comment' => $this->_valuesComment,
			'required' => false,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_positions_footer_position[@type="discount"]_margin')
		))->setRenderer($this->_defaultFieldRenderer);

		$fieldset->addField('page_positions_footer_discount_cdata', 'textarea', array(
			'name' => 'page_positions_footer_discount_cdata[value]',
			'label' => Mage::helper('delightpdf')->__('Elements for Discount-Section'),
			'comment' => $this->_elementsComment,
			'style' => 'height:250px;width:100%;',
			'required' => false,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_positions_footer_position[@type="discount"]_cdata')
		))->setRenderer($this->_defaultFieldRenderer);

		// Taxes
		$fieldset->addField('page_positions_footer_tax_margin', 'text', array(
			'name' => 'page_positions_footer_tax_margin[value]',
			'label' => Mage::helper('delightpdf')->__('Margin below'),
			'comment' => $this->_valuesComment,
			'required' => false,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_positions_footer_position[@type="tax"]_margin')
		))->setRenderer($this->_defaultFieldRenderer);

		$fieldset->addField('page_positions_footer_tax_cdata', 'textarea', array(
			'name' => 'page_positions_footer_tax_cdata[value]',
			'label' => Mage::helper('delightpdf')->__('Elements for Tax-Section'),
			'comment' => $this->_elementsComment,
			'style' => 'height:250px;width:100%;',
			'required' => false,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_positions_footer_position[@type="tax"]_cdata')
		))->setRenderer($this->_defaultFieldRenderer);

		// Shipping Coast
		$fieldset->addField('page_positions_footer_shipping_margin', 'text', array(
			'name' => 'page_positions_footer_shipping_margin[value]',
			'label' => Mage::helper('delightpdf')->__('Margin below'),
			'comment' => $this->_valuesComment,
			'required' => false,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_positions_footer_position[@type="shipping"]_margin')
		))->setRenderer($this->_defaultFieldRenderer);

		$fieldset->addField('page_positions_footer_shipping_cdata', 'textarea', array(
			'name' => 'page_positions_footer_shipping_cdata[value]',
			'label' => Mage::helper('delightpdf')->__('Elements for Shipping-Section'),
			'comment' => $this->_elementsComment,
			'style' => 'height:250px;width:100%;',
			'required' => false,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_positions_footer_position[@type="shipping"]_cdata')
		))->setRenderer($this->_defaultFieldRenderer);

		// Adjustment Refund
		$fieldset->addField('page_positions_footer_adjustmentrefund_margin', 'text', array(
			'name' => 'page_positions_footer_adjustmentrefund_margin[value]',
			'label' => Mage::helper('delightpdf')->__('Margin below'),
			'comment' => $this->_valuesComment,
			'required' => false,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_positions_footer_position[@type="adjustmentrefund"]_margin')
		))->setRenderer($this->_defaultFieldRenderer);

		$fieldset->addField('page_positions_footer_adjustmentrefund_cdata', 'textarea', array(
			'name' => 'page_positions_footer_adjustmentrefund_cdata[value]',
			'label' => Mage::helper('delightpdf')->__('Elements for Refund-Section'),
			'comment' => $this->_elementsComment,
			'style' => 'height:250px;width:100%;',
			'required' => false,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_positions_footer_position[@type="adjustmentrefund"]_cdata')
		))->setRenderer($this->_defaultFieldRenderer);

		// Adjustment Fee
		$fieldset->addField('page_positions_footer_adjustmentfee_margin', 'text', array(
			'name' => 'page_positions_footer_adjustmentfee_margin[value]',
			'label' => Mage::helper('delightpdf')->__('Margin below'),
			'comment' => $this->_valuesComment,
			'required' => false,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_positions_footer_position[@type="adjustmentfee"]_margin')
		))->setRenderer($this->_defaultFieldRenderer);

		$fieldset->addField('page_positions_footer_adjustmentfee_cdata', 'textarea', array(
			'name' => 'page_positions_footer_adjustmentfee_cdata[value]',
			'label' => Mage::helper('delightpdf')->__('Elements for Fee-Section'),
			'comment' => $this->_elementsComment,
			'style' => 'height:250px;width:100%;',
			'required' => false,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_positions_footer_position[@type="adjustmentfee"]_cdata')
		))->setRenderer($this->_defaultFieldRenderer);

		// Grandtotal
		$fieldset->addField('page_positions_footer_grandtotal_margin', 'text', array(
			'name' => 'page_positions_footer_grandtotal_margin[value]',
			'label' => Mage::helper('delightpdf')->__('Margin below'),
			'comment' => $this->_valuesComment,
			'required' => false,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_positions_footer_position[@type="grandtotal"]_margin')
		))->setRenderer($this->_defaultFieldRenderer);

		$fieldset->addField('page_positions_footer_grandtotal_cdata', 'textarea', array(
			'name' => 'page_positions_footer_grandtotal_cdata[value]',
			'label' => Mage::helper('delightpdf')->__('Elements for Grandtotal-Section'),
			'comment' => $this->_elementsComment,
			'style' => 'height:250px;width:100%;',
			'required' => false,
			'scope' => $this->getScope(),
			'scope_id' => $this->getScopeId(),
			'scope_label' => $this->getScopeLabel(),
			'can_use_default_value' => $this->canUseDefaultValue(),
			'inherit' => $this->pdf->isInherited('page_positions_footer_position[@type="grandtotal"]_cdata')
		))->setRenderer($this->_defaultFieldRenderer);
		*/
	}

}
