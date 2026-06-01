<?php
/**
 * @version     1.0.0
 * @package     com_tjreports
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      TechJoomla <extensions@techjoomla.com> - http://www.techjoomla.com
 */

// no direct access
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;

$displayFilters = $this->filters;
$filters = $this->filterValues;
$classForShowHide = '';
$user = Factory::getUser();

if ($this->filterLevel == 2)
{
	$classForShowHide = 'col-filter-header';
}

foreach($displayFilters as $searchKey => $filter)
{
	$searchType = $filter['search_type'];
	$multiple   = $filter['multiple'];
	$searchValue = isset($filters[$searchKey]) ? $filters[$searchKey] : '';
	$filterHtml = '';
	$filterHide = $searchValue === '' ? 'filter-hide' : 'filter-show';
	
	if ($searchType == 'text')
	{
		// Show remove button with search box conditionally
		if (!isset($filter['showRemoveButton']))
		{
			$filter['showRemoveButton'] = true;
		} 

		$filterHtml = '<div class="input-group mr-10">
							<input type="text" placeholder="'. $filter['placeholder'] . '" name="filters[' . $searchKey . ']"
									class="input input-mini filter-input ' . $filterHide . '" ' .
									'onkeydown="tjrContentUI.report.submitOnEnter(event);"
									onblur="tjrContentUI.report.submitTJRData();"
									value="' . htmlspecialchars($searchValue) . '"
								/>';

						// Don't show remove button with search box if 'showRemoveButton' param set to false 
						if ($filter['showRemoveButton'] != false)
						{
							$filterHtml .= '<span class="input-group-btn">
								<button class="btn btn-secondary close-icon" type="button" title="Cancel Search">
									<i class="fa fa-remove"></i>
								</button>
							</span>';
						}

						$filterHtml .= '</div>';

		if(isset($this->colKey))
		{
			$filterHtml .= HTMLHelper::_('grid.sort', '', $this->colKey, $this->listDirn, $this->listOrder);
		}
	}
	elseif($searchType == 'select' && isset($filter['select_options']) && !isset($multiple))
	{
 
		$svalue = isset($filter['select_value']) ? $filter['select_value'] : "value";
		$stext  = isset($filter['select_text']) ? $filter['select_text'] : "text";

		$filterHtml = '<div class="input-group mr-10">';

		if($searchKey == 'cluster_id' || $searchKey == 'agency' || $searchKey=='cluster')
		{
			$filterHtml .= HTMLHelper::_('select.genericlist', $filter['select_options'], 'filters[' . $searchKey . ']','class="filter-input '  . $filterHide . '" size="1" onchange="tjrContentUI.report.removetag();tjrContentUI.report.submitTJRData();"',
							$svalue, $stext, $searchValue);
		}
		else
		{
			$filterHtml .= HTMLHelper::_('select.genericlist', $filter['select_options'], 'filters[' . $searchKey . ']','class="filter-input '  . $filterHide . '" size="1" onchange="tjrContentUI.report.submitTJRData();"',
							$svalue, $stext, $searchValue);
		}
		

		if ($this->filterLevel == 1)
		{
			$filterHtml	.= '</div>';
		}
		else
		{
			$filterHtml .= '<span class="input-group-btn ml-10">
								<button class="btn report-btn btn-secondary close-icon" type="button" title="Cancel Search">
									<i class="fa fa-remove"></i>
								</button>
							</span></div>';
		}

		if(isset($this->colKey))
		{
			$filterHtml .= HTMLHelper::_('grid.sort', '', $this->colKey, $this->listDirn, $this->listOrder);
		}
	}
	elseif($searchType == 'date.range' || $searchType == 'calendar')
	{
		$j = ($searchType == 'date.range') ? 2 : 1;

		for ($i=1; $i<=$j; $i++)
		{
			$altfieldKey = '';

			if ($searchType == 'date.range')
			{
				$fieldKey    =  ($i == 1) ?  ($searchKey . '_from') : ($searchKey . '_to');
				$altfieldKey =  ($i == 1) ?  ($searchKey . '_to') : ($searchKey . '_from');
			}
			else
			{
				$fieldKey =  $searchKey;
			}

			$searchValue = isset($filters[$fieldKey]) ? $filters[$fieldKey] : '';
			$dateFormat  = isset($filters['dateFormat']) ? $filters['dateFormat'] : '%Y-%m-%d';

			if ($j == 2)
			{
				$altSearchValue = isset($filters[$altfieldKey]) ? $filters[$altfieldKey] : '';
				$filterHide = ($searchValue === '' && $altSearchValue === '') ? 'filter-hide' : 'filter-show';
			}
			else
			{
				$filterHide = $searchValue === '' ? 'filter-hide' : 'filter-show';
			}

			$attrib		 = array('class' => 'tjrsmall-input dash-calendar validate-ymd-date ' . $filterHide);

			if (isset($filter[$fieldKey]['attrib']))
			{
				$fieldAttr = array_merge($filter[$fieldKey]['attrib'], $attrib);
			}
			elseif (isset($filter['attrib']))
			{
				$fieldAttr = array_merge($filter['attrib'], $attrib);
			}
			else
			{
				$fieldAttr = $attrib;
			}

			$filterHtml  .= '<div class="filter-search controls custom-group input-group mr-10">'
				. HTMLHelper::_('calendar', htmlspecialchars($searchValue), 'filters['. $fieldKey . ']', 'filters_' . $fieldKey , $dateFormat, $fieldAttr);

			if ($this->filterLevel == 1)
			{
				$filterHtml	.= '</div>';
			}
			elseif ($this->filterLevel != 1 && $i != 1 || $searchType == 'calendar' )
			{
				$filterHtml	.= '<span class="input-group-btn custom-group-btn ml-10">
								<button class="btn btn-secondary close-icon" type="button" title="Cancel Search">
									<i class="fa fa-remove"></i>
								</button>
							</span></div>';
			}

			if(isset($this->colKey))
			{
				$filterHtml .= HTMLHelper::_('grid.sort', '', $this->colKey, $this->listDirn, $this->listOrder);
			}
		}
	}
	elseif($searchType == 'html')
	{
		$filterHtml = $filter['html'];
	}
	elseif ($multiple && ($user->authorise('core.manageall', 'com_cluster') || $user->authorise('core.admin')))
	{ 	
		
		
		$svalue = isset($filter['select_value']) ? $filter['select_value'] : "value";
		$stext  = isset($filter['select_text']) ? $filter['select_text'] : "text";

		$filterHtml = '<div class="tagwidth input-group mr-10">';
		$multiple   = 'multiple = multiple';	

		$filterHtml .= HTMLHelper::_('select.genericlist', $filter['select_options'], 'filters[' . $searchKey . '][]',' data-placeholder="'. Text::_('COM_DPE_FORM_LBL_TAG') . '" class="input-medium" size="1" onchange="tjrContentUI.report.removecluster();tjrContentUI.report.submitTJRData();" multiple="multiple"',
			$svalue, $stext, $searchValue);
			$filterHtml	.= '</div>';
	}
	?>
		<div class="filter-search controls pull-left pt-10 <?php echo $classForShowHide; ?>">
			<?php echo $filterHtml;?>
		</div>
	<?php
}
