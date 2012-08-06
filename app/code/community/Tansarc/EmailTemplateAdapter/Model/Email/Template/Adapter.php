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

class Tansarc_EmailTemplateAdapter_Model_Email_Template_Adapter extends Mage_Core_Model_Email_Template
{
	/**
     * @var int
	 * - storeId we should use for getting the config info
	 * - number of custom fields we process, copy paste more fields in this module's system.xml when 5 is too few 
	 */
	protected $_storeId;

    /** @var int */
	protected $_customFieldCount = 5;


    public function _afterLoad()
    {
        parent::_afterLoad();

        //Only filter when we are dealing with html type data
        if($this->getTemplateType() == self::TYPE_HTML){
            $this->setTemplateText($this->_replaceTemplateText($this->getTemplateText()));
        }
    }


    /**
     * Load template by code
     *
     * @param   string $templateCode
     * @return   Mage_Core_Model_Email_Template
     */
    public function loadByCode($templateCode)
    {
        $this->addData($this->getResource()->loadByCode($templateCode));
        return $this;
    }


    /**
     * Overload the original @ Mage_Core_Model_Email_Template
     * 
     * Load default email template from locale translate
     *
     * @param string $templateId
     * @param string $locale
     * @return \Tansarc_EmailTemplateAdapter_Model_Email_Template_Adapter
     */
    public function loadDefault($templateId, $locale=null)
    {
        //We are not using the email template adapter
    	if($this->_isExtensionActive() == false){
    		return parent::loadDefault($templateId,$locale);
    	}
    	
        $defaultTemplates = self::getDefaultTemplates();
        if (!isset($defaultTemplates[$templateId])) {
            return $this;
        }

        $data = &$defaultTemplates[$templateId];
        $this->setTemplateType($data['type']=='html' ? self::TYPE_HTML : self::TYPE_TEXT);
        
        $templateText = Mage::app()->getTranslator()->getTemplateFile(
            $data['file'], 'email', $locale
        );


        
        //Only filter when we are dealing with html type data
        if($data['type'] == 'html'){
        	$templateText = $this->_replaceTemplateText($templateText);
        }
        //Do what we used to do
        else{
        	if (preg_match('/<!--@subject\s*(.*?)\s*@-->/', $templateText, $matches)) {
                $this->setTemplateSubject($matches[1]);
                $templateText = str_replace($matches[0], '', $templateText);
        	}
        }

		//Remove comment lines
        $templateText = preg_replace('#\{\*.*\*\}#suU', '', $templateText);
        
        $this->setTemplateText($templateText);
        $this->setId($templateId);

        return $this;
    }


    /**
     * Overload the original @ Mage_Core_Model_Email_Template
     *
     * @param int          $templateId
     * @param array|string $sender
     * @param string       $email
     * @param string       $name
     * @param array        $vars
     * @param null         $storeId
     *
     * @return \Tansarc_EmailTemplateAdapter_Model_Email_Template_Adapter
     */
    public function sendTransactional($templateId, $sender, $email, $name, $vars=array(), $storeId=null)
    {
    	$this->_loadStoreId($vars,$storeId); 

    	//We are not using the email template adapter
    	if($this->_isExtensionActive() == false){
    		return parent::sendTransactional($templateId, $sender, $email, $name, $vars, $this->_storeId);
    	}

    	if($this->_allowSendTemplate($templateId)){
    		$vars['email_template_adapter'] = $this->_getAdditionalVariables(); 	    
			return parent::sendTransactional($templateId, $sender, $email, $name, $vars, $this->_storeId);
    	}
    	$this->setSentSuccess(false);    	
    	return $this;
    }


