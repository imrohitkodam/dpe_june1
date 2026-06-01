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

jimport('joomla.application.component.controllerform');
JLoader::import('AdvsearchHelper', JPATH_ROOT . '/components/com_advsearch/helpers/advsearch.php');
/**
 * Class Advsearch controller
 *
 * @since  3.3
 */
class AdvsearchControllerCreatemapping extends JControllerForm
{
	protected $view_list;
	/**
	 * Class constructor.
	 *
	 * @param   array  $config  A named array of configuration variables.
	 *
	 * @since   1.6
	 */
	public function __construct($config = array())
	{
		$this->view_list = 'createmapping';

		parent::__construct($config);
	}

	/**
	 * Method to get fields.
	 *
	 * @return  list of fields.
	 *
	 * @since   1.6
	 */
	public function getIndexerFields()
	{
		$jinput        = JFactory::getApplication()->input;
		$indexer       = $jinput->get('indexerValue', '', 'int');
		$primary_index = $jinput->get('primary_index', '', 'int');
		$id            = $jinput->get('id', '', 'int');
		$primary_field_label = " ";
		$items = " ";

		if ($id)
		{
			$items         = $this->getModel()->getDataMapp();
		}

		$IndexerFieldsData = $this->getModel()->getIndexerFieldsData($indexer);
		$AdvsearchHelper  = new AdvsearchHelper;
		$whereCondition = 'primary_index_id = ' . $indexer;

		$primary_field_result = $AdvsearchHelper->getDataValues('#__tjrecommendations_mapping', 'id', $whereCondition, 'loadResult');

		if ($IndexerFieldsData && $indexer)
		{
			echo '<div class="table-responsive table-condensed">';
				echo '<table class="table">';
					echo '<thead style="background:#F5F5F5;"><tr><th><div>' . JText::_('COM_ADVSEARCH_FIELDS_' . $primary_index) . '</div></th></tr></thead>';
					echo '<tbody>';

						if ($primary_index == 1)
						{
							if ($id)
							{
								foreach ($items as $fdata)
								{
									if ($fdata->primary_field_id)
									{
										$whereCondition = 'id = ' . $fdata->primary_field_id;
										$whereCondition .= ' And indexer_id = ' . $id;
										$primary_field_label = $AdvsearchHelper->getDataValues('#__advanced_search_indexer_fields', 'mapping_label', $whereCondition, 'loadResult');
									}

									echo '<tr>';
										echo '<td>';
											echo $primary_field_label;
											echo '<input type="hidden" name="primaryIndexField[]" id="primaryIndex" value="' . $fdata->primary_field_id . '">';
											echo '<input type="hidden" name="xref_id[]" id="primaryIndex" value="' . $fdata->xref_id . '">';
										echo '</td>';
									echo '</tr>';
								}
							}
							else
							{
								if ($primary_field_result)
								{
									echo '<tr><td>You have already created Mapping for this type. Please check Mapping list.</td></tr>';
									Jexit();
								}

								foreach ($IndexerFieldsData as $fdata)
								{
									if ($fdata->mapping_field)
									{
										echo '<tr>';
											echo '<td>';
												echo $fdata->mapping_label;
												echo '<input type="hidden" name="primaryIndexField[]" id="primaryIndex" value="' . $fdata->id . '">';
												echo '<input type="hidden" name="xref_id[]" id="primaryIndex" value="">';
											echo '</td>';
										echo '</tr>';
									}
								}
							}
						}
						elseif ($primary_index == 2)
						{
							if ($id)
							{
								foreach ($items as $fdata)
								{
									echo '<tr>';
										$options = array();
										$options[] = JHTML::_('select.option', 'Select Indexer Field', 'NaN');

										foreach ($IndexerFieldsData as $key => $val)
										{
											$options[] = JHTML::_('select.option', $val->mapping_label, $val->id);
										}

										echo '<td>';
											echo JHTML::_('select.genericlist', $options,  'secondaryIndexField[]', 'class="required" ', 'text', 'value', $fdata->secondary_field_id);
										echo '</td>';
										echo '<td><input type="text" name="ordering[]" id="ordering" value="' . $fdata->ordering . '"></td>';

										$TypeOptions = array();
										$TypeOptions[] = JHTML::_('select.option', 'Select Type of match', 'NaN');
										$TypeOptions[] = JHTML::_('select.option', 'Exact', 'exact');
										$TypeOptions[] = JHTML::_('select.option', 'Fuzzy', 'fuzzy');
										$TypeOptions[] = JHTML::_('select.option', 'Numerical', 'numerical');

										echo '<td>';
											echo JHTML::_('select.genericlist', $TypeOptions,  'type[]', 'class="required" ', 'text', 'value', $fdata->type);
										echo '</td>';

									echo '</tr>';
								}
							}
							else
							{
								/*if ($primary_field_result)
								{
									echo '<b>You have already created Mapping for this type. Please check Mapping list.</b>';
									Jexit();
								}*/

								foreach ($IndexerFieldsData as $key => $fdata)
								{
									if ($fdata->mapping_field)
									{
										echo '<tr>';
											$options = array();
											$options[] = JHTML::_('select.option', 'Select Indexer Field', 'NaN');

											foreach ($IndexerFieldsData as $key_d => $val)
											{
												if ($val->mapping_field)
												{
													$options[] = JHTML::_('select.option', $val->mapping_label, $val->id);
												}
											}

											echo '<td>';
												echo JHTML::_('select.genericlist', $options,  'secondaryIndexField[]', 'class="required" ', 'text', 'value', '');
											echo '</td>';
											echo '<td><input type="text" name="ordering[]" id="ordering" value=""></td>';

											$TypeOptions = array();
											$TypeOptions[] = JHTML::_('select.option', 'Select Type of match', 'NaN');
											$TypeOptions[] = JHTML::_('select.option', 'Exact', 'exact');
											$TypeOptions[] = JHTML::_('select.option', 'Fuzzy', 'fuzzy');
											$TypeOptions[] = JHTML::_('select.option', 'Numerical', 'numerical');

											echo '<td>';
												echo JHTML::_('select.genericlist', $TypeOptions,  'type[]', 'class="required searchtype" ', 'text', 'value', '');
											echo '</td>';

										echo '</tr>';
									}
								}
							}
						}

					echo '</tbody>';
				echo '</table>';
			echo '</div>';
			Jexit();
		}
		else
		{
			echo 'List is empty';
			Jexit();
		}
	}

	/**
	 * Function that allows child controller access to model data after the data has been saved.
	 *
	 * @return	void
	 *
	 * @since	3.1
	 */
	public function saveMapping()
	{
		$this->getModel()->saveMapping();

		// Preset the redirect
		// $this->setRedirect(JRoute::_('index.php?option=com_advsearch&view=searchindexer', false));

		$link = JURI::Base() . "index.php?option=com_advsearch&view=createmapping";
		$msg = "Mapping saved successfully";
		$this->setRedirect($link, $msg);

		return;
	}
}
