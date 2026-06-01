<?php

/**
 * @package         Regular Labs Library
 * @version         25.11.14282
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2025 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */
namespace RegularLabs\Library\Plugin;

defined('_JEXEC') or die;
use Joomla\CMS\Editor\Button\Button;
use Joomla\CMS\Event\Editor\EditorButtonsSetupEvent;
use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\Plugin\CMSPlugin as JCMSPlugin;
use Joomla\CMS\Session\Session;
use Joomla\Event\DispatcherInterface as JDispatcherInterface;
use Joomla\Event\SubscriberInterface;
use ReflectionClass;
use RegularLabs\Library\Extension;
use RegularLabs\Library\Input;
use RegularLabs\Library\Parameters;
use RegularLabs\Library\Protect;
use function func_num_args;
use function in_array;
class EditorButton extends JCMSPlugin implements SubscriberInterface
{
    static $_extra_events = [];
    protected $asset;
    protected $author;
    protected $button_icon = '';
    protected $check_installed;
    protected $editor_name = '';
    protected $enable_on_acymailing = \false;
    protected $folder;
    protected $main_type = 'plugin';
    protected $popup_class = '';
    protected $require_core_auth = \true;
    private $_params;
    private $_pass;
    public function __construct($config = [])
    {
        if ($config instanceof JDispatcherInterface) {
            $dispatcher = $config;
            $config = func_num_args() > 1 ? func_get_arg(1) : [];
            parent::__construct($dispatcher, $config);
        } else {
            parent::__construct($config);
        }
        $this->popup_class = $this->popup_class ?: 'Plugin.EditorButton.' . $this->getClassName() . '.Popup';
    }
    public static function getSubscribedEvents(): array
    {
        return ['onEditorButtonsSetup' => 'onEditorButtonsSetup', ...static::$_extra_events];
    }
    public function extraChecks(object $params): bool
    {
        return \true;
    }
    public function onEditorButtonsSetup(EditorButtonsSetupEvent $event): void
    {
        $disabled = $event->getDisabledButtons();
        if (in_array($this->_name, $disabled)) {
            return;
        }
        $this->editor_name = $event->getEditorId();
        if (!$this->passChecks()) {
            return;
        }
        $buttons = $this->renderButtons();
        if (empty($buttons)) {
            return;
        }
        $this->loadScripts();
        $this->loadStyles();
        $subject = $event->getButtonsRegistry();
        foreach ($buttons as $button) {
            $subject->add($button);
        }
    }
    protected function getButtonText(): string
    {
        $params = $this->getParams();
        $text_ini = strtoupper(str_replace(' ', '_', $params->button_text ?? $this->_name));
        $text = JText::_($text_ini);
        if ($text == $text_ini) {
            $text = JText::_($params->button_text ?? $this->_name);
        }
        return trim($text);
    }
    protected function getParams(): object
    {
        if (!is_null($this->_params)) {
            return $this->_params;
        }
        switch ($this->main_type) {
            case 'component':
                if (Protect::isComponentInstalled($this->_name)) {
                    // Load component parameters
                    $this->_params = Parameters::getComponent($this->_name);
                }
                break;
            case 'plugin':
            default:
                if (Protect::isSystemPluginInstalled($this->_name)) {
                    // Load plugin parameters
                    $this->_params = Parameters::getPlugin($this->_name);
                }
                break;
        }
        return $this->_params;
    }
    protected function getPopupLink(): string
    {
        return 'index.php?rl_qp=1' . '&class=' . $this->popup_class . '&editor=' . $this->editor_name . '&tmpl=component' . '&' . Session::getFormToken() . '=1';
    }
    protected function getPopupOptions(): array
    {
        return ['popupType' => 'iframe', 'height' => '1600px', 'width' => '1200px'];
    }
    protected function loadScripts(): void
    {
    }
    protected function loadStyles(): void
    {
    }
    protected function renderButtons(): array
    {
        return [new Button($this->_name, [
            'action' => 'modal',
            'name' => $this->_name,
            'text' => $this->getButtonText(),
            'icon' => $this->_name . '" aria-hidden="true">' . $this->button_icon . '<span></span class="hidden',
            'iconSVG' => $this->button_icon,
            // This is whole Plugin name, it is needed for keeping backward compatibility
            'link' => $this->getPopupLink(),
            'options' => $this->getPopupOptions(),
        ])];
    }
    private function getClassName(): string
    {
        return (new ReflectionClass($this))->getShortName();
    }
    private function isInstalled(): bool
    {
        $extensions = !is_null($this->check_installed) ? $this->check_installed : [$this->main_type];
        return Extension::areInstalled($this->_name, $extensions);
    }
    private function passChecks(): bool
    {
        if (!is_null($this->_pass)) {
            return $this->_pass;
        }
        $this->_pass = \false;
        if (!Extension::isFrameworkEnabled()) {
            return \false;
        }
        if (!Extension::isAuthorised($this->require_core_auth)) {
            return \false;
        }
        if (!$this->isInstalled()) {
            return \false;
        }
        if (!$this->enable_on_acymailing && Input::get('option', '') == 'com_acymailing') {
            return \false;
        }
        $params = $this->getParams();
        if (!Extension::isEnabledInComponent($params)) {
            return \false;
        }
        if (!Extension::isEnabledInArea($params)) {
            return \false;
        }
        if (!$this->extraChecks($params)) {
            return \false;
        }
        $this->_pass = \true;
        return \true;
    }
}