    /**
     * Check if we enable this extension
     *
     * @return bool
     */
    private function _isExtensionActive(){
    	//Is this extension enabled?
 		if($this->_getEmailTemplateAdapterConfig('email_template_adapter/adapter/active') == 1){
 			return TRUE;
 		}
 		return false;
    }

    
    /**
     * @param $configName
     * @return mixed
     *
     * Get config values of this extension
     */
    private function _getEmailTemplateAdapterConfig($configName){
    	return Mage::getStoreConfig($configName, $this->_storeId);
    }

    
    /**
     * Modify the default template according to the specific configuration
     *
     * @param $templateText
     * @return string
     */
    private function _replaceTemplateText($templateText){

        if(Mage::getStoreConfig('email_template_adapter/styles/closure', $this->_storeId) == '1'){
            //Remove closure: Thanks again, {{var store.getFrontendName()}}

            // find the greeting anchor
            $lastOccurrence = strrpos($templateText,'{{var store.getFrontendName()}}');

            //find the next </tr> relative to the anchor
            $tale = strpos($templateText, '</tr>', $lastOccurrence);

            //just to find the <tr> before the anchor
            $temp = substr($templateText,0,$lastOccurrence);
            $head = strrpos($temp,'<tr');

            // define part to remove
            $removePart = substr($templateText,0,$tale);
            $removePart = substr($removePart,$head);
            $removePart = $removePart.'</tr>';

            // finally, remove it
            $templateText = str_replace($removePart,'',$templateText);
        }


        // style some elements
        $style_selector = Mage::getStoreConfig('email_template_adapter/styles/style_selector', $this->_storeId);
        $style_selector = unserialize($style_selector);
        
        if(!is_array($style_selector)) $style_selector = array();
        
        foreach ($style_selector as $key => $value){
            if ($key == 'a'){
                $templateText = preg_replace('/<'.$key.' href="(.*?)" style="[^"]+">(.*?)<\/'.$key.'>/', '<'.$key.' style="'.$value.'" href="$1" >$2</'.$key.'>', $templateText);
            } else {
                $templateText = preg_replace('/<'.$key.' style="[^"]+">(.*?)<\/'.$key.'>/', '<'.$key.' style="'.$value.'">$1</'.$key.'>', $templateText);
            }
        }

    	//Processing done flags
    	$containerDivided 		= false;
        $middleContainerDivided = false;
        $subjectReplaced 		= false;
        $containerReplaced 		= false;
        $headerReplaced 		= false;
        $middleContainerAdded 	= false;

        //Configuration data, store dependant so loaded through protected function
        //Divider lines
        $containerDivideHereLine 		= $this->_getEmailTemplateAdapterConfig('email_template_adapter/container/dividehere');
        $middleContainerDivideHereLine 	= $this->_getEmailTemplateAdapterConfig('email_template_adapter/middle/containerdividehere');
        $headerStartHereLine 			= $this->_getEmailTemplateAdapterConfig('email_template_adapter/header/starthere');
        $middleStartHereLine 			= $this->_getEmailTemplateAdapterConfig('email_template_adapter/middle/starthere');

        //Replace with content
        $containerHtml 			= $this->_getEmailTemplateAdapterConfig('email_template_adapter/container/html');
        $middleContainerHtml 	= $this->_getEmailTemplateAdapterConfig('email_template_adapter/middle/containerhtml');
        $headerHtml 			= $this->_getEmailTemplateAdapterConfig('email_template_adapter/header/html');
        $footerHtml 			= $this->_getEmailTemplateAdapterConfig('email_template_adapter/footer/html');

        //Var init
        $containerTop = '';
        $containerBottom = '';
        $middleContainerTop = '';
        $middleContainerBottom = '';
        $newTemplateLines = array();//Use array as we can create nice and clean line by line output again
        $skipLines = array();

        //Split the new container html in 2 parts, top and bottom
        if($containerHtml){
	        $containerHtmlLines = explode(PHP_EOL,$containerHtml);
	        foreach($containerHtmlLines as $line){
	        	$line = trim($line,PHP_EOL);
	        	if(strpos($line,$containerDivideHereLine) !== false) {
	        		$containerDivided = true;
	        		continue;//skip this line
	        	}
	        	if($containerDivided){
	        		$containerBottom .= $line;
	        		continue;
	        	}
	        	$containerTop .= $line;
	        }
        }

        //Split the middleContainer in 2 parts, top and bottom
        if($middleContainerHtml && $middleContainerDivideHereLine != ''){
	        $middleContainerHtmlLines = explode(PHP_EOL,$middleContainerHtml);
	        foreach($middleContainerHtmlLines as $key=>$line){
	        	$line = trim($line,PHP_EOL);
	        	if(strpos($line,$middleContainerDivideHereLine) !== false) {
	        		$middleContainerDivided = true;
	        		continue;//skip the line
	        	}
	        	if($middleContainerDivided){
	        		$middleContainerBottom .= $line;
	        		continue;
	        	}
	        	$middleContainerTop .= $line;
	        }
        }

        //Put the templateText into an array, line by line
        //We need to do this in order to delete the bottom part of the old container
        $templateLines = explode(PHP_EOL,$templateText);
        //Go through the lines, leave the subject and delete everything before: header search string
		foreach($templateLines as $lineNr => $line){
			$line = trim($line,PHP_EOL);
			if(!$line){
				$newTemplateLines[] =  $line;
				continue;
			}

			//Do we skip this line?
			if(isset($skipLines[$lineNr]) && $skipLines[$lineNr] == true){
				continue;
			}

			//Replace the subject
			if(!$subjectReplaced){
		        //Filter out the subject line
		        if (preg_match('/<!--@subject\s*(.*?)\s*@-->/', $line, $matches)) {
	    	       	$this->setTemplateSubject($matches[1]);
					$subjectReplaced = true;
	    	       	continue;
	        	}
			}

			//Replace the container, only if set
			if(!$containerReplaced && $containerHtml && $headerStartHereLine){
				//Found the header start line, add the container top
				if(strpos($line,$headerStartHereLine) !== false) {
					$newTemplateLines[] =  $containerTop;
					$containerReplaced = true;
					continue;
				}
				//Delete the closing tags from the end of the template
				$closeTag = $this->_getClosingTag($line);
				//do not remove the style this way as it goes automatically already
				if($closeTag && $closeTag != '</style>'){
					$skipLine = $this->_removeClosingTag($templateLines,$closeTag);
					if($skipLine !== false){
						$skipLines[$skipLine] = true;
					}
				}
				continue;
			}
			//Replace the header, only if set
			if(!$headerReplaced && $headerHtml && $middleStartHereLine){
				//Found the correct line, middle starts here, now add the header and the top of the middle container
				if (strpos($line,$middleStartHereLine) !== false) {
					$newTemplateLines[] =  $headerHtml;
					$newTemplateLines[] =  $middleContainerTop;
					$headerReplaced = true;
					$middleCintainerAdded = true;
					continue;
				}
				continue;
			}
			$newTemplateLines[] =  $line;

			//Are we still looking for the middle start for adding the middle container top
			if($middleContainerAdded == false && $middleStartHereLine && $middleContainerHtml){
				if(strpos($line,$middleStartHereLine) !== false) {
					$newTemplateLines[] =  $middleContainerTop;
					$middleCintainerAdded = true;
				}
			}
		}

		//Add the bottom of the middle container, footer and the bottom of the container
		$newTemplateLines[] =  $middleContainerBottom;
		$newTemplateLines[] =  $footerHtml;
		$newTemplateLines[] =  $containerBottom;

		return implode(PHP_EOL,$newTemplateLines);
    }


