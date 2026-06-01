<?php
/**    
 * SEO Glossary Base Controller
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

// No direct access
defined('_JEXEC') or die;

// import Joomla controller library
jimport('joomla.application.component.controller');
require_once(JPATH_SITE . '/components/com_seoglossary/helpers/compat.php');

class SeoglossaryController extends SeogController
{
	/**
	 * Method to display a view.
	 *
	 * @param	boolean			$cachable	If true, the view output will be cached
	 * @param	array			$urlparams	An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return	JController		This object to support chaining.
	 * @since	1.5
	 */
	public function display($cachable = false, $urlparams = false)
	{
		require_once JPATH_COMPONENT.'/helpers/seoglossary.php';

		// Load the submenu.

		SeoglossaryBackendHelper::addSubmenu(JFactory::getApplication()->input->getString('view', 'panel'));

				$view		= JFactory::getApplication()->input->getString('view', 'panel');
        JFactory::getApplication()->input->set('view', $view);
				parent::display();
				return $this;
	}
	public function import_csv()
    {
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_seoglossary/models', 'SeoglossaryModel');
		// Get an instance of the generic articles model
		$model = JModelLegacy::getInstance('Glossaries', 'SeoglossaryModel', array('ignore_request' => true));
		$model->saveCsv();
    }
	 public function export_csv()
    {
	ob_end_clean();
    $app = JFactory::getApplication();
      $config=JFactory::getConfig();
    $name=JApplicationHelper::stringURLSafe($config->get('sitename'));
      header("Content-type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=".$name."_seoglossary.csv");
    header("Pragma: no-cache");
    header("Expires: 0");
	header('Content-Transfer-Encoding: binary'); 
    $output = fopen('php://output', 'w');
		fputcsv($output, array('id','tterm','alias','tsynonyms','tfirst','tdefinition','tmore','catid','exturl','extlink','nofollow','target','metakey','metadesc','icon'));
    JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_seoglossary/models', 'SeoglossaryModel');
    
		// Get the dbo
		$db = JFactory::getDbo();

		// Get an instance of the generic articles model
		$model = JModelLegacy::getInstance('Glossaries', 'SeoglossaryModel', array('ignore_request' => true));

		// Set application parameters in model
		$app       = JFactory::getApplication();
		$model->setState('list.start', 0);
		$model->setState('list.limit', -1);
		$model->setState('list.ordering', 'a.id');
		$model->setState('list.direction', 'desc');
		$items = $model->getItems();
		foreach($items as $item)
		{
		   fputcsv($output,array($item->id,str_replace(array('"',"\n", "\r"),array("'"," "," "),$item->tterm),str_replace(array('"',"\n", "\r"),array("'"," "," "),@$item->alias),str_replace(array('"',"\n", "\r"),array("'"," "," "),@$item->tsynonyms),str_replace(array('"',"\n", "\r"),array("'"," "," "),$item->tfirst),str_replace(array('"',"\n", "\r"),array("'"," "," "),$item->tdefinition),str_replace(array('"',"\n", "\r"),array("'"," "," "),$item->tmore),str_replace(array('"',"\n", "\r"),array("'"," "," "),$item->catname),str_replace(array('"',"\n", "\r"),array("'"," "," "),$item->exturl),str_replace(array('"',"\n", "\r"),array("'"," "," "),$item->extlink),str_replace(array('"',"\n", "\r"),array("'"," "," "),$item->nofollow),str_replace(array('"',"\n", "\r"),array("'"," "," "),$item->target),str_replace(array('"',"\n", "\r"),array("'"," "," "),$item->metakey),str_replace(array('"',"\n", "\r"),array("'"," "," "),$item->metadesc),$item->icon), ",");
		   }
    
    $app->close();die();
    }
	 public function overridehelper()
	{
		jimport('joomla.filesystem.folder');
        jimport('joomla.filesystem.file');
		$app=JFactory::getApplication();
		$document=JFactory::getDocument();
        $db=JFactory::getDbo();
        $sql="select * from #__template_styles where client_id=0 and home=1";
        $db->setQuery($sql);
        $result=$db->loadObject();
        $tpath=JPATH_SITE."/templates/".$result->template."/html/com_seoglossary/seoglossary.php";
		$cpath=JPATH_SITE."/components/com_seoglossary/helpers/seoglossary.php";
  $msg=JText::_('COM_SEOGLOSSARY_FORM_LBL_HELPER_EXIST');
  $app->enqueueMessage(JText::_($msg));
  if(JFolder::create(JPATH_SITE."/templates/".$result->template."/html/com_seoglossary/")) {
			}
  		JFile::copy($cpath,$tpath);
			$app->redirect($_SERVER['HTTP_REFERER']);
	}
}
