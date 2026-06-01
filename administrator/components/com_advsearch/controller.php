<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_Advsearch
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (c) 2009-2017 TechJoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>.
 * @link        http://techjoomla.com.
 */


// No direct access
defined('_JEXEC') or die;
JLoader::import('components.com_advsearch.helpers.advsearch', JPATH_ADMINISTRATOR);

/**
 * Class Advsearch controller.
 *
 * @since  1.6
 */
class AdvsearchController extends JControllerLegacy
{
	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe url parameters and their variable types.
	 *
	 * @return    JController        This object to support chaining.
	 *
	 * @since    1.5
	 */
	public function display($cachable = false, $urlparams = false)
	{
		// #require_once JPATH_COMPONENT . '/helpers/advsearch.php';

		$view = JFactory::getApplication()->input->getCmd('view', 'searchindexer');

		JFactory::getApplication()->input->set('view', $view);

		parent::display($cachable, $urlparams);

		return $this;
	}

	/**
	 * Method to get the types
	 *
	 * @return JController object
	 *
	 * @since	1.6
	 */
	public function get_types()
	{
		$jinput   = JFactory::getApplication()->input;
		$client   = $jinput->get('client');
		$id       = $jinput->get('id');
		$disabled = "";

		if ($id && $id != "undefined")
		{
			$model       = $this->getModel('createindexer');
			$IndexerData = $model->getIndexer($id);
			$disabled    = 'disabled="disabled"';
		}

		JPluginHelper::importPlugin('advsearch', $client);
		$dispatcher = JEventDispatcher::getInstance();
		$types      = $dispatcher->trigger('getTypeAdv');

		if (!(empty($types)))
		{
			echo '
				<div class="span2" style= "font-weight:bold;">' . JText::_('COM_ADVSEARCH_SELECT_TYPES') . '</div>
			<div  class="span6">';
			$options = array();
			$options[] = JHTML::_('select.option', 'Select Classification', '0');

			foreach ($types[0] as $row)
			{
				$options[] = JHTML::_('select.option', $row->name, $row->alias);
			}

			if ($id && $id != "undefined")
			{
				echo JHTML::_('select.genericlist', $options, 'select_types', 'class="required"' . $disabled . '', 'text', 'value', $IndexerData->type_name);
			}
			else
			{
				echo JHTML::_('select.genericlist', $options, 'select_types', 'class="required"' . $disabled . '', 'text', 'value', '');
			}

			if ($id)
			{
				echo '<span style="display:none">'
				. JHTML::_('select.genericlist', $options, 'select_types', 'class="required" ', 'text', 'value', $IndexerData->type_name) . '</span>';
			}

			echo '</div> ';
		}
		else
		{
			echo '<td style="font-weight:bold;">' . JText::_('COM_ADVSEARCH_SELECT_TYPES') . '</td>
				  <td>Fields not found</td>';
		}

		jexit();
	}

