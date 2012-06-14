<?php

/**
 * Template Filter Model
 */
class Tansarc_EmailTemplateAdapter_Model_Email_Template_Filter extends Mage_Core_Model_Email_Template_Filter
{
    const CONSTRUCTION_CONFIGFLAG_PATTERN = '/{{configFlag\s*(.*?)}}(.*?)({{else}}(.*?))?{{\\/configFlag\s*}}/si';


    /**
     * Filter the string as template.
     * I've added the configFlagDirective line.
     *
     * @param string $value
     *
     * @throws Exception
     * @return string
     */
    public function filter($value)
    {
        // "depend" and "if" operands should be first
        foreach (array(
            self::CONSTRUCTION_DEPEND_PATTERN => 'dependDirective',
            self::CONSTRUCTION_IF_PATTERN     => 'ifDirective',
            self::CONSTRUCTION_CONFIGFLAG_PATTERN => 'configFlagDirective'
            ) as $pattern => $directive) {
            if (preg_match_all($pattern, $value, $constructions, PREG_SET_ORDER)) {
                foreach($constructions as $index => $construction) {
                    $replacedValue = '';
                    $callback = array($this, $directive);
                    if(!is_callable($callback)) {
                        continue;
                    }
                    try {
                        $replacedValue = call_user_func($callback, $construction);
                    } catch (Exception $e) {
                        throw $e;
                    }
                    $value = str_replace($construction[0], $replacedValue, $value);
                }
            }
        }

        if(preg_match_all(self::CONSTRUCTION_PATTERN, $value, $constructions, PREG_SET_ORDER)) {
            foreach($constructions as $index=>$construction) {
                $replacedValue = '';
                $callback = array($this, $construction[1].'Directive');
                if(!is_callable($callback)) {
                    continue;
                }
                try {
					$replacedValue = call_user_func($callback, $construction);
                } catch (Exception $e) {
                	throw $e;
                }
                $value = str_replace($construction[0], $replacedValue, $value);
            }
        }
        return $value;
    }


    /**
     * Works like:
     * {{configFlag general/store_information/phone}}Het telefoonnummer is: {{config path='general/store_information/phone'}}{{/configFlag}}
     * {{configFlag general/store_information/phone == '123123'}}OK COOL!{{/configFlag}}
     *
     * @param array$construction
     * @return string
     */
    public function configFlagDirective($construction)
    {
        if (count($this->_templateVars) == 0) {
            // If template preprocessing
            return $construction[0];
        }

        $var = Mage::getStoreConfigFlag($construction[1]);

        if (strpos($construction[1],'=='))
        {
            $compareValues = explode('==',$construction[1]);
            $construction[1] = trim($compareValues[0]);
            $compare = trim($compareValues[1]);

            if ($var == $compare)
            {
                return $construction[2];
            } else {
                if (isset($construction[3]) && isset($construction[4])) {
                    return $construction[4];
                }
            }
        }

        if($var == '') {
            if (isset($construction[3]) && isset($construction[4])) {
                return $construction[4];
            }
            return '';
        } else {
            return $construction[2];
        }
    }


    /**
     * Works like:
     * {{depend billing.getFirstName()}}Congratulations on having a first name{{/depend}}
     * {{depend billing.getFirstName() == john}}Hi John!{{/depend}}
     *
     * @param $construction
     * @return string
     */
    public function dependDirective ($construction)
    {
        if (count($this->_templateVars) == 0) {
            // If template preprocessing
            return $construction[0];
        }

        if (strpos($construction[1],'=='))
        {
            $compareValues = explode('==',$construction[1]);
            $construction[1] = trim($compareValues[0]);
            $compare = trim($compareValues[1]);

            if ($this->_getVariable($construction[1], '') == $compare)
            {
                return $construction[2];
            } else {
                return '';
            }
        }

        if ($this->_getVariable($construction[1], '') == '') {
            return '';
        } else {
            return $construction[2];
        }
    }


    /**
     * Works like:
     * {{if billing.getFirstName()}}Congratulations on having a first name{{else}}Ahhhh too bad{{/if}}
     * {{if billing.getFirstName() == john}}Hi John!{{else}}You're not John :({{/if}}
     *
     * @param $construction
     * @return string
     */
    public function ifDirective($construction)
    {
        if (count($this->_templateVars) == 0) {
            return $construction[0];
        }

        if (strpos($construction[1],'=='))
        {
            $compareValues = explode('==',$construction[1]);
            $construction[1] = trim($compareValues[0]);
            $compare = trim($compareValues[1]);

            if ($this->_getVariable($construction[1], '') == $compare)
            {
                return $construction[2];
            } else {
                if (isset($construction[3]) && isset($construction[4])) {
                    return $construction[4];
                }
            }
        }

        if($this->_getVariable($construction[1], '') == '') {
            if (isset($construction[3]) && isset($construction[4])) {
                return $construction[4];
            }
            return '';
        } else {
            return $construction[2];
        }
    }


}
