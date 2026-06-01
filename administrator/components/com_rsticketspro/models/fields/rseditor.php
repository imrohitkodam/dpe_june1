<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('JPATH_PLATFORM') or die;

require_once JPATH_ADMINISTRATOR.'/components/com_rsticketspro/helpers/rsticketspro.php';

if (RSTicketsProHelper::getConfig('allow_rich_editor'))
{
	JFormHelper::loadFieldClass('editor');
	
	class JFormFieldRSEditor extends JFormFieldEditor
	{
		public $type = 'RSEditor';
		
		public function getInput()
		{
			$this->element['buttons'] = RSTicketsProHelper::getConfig('allow_rich_editor_buttons') ? 'true' : 'false';
			$this->buttons = (bool) RSTicketsProHelper::getConfig('allow_rich_editor_buttons');
			
			return parent::getInput();
		}
	}
}
else
{
	JFormHelper::loadFieldClass('textarea');
	
	class JFormFieldRSEditor extends JFormFieldTextarea
	{
		protected $type = 'RSEditor';
	}
}