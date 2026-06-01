<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */
defined('JPATH_PLATFORM') or die;

class JFormFieldRSUploads extends JFormField
{
	protected $type = 'RSUploads';
	
	protected function getInput()
	{
		// Initialize some field attributes.
		$accept = $this->element['accept'] ? ' accept="' . (string) $this->element['accept'] . '"' : '';
		$size = $this->element['size'] ? ' size="' . (int) $this->element['size'] . '"' : '';
		$class = $this->element['class'] ? ' class="' . (string) $this->element['class'] . '"' : '';
		$disabled = ((string) $this->element['disabled'] == 'true') ? ' disabled="disabled"' : '';

		// Initialize JavaScript field attributes.
		$onchange = $this->element['onchange'] ? ' onchange="' . (string) $this->element['onchange'] . '"' : '';
		$onclick  = $this->element['onclick'] ? ' onclick="' . (string) $this->element['onclick'] . '"' : '';

		// button for "more files"
		$button = '<button type="button" class="btn btn-secondary" '.$onclick.'>'.JText::_('RST_ADD_MORE_ATTACHMENTS').'</button>';
		
		return '<input type="file" name="' . $this->name . '[]" id="' . $this->id . '"' . ' value=""' . $accept . $disabled . $class . $size
			. $onchange . ' /> '.$button;
	}
}