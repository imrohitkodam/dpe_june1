<?php

/**
 * @version     1.0.34
 * @package     com_simplesharing
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @author      NYC HelpDesk.co LLC <support@nychelpdesk.co> - nychelpdesk.co
 */
// No direct access
defined('_JEXEC') or die;

/**
 * Simplesharing helper.
 */
class SimplesharingHelper {

    /**
     * Configure the Linkbar.
     */
    public static function addSubmenu($vName = '') {
        		JHtmlSidebar::addEntry(
			JText::_('COM_SIMPLESHARING_TITLE_SLAVEWEBSITES'),
			'index.php?option=com_simplesharing&view=slavewebsites',
			$vName == 'slavewebsites'
		);
		JHtmlSidebar::addEntry(
			JText::_('COM_SIMPLESHARING_TITLE_ARTICLES'),
			'index.php?option=com_simplesharing&view=articles',
			$vName == 'articles'
		);

    }

    /**
     * Gets a list of the actions that can be performed.
     *
     * @return	JObject
     * @since	1.6
     */
    public static function getActions() {
        $user = JFactory::getUser();
        $result = new JObject;

        $assetName = 'com_simplesharing';

        $actions = array(
            'core.admin', 'core.manage', 'core.create', 'core.edit', 'core.edit.own', 'core.edit.state', 'core.delete'
        );

        foreach ($actions as $action) {
            $result->set($action, $user->authorise($action, $assetName));
        }

        return $result;
    }


    public static function relToAbs($element, $text)
        {
          $base = JUri::root();
          if (empty($base))
            return $text;
          // base url needs trailing /
          if (substr($base, -1, 1) != "/")
            $base .= "/";
          
          // Replace images
          $pattern = "/<{$element}([^>]*) " . 
                     "src=\"([^http|ftp|https][^\"]*)\"/";

          $replace = "<{$element}\${1} src=\"" . $base . "\${2}\"";
          $text = preg_replace($pattern, $replace, $text);
          // Done
          return $text;
        }
}
