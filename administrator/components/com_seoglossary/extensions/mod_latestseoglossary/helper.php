<?php
/**
 * Latest SEO Glossary module helper
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

// no direct access
defined('_JEXEC') or die;
require_once(JPATH_SITE . '/components/com_seoglossary/helpers/compat.php');

SeogModel::addIncludePath(JPATH_SITE . '/components/com_seoglossary/models', 'SeoglossaryModel');

// import the Joomla! arrayhelper library
jimport('joomla.utilities.arrayhelper');

class modLatestseoglossaryHelper
{
	static function getList($params)
	{
            
            	$app = JFactory::getApplication();

		// Get an instance of the Entries model
//        require_once(JPATH_SITE . '/components/com_seoglossary/helpers/compat.php');
//        $model = SeogModel::getInstance('glossaries', 'SeoglossaryModel', array('ignore_request' => true));
        require_once(JPATH_SITE . '/components/com_seoglossary/models/glossaries.php');
        $model = new SeoglossaryModelGlossaries(array('ignore_request' => true));
        if ($model) {
            $model->setState('filter.state', '1');

            // Access filter
            $authorised = JFactory::getUser()->getAuthorisedViewLevels();
            $model->setState('filter.access', $authorised);
	    $model->setState('filter.catid', $params->get('catid',''));
	    $model->setState('filter.search', '');
            $model->setState('list.ordering', $params->get('ordering','id'));
            $model->setState('list.direction', $params->get('ordering_direction','desc'));
            $model->setState('list.start', 0);
            $model->setState('list.limit', (int)$params->get('count', 5));

            return $model->getItems();
        }

        return array();

	}
}