<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewArticle extends JViewLegacy
{
	protected $item;
	
	public function display($tpl = null)
	{
		// set the JSON headers
		header('Content-Type: application/json; charset=utf-8');
		
		$this->item	= $this->get('article');

		if (!$this->item->id || !$this->item->published || (!RSTicketsProHelper::isStaff() && $this->item->private))
		{
			throw new Exception(JText::_('RST_CANNOT_VIEW_ARTICLE'));
		}
		
		if (!RSTicketsProHelper::getConfig('allow_rich_editor'))
		{
			$this->item->text = strip_tags($this->item->text);
		}
		
		// display the result
		echo json_encode(array('text' => $this->item->text));
		
		// end application
		JFactory::getApplication()->close();
	}
}