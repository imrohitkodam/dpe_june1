<?php

/*
 * @package     Perfect Publisher
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2022 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see         https://www.extly.com
 */

defined('_JEXEC') || exit;

use XTP_BUILD\Extly\Infrastructure\Service\Cms\Joomla\JoomlaVersionAwareTrait;

/**
 * AutoTweetToolbar.
 *
 * @since       1.0
 */
class AutotweetToolbar extends XTF0FToolbar
{
    use JoomlaVersionAwareTrait;

    protected $isModule = false;

    protected $isModal = false;

    protected $isSubview = false;

    /**
     * Class constructor.
     *
     * @param array $config Configuration parameters
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $platform = XTF0FPlatform::getInstance();
        $this->perms->editown = $platform->authorise('core.edit.own', $this->input->getCmd('option', 'com_foobar'));
        $this->perms->manage = $platform->authorise('core.manage', $this->input->getCmd('option', 'com_foobar'));

        $layout = $this->input->get('layout', null, 'cmd');
        $toolbar = $this->input->get('toolbar', null, 'cmd');

        $this->isModule = ('module' === $layout);
        $this->isModal = ('modal' === $layout);
        $this->isSubview = ('none' === $toolbar);

        $this->detectJoomlaVersion();
    }

    /**
     * onCpanelBrowse.
     *
     * @return void
     */
    public function onCpanelsBrowse()
    {
        $this->renderMainMenu();
        $this->title(VersionHelper::getTitle(JText::_('COM_AUTOTWEET_TITLE_CPANELS')), 'autotweet');
        JToolBarHelper::preferences('com_autotweet');
    }