    /**
     * Take away the closing tag, meant for stripping the default template container
     *
     * @param $templateLines
     * @param $closingTag
     * @return bool|int
     */
    private function _removeClosingTag($templateLines,$closingTag){
    	//Reverse search for the closing tag
    	for($i=count($templateLines)-1;$i;$i--){
    		if(strpos($templateLines[$i],$closingTag) !== false){
    			return $i;
    		}
    	}
    	return false;
    }


    /**
     * Get the closing html tag for the parsed line
     *
     * @param $line
     * @return null|string
     */
    private function _getClosingTag($line){
    	//trim the line so we start with the first char
    	$line = trim($line);
    	if(!$line){
    		return null;
    	}
    	//No tag on this line
    	if(substr($line,0,1) != '<'){
    		return null;	
    	}
    	//Skip comment lines and closing tags
    	if(substr($line,1,1) == '!' || substr($line,1,1) == '/'){
    		return null;	
    	}
    	$tagClose = strpos($line,'>');
    	if($tagClose === false){
    		//not a valid line
    		return null;
    	}
    	$firstSpace = strpos($line,' ');
    	if($firstSpace && $firstSpace < $tagClose){
			$tag = substr($line,1,$firstSpace-1);	
    	}
    	else{
    		$tag = substr($line,1,$tagClose-1);
    	}
    	//closing tag looks like this:
    	return '</'.$tag.'>';
    }


