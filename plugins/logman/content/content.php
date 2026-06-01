<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2018 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Content LOGman Plugin.
 *
 * Provides event handlers for dealing with com_content events.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanContent extends ComLogmanPluginJoomla
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'activities' => array(
                'context_map' => array('com_content.form' => 'com_content.article'),
            ),
            'routes'     => array('enabled' => true, 'conditions' => array('view' => 'article'))
        ))->append(array('impressions' => clone $config->routes));

        parent::_initialize($config);
    }

    public function onContentAfterSave($context, $content, $isNew)
    {
        // Map inconsistent contexts.
        if (isset($this->_aliases[$context])) {
            $context = $this->_aliases[$context];
        }

        parent::onContentAfterSave($context, $content, $isNew);
    }

    protected function _canLogImpression($query)
    {
        // Allow logging of impressions from guest
        if (JComponentHelper::isInstalled('com_leadman')) {
            self::$_settings->log_guest_actions = true;
        }

        $result = parent::_canLogImpression($query);

        if ($result)
        {
            $url = $this->getObject('request')->getUrl()->toString(KHttpUrl::PATH);

            if (strpos($url, 'favicon.ico') !== false) $result = false;
        }

        return $result;
    }

    protected function _getItems($ids, KObjectConfig $config)
    {
        $config->type = 'content';

        return parent::_getItems($ids, $config);
    }

    protected function _getArticleImpressionData($query)
    {
        $data = $this->_getImpressionData($query);

        $article = JTableContent::getInstance('content');

        if ($article->load(1)) {
            $data['title'] = $article->title;
        }

        return $data;
    }

    /**
     * Impression controller getter
     *
     * @param array $config An optional configuration array for the controller.
     *
     *  @return KControllerInterface The activity controller
     */
    protected function _getImpressionController($config = array())
    {
        if (JComponentHelper::isInstalled('com_leadman')) {
            $config['log_guests_activities'] = true;
        }

        return parent::_getImpressionController($config);
    }
}
