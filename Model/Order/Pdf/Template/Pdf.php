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
 * Custom PDF-Generator, based on a XML-Configuration
 *
 * @category   Custom
 * @package    Delight_Delightpdf
 * @author     delight software gmbh <info@delightsoftware.com>
 */
class Delight_Delightpdf_Model_Order_Pdf_Template_Pdf {
	const DEFAULT_PAGE_BORDER = 42.525;
	const DEFAULT_PAGE_FORMAT = 'a4';

	protected $store;
	protected $fixedNum;
	protected $notInherited;
	protected $blockDBValue;

	protected $pdfType;
	protected $pdf;
	protected $currentPage;
	protected $style;

	protected $fontRegular;
	protected $fontBold;
	protected $fontItalic;
	protected $fontBoldItalic;
	protected $fontFixedRegular;
	protected $fontFixedBold;
	protected $fontFixedItalic;
	protected $fontFixedBoldItalic;

	private $config;
	private $pageFormat;

	private $currentInvoice;
	private $currentOrder;
	private $currentStore;
	private $isPaid;
	private $paymentPending;

	private $xBox;
	private $yBox;
	private $yBoxDelta;
	private $xBoxDelta;

	private $pageSize;
	private $pageBorder;
	private $yTop;
	private $yBottom;
	private $yBottomPositions;
	private $positionsFooterHeight;

	private $cummulativeTotal;
	private $currentPosition;
	private $currentPositionNumber;

	private $footerNode;
	private $numPages;
	private $pageNumber;

	/**
	 * Initialization
	 *
	 * @access public
	 */
	public function __construct() {
		$this->blockDBValue = array();
		$this->_initRenderer();
	}

	/**
	 * Set the regular font
	 *
	 * @param Zend_Pdf_Font $font Regular Font
	 * @param Zend_Pdf_Font $font Regular Font FixedWidth
	 * @access public
	 */
	public function setFontRegular(Zend_Pdf_Font $font = null, Zend_Pdf_Font $fixed = null) {
		$this->fontRegular = $font;
		$this->fontFixedRegular = $fixed;
	}

	/**
	 * Set the bold font
	 *
	 * @param Zend_Pdf_Font $font Bold Font
	 * @param Zend_Pdf_Font $font Bold Font FixedWidth
	 * @access public
	 */
	public function setFontBold(Zend_Pdf_Font $font = null, Zend_Pdf_Font $fixed = null) {
		$this->fontBold = $font;
		$this->fontFixedBold = $fixed;
	}

	/**
	 * Set the italic font
	 *
	 * @param Zend_Pdf_Font $font Italic Font
	 * @param Zend_Pdf_Font $font Italic Font FixedWidth
	 * @access public
	 */
	public function setFontItalic(Zend_Pdf_Font $font = null, Zend_Pdf_Font $fixed = null) {
		$this->fontItalic= $font;
		$this->fontFixedItalic= $fixed;
	}

	/**
	 * Set the bold and italic font
	 *
	 * @param Zend_Pdf_Font $font Bold and Italic Font
	 * @param Zend_Pdf_Font $font Bold and Italic Font FixedWidth
	 * @access public
	 */
	public function setFontBoldItalic(Zend_Pdf_Font $font = null, Zend_Pdf_Font $fixed = null) {
		$this->fontBoldItalic = $font;
		$this->fontFixedBoldItalic = $fixed;
	}

	/**
	 * Return the Filename for a specific PDF-Template-File
	 *
	 * @param string $template
	 * @param int $storeId
	 * @return string
	 */
	public function getPdfTemplatePath($template, $storeId=null) {
		$template = 'delightpdf/'.$template.'.xml';
		$package = Mage::getStoreConfig('design/package/name', $storeId);
		$file = Mage::getBaseDir('design').DS.Mage::getDesign()->getTemplateFilename($template, array('_relative'=>true, '_area'=>'frontend', '_package'=>$package));

		// If there is no PDF-Template look in the default-folder
		// TODO: Check if this is really needed: getFallbackTheme() calls getFilename() which has a "Fallback"
		if (!is_file($file)) {
			$package = 'default';
			$file = Mage::getBaseDir('design').DS.Mage::getDesign()->getTemplateFilename($template, array('_relative'=>true, '_area'=>'frontend', '_package'=>$package));
		}
		return $file;
	}

	/**
	 * Parse and initialize the PDF-Document based on the Template
	 *
	 * @param string $template relative path inside your store-TemplatePath to the template
	 * @param int $storeId StoreID - needed to get the right Template from an AdminRequest
	 * @access public
	 * @throws Exception if the PDF-Template does not exist
	 */
	public function parseTemplate($template, $storeId=null) {
		$this->store = is_numeric($storeId) ? (int)$storeId : null;
		$this->pdfType = strtolower($template);

		$file = $this->getPdfTemplatePath($template, $storeId);
		if (is_file($file)) {
			$this->config = new DOMDocument();
			$this->config->preserveWhiteSpace = true;
			$this->config->formatOutput = true;
			$this->config->load($file);
			$this->config->formatOutput = true;
			$this->_parsePageConfig();

		} else {
			$this->config = null;
			Mage::throwException('Unable to parse PDF-Layout '.$template.': '.$file);
		}
	}

	/**
	 * Load a PDF-Template-Configuration by the given DOMDocument
	 *
	 * @param DOMDocument $dom PDF-Configuration DOM
	 * @access public
	 */
	public function parseTemplateByDOMDocument(DOMDocument $dom) {
		$this->config = $dom;
		$this->notInherited = array(0, 0); // we don't need the store-configuration if we use a DOMDocument
		$this->_parsePageConfig();
	}

	/**
	 * Create the PDF-Document for all Invoices in $invoices
	 *
	 * @param array $invoices all Invoices
	 * @access public
	 */
	public function createPdf(array $invoices) {
		foreach ($invoices as $invoice) {
			$this->currentInvoice = $invoice;
			$this->currentOrder = $this->currentInvoice->getOrder();
			$this->currentStore = $this->currentInvoice->getStore();
			$this->store = $this->currentStore->getId();
			//$this->isPaid = ($this->currentInvoice->getState() == Mage_Sales_Model_Order_Invoice::STATE_PAID);
			//$this->isPaid = ($this->currentOrder->getStatus() == Mage_Sales_Model_Order::STATE_COMPLETE);
			$this->isPaid = ($this->currentOrder->getState() == Mage_Sales_Model_Order::STATE_COMPLETE) || ($this->currentOrder->getState() == Mage_Sales_Model_Order::STATE_PROCESSING) || ($this->currentInvoice->getState() == Mage_Sales_Model_Order_Invoice::STATE_PAID);
			$this->paymentPending = ($this->currentOrder->getState() == Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) || ($this->currentOrder->getState() == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);

			$pdfTitle = Mage::helper('sales')->__('Invoice from ').': '.$this->currentStore->getWebsite()->name;
			$this->_setMetadata($pdfTitle);
			$this->_createPage();

			$this->cummulativeTotal = 0;
			$this->_createOrderPositions();
		}
		$this->_createPageFooter();
	}

	/**
	 * Return the PDF
	 *
	 * @return Zend_Pdf
	 * @access public
	 */
	public function getPdf() {
		return $this->pdf;
	}