    /**
     * onComposers.
     *
     * @return void
     */
    public function onComposers()
    {
        $this->title(VersionHelper::getTitle(JText::_('COM_AUTOTWEET_TITLE_COMPOSERS_EDIT')), 'perfectpub-logo.svg');

        $option = $this->input->getCmd('option', 'com_foobar');
        $componentName = str_replace('com_', '', $option);

        // Set toolbar title
        $subtitle_key = strtoupper($option.'_TITLE_'.XTF0FInflector::pluralize($this->input->getCmd('view', 'cpanel'))).'_EDIT';
        JToolBarHelper::title(JText::_(strtoupper($option)).': '.JText::_($subtitle_key), $componentName);

        // Set toolbar icons
        if (($this->perms->edit) || ($this->perms->editown)) {
            // Show the apply button only if I can edit the record, otherwise I'll return to the edit form and get a
            // 403 error since I can't do that
            JToolBarHelper::apply();
        }

        JToolBarHelper::save();

        if ($this->perms->create) {
            JToolBarHelper::custom('savenew', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
        }

        self::calendar('xticon far fa-calendar', 'COM_AUTOTWEET_VIEW_CALENDAR_TITLE');

        $this->cancel();
    }

    /**
     * onPostsBrowse.
     *
     * @return void
     */
    public function onPostsBrowse()
    {
        if ($this->isModule) {
            return;
        }

        $this->title(VersionHelper::getTitle(JText::_('COM_AUTOTWEET_TITLE_POSTS')), 'perfectpub-logo.svg');

        $allow_new_reqpost = EParameter::getComponentParam(CAUTOTWEETNG, 'allow_new_reqpost', 0);

        if (($this->perms->create) && ($allow_new_reqpost)) {
            JToolBarHelper::addNew();
        }

        if (($this->perms->edit) || ($this->perms->editown)) {
            JToolBarHelper::editList();
        }

        if ($this->perms->create || (($this->perms->edit) || ($this->perms->editown))) {
            JToolBarHelper::divider();
        }

        if ($this->perms->editstate) {
            JToolBarHelper::publishList();

            // JToolBarHelper::unpublishList();

            JToolBarHelper::divider();
        }

        if ($this->perms->create) {
            JToolBarHelper::custom('copy', 'copy.png', 'copy_f2.png', 'COM_AUTOTWEET_COMMON_COPY_LABEL', false);
            JToolBarHelper::divider();
        }

        if ($this->perms->delete) {
            $option = $this->input->get('option', 'com_autotweet', 'cmd');
            $msg = JText::_($option.'_CONFIRM_DELETE');
            JToolBarHelper::deleteList($msg);
        }

        if ($this->perms->manage) {
            $this->batchButton();
            $this->trash('purge', 'COM_AUTOTWEET_COMMON_PURGE_LABEL', false);
        }

        $this->closeToDashboard();
    }

    /**
     * onRequestsBrowse.
     *
     * @return void
     */
    public function onRequestsBrowse()
    {
        $this->title(VersionHelper::getTitle(JText::_('COM_AUTOTWEET_TITLE_REQUESTS')), 'perfectpub-logo.svg');

        $allow_new_reqpost = EParameter::getComponentParam(CAUTOTWEETNG, 'allow_new_reqpost', 0);

        if (($this->perms->create) && ($allow_new_reqpost)) {
            JToolBarHelper::addNew();
        }

        if (($this->perms->edit) || ($this->perms->editown)) {
            JToolBarHelper::editList();
        }

        if ($this->perms->create || (($this->perms->edit) || ($this->perms->editown))) {
            JToolBarHelper::divider();
        }

        if ($this->perms->editstate) {
            $this->processButton();
            JToolBarHelper::divider();
        }

        if ($this->perms->create) {
            JToolBarHelper::custom('copy', 'copy.png', 'copy_f2.png', 'COM_AUTOTWEET_COMMON_COPY_LABEL', false);
            JToolBarHelper::divider();
        }

        if ($this->perms->delete) {
            $option = $this->input->get('option', 'com_autotweet', 'cmd');
            $msg = JText::_($option.'_CONFIRM_DELETE');
            JToolBarHelper::deleteList($msg);
        }

        if ($this->perms->manage) {
            $this->trash('purge', 'COM_AUTOTWEET_COMMON_PURGE_LABEL', false);
            $this->batchButton();
        }

        self::calendar('xticon far fa-calendar', 'COM_AUTOTWEET_VIEW_CALENDAR_TITLE');

        $this->closeToDashboard();
    }

    /**
     * calendar.
     *
     * @param string $icon Param
     * @param string $alt  Param
     *
     * @return void
     */
    public static function calendar($icon, $alt)
    {
        $bar = JToolbar::getInstance('toolbar');
        $title = JText::_($alt);

        if (PERFECT_PUB_PRO) {
            $dhtml = "<a class=\"xt-toolbar-button btn btn-small\" onclick=\"window.open(this.href,'{$title}','scrollbars=yes,resizable=yes,location=no,menubar=no,status=no,toolbar=no,left=0,top=0,width=960,height=720');return false;\" href=\"index.php?option=com_autotweet&view=calendar&tmpl=component\" target=\"_blank\" data-original-title=\"{$title}\" rel=\"tooltip\"><i class=\"{$icon}\"></i> {$title}</a>";
            $bar->appendButton('Custom', $dhtml, 'calendar');
        } else {
            $dhtml = "<a class=\"xt-toolbar-button btn btn-small disabled\" onclick=\"window.open(this.href,'{$title}','');return false;\" href=\"https://www.extly.com/perfect-publisher.html\" target=\"_blank\" data-original-title=\"{$title}\" rel=\"tooltip\"><i class=\"{$icon} muted\"></i> {$title}</a>";
            $bar->appendButton('Custom', $dhtml, 'calendar');
        }
    }

    /**
     * onRequests.
     *
     * @return void
     */
    public function onRequests()
    {
        $this->onAdd();
        $this->title(VersionHelper::getTitle(JText::_('COM_AUTOTWEET_TITLE_REQUEST_EDIT')), 'perfectpub-logo.svg');
    }

    /**
     * onEvergreens.
     *
     * @return void
     */
    public function onEvergreens()
    {
        JToolbar::getInstance('toolbar')->appendButton('Link', 'arrow-left', 'COM_AUTOTWEET_TITLE_CPANELS', 'index.php?option=com_autotweet&view=cpanels');

        $this->title(VersionHelper::getTitle(JText::_('COM_AUTOTWEET_TITLE_EVERGREEN_EDIT')), 'perfectpub-logo.svg');

        if ($this->perms->manage) {
            // $this->batchButton();

            $option = $this->input->get('option', 'com_autotweet', 'cmd');
            $msg = JText::_($option.'_CONFIRM_DELETE');
            JToolBarHelper::deleteList($msg);
        }

        $this->closeToDashboard();
    }

    /**
     * onChannelsBrowse.
     *
     * @return void
     */
    public function onChannelsBrowse()
    {
        $this->title(VersionHelper::getTitle(JText::_('COM_AUTOTWEET_TITLE_CHANNELS')), 'perfectpub-logo.svg');
        $this->renderMenuForBrowseWithCopy();
    }

    /**
     * onChannels.
     *
     * @return void
     */
    public function onChannels()
    {
        $this->onAdd();
        $this->title(VersionHelper::getTitle(JText::_('COM_AUTOTWEET_TITLE_CHANNELS_EDIT')), 'perfectpub-logo.svg');
    }

    /**
     * onRulesBrowse.
     *
     * @return void
     */
    public function onRulesBrowse()
    {
        $this->title(VersionHelper::getTitle(JText::_('COM_AUTOTWEET_TITLE_RULES')), 'perfectpub-logo.svg');
        $this->renderMenuForBrowseWithCopy();
    }

    /**
     * onRules.
     *
     * @return void
     */
    public function onRules()
    {
        $this->onAdd();
        $this->title(VersionHelper::getTitle(JText::_('COM_AUTOTWEET_TITLE_RULE_EDIT')), 'perfectpub-logo.svg');
    }

    /**
     * onPostBrowse.
     *
     * @return void
     */
    public function onPostBrowse()
    {
        throw new Exception('What? onPostBrowse');
    }

    /**
     * onPosts.
     *
     * @return void
     */
    public function onPosts()
    {
        $this->onAdd();
        $this->title(VersionHelper::getTitle(JText::_('COM_AUTOTWEET_TITLE_POST_EDIT')), 'perfectpub-logo.svg');
    }

    /**
     * onFeedsBrowse.
     *
     * @return void
     */
    public function onFeedsBrowse()
    {
        $this->title(VersionHelper::getTitle(JText::_('COM_AUTOTWEET_TITLE_FEEDS')), 'perfectpub-logo.svg');
        $this->renderMenuForBrowseWithCopy();

        if ($this->perms->manage) {
            $this->importButton();
        }
    }

    /**
     * onFeedsAdd.
     *
     * @return void
     */
    public function onFeeds()
    {
        // $option = $this->input->getCmd('option', 'com_foobar');
        // $componentName = str_replace('com_', '', $option);

        // Set toolbar title
        // $subtitle_key = strtoupper($option.'_TITLE_'.XTF0FInflector::pluralize($this->input->getCmd('view', 'cpanel'))).'_EDIT';
        // $this->title(JText::_(strtoupper($option)).' &ndash; <small>'.JText::_($subtitle_key).'</small>', $componentName);
        $this->title(VersionHelper::getTitle(JText::_('COM_AUTOTWEET_TITLE_FEEDS_EDIT')), 'perfectpub-logo.svg');

        // Set toolbar icons
        $bar = JToolbar::getInstance('toolbar');

        // Add an 'Apply & Preview' button
        $bar->appendButton('Standard', 'apply', 'COM_AUTOTWEET_VIEW_FEED_PREVIEW_JTOOLBAR_APPLY', 'apply', false);

        JToolBarHelper::save();

        if ((isset($this->perms->create)) && ($this->perms->create)) {
            JToolBarHelper::custom('savenew', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
        }

        if ((isset($this->perms->manage)) && ($this->perms->manage)) {
            $this->importButton();
        }

        $this->cancel();
    }

    /**
     * onInfosBrowse.
     *
     * @return void
     */
    public function onInfosBrowse()
    {
        $this->renderMainMenu();
        $this->title(VersionHelper::getTitle(JText::_('COM_AUTOTWEET_TITLE_INFOS')), 'perfectpub-logo.svg');
        JToolBarHelper::preferences('com_autotweet');
    }

    /**
     * onManagersEdit.
     *
     * @return void
     */
    public function onManagersEdit()
    {
        $this->title(VersionHelper::getTitle(JText::_('COM_AUTOTWEET_TITLE_MANAGERS_EDIT')), 'perfectpub-logo.svg');

        // Set toolbar icons
        JToolBarHelper::apply();
        JToolBarHelper::save();
        $this->closeToDashboard();
    }

    /**
     * Renders the submenu (toolbar links) for all detected views of this component.
     *
     * @return void
     */
    public function renderSubmenu()
    {
        $views = $this->internalViews();

        foreach ($views as $label => $view) {
            if (!is_array($view)) {
                $this->addSubmenuLink($view);
            } else {
                $label_text = JText::_($label);
                $label_icon = JText::_(str_replace('_TITLE_', '_ICON_', $label));
                $label = $label_icon.' '.$label_text;
                $this->appendLink($label, '', false);

                foreach ($view as $v) {
                    $this->addSubmenuLink($v, $label);
                }
            }
        }
    }

    /**
     * Append a link to the link bar.
     *
     * @param string      $name   The text of the link
     * @param string|null $link   The link to render; set to null to render a separator
     * @param bool        $active True if it's an active link
     * @param string|null $icon   Icon class (used by some renderers, like the Bootstrap renderer)
     * @param string|null $parent The parent element (referenced by name)) Thsi will create a dropdown list
     *
     * @return void
     */
    public function appendLink($name, $link = null, $active = false, $icon = null, $parent = '')
    {
        parent::appendLink($name, $link, $active, $icon, $parent);
    }

    /**
     * Title cell.
     * For the title and toolbar to be rendered correctly,
     * this title fucntion must be called before the starttable function and the toolbars icons
     * this is due to the nature of how the css has been used to postion the title in respect to the toolbar.
     *
     * @param string $title the title
     * @param string $icon  the space-separated names of the image
     *
     * @return void
     */
    public static function title($title, $icon = 'generic.png')
    {
        $layout = new JLayoutFile('joomla.toolbar.title');
        $html = $layout->render(['title' => $title, 'icon' => $icon]);

        $app = \Joomla\CMS\Factory::getApplication();
        $app->JComponentTitle = $html;
        \Joomla\CMS\Factory::getDocument()->setTitle($app->getCfg('sitename').' - '.$title);
    }

    /**
     * Writes a common 'trash' button for a list of records.
     *
     * @param string $task  an override for the task
     * @param string $alt   an override for the alt text
     * @param bool   $check true to allow lists
     *
     * @return void
     */
    public static function trash($task = 'remove', $alt = 'JTOOLBAR_TRASH', $check = true)
    {
        $bar = JToolbar::getInstance('toolbar');

        // Add a trash button.
        $bar->appendButton('Confirm', JText::_('COM_AUTOTWEET_CONFIRM_PURGE'), 'trash', $alt, $task, $check, false);
    }

    /**
     * Writes a common 'batch' button for a list of records.
     *
     * @param string $alt an override for the alt text
     *
     * @return void
     */
    public function batchButton($alt = 'JTOOLBAR_BATCH')
    {
        $bar = JToolbar::getInstance('toolbar');

        if ($this->isJ4) {
            $bar->popupButton('batch')
                ->text('JTOOLBAR_BATCH')
                ->selector('collapseModal')
                ->listCheck(true);

            return;
        }

        $title = JText::_('JTOOLBAR_BATCH');
        $layout = new JLayoutFile('joomla.toolbar.batch');
        $dhtml = $layout->render(['title' => $title]);
        $bar->appendButton('Custom', $dhtml, 'batch');
    }

    /**
     * Renders the toolbar for the component's Add pages.
     *
     * @return void
     */
    public function onAdd()
    {
        $option = $this->input->getCmd('option', 'com_foobar');
        $componentName = str_replace('com_', '', $option);

        // Set toolbar title
        $subtitle_key = strtoupper($option.'_TITLE_'.XTF0FInflector::pluralize($this->input->getCmd('view', 'cpanel'))).'_EDIT';
        JToolBarHelper::title(JText::_(strtoupper($option)).' &ndash; <small>'.JText::_($subtitle_key).'</small>', $componentName);

        // Set toolbar icons
        if ($this->perms->edit || $this->perms->editown) {
            // Show the apply button only if I can edit the record, otherwise I'll return to the edit form and get a
            // 403 error since I can't do that
            JToolBarHelper::apply();
        }

        JToolBarHelper::save();

        if ($this->perms->create) {
            JToolBarHelper::custom('savenew', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
        }

        $this->cancel();
    }

    /**
     * Renders the toolbar for the component's Add pages.
     *
     * @return void
     */
    public function onComposerPosts()
    {
    }

    public static function tabPaneActive()
    {
        return EXTLY_J3 ? 'tab-pane active' : 'tab-pane fade show active';
    }

    /**
     * getMyViews.
     *
     * @return array
     */
    protected function getMyViews()
    {
        $views = ['cpanel'];

        $allViews = parent::getMyViews();

        foreach ($allViews as $view) {
            if (!in_array($view, $views, true)) {
                $views[] = $view;
            }
        }

        return $views;
    }

    private static function renderHiddenInputReturnurl()
    {
        $url = base64_encode(JRoute::_('index.php?option=com_autotweet&view=cpanels'));

        return '<input type="hidden" name="returnurl" value="'.$url.'" />';
    }

    /**
     * renderMainMenu.
     *
     * @return void
     */
    private function renderMainMenu()
    {
        // On frontend, buttons must be added specifically
        [$isCli, $isAdmin] = XTF0FDispatcher::isCliAdmin();

        if (($isAdmin) || ($this->renderFrontendSubmenu)) {
            $this->renderSubmenu();
        }

        if ((!$isAdmin) && (!$this->renderFrontendButtons)) {
            return;
        }
    }

    /**
     * renderMenuForBrowseWithCopy.
     *
     * @param bool $allowCopy param
     *
     * @return void
     */
    private function renderMenuForBrowseWithCopy($allowCopy = true)
    {
        // Add toolbar buttons
        if ($this->perms->create) {
            JToolBarHelper::addNew();
        }

        if (($this->perms->edit) || ($this->perms->editown)) {
            JToolBarHelper::editList();
        }

        if ($this->perms->create || (($this->perms->edit) || ($this->perms->editown))) {
            JToolBarHelper::divider();
        }

        if ($this->perms->editstate) {
            JToolBarHelper::publishList();
            JToolBarHelper::unpublishList();
            JToolBarHelper::divider();
        }

        if (($allowCopy) && ($this->perms->create)) {
            JToolBarHelper::custom('copy', 'copy.png', 'copy_f2.png', 'COM_AUTOTWEET_COMMON_COPY_LABEL', false);
            JToolBarHelper::divider();
        }

        if ($this->perms->delete) {
            $option = $this->input->get('option', 'com_autotweet', 'cmd');
            $msg = JText::_($option.'_CONFIRM_DELETE');
            JToolBarHelper::deleteList($msg);
        }

        $this->closeToDashboard();
    }

    /**
     * internalViews.
     *
     * @return array
     */
    private function internalViews()
    {
        $views = [
            'cpanels',
            'composer',
        ];

        // Activities menu definition
        $activities = ['requests'];

        // Rules and evergreens - Only in the backend
        if (PERFECT_PUB_PRO) {
            $activities[] = 'evergreens';
        }

        $activities[] = 'rules';

        $activities[] = 'posts';
        $views['COM_AUTOTWEET_TITLE_ACTIVITIES'] = $activities;

        $views[] = 'channels';

        // Feeds and System Check - Only in the backend
        $views[] = 'feeds';
        $views[] = 'infos';
        $views[] = 'usermanual';
        $views[] = 'options';

        return $views;
    }

    /**
     * addSubmenuLink.
     *
     * @param object $view   Param
     * @param object $parent Param
     *
     * @return void
     */
    private function addSubmenuLink($view, $parent = null)
    {
        static $activeView = null;

        if (empty($activeView)) {
            $activeView = $this->input->getCmd('view', 'cpanel');
        }

        if ('cpanels' === $activeView) {
            $activeView = 'cpanel';
        }

        $icon_key = strtoupper($this->component).'_ICON_'.strtoupper($view);
        $icon = JText::_($icon_key);

        $key = strtoupper($this->component).'_TITLE_'.strtoupper($view);

        if (strtoupper(JText::_($key)) === $key) {
            $altview = XTF0FInflector::isPlural($view) ? XTF0FInflector::singularize($view) : XTF0FInflector::pluralize($view);
            $key2 = strtoupper($this->component).'_TITLE_'.strtoupper($altview);

            if (strtoupper(JText::_($key2)) === $key2) {
                $name = ucfirst($view);
            } else {
                $name = JText::_($key2);
            }
        } else {
            $name = JText::_($key);
        }

        if ('usermanual' === $view) {
            $link = 'https://www.extly.com/docs/';
        } elseif ('options' === $view) {
            $component = urlencode($this->component);
            $uri = (string) \Joomla\CMS\Uri\Uri::getInstance();
            $return = urlencode(base64_encode($uri));

            $link = 'index.php?option=com_config&amp;view=component&amp;component='
                    .$component.'&amp;path=&amp;return='.$return;
        } else {
            $link = 'index.php?option='.$this->component.'&view='.$view;
        }

        $active = $view === $activeView;

        $this->appendLink($icon.' '.$name, $link, $active, null, $parent);
    }

    private function processButton()
    {
        if ($this->isJ4) {
            JToolBarHelper::custom('process', 'tasks', 'tasks', 'COM_AUTOTWEET_COMMON_PROCESS_LABEL', false);

            return;
        }

        JToolBarHelper::custom('process', 'process.png', 'process.png', 'COM_AUTOTWEET_COMMON_PROCESS_LABEL', false);
    }

    private function importButton()
    {
        if ($this->isJ4) {
            JToolBarHelper::custom('import', 'tasks', 'tasks', 'COM_AUTOTWEET_COMMON_IMPORT_LABEL', false);

            return;
        }

        JToolBarHelper::custom('import', 'process.png', 'process.png', 'COM_AUTOTWEET_COMMON_IMPORT_LABEL', false);
    }

    private function cancel()
    {
        JToolBarHelper::cancel('cancel', 'JTOOLBAR_CLOSE');
    }

    private function closeToDashboard()
    {
        $url = JRoute::_('index.php?option=com_autotweet&view=cpanels');
        $bar = JToolbar::getInstance('toolbar');
        $title = JText::_('JTOOLBAR_CLOSE');
        $icon = 'cancel';

        if (EXTLY_J3) {
            $dhtml = "<div class=\"btn-wrapper\" id=\"toolbar-{$icon}\"><button onclick=\"document.location= '{$url}';\" class=\"btn btn-small button-{$icon}\"><span class=\"icon-{$icon}\" aria-hidden=\"true\"></span>{$title}</button>";
            $bar->appendButton('Custom', $dhtml, 'cancel');

            return;
        }

        $dhtml = "<a class=\"xt-toolbar-button btn btn-small\" onclick=\"document.location= '{$url}';\" data-original-title=\"{$title}\" rel=\"tooltip\"><i class=\"{$icon}\"></i> {$title}</a>";
        $bar->appendButton('Custom', $dhtml, 'cancel');
    }
}
