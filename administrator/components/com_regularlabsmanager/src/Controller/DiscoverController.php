<?php
/**
 * @package         Regular Labs Extension Manager
 * @version         9.2.5
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2025 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */

namespace RegularLabs\Component\RegularLabsExtensionsManager\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

class DiscoverController extends BaseController
{
    public function display($cachable = false, $urlparams = [])
    {
        $this->app->getSession()->remove('rlem-results');

        parent::display($cachable, $urlparams);

        $this->app->close();

        return '';
    }
}
