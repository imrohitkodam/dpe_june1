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

use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\MVC\Controller\BaseController;
use RegularLabs\Component\RegularLabsExtensionsManager\Administrator\Helper\ExtensionsHelper;
use RegularLabs\Component\RegularLabsExtensionsManager\Administrator\Model\ProcessModel;

class ProcessController extends BaseController
{
    public function display($cachable = false, $urlparams = [])
    {
        parent::display($cachable, $urlparams);

        $this->app->getSession()->remove('rlem-results');

        $this->app->close();

        return '';
    }

    public function install()
    {
        $this->preprocess();

        /** @var ProcessModel $model */
        $model  = $this->getModel('Process');
        $result = $model->install();

        $this->postprocess();

        echo $result ? 1 : 0;

        $this->app->close();
    }

    public function postprocess()
    {
        $session = $this->app->getSession();

        $extensions           = $session->get('rlem-results', []);
        $extension            = ExtensionsHelper::getByAlias($this->input->get('extension'));
        $extension->messages  = $this->app->getMessageQueue();
        $extension->has_error = false;

        foreach ($extension->messages as $i => $message)
        {
            if (str_contains($message['message'], 'MaxMind GeoLite2'))
            {
                unset($extension->messages[$i]);
                continue;
            }

            if ($message['type'] == 'error')
            {
                $extension->has_error = true;
            }
        }

        $extensions[] = $extension;

        $session->set('rlem-results', $extensions);
    }

    public function preprocess()
    {
        $this->checkToken();

        if ( ! $this->app->getIdentity()->authorise('core.admin'))
        {
            throw new NotAllowed(JText::_('JERROR_ALERTNOAUTHOR'), 403);
        }
    }

    public function start($cachable = false, $urlparams = [])
    {
        return $this->display($cachable, $urlparams);
    }

    public function uninstall()
    {
        $this->preprocess();

        /** @var ProcessModel $model */
        $model  = $this->getModel('Process');
        $result = $model->uninstall();

        $this->postprocess();

        echo $result ? 1 : 0;

        $this->app->close();
    }
}
