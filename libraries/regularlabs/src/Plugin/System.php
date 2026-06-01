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
use Joomla\CMS\Application\CMSApplication as JCMSApplication;
use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Event\Finder\ResultEvent;
use Joomla\CMS\Event\Model\PrepareFormEvent;
use Joomla\CMS\Event\Module\AfterRenderModuleEvent;
use Joomla\CMS\Event\Module\BeforeRenderModuleEvent;
use Joomla\CMS\Event\Module\PrepareModuleListEvent;
use Joomla\CMS\Event\Plugin\AjaxEvent;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Form\Form as JForm;
use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\Plugin\CMSPlugin as JCMSPlugin;
use Joomla\CMS\Plugin\PluginHelper as JPluginHelper;
use Joomla\Component\Finder\Administrator\Indexer\Helper as JIndexerHelper;
use Joomla\Component\Finder\Administrator\Indexer\Query as JIndexerQuery;
use Joomla\Component\Finder\Administrator\Indexer\Result as JIndexerResult;
use Joomla\Database\DatabaseDriver as JDatabaseDriver;
use Joomla\Event\DispatcherInterface as JDispatcherInterface;
use Joomla\Event\Event as JEvent;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry as JRegistry;
use RegularLabs\Library\Article;
use RegularLabs\Library\DB;
use RegularLabs\Library\Document;
use RegularLabs\Library\Input;
use RegularLabs\Library\Language;
use RegularLabs\Library\Protect;
use RegularLabs\Library\User;
use function func_num_args;
class System extends JCMSPlugin implements SubscriberInterface
{
    static $_extra_events = [];
    public $_alias = '';
    public $_lang_prefix = '';
    public $_title = '';
    protected $_can_disable_by_url = \true;
    protected $_doc_ready = \false;
    protected $_enable_in_admin = \false;
    protected $_enable_in_frontend = \true;
    protected $_enable_in_indexer = \true;
    protected $_id = 0;
    protected $_page_types = ['html', 'feed', 'pdf', 'xml', 'ajax', 'json', 'raw'];
    protected $_pass;
    /**
     * @var    JCMSApplication
     */
    protected $app;
    protected $autoloadLanguage = \true;
    /**
     * @var    JDatabaseDriver
     */
    protected $db;
    public function __construct($config = [])
    {
        if ($config instanceof JDispatcherInterface) {
            $dispatcher = $config;
            $config = func_num_args() > 1 ? func_get_arg(1) : [];
            parent::__construct($dispatcher, $config);
        } else {
            parent::__construct($config);
        }
        if (isset($config['id'])) {
            $this->_id = $config['id'];
        }
        $this->app = JFactory::getApplication();
        $this->db = DB::get();
        if (empty($this->_alias)) {
            $this->_alias = $this->_name;
        }
        if (empty($this->_title)) {
            $this->_title = strtoupper($this->_alias);
        }
        $this->init();
    }
    public static function getSubscribedEvents(): array
    {
        return ['onAfterDispatch' => 'onAfterDispatch', 'onAfterInitialise' => 'onAfterInitialise', 'onAfterRender' => 'onAfterRender', 'onAfterRenderModule' => 'onAfterRenderModule', 'onAfterRoute' => 'onAfterRoute', 'onBeforeCompileHead' => 'onBeforeCompileHead', 'onContentPrepare' => 'onContentPrepare', 'onContentPrepareForm' => 'onContentPrepareForm', 'onFinderResult' => 'onFinderResult', ...static::$_extra_events];
    }
    public function handleAjaxResult(mixed $data, ?JEvent $event = null): mixed
    {
        if ($event instanceof AjaxEvent) {
            $event->addResult($data);
            return null;
        }
        if ($event instanceof JEvent) {
            $event->setArgument('result', [$data]);
            return null;
        }
        return $data;
    }
    public function handleOnFinderResult(JIndexerResult $item, JIndexerQuery|array $query): void
    {
        $description = $item->description ?? '';
        $summary = $item->getElement('summary') ?? '';
        if (empty($description) && empty($summary)) {
            return;
        }
        $article = (object) ['id' => $item->getElement('id')];
        if (!empty($description)) {
            $article->fulltext = $description;
            Article::processText('fulltext', $article, $this, 'processArticle', ['article', 'com_finder.index', $article]);
            $item->description = JIndexerHelper::parse($article->fulltext);
        }
        if ($description == $summary) {
            $item->setElement('summary', $item->description);
            return;
        }
        if (!empty($summary)) {
            $article->fulltext = $summary;
            Article::processText('fulltext', $article, $this, 'processArticle', ['article', 'com_finder.index', $article]);
            $item->setElement('summary', $article->fulltext);
        }
    }
    public function handleOnPrepareModuleList(?array &$modules): void
    {
    }
    public function handleOnRenderModule(?object &$module): void
    {
    }
    public function init(): void
    {
    }
    /**
     * @param string $extension
     * @param string $basePath
     *
     * @return  bool
     */
    public function loadLanguage($extension = '', $basePath = JPATH_ADMINISTRATOR)
    {
        parent::loadLanguage('plg_system_regularlabs', JPATH_LIBRARIES . '/regularlabs');
        return parent::loadLanguage();
    }
    public function onAfterDispatch(): void
    {
        if (!$this->passChecks()) {
            return;
        }
        $this->handleOnAfterDispatch();
        $buffer = Document::getComponentBuffer();
        if (empty($buffer) || !is_string($buffer)) {
            return;
        }
        $this->loadStylesAndScripts($buffer);
        if (empty($buffer)) {
            return;
        }
        $this->changeDocumentBuffer($buffer);
        Document::setComponentBuffer($buffer);
    }
    public function onAfterInitialise(): void
    {
        Language::load('plg_system_' . $this->_name);
        if (!$this->passChecks()) {
            return;
        }
        $this->handleOnAfterInitialise();
    }
    public function onAfterRender(): void
    {
        if (!$this->passChecks()) {
            return;
        }
        $this->handleOnAfterRender();
        $html = $this->app->getBody();
        if ($html == '') {
            return;
        }
        if (!$this->changeFinalHtmlOutput($html)) {
            return;
        }
        $this->cleanFinalHtmlOutput($html);
        $this->app->setBody($html);
    }
    public function onAfterRenderModule($event): void
    {
        if (!$this->passChecks()) {
            return;
        }
        if ($event instanceof AfterRenderModuleEvent) {
            $module = $event->getModule();
            $params = $event->getArguments();
        } elseif ($event instanceof JEvent) {
            $module = $event->getArguments()[0];
            $params = $event->getArguments()[1] ?? [];
        } else {
            $module = $event;
            $params = func_get_arg(1);
        }
        $this->handleOnAfterRenderModule($module, $params);
    }
    public function onAfterRoute(): void
    {
        $this->_doc_ready = \true;
        if (!$this->passChecks()) {
            return;
        }
        $this->handleOnAfterRoute();
    }
    public function onBeforeCompileHead(): void
    {
        if (!$this->passChecks()) {
            return;
        }
        $this->handleOnBeforeCompileHead();
    }
    /**
     * Example:
     *  new ContentPrepareEvent('onEventName', ['context' => 'com_example.example', 'subject' => $contentObject, 'params' => $params, 'page' => $pageNum]);
     */
    public function onContentPrepare($event): void
    {
        if (!$this->passChecks()) {
            return;
        }
        if ($event instanceof ContentPrepareEvent) {
            $context = $event->getContext();
            $article = $event->getItem();
            $params = $event->getParams();
            $page = $event->getPage() ?? 0;
        } elseif ($event instanceof JEvent) {
            $context = $event->getArguments()[0];
            $article = $event->getArguments()[1] ?? (object) [];
            $params = $event->getArguments()[2] ?? new JRegistry();
            $page = $event->getArguments()[3] ?? 0;
        } else {
            $context = $event;
            $article = func_get_arg(1);
            $params = func_get_arg(2);
            $page = func_get_arg(3) ?? 0;
        }
        $area = isset($article->created_by) ? 'article' : 'other';
        $context = $params instanceof JRegistry && $params->get('rl_search') ? 'com_search.' . $params->get('readmore_limit') : ($context ?: '');
        if (!is_string($context)) {
            $context = '';
        }
        if (!$this->handleOnContentPrepare($area, $context, $article, $params, $page)) {
            return;
        }
        Article::process($article, $context, $this, 'processArticle', [$area, $context, $article, $page]);
    }
    public function onContentPrepareForm($event): void
    {
        if (!$this->passChecks()) {
            return;
        }
        if ($event instanceof PrepareFormEvent) {
            $form = $event->getForm();
            $data = $event->getData();
        } elseif ($event instanceof JEvent) {
            $form = $event->getArguments()[0];
            $data = $event->getArguments()[1] ?? (object) [];
        } else {
            $form = func_get_arg(0);
            $data = func_get_arg(1);
        }
        $data = (object) $data;
        $this->handleOnContentPrepareForm($form, $data);
    }
    public function onFinderResult($event): void
    {
        if (!$this->passChecks()) {
            return;
        }
        if ($event instanceof ResultEvent) {
            $item = $event->getItem();
            $query = $event->getQuery();
        } elseif ($event instanceof JEvent) {
            $item = $event->getArguments()[0];
            $query = $event->getArguments()[1] ?? [];
        } else {
            $item = func_get_arg(0);
            $query = func_get_arg(1);
        }
        $this->handleOnFinderResult($item, $query);
    }
    public function onPrepareModuleList($event): void
    {
        if (!$this->passChecks()) {
            return;
        }
        if ($event instanceof PrepareModuleListEvent) {
            $modules = $event->getModules();
        } elseif ($event instanceof JEvent) {
            $modules =& $event->getArguments()[0];
        } else {
            $modules = $event;
        }
        $this->handleOnPrepareModuleList($modules);
        if ($event instanceof PrepareModuleListEvent) {
            $event->updateModules($modules);
        }
    }
    public function onRenderModule($event): void
    {
        if (!$this->passChecks()) {
            return;
        }
        if ($event instanceof BeforeRenderModuleEvent) {
            $module = $event->getModule();
        } elseif ($event instanceof JEvent) {
            $module = $event->getArguments()[0];
        } else {
            $module = $event;
        }
        $this->handleOnRenderModule($module);
    }
    public function processArticle(string &$string, string $area = 'article', string $context = '', mixed $article = null, int $page = 0): void
    {
    }
    protected function changeDocumentBuffer(string &$buffer): bool
    {
        return \false;
    }
    protected function changeFinalHtmlOutput(string &$html): bool
    {
        return \false;
    }
    protected function changeModulePositionOutput(string &$buffer, object &$params): void
    {
    }
    protected function cleanFinalHtmlOutput(string &$html): void
    {
    }
    protected function extraChecks(): bool
    {
        return \true;
    }
    protected function handleFeedArticles(): void
    {
        if (!empty($this->_page_types) && !in_array('feed', $this->_page_types, \true)) {
            return;
        }
        if (!Document::isFeed() && Input::get('option', '') != 'com_acymailing') {
            return;
        }
        if (!isset(Document::get()->items)) {
            return;
        }
        $context = 'feed';
        $items = Document::get()->items;
        $params = null;
        foreach ($items as $item) {
            $this->handleOnContentPrepare('article', $context, $item, $params);
        }
    }
    protected function handleOnAfterDispatch(): void
    {
        $this->handleFeedArticles();
    }
    protected function handleOnAfterInitialise(): void
    {
    }
    protected function handleOnAfterRender(): void
    {
    }
    protected function handleOnAfterRenderModule(object &$module, array &$params): void
    {
    }
    protected function handleOnAfterRoute(): void
    {
    }
    protected function handleOnBeforeCompileHead(): void
    {
    }
    protected function handleOnContentPrepare(string $area, string $context, mixed &$article, mixed &$params, int $page = 0): bool
    {
        return \true;
    }
    protected function handleOnContentPrepareForm(JForm $form, object $data): void
    {
    }
    protected function is3rdPartyEditPage(): bool
    {
        // Disable on SP PageBuilder edit form: option=com_sppagebuilder&view=form
        if (Input::get('option', '') == 'com_sppagebuilder' && Input::get('view', '') == 'form') {
            return \true;
        }
        return \false;
    }
    protected function loadStylesAndScripts(string &$buffer): void
    {
    }
    protected function passChecks(): bool
    {
        if (!is_null($this->_pass)) {
            return $this->_pass;
        }
        $this->setPass(\false);
        if (!$this->isFrameworkEnabled()) {
            return \false;
        }
        if ($this->is3rdPartyEditPage()) {
            return \false;
        }
        if ($this->_doc_ready && !$this->passPageTypes()) {
            return \false;
        }
        if (!$this->_enable_in_frontend && $this->app->isClient('site')) {
            return \false;
        }
        $is_joomlaupdate = $this->app->input->get('option') == 'com_joomlaupdate' && $this->app->input->get('task') == 'install';
        $is_indexer = $this->app->input->get('option') == 'com_finder' && $this->app->input->get('task') == 'batch';
        if ($this->app->input->get('option')) {
            $this->resetPass();
        }
        if ($is_joomlaupdate) {
            return \false;
        }
        if (!$this->_enable_in_indexer && $is_indexer) {
            return \false;
        }
        $is_admin = !$this->app->isClient('site') && !$is_indexer;
        if (!$this->_enable_in_admin && $is_admin) {
            return \false;
        }
        // disabled by url?
        if ($this->_can_disable_by_url && Protect::isDisabledByUrl($this->_alias)) {
            return \false;
        }
        if (!$this->extraChecks()) {
            return \false;
        }
        $this->setPass(\true);
        return \true;
    }
    protected function passPageTypes(): bool
    {
        if (empty($this->_page_types)) {
            return \true;
        }
        if (in_array('*', $this->_page_types, \true)) {
            return \true;
        }
        if (Document::isFeed()) {
            return in_array('feed', $this->_page_types, \true);
        }
        if (Document::isPDF()) {
            return in_array('pdf', $this->_page_types, \true);
        }
        $page_type = Document::get()->getType();
        return in_array($page_type, $this->_page_types, \true);
    }
    protected function throwError(string $error): void
    {
        // Return if page is not an admin page or the admin login page
        if (!JFactory::getApplication()->isClient('administrator') || User::isGuest()) {
            return;
        }
        // load the admin language file
        JFactory::getApplication()->getLanguage()->load('plg_' . $this->_type . '_' . $this->_name, JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name);
        $text = JText::sprintf($this->_lang_prefix . '_' . $error, JText::_($this->_title));
        $text = JText::_($text) . ' ' . JText::sprintf($this->_lang_prefix . '_EXTENSION_CAN_NOT_FUNCTION', JText::_($this->_title));
        // Check if message is not already in queue
        $messagequeue = JFactory::getApplication()->getMessageQueue();
        foreach ($messagequeue as $message) {
            if ($message['message'] == $text) {
                return;
            }
        }
        JFactory::getApplication()->enqueueMessage($text, 'error');
    }
    private function isFrameworkEnabled(): bool
    {
        if (!defined('REGULAR_LABS_LIBRARY_ENABLED')) {
            $this->setIsFrameworkEnabled();
        }
        if (!REGULAR_LABS_LIBRARY_ENABLED) {
            $this->throwError('REGULAR_LABS_LIBRARY_NOT_ENABLED');
        }
        return REGULAR_LABS_LIBRARY_ENABLED;
    }
    private function resetPass(): void
    {
        $this->_pass = null;
    }
    private function setIsFrameworkEnabled(): void
    {
        if (!JPluginHelper::isEnabled('system', 'regularlabs')) {
            $this->throwError('REGULAR_LABS_LIBRARY_NOT_ENABLED');
            define('REGULAR_LABS_LIBRARY_ENABLED', \false);
            return;
        }
        define('REGULAR_LABS_LIBRARY_ENABLED', \true);
    }
    private function setPass(bool $pass): void
    {
        if (!$this->_doc_ready) {
            return;
        }
        $this->_pass = (bool) $pass;
    }
}
