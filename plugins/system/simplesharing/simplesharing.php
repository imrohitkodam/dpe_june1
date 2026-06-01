<?php

/**
 * @version     1.0.44
 * @package     plg_simplesharing
 * @copyright   Copyright (C) 2014-2017. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @author      NYC HelpDesk.co LLC <support@nychelpdesk.co> - nychelpdesk.co
 */
// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

/**
 * Joomla! System redMigrator Plugin
 *
 * @package		Joomla
 * @subpackage	System
 */
class plgSystemSimpleSharing extends JPlugin {

    /**
     * Folder where the helpers are stored
     *
     * @var  string
     */
    //protected $helpersFolder = null;
    protected $debugMode = null;

    /**
     * Constructor
     *
     * For php4 compatability we must not use the __constructor as a constructor for plugins
     * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
     * This causes problems with cross-referencing necessary for the observer design pattern.
     *
     * @param   object  &$subject  The object to observe
     * @param   array   $config    An array that holds the plugin configuration
     *
     * @since	1.0
     */
    public function __construct(&$subject, $config) {
        parent::__construct($subject, $config);

        // Load plugin language
        $this->loadLanguage();

        //$this->helpersFolder = JPATH_ROOT . '/plugins/system/simplesharing/helpers';

        jimport('joomla.log.log');

        JLog::addLogger(array(
            'text_file' => 'plg.simplesharing.php',
            'text_entry_format' => '{DATETIME} {PRIORITY} {MESSAGE}'
                ), JLog::ALL, 'plg_simplesharing'
        );        
    }

    function onAfterInitialise() {

        $app = JFactory::getApplication();
        $simpleShare = $app->input->getInt('simpleshare', 0);

        if ($simpleShare == 1) {
            $this->debugMode = $app->input->getInt('debug_mode', 0);            
            $this->_log("===Simple Sharing task initialized===");
            jimport('joomla.user.helper');
            $creds = $app->input->getArray(array(
                'SSH_USER' => '',
                'SSH_PW' => '',
                'SSH_KEY' => '',
                'SSH_AUTH' => ''
            ));
            $this->_log("Got authorization data");
            // Getting the database instance
            $db = JFactory::getDbo();
            $this->_log('Starting authorization...', JLog::DEBUG);
            if (!$this->_authorize($db, $creds, $this->params)) {
                $this->_log('Invalid password.', JLog::ERROR);
                JResponse::setHeader('status', 400);
                JResponse::setBody('Invalid password.');
                JResponse::sendHeaders();
                exit;
            }

            $this->_log('Authorized successfully.');

            $mission = '_' . $app->input->getWord('sshtask', '');
            $this->_log("Starting mission: " . $mission);

            try {
                //remove credentials and task from the data array
                $data = array_diff_assoc($app->input->getArray(), $creds);
                if(!empty($data['introtext'])){
                    $data['introtext'] = $app->input->get('introtext', null, 'raw');
                }
                if(!empty($data['fulltext'])){
                    $data['fulltext'] = $app->input->get('fulltext', null, 'raw');
                }
                unset($data['sshtask'],$data['debug_mode']);
                $data['user'] = $creds['user'];
                $return = json_encode($this->$mission($data));
                if ($return !== false) {
                    $this->_log('Mission completed successfully.');
                    echo $return;
                } else {
                    JResponse::setHeader('status', 407);
                    JResponse::setBody('Failed to process the request, check the log file.');
                    JResponse::sendHeaders();
                    exit;
                }
            } catch (Exception $e) {
                $this->_log($e->getMessage(), JLog::ERROR);
                exit;
            }


            exit; // Exit
        }
    }

// end method

    function _getCategories($params = null) {
        $this->_log("Start getting categories");
        try {
            $categories = JHtml::_('category.options', 'com_content');
            $this->_log("Categories loaded successfully.");
            return $categories;
        } catch (Exception $e) {
            $this->_log($e->getMessage(), JLog::ERROR);
            return false;
        }
    }

