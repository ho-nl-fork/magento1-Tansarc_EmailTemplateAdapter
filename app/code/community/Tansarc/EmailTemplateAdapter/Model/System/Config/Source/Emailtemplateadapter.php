<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   Mage
 * @package    Tansarc_EmailTemplateAdapter
 * @copyright  Copyright (c) 2009 Finn Snaterse http://www.tansarc.nl/
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Finn Snaterse <magento@tansarc.nl>
 */

class Tansarc_EmailTemplateAdapter_Model_System_Config_Source_EmailTemplateAdapter
{
    /** @var array */
    protected $_options;

    /**
     * @param bool $isMultiselect
     * @return array
     */
    public function toOptionArray($isMultiselect=false)
    {
        if (!$this->_options) {
        	$this->_options = array();
	        foreach(Mage::getConfig()->getNode('global/template/email')->asArray() as $templateId => $value){
      			array_unshift($this->_options, array(
					'value' => $templateId,
					'label' => $value['label'],
				));
	        }
        }

        $options = $this->_options;
        if(!$isMultiselect){
            array_unshift($options, array('value'=>'', 'label'=> Mage::helper('adminhtml')->__('--Please Select--')));
        }

        return $options;
    }
}