	/**
	 * Add an empty fixed-block to the PDF-Configuration
	 *
	 * @param double $x X-Position
	 * @param double $y Y-position
	 * @param mixed $page Show on Page (Number, first, last, each, all)
	 * @param string $content
	 * @access public
	 */
	public function addFixedBlock($x, $y, $page, $content) {
		$fixed = $this->config->createElement('fixed');
		$fixed->setAttribute('page', $page);
		$fixed->setAttribute('x', $x);
		$fixed->setAttribute('y', $y);
		if (!empty($content)) {
			$d = new DOMDocument();
			$d->loadXML('<root>'.$content.'</root>');
			foreach ($d->firstChild->childNodes as $n) {
				$fixed->appendChild($fixed->ownerDocument->importNode($n->cloneNode(true), true));
			}
		}
		$this->config->getElementsByTagName('page')->item(0)->appendChild($fixed);
	}

	/**
	 * Get the PDF-Configuration as a DOMDocument
	 *
	 * @return DOMDocument
	 * @access public
	 */
	public function getDomConfig() {
		return $this->config;
	}

	/**
	 * Catch all method-calls
	 * Used from Adminhtml to get values from the XML-Template
	 *
	 * @param string $method called method-name
	 * @param array $args arguments passed to the Method
	 * @return mixed
	 * @access public
	 */
	public function __call($method, array $args) {
		switch (substr($method, 0, 3)) {
			case 'num':
				// $args = [ parentTagNames=null|string_string ]
				$name = strtolower(substr($method, 3));
				$parent = isset($args[0]) ? explode('_', $args[0]) : array();
				$node = $this->_getDOMNodeByArray(null, $parent);
				if (!is_null($node)) {
					$nodeList = $node->getElementsByTagName($name);
					return $nodeList->length;
				}
				return 0;
				break;

			case 'get':
				// $args = [ tagName=string, parentTagNames=string_string, number=0 ]
				$name = strtolower(substr($method, 3));
				$tag = isset($args[0]) ? $args[0] : 'pdf'; // If no Element is given, we use the main pdf-tag as element
				$parent = isset($args[1]) ? explode('_', $args[1]) : array();
				$num = isset($args[2]) ? (int)$args[2] : 0;
				$node = $this->_getDOMNodeByArray($tag.'['.($num+1).']', $parent);
				$value = '';

				if (!is_null($node)) {
					if ($name == 'cdata') {
						$d = new DOMDocument();
						$d->formatOutput = true;
						$n = $d->importNode($node->cloneNode(true), true);
						$d->appendChild($n);
						$value = $d->saveXML($n);
						$value = substr($value, strpos($value, '>')+1, -(strlen($n->nodeName)+4));
						$value = preg_replace('/^\s+/smi', '', $value);

					} else if ($node->hasAttribute($name)) {
						$value = strtolower($node->getAttribute($name));
					}
				}

				return utf8_decode($value);
				break;
		}
		return null;
	}

	/**
	 * Get a DOM-Node defined by an Array of Nodes
	 * Parent-Nodes can feature [x] which means that Node number X (beginning is 1 not 0) is meant
	 * ParentNodes and nodename are concated to a xPath-QueryString
	 *
	 * @param string $name NodeName to get
	 * @param array $parent List of nodes the Node $name is in
	 * @return DOMNodeList or null
	 * @access protected
	 */
	protected function _getDOMNodeByArray($name, array $parent) {
		if (!is_array($parent)) {
			$parent = array($name);
		}
		if (!empty($name)) {
			$parent[] = $name;
		}
		$xPath = new DOMXPath($this->config);
		$elements = $xPath->query('//pdf/'.implode('/', $parent));
		if ($elements->length > 0) {
			return $elements->item(0);
		}
		return null;
	}