    /**
     * Gets and fills all the configured variables we want to use in the template
     *  - All custom vatiables
     *  - All system variables
     *
     * for example: get your General contact email into the email you're sending
     *  - value is stored in config data as: trans_email/ident_general/email
     *  - put the following into the email template: {{var email_template_parser.trans_email_ident_general_email}}
     * 
     * returns Varien_Object or null
     * @return null|\Varien_Object
     */
    private function _getAdditionalVariables(){

    	$loadVariables = array();

    	//Get the system variables from config
    	$additionalSystemVariables = $this->_getEmailTemplateAdapterConfig('email_template_adapter/additional/variables');
    	if($additionalSystemVariables){
   			//Replace ; and , delimiters by an end of line
   			$additionalSystemVariables = str_replace(';',PHP_EOL,$additionalSystemVariables);
    		$additionalSystemVariables = str_replace(',',PHP_EOL,$additionalSystemVariables);
    		$loadVariables = explode(PHP_EOL,$additionalSystemVariables);
    	}
    	
    	//Add the custom field variables
    	for($i=0;$i<$this->_customFieldCount;$i++){
    		$j = $i+1;
    		$loadVariables[] = 'email_template_adapter/additional/custom'.$j;
    	}

    	//Create new object and setData
    	$result = new Varien_Object();
    	$returnResult = false;
    	foreach($loadVariables as $variable){
    		$variable = trim($variable);   		
    		if(!$variable){
    			continue;
    		}
    		$value = $this->_getEmailTemplateAdapterConfig($variable);
    		if($value){
    			$returnResult = true;
    			$variable = str_replace('email_template_adapter/additional/','',$variable);
    			$variable = str_replace('/','_',$variable);
    			$result->setData($variable,$value);
    		}
    	}
    	if($returnResult){   	
    		return $result;
    	}
    	return null;
    }
    
	/**
     * Get the storeId we use for determining which settings to load
     *
     * @param $vars
     * @param $storeId
     */
    private function _loadStoreId($vars, $storeId = NULL){
   		//Get the appropriate store ID as it is not always passed into the sendTransactional function
        if (is_null($storeId))
        {
    		//Can we find one of the following objects in the vars?
    		foreach($vars as $var => $object){
                if (is_object($object))
                {
                    switch($var){
                        case 'order':
                            $storeId = $object->getStoreId();
                            break;
                        case 'customer':
                            $storeId = $object->getStoreId();
                            break;
                        default:
                            $storeId = null;
                            break;
                    }
                    if (! is_null($storeId)){
                        break;
                    }
                }
	    	}
	    	//Find it from here if still null
    	    if(is_null($storeId)){
    			$storeId = Mage::app()->getStore()->getStoreId();
    		}
    	}
    	$this->_storeId = $storeId;
    }
    
    /**
     * Are we allowed to send an email based on this template?
     *
     * @param $templateId
     * @return bool
     */
    private function _allowSendTemplate($templateId){
    
    	//Do we have this filter enabled, if not we will allow all templates
    	if($this->_getEmailTemplateAdapterConfig('email_template_adapter/template/active') != 1){
    		return true;
    	}

    	//Get all the default templates
    	$defaultTemplates = $this->getDefaultTemplates();
    	
    	//Is the requested template a default template?
    	//If not, we are allowed to send it as we will only check default templates
    	if(!isset($defaultTemplates[$templateId])){
    		return true;
    	}
    		
    	//This default template is not in the allow list
		if(strpos($this->_getEmailTemplateAdapterConfig('email_template_adapter/template/allow'),$templateId) === false){
			//Use a try catch construction to prevent the system from crashing when reporting an error, used @ customer activation email sending
			try{
    			$this->_getSession()->addError($this->__('Email not sent because the email template is not allowed to be sent. (Email Template Adapter Extension Settings)'));
			}
			catch(Exception $e){
				
			}
    		return false;
		}
		return true;
    }

}