<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

$this->field->startFieldset();
?>
<p><?php echo JText::_('COM_RSTICKETSPRO_PERMISSIONS_NO_LONGER_HERE'); ?></p>
<p><a class="btn btn-primary" href="<?php echo JRoute::_('index.php?option=com_config&view=component&component=com_rsticketspro'); ?>"><?php echo JText::_('COM_RSTICKETSPRO_GO_TO_GLOBAL_CONFIGURATION'); ?></a></p>
<?php
$this->field->endFieldset();