	/**
	 * Initialize the PDF-Renderer and set the default fonts
	 *
	 * @access protected
	 */
	protected function _initRenderer() {
		$this->pdf = new Zend_Pdf();
		$this->style = new Zend_Pdf_Style();

		$this->fontRegular = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
		$this->fontBold = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);
		$this->fontItalic = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_OBLIQUE);
		$this->fontBoldItalic = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD_OBLIQUE);

		$this->fontFixedRegular = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_COURIER);
		$this->fontFixedBold = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_COURIER_BOLD);
		$this->fontFixedItalic = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_COURIER_OBLIQUE);
		$this->fontFixedBoldItalic = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_COURIER_BOLD_OBLIQUE);
	}

	/**
	 * Check if the given value is used from the original or not
	 *
	 * @param string $key Database Key
	 * @return mixed
	 * @access private
	 */
	public function isInherited($key) {
		return !in_array('pdf_'.$key, $this->notInherited) && !in_array($key, $this->notInherited);
	}

	/**
	 * Set a value from the AdminInterface
	 *
	 * @param string $key Param name
	 * @param string $val
	 * @access public
	 * @return void
	 */
	public function setConfig($key, $val) {
		if (!($this->config instanceof DOMDocument)) {
			return;
		}
		$xPath = new DOMXPath($this->config);

		if (substr($key, 0, 4) != 'pdf_') {
			$key = 'pdf_'.$key;
		}
		$key = explode('_', $key);
		$attr = array_pop($key);
		$expr = '//'.implode('/', $key);

		$list = $xPath->query($expr);
		$node = $list->item(0);
		$key = implode('_', $key).'_'.$attr;
		$this->notInherited[] = $key;

		if ($node instanceof DOMNode) {
			if ($attr == 'cdata') {
				try  {
					// remove all nodes
					while ($node->childNodes->length > 0) {
						$node->removeChild($node->firstChild);
					}
					if (!empty($val)) {
						$d = new DOMDocument();
						$d->loadXML('<root>'.$val.'</root>');

						// add the new nodes
						foreach ($d->firstChild->childNodes as $n) {
							if ($n->nodeType == XML_ELEMENT_NODE) {
								$node->appendChild($node->ownerDocument->importNode($n->cloneNode(true), true));
							}
						}
					} else if (empty($val) && ((substr($key, 0, 14) == 'pdf_page_fixed') || (substr($key, 0, 34) == 'pdf_page_positions_footer_position'))) {
						$node->parentNode->removeChild($node);
					}
				} catch (Exception $e) {  }

			} else if (($node->nodeName == 'page') && ($attr == 'size')) {
				$v = explode('_', $node->getAttribute('format'));
				$v[0] = $val;
				$node->setAttribute('format', implode('_', $v));

			} else if (($node->nodeName == 'page') && ($attr == 'orientation')) {
				$v = explode('_', $node->getAttribute('format'));
				$v[1] = $val;
				$node->setAttribute('format', implode('_', $v));

			} else if ($node->hasAttribute($attr)) {
				$node->setAttribute($attr, $val);
			}

		} else {
			if (substr($expr, 0, 16) == '//pdf/page/fixed') {
				// If no node is found, add as many fixed blocks as needed if this is a fixed block value
				$this->addFixedBlock(0, 0, 1, '');

				// Change the number of the fixed block to the inserted one
				// edit: this is dangerous because a later fixed block then may override the current one
				//$list = $xPath->query('//pdf/page/fixed');
				//$key = preg_replace('/pdf_page_fixed_(\d+)/smie', '"pdf_page_fixed_".$list->length', $key);

				$this->setConfig($key, $val);

			} else if (substr($expr, 0, 36) == '//pdf/page/positions/footer/position') {
				$node = $this->config->createElement('position');
				$node->setAttribute('margin', '0');
				$node->setAttribute('type', 'custom');
				$node->appendChild($this->config->createElement('element'));

				$xPath->query('//pdf/page/positions/footer')->item(0)->appendChild($node);
				$this->setConfig($key, $val);
			}
		}
	}

	/**
	 * Define a Parameter to not be loaded from the Database
	 * Used by save and previes PDFs
	 *
	 * @param string $key key to not be loaded from the DB
	 * @access public
	 * @return void
	 */
	public function setAsInherited($key) {
		if (in_array($key, $this->blockDBValue)) return;
		if (substr($key, 0, 4) != 'pdf_') {
			$this->blockDBValue[] = $key;
			$this->blockDBValue[] = 'pdf_'.$key;
		} else {
			$this->blockDBValue[] = substr($key, 4);
			$this->blockDBValue[] = $key;
		}
	}

	/**
	 * Get all values from the current PDF to save on the DB
	 * @return array
	 * @access public
	 */
	public function getSaveValues() {
		$back = array();
		if ($this->config instanceof DOMDocument) {
			$xPath = new DOMXPath($this->config);
			$keys = array();

			foreach ($this->notInherited as $key) {
				if (in_array($key, $this->blockDBValue) || in_array($key, $keys)) {
					continue;
				}
				$keys[] = $key;

				$key = explode('_', $key);
				$attr = array_pop($key);
				if ($attr == 'content') {
					$attr = 'cdata';
				}

				$expr = '//'.implode('/', $key);
				$list = $xPath->query($expr);
				$node = $list->item(0);
				if ($node instanceof DOMNode) {
					$v = new stdClass();
					$v->tag = implode('_', $key);
					$v->attr = $attr;
					$v->value = '';
					$v->type = $this->pdfType;
					$v->store = $this->store;

					if ($attr == 'cdata') {
						if ($node->childNodes->length > 0) {
							$d = new DOMDocument();
							$nd = $d->createElement('root');
							$nd = $d->appendChild($nd);
							foreach ($node->childNodes as $n) {
								if ($n->nodeType == XML_ELEMENT_NODE) {
									$nd->appendChild($nd->ownerDocument->importNode($n->cloneNode(true), true));
								}
							}
							$d->preserveWhiteSpace = false;
							$d->formatOutput = true;
							$v->value = str_replace('<root>', '', str_replace('</root>', '', $d->saveXML($nd)));
							unset($d);
						}

					} else if (($node->nodeName == 'page') && ($attr == 'size')) {
						$_v = explode('_', $node->getAttribute('format'));
						$v->value = $_v[0];

					} else if (($node->nodeName == 'page') && ($attr == 'orientation')) {
						$_v = explode('_', $node->getAttribute('format'));
						$v->value = $_v[1];

					} else if ($node->hasAttribute($attr)) {
						$v->value = $node->getAttribute($attr);
					}
					$back[] = $v;
				}
			}
		}
		return $back;
	}

	/**
	 * Apply all Store-Values from the Database to the real XML
	 *
	 * @access protected
	 * @return void
	 */
	protected function _applyStoreConfig() {
		if (empty($this->notInherited)) {
			$this->notInherited = array();
			$keys = array();

			if ($this->config instanceof DOMDocument) {
				$resource = Mage::getResourceModel('delightpdf/page_collection')
					->addFieldToFilter('store_id', $this->store)
					->addFieldToFilter('pdf', $this->pdfType)
					->load();

				$xPath = new DOMXPath($this->config);
				foreach ($resource as $res) {
					$data = $res->getData();
					if ($data['value'] == 'content') {
						$data['value'] = 'cdata';
					}

					$key = $data['key'].'_'.$data['value'];
					if (in_array($key, $this->blockDBValue) || in_array($key, $this->notInherited)) {
						continue;
					}
					$this->notInherited[] = $key;

					$expr = '//'.str_replace('_', '/', $data['key']);
					$list = $xPath->query($expr);
					$node = $list->item(0);
					if (!($node instanceof DOMNode)) {
						continue;
					}

					// Set the values on the DOM-Nodes
					if ($data['value'] == 'cdata') {
						try {
							$d = new DOMDocument();
							$d->loadXML('<root>'.$data['elements'].'</root>');

							// remove all nodes
							while ($node->childNodes->length > 0) {
								$node->removeChild($node->firstChild);
							}

							// add the new nodes
							foreach ($d->firstChild->childNodes as $n) {
								if ($n->nodeType == XML_ELEMENT_NODE) {
									$node->appendChild($node->ownerDocument->importNode($n->cloneNode(true), true));
								}
							}
							unset($d);
						} catch (Exception $e) {}

					} else if (($node->nodeName == 'page') && ($data['value'] == 'size')) {
						$v = explode('_', $node->getAttribute('format'));
						$v[0] = $data['elements'];
						$node->setAttribute('format', implode('_', $v));

					} else if (($node->nodeName == 'page') && ($data['value'] == 'orientation')) {
						$v = explode('_', $node->getAttribute('format'));
						$v[1] = $data['elements'];
						$node->setAttribute('format', implode('_', $v));

					} else if ($node->hasAttribute($data['value'])) {
						$node->setAttribute($data['value'], $data['elements']);
					}
				}
				unset($resource);
			}
		}
	}

	/**
	 * Parse the PAGE-Part of the configuration
	 *
	 * @access protected
	 * @throws Exception if the Configuration is not valid or no PAGE-Node could be found
	 */
	protected function _parsePageConfig() {
		if ($this->config instanceof DOMDocument) {
			$this->_applyStoreConfig();

			$page = $this->_getNode('page');
			if (!($page instanceof DOMElement)) {
				Mage::throwException('No "page"-Tags found in PDF-Template');
			}

			switch ( strtolower($this->_getAttribute($page, 'type', self::DEFAULT_PAGE_FORMAT)) ) {
				case 'letter':
				case 'letter_portrait':
					$this->pageFormat = Zend_Pdf_Page::SIZE_LETTER;
					$this->yBottom = 0;
					$this->yTop = 792;
					$this->pageSize = array(612,792);
					break;

				case 'letter_landscape':
					$this->pageFormat = Zend_Pdf_Page::SIZE_LETTER_LANDSCAPE;
					$this->yBottom = 0;
					$this->yTop = 612;
					$this->pageSize = array(792,612);
					break;

				case 'a4_landscape':
					$this->pageFormat = Zend_Pdf_Page::SIZE_A4_LANDSCAPE;
					$this->yBottom = 0;
					$this->yTop = 595;
					$this->pageSize = array(842,595);
					break;

				case 'a4':
				case 'a4_portrait':
				default:
					$this->pageFormat = Zend_Pdf_Page::SIZE_A4;
					$this->yBottom = 0;
					$this->yTop = 842;
					$this->pageSize = array(595,842);
					break;
			}
		} else {
			Mage::throwException('Configuration is not a valid XML or could not be loaded');
		}
	}

	/**
	 * Set PDF-Title and other PDF-Metadata
	 *
	 * @param string $title PDF-Title
	 * @access private
	 */
	private function _setMetadata($title='') {
		$this->pdf->properties['Title'] = (string)$title;
		$this->pdf->properties['Author'] = 'delight PHP-PdfTemplating for MagentoCommerce - http://www.delight.ch/';
		$this->pdf->properties['Creator'] = 'delight PHP-PdfTemplating for MagentoCommerce - http://www.delight.ch/';
		$this->pdf->properties['Producer'] = 'delight PHP-PdfTemplating for MagentoCommerce - http://www.delight.ch/';
	}

	/**
	 * Get all Child-Nodes with TagName $name as a DOMNodeList from config or given $node
	 *
	 * @param string $name TagName
	 * @param DOMElement $node optional, Node to get tags from instead of the config
	 * @return DOMNodeList all Nodes
	 * @access private
	 */
	private function _getNodelist($name, DOMElement $node=null) {
		if ($node instanceof DOMElement) {
			return $node->getElementsByTagName($name);
		}
		return $this->config->getElementsByTagName($name);
	}

	/**
	 * Get a single Child-Node
	 *
	 * @param string $name TagName
	 * @param DOMElement $node optional, Node to get the CHild from instead of the config
	 * @param int $number optional, NodeNumber instead the first one
	 * @return DOMElement The Node
	 * @access private
	 */
	private function _getNode($name, DOMElement $node=null, $number=0) {
		$list = $this->_getNodelist($name, $node);
		if ( ($list instanceof DOMNodeList) && ($list->length > $number) ) {
			return $list->item($number);
		}
		return null;
	}

	/**
	 * Get an AttributeValue
	 *
	 * @param DOMElement $node Node to get the Attribute from
	 * @param string $attr AttributeName
	 * @param string $default Default-Value if there is no such Attribute
	 * @return string
	 * @access private
	 */
	private function _getAttribute(DOMElement $node, $attr, $default=null) {
		if ($node->hasAttribute($attr)) {
			return $node->getAttribute($attr);
		}
		return $default;
	}

	/**
	 * Get a Zend_Pdf_Color defined by given $color
	 *
	 * The ColorDefinition can be:
	 *   for Gray:   gray:0-1
	 *   for Color:  #RRGGBB
	 *
	 * @param string $color Color-Definition
	 * @return Zend_Pdf_Color
	 * @access private
	 */
	private function _getColorFromString($color) {
		if (substr($color, 0, 4) == 'gray') {
			$color = new Zend_Pdf_Color_GrayScale(substr($color, 5));
		} else if (substr($color, 0, 1) == '#') {
			$color = new Zend_Pdf_Color_Html($color);
		} else {
			$color = new Zend_Pdf_Color_GrayScale(1);
		}
		return $color;
	}

	/**
	 * Create a new PDF-Page
	 *
	 * @access private
	 */
	private function _createPage() {
		$this->currentPage = $this->pdf->newPage($this->pageFormat);
		$this->pdf->pages[] = $this->currentPage;
		$firstPage = (count($this->pdf->pages) == 1);

		$this->yBottom = 0;
		$this->yTop = $this->pageSize[1];

		$node = $this->_getNode('page');
		if ($node instanceof DOMElement) {
			$this->fixedNum = 0;
			$node = $node->firstChild;
			while ($node instanceof DOMNode) {

				if (strtolower($node->nodeName) == 'header') {
					$this->_getBoxPosition($node);
					$elements = $this->_getNodelist('element', $node);
					if ($elements instanceof DOMNodeList) {
						foreach ($elements as $elem) {
							$this->_parseElementNode($elem);
						}
					}

				} elseif (strtolower($node->nodeName) == 'footer') {
					$this->_getBoxPosition($node);
					$this->yBottom = ($this->yBox > $this->yBottom) ? $this->yBox : $this->yBottom;
					$this->footerNode = $node;

				} else if (strtolower($node->nodeName) == 'fixed') {
					if ( $firstPage && in_array($this->_getAttribute($node, 'page'), array('first', 1, 'each', 'all')) ) {
						$this->_getBoxPosition($node);
						$this->_placeFixedBlock($node);
					}
					$this->fixedNum++;
				}

				$node = $node->nextSibling;
			}
		}

	}

	/**
	 * Create the Footer on each PDF-Page
	 *
	 * @access private
	 */
	private function _createPageFooter() {
		// Add fixed blocks to the last Page
		$fixed = $this->_getNodelist('fixed');
		if ($fixed instanceof DOMNodeList) {
			foreach ($fixed as $elem) {
				if ($this->_getAttribute($elem, 'page') == 'last') {
					$this->_getBoxPosition($elem);
					$this->_placeFixedBlock($elem);
				}
			}
		}

		// Add the Footer to each Page
		if ($this->footerNode instanceof DOMNode) {
			$this->pageNumber = 1;
			$this->numPages = count($this->pdf->pages);
			foreach ($this->pdf->pages as $page) {
				$this->currentPage = $page;
				$this->_getBoxPosition($this->footerNode);
				$elements = $this->_getNodelist('element', $this->footerNode);
				if ($elements instanceof DOMNodeList) {
					foreach ($elements as $elem) {
						$this->_parseElementNode($elem);
					}
				}
				$this->pageNumber += 1;
			}
		}
	}

	/**
	 * Draw the Header for Posittions-Table
	 *
	 * @param DOMElement $node Header-Node from Configuration
	 * @access private
	 */
	private function _createPositionsHeader(DOMElement $node) {
		$this->yBottomPositions = $this->yBox;
		$elements = $this->_getNodelist('element', $node);
		if ($elements instanceof DOMNodeList) {
			foreach ($elements as $elem) {
				$y = $this->_parseElementNode($elem);
				$this->yBottomPositions = ($y < $this->yBottomPositions) ? $y : $this->yBottomPositions;
			}
		}
	}

	/**
	 * Draw a position inside the Positions-Table
	 *
	 * @param DOMElement $node Position-Node from Configuration
	 * @return float Height of the current Position
	 * @access private
	 */
	private function _createPositionsEntry(DOMElement $node, $show=true) {
		$back = $this->yBottomPositions;
		$elements = $this->_getNodelist('element', $node);
		if ($elements instanceof DOMNodeList) {
			foreach ($elements as $elem) {
				$y = $this->_parseElementNode($elem, $show);
				if ($show) {
					$this->yBottomPositions = ($y < $this->yBottomPositions) ? $y : $this->yBottomPositions;
				}
				$back = ($y < $back) ? $y : $back;
			}
		}
		return $this->yBottomPositions - $back;
	}

	/**
	 * Draw the Positions-Table Footer
	 *
	 * @param DOMElement $node Footer-Node from Configuration
	 */
	private function _createPositionsFooter(DOMElement $node) {
		$elements = $this->_getNodelist('position', $node);
		if ($elements instanceof DOMNodeList) {
			foreach ($elements as $elem) {
				$type = $this->_getAttribute($elem, 'type', null);
				$margin = $this->_getAttribute($elem, 'margin', 0);

				// some elements should only be showed if there is a value
				$posElems = null;
				switch (strtolower($type)) {
					case 'discount':
						if ((float)$this->currentInvoice->getDiscountAmount()) {
							$posElems = $this->_getNodeList('element', $elem);
						}
						break;

					case 'tax':
						if ((float)$this->currentInvoice->getTaxAmount()) {
							$posElems = $this->_getNodeList('element', $elem);
						}
						break;

					case 'shipping':
						if ((float)$this->currentInvoice->getShippingAmount()) {
							$posElems = $this->_getNodeList('element', $elem);
						}
						break;

					case 'adjustmentrefund':
						if ($this->currentInvoice->getAdjustmentPositive()) {
							$posElems = $this->_getNodeList('element', $elem);
						}
						break;

					case 'adjustmentfee':
						if ($this->currentInvoice->getAdjustmentNegative()) {
							$posElems = $this->_getNodeList('element', $elem);
						}
						break;

					default:
						$posElems = $this->_getNodeList('element', $elem);
						break;
				}

				if ($posElems instanceof DOMNodeList) {
					$this->yBox -= $margin;
					foreach ($posElems as $pElem) {
						$y = $this->_parseElementNode($pElem);
						$this->yBottomPositions = ($y < $this->yBottomPositions) ? $y : $this->yBottomPositions;
					}
					$this->yBox = $this->yBottomPositions;
				}
			}
		}
	}

	/**
	 * Calculate the maximum height the Footer from Positions-Table could be
	 * this we need to know when to create a new PDF-Page
	 *
	 * @param DOMNode $node Footer-node from Configuration
	 * @access private
	 */
	private function _calculatePositionFooterHeight(DOMNode $node) {
		$elements = $this->_getNodelist('element', $node);
		$this->positionsFooterHeight = 0;
		if ($elements instanceof DOMNodeList) {
			if ($node->hasAttribute('margin')) {
				$this->positionsFooterHeight = (float)$node->getAttribute('margin');
			}
			foreach ($elements as $elem) {
				$y = $this->_parseElementNode($elem, false);
				$this->positionsFooterHeight += ($this->yTop - $y);
			}
		}
	}

	/**
	 * Create all Positions (incl. Positions-Header) from current Invoice
	 *
	 * @access private
	 */
	private function _createOrderPositions() {
		// Get XML-Configuration-Nodes for Header, Footer, Summary and positions
		$positions = $this->_getNode('positions');
		$header = $this->_getNode('header', $positions);
		$summary = $this->_getNode('summary', $positions);
		$footer = $this->_getNode('footer', $positions);
		$position = $this->_getNode('position', $positions);

		$boxPos = new stdClass();
		$boxPos->x = $this->_getAttribute($positions, 'x', 0);
		$boxPos->y = $this->_getAttribute($positions, 'y', 0);
		$boxPos->positionMargin = $this->_getAttribute($position, 'margin', 0);
		$boxPos->headerMargin = ($header instanceof DOMNode) ? $this->_getAttribute($header, 'margin', 0) : 0;
		$boxPos->summaryMargin = ($summary instanceof DOMNode) ? $this->_getAttribute($summary, 'margin', 0) : 0;
		$boxPos->footerMargin = ($footer instanceof DOMNode) ? $this->_getAttribute($footer, 'margin', 0) : 0;
		$this->xBox = $boxPos->x;
		$this->yBox = $boxPos->y;

		if ($this->yTop < $this->yBox) {
			$this->yBox = $this->yTop;
		} else {
			$this->yTop = $this->yBox;
		}
		$this->yBottomPositions = $this->yBox;
		//$this->yTop = ($this->yTop > $boxPos->y) ? $boxPos->y : $this->yTop;

		$this->positionsFooterHeight = 0;
		if ($footer instanceof DOMNode) {
			$this->_calculatePositionFooterHeight($footer);
		}

		// Get the summary-height
		$summaryHeight = $boxPos->summaryMargin;
		if ($summary instanceof DOMNode) {
			$summaryHeight += $this->_createPositionsEntry($summary, false);
		}

		// Create the positions
		$this->currentPositionNumber = 0;
		$this->yBox -= $boxPos->headerMargin;
		if ($header instanceof DOMNode) {
			$this->_createPositionsHeader($header);
		}
		$this->yBox = $this->yBottomPositions;

		$list = $this->currentInvoice->getAllItems();
		if (count($list) <= 0) {
			$list = $this->currentInvoice->getOrder()->getAllItems();
		}

		foreach ($list as $item) {
			if ($item->getOrderItem() && $item->getOrderItem()->getParentItem()) {
				continue;
			}
			$this->currentPositionNumber += 1;
			$this->currentPosition = $item;
			$this->cummulativeTotal += $this->currentPosition->getRowTotal();

			// Check if the next item can be placed on the current page
			$nextPosHeight = $this->_createPositionsEntry($position, false);
			$nextPosHeight += $boxPos->positionMargin;

			// Insert a new Page
			if (($this->yBottomPositions - $nextPosHeight - $summaryHeight - $this->yBottom) <= $boxPos->summaryMargin) {
				// Add the Summary and create the new page
				$this->yBox -= $boxPos->summaryMargin;
				if ($summary instanceof DOMNode) {
					$this->_createPositionsEntry($summary);
				}
				$this->_createPage();

				$this->xBox = $boxPos->x;
				$this->yBox = $boxPos->y;
				if ($this->yTop < $this->yBox) {
					$this->yBox = $this->yTop;
				} else {
					$this->yTop = $this->yBox;
				}

				$this->yBox -= $boxPos->headerMargin;
				if ($header instanceof DOMNode) {
					$this->_createPositionsHeader($header);
					$this->yBox = $this->yBottomPositions;
				} else {
					$this->yBottomPositions = $this->yBox;
				}
			}

			$this->yBox -= $boxPos->positionMargin;
			$this->_createPositionsEntry($position);
			$this->yBox = $this->yBottomPositions;
		}

		// Add the summary
		$this->yBox -= $boxPos->summaryMargin;
		if ($summary instanceof DOMNode) {
			$this->_createPositionsEntry($summary);
		}
		$this->yBox = $this->yBottomPositions;

		// Insert a new Page if there is no space for the summary
		if (($this->yBottomPositions - $this->positionsFooterHeight) <= $this->yBottom) {
			$this->_createPage();

			$this->xBox = $boxPos->x;
			$this->yBox = $boxPos->y;
			if ($this->yTop < $this->yBox) {
				$this->yBox = $this->yTop;
			} else {
				$this->yTop = $this->yBox;
			}

			$this->yBox -= $boxPos->headerMargin;
			if ($header instanceof DOMNode) {
				$this->_createPositionsHeader($header);
				$this->yBox = $this->yBottomPositions;
			} else {
				$this->yBottomPositions = $this->yBox;
			}
		}

		$this->yBox -= $boxPos->footerMargin;
		if ($footer instanceof DOMNode) {
			$this->_createPositionsFooter($footer);
		}
		$this->yBox = $this->yBottomPositions;
	}

	/**
	 * Set all xBox* and yBox* variables defined by the given Block-Element
	 * A Block-Element is one of: header,fixed,positions,position,summary,footer
	 *
	 * @param DOMElement $node a Block-Element
	 * @access private
	 */
	private function _getBoxPosition(DOMElement $node) {
		$this->xBox = $this->_getAttribute($node, 'x', 0);
		$this->yBox = $this->_getAttribute($node, 'y', $this->pageSize[1]);
		$this->xBoxDelta = $this->_getAttribute($node, 'dx', 0);
		$this->yBoxDelta = $this->_getAttribute($node, 'dy', 0);
	}

	/**
	 * Draw a fixed BlockElement on the current PDF-Page
	 *
	 * @param DOMElement $node a Block-Element from type "fixed"
	 * @access private
	 */
	private function _placeFixedBlock(DOMElement $node) {
		$elements = $this->_getNodelist('element', $node);
		if ($elements instanceof DOMNodeList) {
			foreach ($elements as $elem) {
				$y = $this->_parseElementNode($elem);
				$this->yTop = ($y < $this->yTop) ? $y - $this->yBoxDelta : $this->yTop;
			}
		}
	}

	/**
	 * Parse an Element-Config-Node and draw it
	 *
	 * @param DOMElement $node Element-Node from Configuration
	 * @param boolean $show Draw the Element or not (if not, this function can be used to calculate height of blocks etc.)
	 * @return float lower y position of the element
	 * @access private
	 */
	private function _parseElementNode(DOMElement $node, $show=true) {
		// First: check if this Element should be shown based on the Payment-State of the Invoice
		$paid = strtolower($this->_getAttribute($node, 'ifpaid'));
		if ( ($this->isPaid && ($paid == 'hide')) || (!$this->isPaid && ($paid == 'show')) ) {
			return $this->yBox;
		}

		// Second: check if this Element should be shown if the payment is pending
		$pending = strtolower($this->_getAttribute($node, 'ifpending'));
		if ( ($this->paymentPending && ($pending == 'hide')) || (!$this->paymentPending && ($pending == 'show')) ) {
			return $this->yBox;
		}

		// Third: show the Element
		$y = 0;
		$type = strtolower($this->_getAttribute($node, 'type'));
		$color = $this->_getColorFromString($this->_getAttribute($node, 'color', null));

		switch ($type) {
			case 'rect':
			case 'line':
				$x1 = $this->_getAttribute($node, 'x1');
				$y1 = $this->_getAttribute($node, 'y1');
				$x2 = $this->_getAttribute($node, 'x2');
				$y2 = $this->_getAttribute($node, 'y2');
				$width = (double)$this->_getAttribute($node, 'width', 0.5);

				if ( ($x1 != null) && ($x2 != null) && ($y1 != null) && ($y2 != null) ) {
					$y = ($y2 > $y1) ? $y2 : $y1; // The bigger y-value is returned
					if ($show) {
						$this->currentPage->setLineColor($color);
						$this->currentPage->setLineWidth($width);
						if ($type == 'line') {
							$this->currentPage->drawLine($this->xBox + $x1, $this->yBox - $y1, $this->xBox + $x2, $this->yBox - $y2);
						} else {
							$this->currentPage->setFillColor( $this->_getColorFromString($this->_getAttribute($node, 'fill', null)) );
							$this->currentPage->drawRectangle($this->xBox + $x1, $this->yBox - $y1, $this->xBox + $x2, $this->yBox - $y2, Zend_Pdf_Page::SHAPE_DRAW_FILL);
						}
					}
				}
				break;

			case 'text':
				$x = $this->_getAttribute($node, 'x');
				$y = $this->_getAttribute($node, 'y');
				$width = $this->_getAttribute($node, 'width');
				$length = $this->_getAttribute($node, 'length');
				$format = strtolower($this->_getAttribute($node, 'format', 'normal'));
				$align = $this->_getAttribute($node, 'align', 'left');
				$size = $this->_getAttribute($node, 'size', 7);
				$style = $this->_parseTextStyle($this->_getAttribute($node, 'style', ''));
				$text = $this->_getNodeText($node);
				$text = trim($text);
				$text = explode("\n", $text);
				$stringWidth = 0;
				$lineSpaceFact = 1.1;

				if ($show) {
					switch ($format) {
						case 'bold':
							$this->currentPage->setFont($this->fontBold, $size);
							$stringFont = $this->fontBold;
							break;
						case 'fixedbold':
							$this->currentPage->setFont($this->fontFixedBold, $size);
							$stringFont = $this->fontFixedBold;
							break;

						case 'italic':
							$this->currentPage->setFont($this->fontItalic, $size);
							$stringFont = $this->fontItalic;
							break;
						case 'fixeditalic':
							$this->currentPage->setFont($this->fontFixedItalic, $size);
							$stringFont = $this->fontFixedItalic;
							break;

						case 'bolditalic':
							$this->currentPage->setFont($this->fontBoldItalic, $size);
							$stringFont = $this->fontBoldItalic;
							break;
						case 'fixedbolditalic':
							$this->currentPage->setFont($this->fontFixedBoldItalic, $size);
							$stringFont = $this->fontFixedBoldItalic;
							break;

						case 'fixed':
							$this->currentPage->setFont($this->fontFixedRegular, $size);
							$stringFont = $this->fontFixedRegular;
							break;
						default:
							$this->currentPage->setFont($this->fontRegular, $size);
							$stringFont = $this->fontRegular;
							break;
					}

					$this->currentPage->setFillColor($color);

					// Set Cra/Wordspacing
					if ($style->charSpacing > 0) {
						$this->_setCharSpacing($style->charSpacing);
					}
					if ($style->wordSpacing > 0) {
						$this->_setWordSpacing($style->wordSpacing);
					}

					// Check for lines longer than the given width or the site
					$_text = $text;
					$text = array();
					$maxWidth = -1;
					if (!empty($width)) {
						$maxWidth = (float)$width;
					}
					if (!empty($length)) {
						$maxWidth = (float)$length;
					}
					if ($maxWidth <= 0) {
						switch (strtolower($align)) {
							case 'right':
								$maxWidth = ($this->pageSize[0] - $this->xBox - ($this->pageSize[0]-$x));
								break;
							case 'center':
								$maxWidth = ($this->pageSize[0] - $this->xBox - ($x/2) - $this->pageBorder);
								break;
							default:
								$maxWidth = ($this->pageSize[0] - $this->xBox - $x - $this->pageBorder);
								break;
						}
					}
					foreach ($_text as $line) {
						$line = trim($line);
						$stringWidth = $this->_getStringWidth($line, $stringFont, $size);

						if ($stringWidth > $maxWidth) {
							$_line = explode(' ', $line);
							$line = '';
							foreach ($_line as $l) {
								if (!empty($line)) {
									$line .= ' ';
								}

								$stringWidth = $this->_getStringWidth($line.$l, $stringFont, $size);
								if ($stringWidth > $maxWidth) {
									$text[] = trim($line);
									$line = '';
								}
								$line .= $l;
							}
							if (!empty($line)) {
								$text[] = trim($line);
							}

						} else {
							$text[] = $line;
						}
					}

					// Draw the Text
					if (!empty($length) && (count($text) > 1)) {
						$text = array($text[0]);
					}
					foreach ($text as $line) {
						$line = trim($line);
						if (empty($line)) {
							continue;
						}

						$stringWidth = $this->_getStringWidth($line, $stringFont, $size);
						switch (strtolower($align)) {
							case 'right':
								$xPos = $this->xBox + $x - $stringWidth;
								break;
							case 'center':
								$xPos = $this->xBox + $x - ($stringWidth/2);
								break;
							default:
								$xPos = $this->xBox + $x;
								break;
						}
						$this->currentPage->drawText(utf8_decode($line), $xPos, $this->yBox - $y, 'UTF-8');
						$y += ($size * $lineSpaceFact);
					}
					$y -= ($size * $lineSpaceFact); // the offset from the last line has to be removed

					// Reset Char/Word-Spacing
					if ($style->charSpacing > 0) {
						$this->_setCharSpacing(0);
					}
					if ($style->wordSpacing > 0) {
						$this->_setWordSpacing(0);
					}
				}
				break;

			case 'image':
				$x = $this->_getAttribute($node, 'x');
				$y = $this->_getAttribute($node, 'y');
				$width = $this->_getAttribute($node, 'width', 100);
				$height = $this->_getAttribute($node, 'height', 100);
				$src = $this->_getAttribute($node, 'src', 'sales/identity/logo');

				if ($show) {
					$this->_insertImage($src, $this->xBox + $x, $this->yBox - $y, $width, $height);
				}
				break;
		}
		return $this->yBox - $y;
	}

	/**
	 * Returns the total width in points of the string using the specified font and
	 * size.
	 *
	 * from Willie Alberty at http://framework.zend.com/issues/browse/ZF-313
	 *
	 * This is not the most efficient way to perform this calculation. I'm
	 * concentrating optimization efforts on the upcoming layout manager class.
	 * Similar calculations exist inside the layout manager class, but widths are
	 * generally calculated only after determining line fragments.
	 *
	 * @param string $string
	 * @param Zend_Pdf_Resource_Font $font
	 * @param float $fontSize Font size in points
	 * @return float
	 */
	private function _getStringWidth($string, $font, $fontsize) {
		// Replace special chars with some equivalent in width
		$pattern = array('ö','ü','ä','Ö','Ü','Ä','ß','€','Â ');
		$replace = array('o','u','a','O','U','A','S','E',' ');
		$string  = str_replace($pattern, $replace, $string);

		$drawingString = iconv('UTF-8', 'UTF-16BE', $string);
		$characters = array();
		for ($i = 0; $i < strlen($drawingString); $i++) {
		   $characters[] = (ord($drawingString[$i++]) << 8) | ord($drawingString[$i]);
		}
		$glyphs = $font->glyphNumbersForCharacters($characters);
		$widths = $font->widthsForGlyphs($glyphs);
		$stringWidth = (array_sum($widths) / $font->getUnitsPerEm()) * $fontsize;
		return $stringWidth;
	}

	/**
	 * Parse the Style-Attribute on an Text-Node
	 *
	 * @param string $style Style-Attribute-Value
	 * @return stdClass { wordSpacing:#.#, charSpacing:#.# }
	 * @access private
	 */
	private function _parseTextStyle($style) {
		$back = new stdClass();
		$back->charSpacing = 0;
		$back->wordSpacing = 0;
		$style = explode(';', $style);
		foreach ($style as $s) {
			$s = explode(':', $s);
			switch (strtolower($s[0])) {
				case 'word-spacing':
				case 'wordspacing':
					$back->wordSpacing = (double)$s[1];
					break;
				case 'char-spacing':
				case 'charspacing':
					$back->charSpacing = (double)$s[1];
					break;
			}
		}
		return $back;
	}

	private function _setCharSpacing($spacing) {
		$this->currentPage->rawWrite((double)$spacing." Tc\n", 'PDF');
	}

	private function _setWordSpacing($spacing) {
		$this->currentPage->rawWrite((double)$spacing." Tw\n", 'PDF');
	}

	private function _insertImage($image, $x, $y, $width, $height) {
		$_image = Mage::getStoreConfig($image, $this->currentStore);
		if (!empty($_image) && ($image == 'sales/identity/logo')) {
			$image = '/sales/store/logo/'.$_image;
		}
		$image = implode(DS, substr_count($image, '/') > 0 ? explode('/', $image) : explode('\\', $image));
		$image = Mage::getStoreConfig('system/filesystem/media', $this->currentStore).DS.$image;
		if (is_file($image)) {
			// Scale the Image
			$size = getimagesize($image);
			$imgWidth = $size[0] > $width ? $width : $size[0];
			$imgHeight = $imgWidth * $size[1] / $size[0];
			if ($imgHeight > $height) {
				$imgHeight = $size[1] > $height ? $height : $size[1];
				$imgWidth = $size[0] * $imgHeight / $size[1];
			}
			$image = Zend_Pdf_Image::imageWithPath($image);
			$this->currentPage->drawImage($image, $x, $y, $x+$imgWidth, $y+$imgHeight);
		}
	}

	/**
	 * Get the TextValue from a TextNode
	 *
	 * Variables can be used - see functions _getXYText for variables
	 *   __:Translatable-Text
	 *   invoice:VarFromInvoice
	 *   order:VarFromOrder
	 *   position:VarFromOrderPosition
	 *   page:VarFromPDFPage
	 *   system:VarFromSystem
	 *
	 * @param DOMElement $node Text-Node
	 * @return string utf8_encoded
	 */
	protected function _getNodeText(DOMElement $node) {
		$text = utf8_decode($node->textContent);
		$match = array();
		if (preg_match_all('/{([^:]+):([^}]+)}/smi', $text, $match, PREG_SET_ORDER)) {
			foreach ($match as $k) {
				switch (strtolower($k[1])) {
					case '__':
						$text = str_replace($k[0], Mage::helper('delightpdf')->__($k[2]), $text);
						break;

					case 'invoice':
						$val = $this->_getInvoiceText($k[2]);
						$text = str_replace($k[0], $val, $text);
						break;

					case 'order':
						$val = $this->_getOrderText($k[2]);
						$text = str_replace($k[0], $val, $text);
						break;

					case 'position':
						$val = $this->_getPositionText($k[2]);
						$text = str_replace($k[0], $val, $text);
						break;

					case 'page':
						$val = $this->_getPageText($k[2]);
						$text = str_replace($k[0], $val, $text);
						break;

					case 'system':
						$val = $this->_getSystemText($k[2]);
						$text = str_replace($k[0], $val, $text);
						break;
				}
			}
		}
		return utf8_encode($text);
	}

	/**
	 * Get a System-Varibale
	 *
	 * Create-Date -> date=Format
	 * Create-Date + num days -> calcdate=Days,Format
	 * Current-Date -> curdate=Format
	 * Current-Date + num days -> curdatecalc=Days,Format
	 *
	 * @param string $key VariableName to get
	 * @return string the Date
	 * @access private
	 */
	private function _getSystemText($key) {
		$value = '';
		$key = explode('=', $key);
		switch ($key[0]) {
			case 'date':
				$value = $this->currentOrder->getCreatedAtDate()->toString($key[1]);
				break;

			case 'calcdate':
				$date = $this->currentOrder->getCreatedAtDate();
				$days = substr($key[1], 0, strpos($key[1], ','));
				$fmt = substr($key[1], strpos($key[1], ',') + 1);
				$date->addTimestamp((int)$days*24*3600);
				$value = $date->toString($fmt);
				break;

			case 'curdate':
				$date = Mage::app()->getLocale()->storeDate($this->currentOrder->getStore(), time(), true);
				$value = $date->toString($key[1]);
				break;

			case 'curdatecalc':
				$days = substr($key[1], 0, strpos($key[1], ','));
				$fmt = substr($key[1], strpos($key[1], ',') + 1);
				$date = Mage::app()->getLocale()->storeDate($this->currentOrder->getStore(), time()+(int)$days*24*3600, true);
				$value = $date->toString($fmt);
				break;
		}
		return $value;
	}

	/**
	 * Get a Value from the current Invoice
	 *
	 * @param string $key Variable to get
	 * @return string the Value
	 * @access private
	 */
	private function _getInvoiceText($key) {
		$value = '';
		$address = $this->currentOrder->getBillingAddress();
		if (!$address) {
			$address = $this->currentOrder->getShippingAddress();
		}

		$v = explode('=', $key, 2);
		$format = null;
		if (count($v) == 2) {
			$key = $v[0];
			$format = $v[1];
		}
		unset($v);

		switch ($key) {
			case 'company':
				$value = !empty($address) ? $address->getCompany() : 'Company';
				break;

			case 'fullname':
			case 'name':
				$value = !empty($address) ? $address->getName() : 'Name';
				break;

			case 'address':
				$value = !empty($address) ? $address->getStreet1() : 'Address';
				break;

			case 'fullcity':
			case 'city':
				$value = !empty($address) ? $address->getPostcode().' '.$address->getCity() : 'PLZ City';
				break;

			case 'country':
				$value = !empty($address) ? $address->getCountryModel()->getName() : 'Country';
				break;

			case 'ordernumber':
				//$value = $this->currentOrder->getRealOrderId();
				$value = $this->currentOrder->getIncrementId();
				break;

			case 'number':
				$value = $this->currentInvoice->getIncrementId();
				break;

			case 'discount':
				$value = $this->currentOrder->formatPriceTxt(0.00 - $this->currentInvoice->getDiscountAmount());
				break;

			case 'tax':
				$value = $this->currentOrder->formatPriceTxt($this->currentInvoice->getTaxAmount());
				break;

			case 'shipping':
				$value = $this->currentOrder->formatPriceTxt($this->currentInvoice->getShippingAmount());
				break;

			case 'adjustmentrefund':
				$value = $this->currentOrder->formatPriceTxt($this->currentInvoice->getAdjustmentPositive());
				break;

			case 'adjustmentfee':
				$value = $this->currentOrder->formatPriceTxt($this->currentInvoice->getAdjustmentNegative());
				break;

			case 'total':
				$value = $this->currentOrder->formatPriceTxt($this->currentInvoice->getGrandTotal());
				break;

			case 'pagesummary':
				$value = $this->currentOrder->formatPriceTxt($this->cummulativeTotal);
				break;

			default:
				if (substr($key, 0, 8) == 'address_') {
					$value = !empty($address) ? $address->getData($key) : '';
				} else {
					$value = $this->currentInvoice->getData($key);
				}

				if ($format == 'price') {
					$value = $this->currentOrder->formatPriceTxt($value);
				}
				$value = empty($value) ? '' : $value;
				break;
		}
		return $value;
	}

	/**
	 * Get a value fom current Order
	 *
	 * @param string $key Variable to get
	 * @return string the Value
	 * @access private
	 */
	private function _getOrderText($key) {
		$value = '';
		$address = $this->currentOrder->getShippingAddress();
		if (!$address) {
			$address = $this->currentOrder->getBillingAddress();
		}

		$v = explode('=', $key, 2);
		$format = null;
		if (count($v) == 2) {
			$key = $v[0];
			$format = $v[1];
		}
		unset($v);

		switch ($key) {
			case 'company':
				$value = $address->getCompany();
				break;

			case 'fullname':
			case 'name':
				$value = $address->getName();
				break;

			case 'address':
				$value = $address->getStreet1();
				break;

			case 'fullcity':
			case 'city':
				$value = $address->getPostcode().' '.$address->getCity();
				break;

			case 'country':
				$value = $address->getCountryModel()->getName();
				break;

			case 'state':
				$value = Mage::helper('delightpdf')->__('state_'.$this->currentOrder->getStatus());
				break;

			default:
				if (substr($key, 0, 8) == 'address_') {
					$value = !empty($address) ? $address->getData($key) : '';
				} else {
					$value = $this->currentOrder->getData($key);
				}

				if ($format == 'price') {
					$value = $this->currentOrder->formatPriceTxt($value);
				}
				$value = empty($value) ? '' : $value;
				break;

		}
		return $value;
	}

	/**
	 * Get a value from current Order-Position
	 *
	 * @param string $key Variable to get
	 * @return string the Value
	 * @access private
	 */
	private function _getPositionText($key) {
		$value = '';

		$v = explode('=', $key, 2);
		$format = null;
		if (count($v) == 2) {
			$key = $v[0];
			$format = $v[1];
		}
		unset($v);

		switch ($key) {
			case 'position':
				$value = $this->currentPositionNumber;
				break;

			case 'number':
				if ($this->currentPosition->getOrderItem() && $this->currentPosition->getOrderItem()->getProductOptionByCode('simple_sku')) {
					$value = $this->currentPosition->getOrderItem()->getProductOptionByCode('simple_sku');
				} else {
					$value = $this->currentPosition->getSku();
				}
				break;

			case 'name':
				$value = strip_tags($this->currentPosition->getName());
				break;

			case 'quantity':
				$value = (int)$this->currentPosition->getQty();
				break;

			case 'price':
				$value = $this->currentOrder->formatPriceTxt($this->currentPosition->getPrice());
				break;

			case 'tax':
				$value = $this->currentOrder->formatPriceTxt($this->currentPosition->getTaxAmount());
				break;

			case 'summary':
				$value = $this->currentOrder->formatPriceTxt($this->currentPosition->getRowTotal());
				break;

			case 'description':
				//if ($this->isPaid) {
				//	$productNumber = $this->_getPositionText('number');
				//	$invoiceNumber = $this->_getInvoiceText('number');
					// TODO: Based on this two values we can now fetch a Serial-Number if there is one for this Product
				//}
				$value = strip_tags($this->currentPosition->getDescription());
				//$value = 'Additional description like serials and so on'.chr(10).'can be also multiple'.chr(10).'lines'.chr(10).';-)';
				break;

			case 'serial':
				$value = '';
				try {
					if (!$this->currentPosition->getDelightserialNumbers()) {
						//$serialCollection = Delight_Delightserial_Model_Purchased::getProductSerialCollection($this->currentPosition->getProductId(), $this->currentPosition->getOrderId());
						$serialCollection = Mage::getModel('delightserial/purchased')->getProductSerialCollection($this->currentPosition->getProductId(), $this->currentPosition->getOrderId());
						$serials = array();
						foreach ($serialCollection as $s) {
							$serials[] = $s->getSerialNumber();
						}
					} else {
						$serials = $this->currentPosition->getDelightserialNumbers();
					}
					if (is_array($serials) && (count($serials) > 0)) {
						foreach ($serials as $serial) {
							$value .= chr(10).'    '.$serial;
						}
					} else {
						$value .= chr(10).'    -';
					}
				} catch (Exception $e) {
					// Don't throw an Exception, just return an empty value if there are
					// Serials on the Template but Delight_Delightserial is not installed
				}
				break;

			default:
				$value = $this->currentPosition->getData($key);
				if ($format == 'price') {
					$value = $this->currentOrder->formatPriceTxt($value);
				}
				$value = empty($value) ? '' : $value;
				break;
		}
		return $value;
	}

	/**
	 * Get a value from current PDF-Page
	 *
	 * @param string $key Variable to get
	 * @return string the Value
	 * @access private
	 */
	private function _getPageText($key) {
		$value = '';
		switch ($key) {
			case 'page':
			case 'site':
				$value = $this->pageNumber;
				break;

			case 'pages':
			case 'sites':
				$value = $this->numPages;
				break;
		}
		return $value;
	}

}

?>