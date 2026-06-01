<?php

/**
 * @package     Joomla
 * @subpackage  com_seaichat
 *
 * @copyright   (C) 2026 SE Extensions
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace SolarEclipse\Component\SeAiChat\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

class DisplayController extends BaseController
{
    protected $app;

    public function __construct($config = [], $factory = null, $app = null, $input = null)
    {
        parent::__construct($config, $factory, $app, $input);
        $this->app = \Joomla\CMS\Factory::getApplication();
    }
    protected $default_view = 'dashboard';

    public function display($cachable = false, $urlparams = []): BaseController
    {
        return parent::display($cachable, $urlparams);
    }

    /**
     * Activate license.
     */
    public function activateLicense(): void
    {
        $this->checkToken();

        require_once JPATH_ADMINISTRATOR . '/components/com_seaichat/helpers/LicenseChecker.php';

        $result = \SeAiChatLicenseChecker::activate();

        if ($result['success']) {
            $this->app->enqueueMessage('License activated successfully!', 'success');
        } else {
            $this->app->enqueueMessage($result['error'], 'error');
        }

        $this->setRedirect(\Joomla\CMS\Router\Route::_('index.php?option=com_seaichat&view=dashboard', false));
    }

    /**
     * Check / re-validate license.
     */
    public function checkLicense(): void
    {
        $this->checkToken();

        require_once JPATH_ADMINISTRATOR . '/components/com_seaichat/helpers/LicenseChecker.php';

        // Wipe and re-validate fresh
        \SeAiChatLicenseChecker::deactivate();
        $result = \SeAiChatLicenseChecker::activate();

        if ($result['success']) {
            $data = $result['data'] ?? [];
            $expiresAt = $data['expires_at'] ?? '';
            $isExpired = !empty($expiresAt) && (strtotime($expiresAt) < time());

            if ($isExpired) {
                $this->app->enqueueMessage('License verified. Your license has expired — the extension still works but updates require renewal.', 'warning');
            } else {
                $this->app->enqueueMessage('License verified successfully. Your license is active.', 'success');
            }
        } else {
            $this->app->enqueueMessage($result['error'], 'error');
        }

        $this->setRedirect(\Joomla\CMS\Router\Route::_('index.php?option=com_seaichat&view=dashboard', false));
    }

    /**
     * Deactivate license.
     */
    public function deactivateLicense(): void
    {
        $this->checkToken();

        require_once JPATH_ADMINISTRATOR . '/components/com_seaichat/helpers/LicenseChecker.php';

        \SeAiChatLicenseChecker::deactivate();
        $this->app->enqueueMessage('License deactivated. The local activation has been removed.', 'warning');

        $this->setRedirect(\Joomla\CMS\Router\Route::_('index.php?option=com_seaichat&view=dashboard', false));
    }

}
