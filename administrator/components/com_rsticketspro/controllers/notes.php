<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproControllerNotes extends JControllerAdmin
{
	public function getModel($name = 'Note', $prefix = 'RsticketsproModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function delete()
	{
		parent::delete();

		$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list . '&ticket_id=' . JFactory::getApplication()->input->getInt('ticket_id') . '&tmpl=component', false));
	}
}