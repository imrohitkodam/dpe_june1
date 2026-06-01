<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RSDropdown
{
	protected $context = '';
	protected $deletable = true;
	protected $editable = true;

	protected $customItems = array();

	public function __construct($options=array()) {
		foreach ($options as $k => $v) {
			$this->$k = $v;
		}
	}

	public function addCustomItem($label, $link = 'javascript:void(0)', $linkAttributes = '', $className = '', $ajaxLoad = false, $jsCallBackFunc = null) {
		$this->customItems[] = (object) array(
			'label' => $label,
			'link' => $link,
			'linkAttributes' => $linkAttributes,
			'className' => $className,
			'ajaxLoad' => $ajaxLoad,
			'jsCallBackFunc' => $jsCallBackFunc
		);
	}

	public function show($i, $item) {
		static $init = false;
		if (!$init) {
			JHtml::_('dropdown.init');
			JFactory::getDocument()->addScriptDeclaration("
			// This fixes Joomla! jQuery.attr() bug.
			jQuery(document).ready(function($){
				contextAction = function (cbId, task) {
					$('input[name=\"cid[]\"]').removeAttr('checked');
					$('#' + cbId).prop('checked', true);

					Joomla.submitbutton(task);
				}
			});
			");
		}

		$last = substr($this->context, -1);
		if ($last == 'y') {
			$pluralcontext = substr($this->context, 0, -1).'ies';
		} elseif ($last == 's') {
			$pluralcontext = $this->context.'es';
		} else {
			$pluralcontext = $this->context.'s';
		}

		// Create dropdown items
		if ($this->editable) {
			$context = $this->context.'.';
			JHtml::_('dropdown.edit', $item->id, $context);
		}

		// Custom items
		if ($this->customItems) {
			foreach ($this->customItems as $customItem) {
				JHtml::_('dropdown.addCustomItem', $customItem->label, $customItem->link, $customItem->linkAttributes, $customItem->className, $customItem->ajaxLoad, $customItem->jsCallBackFunc);
			}
		}

		if (isset($item->published)) {
			JHtml::_('dropdown.divider');
			$context = $pluralcontext.'.';
			if ($item->published) {
				JHtml::_('dropdown.unpublish', 'cb' . $i, $context);
			} else {
				JHtml::_('dropdown.publish', 'cb' . $i, $context);
			}
		}

		if ($this->deletable) {
			$context = $pluralcontext.'.';
			JHtml::_('dropdown.trash', 'cb' . $i, $context);
		}

		// Render dropdown list
		$html = JHtml::_('dropdown.render');
		if ($this->customItems) {
			// prepare the placeholders
			$placeholders = array_keys(get_object_vars($item));
			$values		  = array_values(get_object_vars($item));
			array_walk($placeholders, array('RSDropdown', 'addCurlyBrackets'));

			echo str_replace($placeholders, $values, $html);
		} else {
			echo $html;
		}
	}

	public static function addCurlyBrackets(&$value, $index) {
		$value = '{'.$value.'}';
	}
}