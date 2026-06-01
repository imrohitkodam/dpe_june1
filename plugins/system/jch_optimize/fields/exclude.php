<?php

/**
 * JCH Optimize - Performs several front-end optimizations for fast downloads
 *
 * @package   jchoptimize/joomla-platform
 * @author    Samuel Marshall <samuel@jch-optimize.net>
 * @copyright Copyright (c) 2020 Samuel Marshall / JCH Optimize
 * @license   GNU/GPLv3, or later. See LICENSE file
 *
 * If LICENSE file missing, see <http://www.gnu.org/licenses/>.
 */

defined('_JEXEC') or die;

use JchOptimize\Platform\Plugin;
use JchOptimize\Platform\Profiler;
use JchOptimize\Core\Helper;
use JchOptimize\Core\Admin;

JFormHelper::loadFieldClass('Textarea');
Plugin::getPluginParams();

abstract class JchTextarea extends JFormFieldTextarea
{

        protected $aOptions = array();

        public function setup(SimpleXMLElement $element, $value, $group = NULL)
        {
                JCH_DEBUG ? Profiler::mark('beforeSetup' . $this->type) : null;

                $value = $this->castValue($value);

                JCH_DEBUG ? Profiler::mark('afterSetup' . $this->type) : null;

                return parent::setup($element, $value, $group);
        }

        protected function castValue($value)
        {
                
        }

}

abstract class JFormFieldExclude extends JchTextarea
{

        protected static $oParams = null;
        protected static $oParser = null;
        protected $ajax_params    = '';
        protected $first_field    = false;
	protected $filegroup = 'file';

        
        /**
         * 
         * @param type $value
         * @return type
         */
        protected function castValue($value)
        {
                if (!is_array($value))
                {
                        $value = Helper::getArray($value);
                }

                return $value;
        }

        /**
         * 
         * @return type
         */
        protected function setOptions()
        {
                $this->aOptions = $this->getFieldOptions();
        }

        /**
         * 
         * @param type $sType
         * @param type $sParam
         * @param type $sGroup
         */
        protected function setAjaxParams()
        {
                $this->ajax_params = '"type": "' . $this->filetype . '", "param": "' . $this->fieldname . '", "group": "' . $this->filegroup . '"';
        }

        /**
         * 
         * @return type
         */
        protected function getInput()
        {
                $attributes = 'class="inputbox chzn-custom-value input-xlarge jch-exclude" multiple size="5" data-jch_type="' . $this->filetype . '" data-jch_param="' . $this->fieldname . '" data-jch_group="' . $this->filegroup . '"';
		$options = array();

		foreach($this->value as $excludevalue)
		{
			$options[$excludevalue] = Admin::{'prepare' . ucfirst($this->filegroup) . 'Values'}($excludevalue);
;
		}

                $select = JHTML::_('select.genericlist', $options, 'jform[params][' . $this->fieldname . '][]', $attributes, 'id', 'name', $this->value, $this->id);

			
		$field = '<div id="div-' . $this->fieldname . '"> ' . $select . '

                                <img id="img-' . $this->fieldname . '" src="' . JUri::root() . 'media/plg_jchoptimize/images/exclude-loader.gif" />
</div>';
//<script type="text/javascript">
//';
//
//               // if ($this->first_field)
//               // {
//               //         $field .= $this->getFirstField();
//               // }
//               // else
//               // {
//               //         $field .= $this->getObserverField();
//               // }
//
//                $field .= '
//</script> ';

                return $field;
        }

        /**
         * 
         * @return type
         */
        protected function getAjaxFunction()
        {
		$this->setAjaxParams();

                $field = '
var timestamp = getTimeStamp();                       
jQuery("div#div-' . $this->fieldname . '").load(
        jch_ajax_url + "&action=getmultiselect&_=" + timestamp,
        {' . $this->ajax_params . ', "name": "' . $this->name . '", "value": ' . json_encode($this->value) . '},
        function(){
                jQuery("img-' . $this->fieldname . '").hide(); 
                
                if(!jQuery.isEmptyObject(jch_observers)){
                        window["create" + jch_observers.shift()]();
                }
                
                jQuery("#jform_params_' . $this->fieldname . '").chosen();
        }
);';

                return $field;
        }

        /**
         * 
         * @return string
         */
        protected function getFirstField()
        {
                //$field = 'jQuery(document).ready(function()
                //        {
                //                ';
                $field = $this->getAjaxFunction();
                //$field .= '
                //        });
                //';

                return $field;
        }

        /**
         * 
         * @return string
         */
        protected function getObserverField()
        {
                $field = 'jch_observers.push("' . ucfirst($this->fieldname) . '");
function create' . ucfirst($this->fieldname) . '()
{
                        ';
                $field .= $this->getAjaxFunction();
                $field .= '
};
                ';

                return $field;
        }

}