	/**
	 * Method to get the types
	 *
	 * @return void
	 *
	 * @since	1.6
	 */
	public function getFields()
	{
		$AdvsearchHelper = new AdvsearchHelper;

		//  Check whether Indexer is created for the type of the field.
		$model   = $this->getModel('createindexer');
		$result  = $model->getFields();
		$jinput   = JFactory::getApplication()->input;
		$id       = $jinput->get('id');
		$cronurl = '';

		// If indexer found then tell the user that you have already created indexer
		// Else fetch fields by triggering getFields plugin event.
		if ($result && $id == "undefined")
		{
			Jexit();
		}
		else
		{
			$jinput		= JFactory::getApplication()->input;
			$type		= $jinput->get('type');
			$id       = $jinput->get('id');

			$IndexerData = new stdClass;

			if ($id && $id != "undefined")
			{
				$model       = $this->getModel('createindexer');
				$IndexerData = $model->getIndexer($id);
				$client = $jinput->get('client', '', 'STRING');
				$params = JComponentHelper::getParams('com_advsearch');
				$privateKey = $params->get('private_key');

				$IndexerFieldsData = $model->getIndexerFields($id);
				$link              = JURI::Root() .
				'index.php?option=com_advsearch&task=cronjob&pkey=' . $privateKey . '&type=' . $IndexerData->type_name . '&client=' . $client;

				$cronurl = '<div class="span12">';
					$cronurl .= '<div class="span2"><strong>' . JText::_('COM_ADVSEARCH_CRON_URL') . '</strong></div>';
					$cronurl .= '<div class="span9"><a target="_blank" href="' . $link . '">' . $link . '</a></div>';
				$cronurl .= '</div>';
			}

			$jinput   = JFactory::getApplication()->input;
			$client   = $jinput->get('client');
			JPluginHelper::importPlugin('advsearch', $client);
			$dispatcher = JEventDispatcher::getInstance();
			$fields     = $dispatcher->trigger('getFields', array($type));

			if (!(empty($fields)))
			{
				if ($fields[0])
				{
					$batch_size = '';
					$type       = '';

					if ($IndexerData->name)
					{
						$type = $IndexerData->name;
					}

					if ($IndexerData->batch_size > 0)
					{
						$batch_size = $IndexerData->batch_size;
					}

					echo '<div>
						<div class="span12" style="margin-left:15px;">
							<div class="span2"><strong>' . JText::_('COM_ADVSEARCH_SEARCH_INDEXER_NAME') . '</strong></div>
							<div class="span6"> <input type="text" name="name-for-type" value="' . $type . '" /></div>
						</div>
						<div class="span12">
							<div class="span2"><strong>' . JText::_('COM_ADVSEARCH_SEARCH_BATCH_SIZE') . '</strong></div>
							<div class="span6"><input type="text" name="batch_size" size="8" value="' . $batch_size . '"></div></div>';
					echo $cronurl;

					echo '<div class="table-responsive table-condensed" style="width:100%;height:400px;overflow-x:scroll;">

						<table class="table">

							  <thead style="background:#F5F5F5;">
								<tr>
								  <th>
									<div>' . JText::_('COM_ADVSEARCH_SEARCH_FIELD_LABEL') . '</div>
								  </th>
								  <th>
									<div>' . JText::_('COM_ADVSEARCH_SEARCH_FIELD_LABEL_FOR_SEARCH') . '</div>
								  </th>



								  <!--th class="second">
									<div>' . JText::_('COM_ADVSEARCH_SEARCH_ZOO_DATA_TYPE') . '</div>
								  </th-->

								  <th>
									<div>' . JText::_('COM_ADVSEARCH_SEARCH_MAP_WITH_DATA_TYPE') . '</div>
								  </th>
									<th style="display:none">
									<div>' . JText::_('COM_ADVSEARCH_ORDERING') . '</div>
								  </th>
								  <th>
									<div>' . JText::_('COM_ADVSEARCH_MAP_BASIC_SEARCH') . '</div>
								  </th>
								  <th>
									<div>' . JText::_('COM_ADVSEARCH_SEARCH_TERM') . '</div>
								  </th>
								  <th>
									<div>' . JText::_('COM_ADVSEARCH_GRID_FILTER') . '</div>
								  </th>
								  <th>
									<div>' . JText::_('COM_ADVSEARCH_LANDING_PAGE') . '</div>
								  </th>
									<th>
									<div>' . JText::_('COM_ADVSEARCH_USE_AS') . '</div>
								  </th>
									<th>
									<div>' . JText::_('COM_ADVSEARCH_CATEGORY_SEARCH') . '</div>
								  </th>
									<th>
									<div>' . JText::_('COM_ADVSEARCH_DISPLAY_IN_SEARCH') . '</div>
								  </th>
								  <th></th>
								</tr>
								</thead>
								<tbody>';
				}
				else
				{
					echo JText::_('COM_ADVSEARCH_FIELD_NOT_FOUND');
				}

				$i = 0;

				$db = JFactory::getDBO();

				if (is_array($fields))
				{
					foreach ($fields[0] as $k => $ele)
					{
						$DataResult = new stdClass;

						if ($id && $id != "undefined")
						{
							$query = "select * FROM #__advanced_search_indexer_fields where field_code = '$k'
											 AND indexer_id = $id";
							$db->setQuery($query);
							$DataResult = $db->loadObject();
						}

						$val        = $i % 2;
						$DataResult = (array) $DataResult;

						if (!empty($DataResult))
						{
							echo '<tr id="row' . $i . '" class="row' . $val . '" >';
							echo '<td style="width:200px">' . $ele['name'] . '</td>';
							echo '<td>
							<input type="text" name="mapping_label[' . $i . ']" id="mapping_label[' . $i . ']" value="' . $DataResult->mapping_label . '" size="40" />
							<input type="hidden" name="field_code[' . $i . ']" id="field_code[' . $i . ']" value="' . $k . '"/>
							<input type="hidden" name="field_type[' . $i . ']" id="field_type[' . $i . ']" value="' . $ele['type'] . '"/>
							<input type="hidden" name="field_name[' . $i . ']" id="field_name[' . $i . ']" value="' . $ele['name'] . '"/>
							<input type="hidden" name="field_options[' . $i . ']" id="field_name[' . $i . ']" value="' . $ele['option'] . '"/>
							</td>';

							echo '<td>';
							echo $AdvsearchHelper->categoryDropdownListAdd($i, $ele['type'], $DataResult->mapping_field);
							echo '</td>';

							if ($DataResult->basic_search == 1)
							{
								$checked = "checked='checked'";
							}
							else
							{
								$checked = "";
							}

							echo '<td>
							<input type="checkbox" ' . $checked . ' name="basic_search[' . $i . ']" id="basic_search[' . $i . ']" size="40" />
							</td>';

							if ($DataResult->search_term == 1)
							{
								$checked = "checked='checked'";
							}
							else
							{
								$checked = "";
							}

							echo '<td>
							<input type="checkbox" ' . $checked . ' name="search_term[' . $i . ']" id="search_term[' . $i . ']" size="40" />
							</td>';

							if ($DataResult->grid_filter == 1)
							{
								$checkeds = "checked='checked'";
							}
							else
							{
								$checkeds = "";
							}

							echo '<td>
							<input type="checkbox" ' . $checkeds . ' name="grid_filter[' . $i . ']" id="grid_filter[' . $i . ']" size="40" />
							</td>';

							if ($DataResult->landing_page == 1)
							{
								$checkedss = "checked='checked'";
							}
							else
							{
								$checkedss = "";
							}

							/*echo '<td align="left" width="10%">'.$DataResult->landing_page.'                    </td>';*/

							echo '<td>
							<input type="checkbox" ' . $checkedss . ' name="landing_page[' . $i . ']" id="landing_page[' . $i . ']" size="40" />
							</td>';
							echo '<td>';
							echo $AdvsearchHelper->UseAsDropdown($i, $ele['type'], $DataResult->useas);

							echo '</td>';

							if ($DataResult->category_search == 1)
							{
								$checkedCS = "checked='checked'";
							}
							else
							{
								$checkedCS = "";
							}

							echo '<td>
							<input type="checkbox" ' . $checkedCS . ' name="category_search[' . $i . ']" id="category_search[' . $i . ']" size="40" />
							</td>';

							if ($DataResult->display_search == 1)
							{
								$checkedCS = "checked='checked'";
							}
							else
							{
								$checkedCS = "";
							}

							echo '<td>
							<input type="checkbox" ' . $checkedCS . ' name="display_search[' . $i . ']" id="display_search[' . $i . ']" size="40" />
							</td>';

							echo '</tr>';
						}
						else
						{
							echo '<tr id="row' . $i . '" class="row' . $val . '" >';
							echo '<td>' . $ele['name'] . '</td>';
							echo '<td style="">
							<input type="text" name="mapping_label[' . $i . ']" id="mapping_label[' . $i . ']" value="' . $ele['label'] . '" size="40" />
							<input type="hidden" name="field_code[' . $i . ']" id="field_code[' . $i . ']" value="' . $k . '"/>
							<input type="hidden" name="field_type[' . $i . ']" id="field_type[' . $i . ']" value="' . $ele['type'] . '"/>
							<input type="hidden" name="field_name[' . $i . ']" id="field_name[' . $i . ']" value="' . $ele['name'] . '"/>
							<input type="hidden" name="field_options[' . $i . ']" id="field_name[' . $i . ']" value="' . $ele['option'] . '"/>
							</td>';

							echo '<td>';
							echo $AdvsearchHelper->categoryDropdownListAdd($i, $ele['type'], 0);

							echo '</td>';

							echo '<td>
							<input type="checkbox" name="basic_search[' . $i . ']" id="basic_search[' . $i . ']" size="40" />
							</td>';

							echo '<td>
							<input type="checkbox" name="search_term[' . $i . ']" id="search_term[' . $i . ']" size="40" />
							</td>';

							echo '<td>
							<input type="checkbox" name="grid_filter[' . $i . ']" id="grid_filter[' . $i . ']" size="40" />
							</td>';

							echo '<td>
							<input type="checkbox" name="landing_page[' . $i . ']" id="landing_page[' . $i . ']" size="40" />
							</td>';

							echo '<td>';
							echo $AdvsearchHelper->UseAsDropdown($i, $ele['type'], 0);

							echo '</td>';

							echo '<td>
							<input type="checkbox" name="category_search[' . $i . ']" id="category_search[' . $i . ']" size="40" />
							</td>';

							echo '<td>
							<input type="checkbox" name="display_search[' . $i . ']" id="display_search[' . $i . ']" size="40" />
							</td>';
							echo '</tr>';
						}

						$i++;
					}
				}
			}

			echo ' </tbody> </table> </div>';

			jexit();
		}
	}
}