    function _createItem($data = null) {
        $this->_log('Started create article');
        jimport('joomla.filesystem.file');
        JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_content/models');
        JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_content/tables');
        $model = JModelLegacy::getInstance('Article', 'ContentModel');
        $user = $data['user'];
        $featured = $data['featured'];
        $creatCopy =  $data['create_copy'];
        $this->_log("Create copy=". $creatCopy);
        unset($data['user'], $data['featured'], $data['create_copy']);
        //Check whether the content already exists, with title
        $db = JFactory::getDbo();
        $this->_log('Checking for article with title: '. $data['title']);
        $query = $db->getQuery(true);
        $query->select('*')
                ->from("#__content")
                ->where('title=' . $db->q($data['title']));
        $db->setQuery($query);
        $content = $db->loadObject();
        $pk= null;
//nullify data that needs to be reset after sharing
        if ($content->id && !$creatCopy) { //existing content
            $this->_log('Article found, will update it');
            $data["id"] = $content->id;
            $pk = $content->id;
            $data["asset_id"] = $content->asset_id;
            $data["alias"] = $content->alias;
            $data["ordering"] = $content->ordering;
            $data["modified_by"] = $user->id;
            $data["modified"] = null;
            $data["created_by"] = $user->id;
            $data["created_by_alias"] = 'simplesharing';
            $data["checked_out"] = $content->checked_out;
            $data["checked_out_time"] = $content->checked_out_time;
            $data["version"] = $content->version;
            $data["ordering"] = $content->ordering;
        } else { // new content                        
            $this->_log('Article not found, will create new one.');
            $data["id"] = 0;
            $data["asset_id"] = null;
            //$data["alias"] = null;
            $data["alias"] = $content->alias;
            $data["created"] = null;
            $data["created_by"] = $user->id;
            $data["created_by_alias"] = 'simplesharing';
            $data["modified"] = null;
            $data["modified_by"] = null;
            $data["checked_out"] = null;
            $data["checked_out_time"] = null;
            $data["version"] = 0;
            $data["ordering"] = 0;
            if($creatCopy && $content->id){                
                $this->_log("Generating new title for an existing article...");
                $title = $content->title;
                $alias = $content->alias;
                do {
                    $title = JString::increment($title);
                    $this->_log("Generated title=". $title);
                    $alias = JString::increment($alias, 'dash');
                    $query = $db->getQuery(true);
                    $query->select('*')
                        ->from("#__content")
                        ->where('alias=' . $db->q($alias) . ' And catid='.$data['catid']);
                    $db->setQuery($query);
                    $content = $db->loadObject();                    
		} while($content);
		$this->_log("Generated title=". $title);
                $data['title'] = $title;
		$data['alias'] = $alias;        
            }
        }
        $this->_log('Started images processing...');
        if (isset($data['images']) && is_array($data['images'])) {
            if (isset($data['images']['image_intro_binary'])) {
                $image_binary = base64_decode($data['images']['image_intro_binary']);
                $fileName = JPATH_SITE . '/' . $data['images']['image_intro'];
                if (!JFile::exists($fileName))
                    JFile::write($fileName, $image_binary);
                unset($data['images']['image_intro_binary']);
            }

            if (isset($data['images']['image_fulltext_binary'])) {
                $image_binary = base64_decode($data['images']['image_fulltext_binary']);
                $fileName = JPATH_SITE . '/' . $data['images']['image_fulltext'];
                if (!JFile::exists($fileName))
                    JFile::write($fileName, $image_binary);
                unset($data['images']['image_fulltext_binary']);
            }
        }
        $this->_log('Completed images processing.');
        try {           
            if($model->save($data)){
                if(is_null($pk)){
                    $pk = $model->getState($model->getName() . '.id');
                }
                $this->_log('Started featured processing.');
                $model->featured($pk, $featured);
                $this->_log('Completed featured processing.');
            $this->_log('Successfully saved!');
            return "Successfully saved";
            }            
        } catch (Exception $e) {
            $this->_log("Saving article failed with error: " . $e->getMessage(), JLog::ERROR);
            return false;
        }
    }

    private function _log($msg, $priority = JLog::DEBUG) {
        if ($this->debugMode || $priority == JLog::ERROR) {
            jimport('joomla.log.log');
            JLog::add($msg, $priority, 'plg_simplesharing');
        }
    }

    private function _authorize($db, &$params, $pluginParams) {
        // Getting the client key        
        $client_key = trim($this->params->get('client_key'));

        // Uncrypt the request
        $key = base64_decode($params['SSH_KEY']);
        $parts = explode(':', $key);
        $key = trim($parts[0]);

        if ($key != $client_key) {
            $this->_log('Client key do not match.', JLog::ERROR);
            JResponse::setHeader('status', 402);
            JResponse::setBody('Client key do not match.');
            JResponse::sendHeaders();
            exit;
        }

        if (!isset($params['SSH_USER']) && !isset($params['SSH_USER'])) {
            $this->_log('Username headers not found.', JLog::ERROR);
            JResponse::setHeader('status', 405);
            JResponse::setBody('Username headers not found.');
            JResponse::sendHeaders();
            exit;
        }

        // Looking the username header
        if (isset($params['SSH_USER'])) {
            $user_decode = base64_decode($params['SSH_USER']);
        }

        $parts = explode(':', $user_decode);
        $user = $parts[0];

        // Looking the username header
        if (isset($params['SSH_PW'])) {
            $password_decode = base64_decode($params['SSH_PW']);
        }

        $parts = explode(':', $password_decode);
        $password = $parts[0];

        // Getting the local username and password
        $query = 'SELECT `id`, `password`'
                . ' FROM #__users'
                . ' WHERE username = ' . $db->quote($user);
        $db->setQuery($query);
        $user_result = $db->loadObject();

        if (!is_object($user_result)) {
            $this->_log('Username not found.', JLog::ERROR);
            JResponse::setHeader('status', 403);
            JResponse::setBody('Username not found.');
            JResponse::sendHeaders();
            exit;
        }

        // Check the password
        $match = JUserHelper::verifyPassword($password, $user_result->password, $user_result->id);
        if (!$match) {
            $this->_log('Username or password do not match.', JLog::ERROR);
            JResponse::setHeader('status', 406);
            JResponse::setBody('Username or password do not match');
            JResponse::sendHeaders();
            exit;
        }
        $user = new JUser($user_result->id);
        if (!$user->authorise('core.admin')) {
            $this->_log('Username is not Super Administrator.', JLog::ERROR);
            JResponse::setHeader('status', 401);
            JResponse::setBody('Username is not Super Administrator');
            JResponse::sendHeaders();
            exit;
        }
        $params['user'] = $user;
        return true;
    }

}

// end class
