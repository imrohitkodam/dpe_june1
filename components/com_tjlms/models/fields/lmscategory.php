use Joomla\CMS\Form\FormField;
use Joomla\CMS\Table\Table;
<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

FormHelper::loadFieldClass('list');

/**
 * JFormFieldCatfilter class for the category custom field.
 * Supports an HTML select list of categories
 *
 * @since  1.3.8
 */
class FormFieldLmscategory extends FormFieldCategory
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.3.8
	 */
	public $type = 'lmscategory';

	/**
	 * Method to get the field options for category
	 * Use the extension attribute in a form to specify the.specific extension for
	 * which categories should be displayed depending on the menu category is set.
	 *
	 * @return  array    The field option objects.
	 *
	 * @since  1.3.8
	 */
	public function getOptions()
	{
		// Get the menuparams and show only that category in the filter.
		$app		= Factory::getApplication();
		$menuParams = new Registry;

		// Get active menu.
		if ($menu = $app->getMenu()->getActive())
		{
			$menuParams->loadString($menu->params);
			$catId 		= $menuParams->get('catid', '');
			$showSubCat = $menuParams->get('showSubcatLessons', '0');

			if (!empty($catId))
			{
				// Get db instance
				$db 		= Factory::getDbo();
				$options	= array();

				// Get user access levels.
				$allowedViewLevels 	= Access::getAuthorisedViewLevels(Factory::getUser()->id);
				$implodedViewLevels = implode(',', $db->q($allowedViewLevels));

				// Get the categories with right access and right id.
				$query 	= $db->getQuery(true);
				$query->select($db->qn('c.id', 'value'));
				$query->select($db->qn('c.title', 'text'));
				$query->select($db->qn(array('c.level', 'c.published', 'c.lft', 'c.language')));
				$query->from($db->qn('#__categories', 'c'));
				$query->where('c.access IN (' . $implodedViewLevels . ')');

				// Check if menu allows to show the sub categories.
				if ($showSubCat)
				{
					$catTable = Table::getInstance('Category', 'JTable');
					$catTable->load((int) $catId);
					$rgt = $catTable->rgt;
					$lft = $catTable->lft;
					$query->where($db->qn('c.lft') . ' >= ' . $lft);
					$query->where($db->qn('c.rgt') . ' <= ' . $rgt);
				}
				else
				{
					$query->where($db->qn('c.id') . ' = ' . $catId);
				}

				$db->setQuery($query);
				$catFilter = $db->loadobjectList();

				// Get the select category option as first option in drop down.
				$options			= array(new stdClass);
				$options[0]->text 	= Text::_("COM_TJLMS_MENU_LESSONS_FIELD_OPTION_SELECT_CAT");
				$options[0]->value 	= "";

				// Show the hierarchy depending on the level of the categories.
				foreach ($catFilter as $i => $opt)
				{
					if ($catFilter[$i]->published == 1)
					{
						$catFilter[$i]->text = str_repeat('- ', !$catFilter[$i]->level ? 0 : $catFilter[$i]->level - 1) . $catFilter[$i]->text;
					}
					else
					{
						$catFilter[$i]->text = str_repeat('- ', !$catFilter[$i]->level ? 0 : $catFilter[$i]->level - 1) . '[' . $catFilter[$i]->text . ']';
					}

					// Displays language code if not set to All
					if ($catFilter[$i]->language !== '*')
					{
						$catFilter[$i]->text = $catFilter[$i]->text . ' (' . $catFilter[$i]->language . ')';
					}

					$options[] = $opt;
				}

				return $options;
			}
			else
			{
				// If menu params are not set then return the extension categories.
				return parent::getOptions();
			}
		}
	}
}
