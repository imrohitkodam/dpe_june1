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

use Joomla\CMS\MVC\Controller\FormController;

defined('_JEXEC') or die;

class DisplayController extends FormController
{
    protected $default_view = 'main';

    public function display($cachable = false, $urlparams = [])
    {
        $view = $this->input->get('view', $this->default_view);

        if ($view !== $this->default_view)
        {
            $this->setRedirect('index.php?option=com_regularlabsmanager');
        }

        parent::display($cachable, $urlparams);
    }
}
