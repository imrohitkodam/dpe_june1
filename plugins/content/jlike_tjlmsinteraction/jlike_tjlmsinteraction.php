<?php
/**
 * @package    JLike
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Restricted access');

// Import library dependencies
jimport('joomla.plugin.plugin');
jimport('joomla.filesystem.file');

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
/**
 * jLike Tjlms plugin class.
 *
 * @since  1.0.0
 */
class PlgContentJLike_Tjlmsinteraction extends CMSPlugin
{
	/**
	 * Constructor - note in Joomla 2.5 PHP4.x is no longer supported so we can use this.
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An array that holds the plugin configuration
	 *
	 * @since  1.0.0
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		$lang = Factory::getLanguage();
		$lang->load('plg_content_jlike_tjlmsinteraction', JPATH_ADMINISTRATOR);
	}

	/**
	 * Function used to get the HTML for Interaction to be shown for item
	 *
	 * DPE HACK changed the trigger name as per joomla4
	 * 
	 * @param   INT  $lesson_id  Id of lesson
	 *
	 * @return  $html
	 *
	 * @since  1.0.0
	 */
	public function onGetLessonInteractions($lesson_id)
	{
		require_once JPATH_SITE . '/components/com_jlike/models/interaction.php';
		require_once JPATH_SITE . '/components/com_jlike/controllers/interaction.json.php';

		$component = new jlikeControllerInteraction(array('name' => 'jlike'));
		$component->addViewPath(JPATH_SITE . '/components/com_jlike' . '/views');
		$component->addModelPath(JPATH_SITE . '/components/com_jlike' . '/models');

		$view  = $component->getView('interaction', 'raw');
		$model = $component->getModel('interaction');
		$templatePath = JPATH_SITE . '/templates/' . Factory::getApplication()->getTemplate() . '/html/com_jlike/interaction';

		if (JFile::exists($templatePath . '/default.php'))
		{
			$view->addTemplatePath($templatePath);
		}
		else
		{
			$view->addTemplatePath(JPATH_SITE . '/components/com_jlike/views/interaction/tmpl');
		}

		$html      = new stdClass;
		$html->ref = 'interaction';

		ob_start();
		$view->display();
		$html->content = ob_get_contents();
		ob_end_clean();

		return $html;
	}
}
