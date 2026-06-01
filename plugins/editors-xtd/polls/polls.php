<?php
/**
 * @package     corejoomla.administrator
 * @subpackage  plg_editor_polls
 *
 * @copyright   Copyright (C) 2009 - 2014 corejoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die();

class plgButtonPolls extends JPlugin
{
	protected $autoloadLanguage = true;
	
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
	}
	
    public function onDisplay($name)
    {
    	$js = "
		function jSelectPoll(id, title, catid, object, link, lang)
		{
			var hreflang = '';
			if (lang !== '')
			{
				var hreflang = ' hreflang = \"' + lang + '\"';
			}
			var tag = '<div>{CONTENTPOLL [\"id\": '+id+']}</div>';
			jInsertEditorText(tag, '" . $name . "');
			SqueezeBox.close();
		}";
    	
    	$doc = JFactory::getDocument();
    	$doc->addScriptDeclaration($js);
    	
    	JHtml::_('behavior.modal');
    	$link = 'index.php?option=com_communitypolls&amp;view=polls&amp;layout=modal&amp;tmpl=component&amp;' . JSession::getFormToken() . '=1';

		$button = new JObject;
		$button->modal = true;
		$button->class = 'btn';
		$button->link = $link;
		$button->text = 'Poll';
		$button->name = 'file-add';
		$button->options = "{handler: 'iframe', size: {x: 800, y: 500}}";

        return $button;
    }
}
