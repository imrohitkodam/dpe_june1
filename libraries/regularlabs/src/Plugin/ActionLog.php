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
use Joomla\CMS\Event\Extension\AfterUninstallEvent;
use Joomla\CMS\Event\Model\AfterChangeStateEvent;
use Joomla\CMS\Event\Model\AfterDeleteEvent;
use Joomla\CMS\Event\Model\AfterSaveEvent;
use Joomla\CMS\Language\Text as JText;
use Joomla\Component\Actionlogs\Administrator\Plugin\ActionLogPlugin as JActionLogPlugin;
use Joomla\Event\DispatcherInterface as JDispatcherInterface;
use Joomla\Event\Event as JEvent;
use Joomla\Event\SubscriberInterface;
use RegularLabs\Library\ArrayHelper;
use RegularLabs\Library\Input;
use RegularLabs\Library\Language;
use RegularLabs\Library\Parameters;
class ActionLog extends JActionLogPlugin implements SubscriberInterface
{
    static $_extra_events = [];
    static $ids = [];
    static $item_titles = [];
    static $item_types = [];
    public $alias = '';
    public $events = [];
    public $lang_prefix_change_state = 'PLG_SYSTEM_ACTIONLOGS';
    public $lang_prefix_delete = 'PLG_SYSTEM_ACTIONLOGS';
    public $lang_prefix_install = 'PLG_ACTIONLOG_JOOMLA';
    public $lang_prefix_save = 'PLG_SYSTEM_ACTIONLOGS';
    public $lang_prefix_uninstall = 'PLG_ACTIONLOG_JOOMLA';
    public $name = '';
    public $option = '';
    public $table = null;
    public function __construct($config = [])
    {
        if ($config instanceof JDispatcherInterface) {
            $dispatcher = $config;
            $config = func_num_args() > 1 ? func_get_arg(1) : [];
            parent::__construct($dispatcher, $config);
        } else {
            parent::__construct($config);
        }
        Language::load('plg_actionlog_joomla', JPATH_ADMINISTRATOR);
        Language::load('plg_actionlog_' . $this->alias);
        $config = Parameters::getComponent($this->alias);
        $enable_actionlog = $config->enable_actionlog ?? \true;
        $this->events = $enable_actionlog ? ['*'] : [];
        if ($enable_actionlog && !empty($config->actionlog_events)) {
            $this->events = ArrayHelper::toArray($config->actionlog_events);
        }
        $this->name = JText::_($this->name);
        $this->option = $this->option ?: 'com_' . $this->alias;
        $this->addItems();
    }
    public static function getSubscribedEvents(): array
    {
        return ['onContentAfterDelete' => 'onContentAfterDelete', 'onContentAfterSave' => 'onContentAfterSave', 'onContentChangeState' => 'onContentChangeState', 'onExtensionAfterDelete' => 'onExtensionAfterDelete', 'onExtensionAfterSave' => 'onExtensionAfterSave', 'onExtensionAfterUninstall' => 'onExtensionAfterUninstall', ...static::$_extra_events];
    }
    public function addItem($extension, $type, $title): void
    {
        self::$item_types[$extension] = $type;
        self::$item_titles[$extension] = $title;
    }
    public function addItems(): void
    {
    }
    public function getItem($context): object
    {
        $item = $this->getItemData($context);
        if (!isset($item->file)) {
            $item->file = JPATH_ADMINISTRATOR . '/components/' . $item->option . '/models/' . $item->type . '.php';
        }
        if (!isset($item->model)) {
            $item->model = $this->alias . 'Model' . ucfirst($item->type);
        }
        if (!isset($item->url)) {
            $item->url = 'index.php?option=' . $item->option . '&view=' . $item->type . '&layout=edit&id={id}';
        }
        return $item;
    }
    public function onContentAfterDelete($event): void
    {
        if ($event instanceof AfterDeleteEvent) {
            $context = $event->getContext();
            $table = $event->getItem();
        } elseif ($event instanceof JEvent) {
            $context = $event->getArguments()[0] ?? '';
            $table = $event->getArguments()[1] ?? null;
        } else {
            $context = $event;
            $table = func_get_arg(1);
        }
        if (!str_contains($context, $this->option)) {
            return;
        }
        if (!ArrayHelper::find(['*', 'delete'], $this->events)) {
            return;
        }
        $item = $this->getItem($context);
        $title = $table->title ?? $table->name ?? $table->id;
        $message = ['action' => 'deleted', 'type' => $item->title, 'id' => $table->id, 'title' => $title];
        $this->addLog([$message], $this->lang_prefix_delete . '_CONTENT_DELETED', $context);
    }
    public function onContentAfterSave($event): void
    {
        if ($event instanceof AfterSaveEvent) {
            $context = $event->getContext();
            $table = $event->getItem();
            $isNew = $event->getIsNew();
            $data = $event->getData();
        } elseif ($event instanceof JEvent) {
            $context = $event->getArguments()[0] ?? '';
            $table = $event->getArguments()[1] ?? null;
            $isNew = $event->getArguments()[2] ?? \false;
            $data = $event->getArguments()[3] ?? [];
        } else {
            $context = $event;
            $table = func_get_arg(1);
            $isNew = func_get_arg(2);
            $data = func_get_arg(3) ?? [];
        }
        $data = ArrayHelper::toArray($data);
        if (isset($data['ignore_actionlog']) && $data['ignore_actionlog']) {
            return;
        }
        if (!str_contains($context, $this->option)) {
            return;
        }
        $event = $isNew ? 'create' : 'update';
        if (!ArrayHelper::find(['*', $event], $this->events)) {
            return;
        }
        $item = $this->getItem($context);
        $title = $table->title ?? $table->name ?? $table->id;
        $item_url = str_replace('{id}', $table->id, $item->url);
        $message = ['action' => $isNew ? 'add' : 'update', 'type' => $item->title, 'id' => $table->id, 'title' => $title, 'itemlink' => $item_url];
        $languageKey = $isNew ? $this->lang_prefix_save . '_CONTENT_ADDED' : $this->lang_prefix_save . '_CONTENT_UPDATED';
        $this->addLog([$message], $languageKey, $context);
    }
    public function onContentChangeState($event): void
    {
        if ($event instanceof AfterChangeStateEvent) {
            $context = $event->getContext();
            $ids = $event->getPks();
            $value = $event->getValue();
        } elseif ($event instanceof JEvent) {
            $context = $event->getArguments()[0] ?? '';
            $ids = $event->getArguments()[1] ?? [];
            $value = $event->getArguments()[2] ?? 0;
        } else {
            $context = $event;
            $ids = func_get_arg(1);
            $value = func_get_arg(2);
        }
        if (!str_contains($context, $this->option)) {
            return;
        }
        if (!ArrayHelper::find(['*', 'change_state'], $this->events)) {
            return;
        }
        switch ($value) {
            case 0:
                $languageKey = $this->lang_prefix_change_state . '_CONTENT_UNPUBLISHED';
                $action = 'unpublish';
                break;
            case 1:
                $languageKey = $this->lang_prefix_change_state . '_CONTENT_PUBLISHED';
                $action = 'publish';
                break;
            case 2:
                $languageKey = $this->lang_prefix_change_state . '_CONTENT_ARCHIVED';
                $action = 'archive';
                break;
            case -2:
                $languageKey = $this->lang_prefix_change_state . '_CONTENT_TRASHED';
                $action = 'trash';
                break;
            default:
                return;
        }
        $item = $this->getItem($context);
        if (!$this->table) {
            if (!is_file($item->file)) {
                return;
            }
            require_once $item->file;
            $this->table = (new $item->model())->getTable();
        }
        foreach ($ids as $id) {
            $this->table->load($id);
            $title = $this->table->title ?? $this->table->name ?? $this->table->id;
            $itemlink = str_replace('{id}', $this->table->id, $item->url);
            $message = ['action' => $action, 'type' => $item->title, 'id' => $id, 'title' => $title, 'itemlink' => $itemlink];
            $this->addLog([$message], $languageKey, $context);
        }
    }
    public function onExtensionAfterDelete($event): void
    {
        if ($event instanceof AfterDeleteEvent) {
            $context = $event->getContext();
            $table = $event->getItem();
        } elseif ($event instanceof JEvent) {
            $context = $event->getArguments()[0] ?? '';
            $table = $event->getArguments()[1] ?? null;
        } else {
            $context = $event;
            $table = func_get_arg(1);
        }
        self::onContentAfterDelete($context, $table);
    }
    public function onExtensionAfterSave($event): void
    {
        if ($event instanceof AfterSaveEvent) {
            $context = $event->getContext();
            $table = $event->getItem();
            $isNew = $event->getIsNew();
        } elseif ($event instanceof JEvent) {
            $context = $event->getArguments()[0] ?? '';
            $table = $event->getArguments()[1] ?? null;
            $isNew = $event->getArguments()[2] ?? \false;
        } else {
            $context = $event;
            $table = func_get_arg(1);
            $isNew = func_get_arg(2);
        }
        self::onContentAfterSave($context, $table, $isNew);
    }
    public function onExtensionAfterUninstall($event): void
    {
        if ($event instanceof AfterUninstallEvent) {
            $installer = $event->getInstaller();
            $eid = $event->getEid();
            $result = $event->getRemoved();
        } elseif ($event instanceof JEvent) {
            $installer = $event->getArguments()[0] ?? null;
            $eid = $event->getArguments()[1] ?? 0;
            $result = $event->getArguments()[2] ?? \false;
        } else {
            $installer = func_get_arg(0);
            $eid = func_get_arg(1);
            $result = func_get_arg(2) ?? \false;
        }
        // Prevent duplicate logs
        if (in_array('uninstall_' . $eid, self::$ids, \true)) {
            return;
        }
        $context = Input::get('option', '');
        if (!str_contains($context, $this->option)) {
            return;
        }
        if (!ArrayHelper::find(['*', 'uninstall'], $this->events)) {
            return;
        }
        if ($result === \false) {
            return;
        }
        $manifest = $installer->get('manifest');
        if ($manifest === null) {
            return;
        }
        self::$ids[] = 'uninstall_' . $eid;
        $message = ['action' => 'uninstall', 'type' => $this->lang_prefix_install . '_TYPE_' . strtoupper($manifest->attributes()->type), 'id' => $eid, 'extension_name' => JText::_($manifest->name)];
        $languageKey = $this->lang_prefix_uninstall . '_EXTENSION_UNINSTALLED';
        $this->addLog([$message], $languageKey, 'com_regularlabsmanager');
    }
    private function getItemData(string $extension): object
    {
        if (str_contains($extension, '.')) {
            [$extension, $type] = explode('.', $extension);
        }
        Language::load($extension);
        $type ??= self::$item_types[$extension] ?? 'item';
        $title = self::$item_titles[$extension] ?? JText::_($extension) . ' ' . JText::_('RL_ITEM');
        return (object) ['context' => $extension . '.' . $type, 'option' => $extension, 'type' => $type, 'title' => $title];
    }
}
