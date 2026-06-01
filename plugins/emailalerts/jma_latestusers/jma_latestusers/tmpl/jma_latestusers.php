<?php
/**
 * @package     JMailAlerts
 * @subpackage  jma_latestusers
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

$rows      = $list;
$disp_name = $this->params->get('disp_name', 0);
$replace   = JUri::root();
$cbImgPath  = $replace . "/images/comprofiler";
$app       = JFactory::getApplication();

$jomSocialItemid = $this->getItemId('index.php?option=com_community');
$cbItemid        = $this->getItemId('index.php?option=com_comprofiler');
?>

<h2 class="subTitle">
	<?php echo JText::_($pluginParams->get('plugintitle')); ?>
</h2>

<table class="jma_latestuser product-table">
	<?php
	$i = 1;

	foreach ($rows as $row)
	{
		if ($i == 1)
		{
			echo "<tr>";
		}

		if ($disp_name == 1)
		{
			if ($app->isClient('administrator'))
			{
				$link = JRoute::_(
					$replace . 'index.php?option=com_community&view=profile&userid=' . $row->id . '&Itemid=' . $jomSocialItemid,
					false
				);
			}
			else
			{
				$link = JUri::root() . substr(
					CRoute::_('index.php?option=com_community&view=profile&userid=' . $row->id),
					strlen(JUri::base(true)) + 1
				);
			}
		}

		if ($disp_name == 2)
		{
			if ($app->isClient('administrator'))
			{
				$link = JRoute::_(
					$replace . 'index.php?option=com_comprofiler&task=viewprofile&user=' . $row->id . '&Itemid=' . $cbItemid,
					false
				);
			}
			else
			{
				$link = JUri::root() . substr(
					JRoute::_('index.php?option=com_comprofiler&task=viewprofile&user=' . $row->id . '&Itemid=' . $cbItemid, false),
					strlen(JUri::base(true)) + 1
				);
			}
		}

		// Jomsocial
		if ($disp_name == 1)
		{
			// To get jomsocial avatar
			$user   = CFactory::getUser($row->id);
			$uimage = $user->getThumbAvatar();

			if (!$app->isClient('site'))
			{
				$uimage = str_replace('administrator/', '', $uimage);
			}
		}

		// CB
		if ($disp_name == 2)
		{
			if ($row->avatar && $row->avatarapproved)
			{
				if (substr_count($row->avatar, "/") == 0)
				{
					$uimage = $cbImgPath . '/tn' . $row->avatar;
				}
				else
				{
					$uimage = $cbImgPath . '/' . $row->avatar;
				}
			}
			elseif ($row->avatar)
			{
				$uimage = $replace . "components/com_comprofiler/plugin/templates/default/images/avatar/tnpending_n.png";
			}
			else
			{
				$uimage = $replace . "components/com_comprofiler/plugin/templates/default/images/avatar/tnnophoto_n.png";
			}
		}

		$show_name = $pluginParams->get('show_name', 'username');

		if ($show_name == 'name')
		{
			$name = $row->name;
		}
		else
		{
			$name = $row->username;
		}

		// Joomla
		if ($disp_name == 0)
		{
			echo '<td class="jma_user_nm jma_latestuser_td">
				<span class="jma_prefspan">' .
					$name .
				'</span>
				<br />
			</td>';
		}
		else
		{
			echo '<td class="jma_user_img">
					<div>
						<a href="' . $link . '" target="_blank" title="' . $name . '">
							<img class="jma_latestuser_img" src="' . $uimage . '" alt="' . $name . '"/>
						</a>

						<br/>

						<a href="' . $link . '" target="_blank" title="' . $name . '">
							<span class="jma_prefspan">' . $name . '</span>
						</a>
						<br />
					</div>
				</td>';
		}

		$i++;

		if ($i > 3)
		{
			$i = 1;
			?>

			</tr>
			<?php
		}
	}
	?>
</table>
