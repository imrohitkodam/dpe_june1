<?php
/*------------------------------------------------------------------------
# mod_universal_ajaxlivesearch - Universal AJAX Live Search
# ------------------------------------------------------------------------
# author    Janos Biro
# copyright Copyright (C) 2011 Offlajn.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://www.offlajn.com
-------------------------------------------------------------------------*/
?><?php
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\Form;

class JElementImageRadio extends JOfflajnFakeElementBase{

  var	$_name = 'ImageRadio';

	function universalfetchElement($name, $value, &$node)
	{
		jimport( 'joomla.filesystem.folder' );
		jimport( 'joomla.filesystem.file' );

		// path to images directory
		$path		= JPATH_ROOT.'/'.$node->attributes('directory');
		$filter		= '\.png$|\.gif$|\.jpg$|\.bmp$|\.ico$';
		$exclude	= $node->attributes('exclude');
		$stripExt	= $node->attributes('stripext');
		$files		= Folder::files($path, $filter);

		$options = array ();

    $document =& Factory::getDocument();
    $document->addStyleDeclaration('
      li fieldset.panelform label.radiobtn{
        width: auto;
      }
    ');


    $imageurl = JURI::root().$node->attributes('directory').'/';

		if ( is_array($files) )
		{
			foreach ($files as $file)
			{
				if ($exclude)
				{
					if (preg_match( chr( 1 ) . $exclude . chr( 1 ), $file ))
					{
						continue;
					}
				}
				if ($stripExt)
				{
					$file = File::stripExt( $file );
				}
				$options[] = JHTML::_('select.option', $file, '<img style="" src="'.str_replace('\\','/',$imageurl.$file).'" />');
			}
		}


		if (!$node->attributes('hide_none'))
		{
			$options[] = JHTML::_('select.option', '-1', '- '.Text::_('None').' -');
		}

		return JHTML::_('select.radiolist',  $options, $name, 'class="inputbox"', 'value', 'text', $value, $this->generateId($name));
	}

}

if(version_compare(JVERSION,'1.6.0','ge')) {
  class FormImageRadio extends JElementImageRadio {}
}