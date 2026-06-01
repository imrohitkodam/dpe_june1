<?php
/**
 * @package    RSTicketsPro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;

$app   = Factory::getApplication();
$items = $app->getMenu()->getItems('link', 'index.php?option=com_dpe&view=rsticketspro');

if (isset($items[0]))
{
	$itemId = $items[0]->id;
}

$redirect = Route::_('index.php?option=com_dpe&view=rsticketspro&Itemid=' . $itemId, false);

$app->redirect($redirect);
