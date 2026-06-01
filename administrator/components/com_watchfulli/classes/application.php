<?php
/**
 * @version     admin/classes/application.php 2024-03-27 zanardigit
 * @package     Watchful Client
 * @author      Watchful
 * @authorUrl   https://watchful.net
 * @copyright   Copyright (c) 2012-2023 Watchful
 * @license     GNU/GPL v3 or later
 */

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

if (!class_exists(AdministratorApplication::class))
{
    require_once JPATH_LIBRARIES . '/src/Application/AdministratorApplication.php';
}


class WatchfulliApplication extends AdministratorApplication
{
    public $_name = 'Administrator';
    public $installStatus;

    /**
     * Original application object
     *
     * @var CMSApplicationInterface
     */
    public $originalApp;

    /**
     * Set the original application
     *
     * @param   CMSApplicationInterface  $originalApp
     */
    public function setOriginalApp($originalApp)
    {
        $this->originalApp = $originalApp;
        $this->configureApp();
    }

    /**
     * Magic method to allow methods to fall through to the original application
     *
     * @param   string  $name
     * @param   array   $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments = [])
    {
        $callback = [&$this->originalApp, $name];
        if (is_object($this->originalApp) && is_callable($callback))
        {
            if (empty($arguments))
            {
                return $this->originalApp->$name();
            }
            else
            {
                return call_user_func_array($callback, $arguments);
            }
        }

		return null;
    }

	/**
	 * Some extensions force an application redirect after install and
	 * that breaks our remote updates. So we override the redirect
	 * method by setting a successful install status and skipping the
	 * redirect itself.
	 * This is far from perfect, because the redirect could also be raised by
	 * an error status, but it's the best we came up with to deal with the
	 * mentioned non-standard behaviour.
	 *
	 * @param   string  $url
	 *
	 * @return boolean
	 * @throws Exception
	 */
    public function redirect($url, $status = 303)
	{
		$this->installStatus = true;

		return true;
	}

    /**
     * We have to override this method to override JPATH_THEMES path
     *
     * @param   bool  $params
     *
     * @return string
     */
    public function getTemplate($params = false)
    {
        $JPATH_THEMES = JPATH_ADMINISTRATOR . '/templates';
        if (is_object($this->template))
        {
            if ($params)
            {
                return $this->template;
            }

            return $this->template->template;
        }

        $admin_style = Factory::getUser()->getParam('admin_style');

        // Load the template name from the database
        $db    = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('template, s.params')
            ->from('#__template_styles as s')
            ->join('LEFT', '#__extensions as e ON e.type=' . $db->quote('template') . ' AND e.element=s.template AND e.client_id=s.client_id');

        if ($admin_style)
        {
            $query->where('s.client_id = 1 AND id = ' . (int) $admin_style . ' AND e.enabled = 1', 'OR');
        }

        $query->where('s.client_id = 1 AND home = ' . $db->quote('1'), 'OR')
            ->order('home');
        $db->setQuery($query);
        $template = $db->loadObject();

        $template->template = InputFilter::getInstance()->clean($template->template, 'cmd');
        $template->params   = new Joomla\Registry\Registry($template->params);

        if (!file_exists($JPATH_THEMES . '/' . $template->template . '/index.php'))
        {
            $this->enqueueMessage(Text::_('JERROR_ALERTNOTEMPLATE'), 'error');
            $template->params   = new Joomla\Registry\Registry();
            $template->template = 'isis';
        }

        // Cache the result
        $this->template = $template;

        if (!file_exists($JPATH_THEMES . '/' . $template->template . '/index.php'))
        {
            throw new InvalidArgumentException(Text::sprintf('JERROR_COULD_NOT_FIND_TEMPLATE', $template->template));
        }

        if ($params)
        {
            return $template;
        }

        return $template->template;
    }

    private function configureApp() {
        if (version_compare(Watchfulli::joomla()->getShortVersion(), '4') === -1) {
            return;
        }
		/** @todo not working in Joomla 3 */
        $this->setContainer(Factory::getContainer());
        $this->setDispatcher($this->originalApp->getDispatcher());

        $session = clone $this->originalApp->getSession();
        $user = new WatchfulliRootUser();

        $session->set('user', $user);

        $this->setSession($session);
        $this->language = $this->originalApp->getLanguage();
        $this->loadIdentity($user);
    }
}
