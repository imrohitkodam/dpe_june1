<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2018 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproControllerRemovedata extends JControllerLegacy
{
	public function process()
    {
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

        $id     = JFactory::getApplication()->input->getInt('id');
        $me     = JFactory::getUser();
        $user   = JFactory::getUser($id);

        try
        {
            if ($me->id == $user->id)
            {
                throw new Exception(JText::_('COM_RSTICKETSPRO_CANNOT_ANONYMISE_LOGGED_IN_USER'));
            }

            if ($user->authorise('core.admin'))
            {
                throw new Exception(JText::_('COM_RSTICKETSPRO_CANNOT_ANONYMISE_SUPER_USER'));
            }

            RSTicketsProHelper::anonymise($id);

            jexit(json_encode(array(
                'message' => array(JText::_('COM_RSTICKETSPRO_DATA_HAS_BEEN_SUCCESSFULLY_ANONYMISED'))
            )));
        }
        catch (Exception $e)
        {
            jexit(json_encode(array(
                'error' => array($e->getMessage())
            )));
        }
    }
}