<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
?>

<div class="<?php echo JMAILALERTS_WRAPPER_CLASS; ?>" id="jmailalerts-healthcheck">
	<?php
	if (!empty($this->sidebar))
	{
		?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>

	<div id="j-main-container" class="span10">
		<?php
	}
	else
	{
		?>
	<div id="j-main-container">
		<?php
	}
	?>

	<table class="table table-striped">
		<thead>
			<tr>
				<th width="50">
					<?php echo Text::_('COM_JMAILALERTS_HEALTHCHECK_ID'); ?>
				</th>
				<th align="center" width="300">
					<?php echo Text::_('COM_JMAILALERTS_CHECK'); ?>
				</th>
				<th width="300">
					<?php echo Text::_('COM_JMAILALERTS_DESC'); ?>
				</th>
				<th width="300">
					<?php echo Text::_('COM_JMAILALERTS_STATUS'); ?>
				</th>
				<th nowrap="nowrap">
					<?php echo Text::_('COM_JMAILALERTS_ACT_NEED'); ?>
				</th>
			</tr>
		</thead>

		<tbody>
			<tr>
				<?php
				$installed = (int) (!empty($this->data['installed'])) ? 1 : 0;
				$class     = $installed ? 'jma-green' : 'jma-red';
				?>

				<td align="center">
					<?php echo "1"; ?>
				</td>

				<td>
					<?php echo Text::_('COM_JMAILALERTS_QUESTION1'); ?>
				</td>

				<td> </td>

				<td>
					<span class="<?php echo $class; ?>">
						<?php
						if ($installed)
						{
							echo Text::_('JYES');
						}
						else
						{
							echo Text::_('JNO');
						}
						?>
					</span>
				</td>

				<td align="left">
					<?php
					if ($installed)
					{
						echo Text::_('COM_JMAILALERTS_NO_ACT');
					}
					else
					{
						echo Text::_('COM_JMAILALERTS_ACT1');
					}
					?>
				</td>
			</tr>

			<tr>
				<?php
				$enabled   = (int) (!empty($this->data['enable'])) ? 1 : 0;
				$class     = $enabled ? 'jma-green' : 'jma-red';
				?>
				<td align="center">
					<?php echo "2"; ?>
				</td>

				<td>
					<?php echo Text::_('COM_JMAILALERTS_QUESTION2'); ?>
				</td>

				<td>
				</td>

				<td>
					<span class="<?php echo $class; ?>">
						<?php
						if ($enabled)
						{
							echo Text::_('JYES');
						}
						else
						{
							echo Text::_('JNO');
						}
						?>
					</span>
				</td>

				<td align="left">
					<?php
					if ($enabled)
					{
						echo Text::_('COM_JMAILALERTS_NO_ACT');
					}
					else
					{
						?>
						<a href="index.php?option=com_plugins" target="_blank">
							<?php echo Text::_('COM_JMAILALERTS_PLG_MANAGER'); ?>
						</a>
						<?php
					}
					?>
				</td>
			</tr>

			<tr>
				<td align="center">
					<?php echo "3"; ?>
				</td>

				<td>
					<?php echo Text::_('COM_JMAILALERTS_QUESTION3'); ?>
				</td>

				<td>
					<?php echo Text::_('COM_JMAILALERTS_DESC_Q3'); ?>
				</td>

				<?php $tdClass = (int) (!empty($this->data['alerts'])) ? "small" : ""; ?>

				<td class="<?php echo $tdClass; ?>">
					<?php
					$warn = 0;
					$lang = Factory::getLanguage();

					// If at least 1 plugin is installed, and at least 1 alert is created
					if ($this->data['installed'] && $this->data['alerts'])
					{
						foreach ($this->pluginsNames as $plgname)
						{
							$lang->load("plg_emailalerts_" . $plgname->element, JPATH_ADMINISTRATOR);

							$plgnm = $plgname->enabled;

							if (!$plgnm)
							{
								?>
								<span class="jma-red">
									<?php
									echo Text::_($plgname->name);
								$warn = 1; ?>
								</span>
								<?php
							}
							else
							{
								echo Text::_($plgname->name);
							}

							echo "<br />";
						}
					}
					else
					{
						?>
						<span class="jma-red">
							<?php
							if ($this->data['installed'] == 0)
							{
								echo Text::_('COM_JMAILALERTS_NO_PLUGINS_ARE_INSTALLED');
							}
						elseif ($this->data['alerts'] == 0)
						{
							echo '-';
						} ?>
						</span>
						<?php
					}
					?>
				</td>

				<td align="left">
					<?php
					$cnt = 0;

					if ($this->data['installed'])
					{
						foreach ($this->pluginsNames as $plgname)
						{
							$plgnm = $plgname->enabled;
							$cnt   = ($plgnm == 0) ? $cnt + 1 : $cnt;
						}

						$plgname = (count($this->pluginsNames) == $cnt) ? 0 : 1;

						if ($plgname == 1 && $warn == 0)
						{
							echo Text::_('COM_JMAILALERTS_NO_ACT');
						}
						else
						{
							echo Text::_('COM_JMAILALERTS_ACT3'); ?>

							<br />

							<a href="index.php?option=com_jmailalerts&view=alerts" target="_blank">
								<?php echo Text::_('COM_JMAILALERTS_MANAGE_ALERT'); ?>
							</a>

							<br/>
							<br/>

							<a href="index.php?option=com_plugins&filter[folder]=emailalerts" target="_blank">
								<?php echo Text::_('COM_JMAILALERTS_PLG_MANAGER'); ?>
							</a>
							<?php
						}
					}
					else
					{
						?>

						<a href="index.php?option=com_installer" target="_blank">
							<?php echo Text::_('COM_JMAILALERTS_PLG_INSTALLER'); ?>
						</a>

						<br/>

						<?php echo Text::_('COM_JMAILALERTS_INSTALL_PLUGINS_FROM_EXTENSION_MANAGER'); ?>

						<?php
					}
					?>
				</td>
			</tr>

			<tr>
				<?php
				$alerts    = (int) (!empty($this->data['alerts'])) ? 1 : 0;
				$class     = $alerts ? 'jma-green' : 'jma-red';
				?>

				<td align="center">
					<?php echo "4"; ?>
				</td>

				<td>
					<?php echo Text::_('COM_JMAILALERTS_QUESTION4'); ?>
				</td>

				<td> </td>

				<td>
					<span class="<?php echo $class; ?>">
						<?php
						if ($alerts)
						{
							echo Text::sprintf('COM_JMAILALERTS_TOTAL_N_ALERTS_FOUND', $this->data['alerts']);
						}
						else
						{
							echo Text::_('JNO');
						}
						?>
					</span>
				</td>

				<td align="left">
					<?php
					if ($alerts)
					{
						echo Text::_('COM_JMAILALERTS_NO_ACT');
					}
					else
					{
						?>
						<a href="index.php?option=com_jmailalerts&view=alerts" target="_blank">
							<?php echo Text::_('COM_JMAILALERTS_MANAGE_ALERT'); ?>
						</a>
						<?php
					}
					?>
				</td>
			</tr>

			<tr>
				<?php
				$published = (int) (!empty($this->data['published'])) ? 1 : 0;
				$class     = $published ? 'jma-green' : 'jma-red';
				?>

				<td align="center">
					<?php echo "5"; ?>
				</td>

				<td>
					<?php echo Text::_('COM_JMAILALERTS_QUESTION5'); ?>
				</td>

				<td> </td>

				<td>
					<span class="<?php echo $class; ?>">
						<?php
						if ($published)
						{
							echo Text::sprintf('COM_JMAILALERTS_TOTAL_N_ALERTS_PUBLISHED', $this->data['published']);
						}
						else
						{
							echo Text::_('JNO');
						}
						?>
					</span>
				</td>

				<td align="left">
					<?php
					if ($published)
					{
						echo Text::_('COM_JMAILALERTS_NO_ACT');
					}
					else
					{
						?>
						<a href="index.php?option=com_jmailalerts&view=alerts" target="_blank">
							<?php echo Text::_('COM_JMAILALERTS_MANAGE_ALERT'); ?>
						</a>
						<?php
					}
					?>
				</td>
			</tr>

			<tr>
				<?php
				$defaults = (int) (!empty($this->data['defaults'])) ? 1 : 0;
				$class    = $defaults ? 'jma-green' : 'jma-red';
				?>
				<td align="center">
					<?php echo "6"; ?>
				</td>

				<td>
					<?php echo Text::_('COM_JMAILALERTS_QUESTION6'); ?>
				</td>

				<td>
					<?php echo Text::_('COM_JMAILALERTS_DESC_Q6'); ?>
				</td>

				<td>
					<span class="<?php echo $class; ?>">
						<?php
						if ($defaults)
						{
							echo Text::sprintf('COM_JMAILALERTS_TOTAL_N_ALERTS_DEFAULT', $this->data['defaults']);
						}
						else
						{
							echo Text::_('JNO');
						}
						?>
					</span>
				</td>

				<td align="left">
					<?php
					if ($defaults)
					{
						echo Text::_('COM_JMAILALERTS_NO_ACT');
					}
					else
					{
						?>
						<a href="index.php?option=com_jmailalerts&view=alerts" target="_blank">
							<?php echo Text::_('COM_JMAILALERTS_MANAGE_ALERT'); ?>
						</a>
						<?php
					}
					?>
				</td>
			</tr>

			<tr>
				<?php
				$synced = (int) (!empty($this->data['synced'])) ? 1 : 0;
				$class  = $synced ? 'jma-green' : 'jma-red';
				?>
				<td align="center">
					<?php echo "7"; ?>
				</td>

				<td>
					<?php echo Text::_('COM_JMAILALERTS_QUESTION7'); ?>
				</td>

				<td> </td>

				<td>
					<span class="<?php echo $class; ?>">
						<?php
						if ($synced)
						{
							echo Text::sprintf('COM_JMAILALERTS_TOTAL_N_ALERTS_SYNCED', $this->data['synced']);
						}
						else
						{
							echo Text::_('JNO');
						}
						?>
					</span>
				</td>

				<td align="left">
					<?php
					if ($synced)
					{
						echo Text::_('COM_JMAILALERTS_NO_ACT');
					}
					else
					{
						?>
						<a href="index.php?option=com_jmailalerts&view=sync" target="_blank">
							<?php echo Text::_('COM_JMAILALERTS_SYNC_MAIL'); ?>
						</a>
						<?php
					}
					?>
				</td>
			</tr>
		</tbody>

		<tfoot>
			<tr>
				<td colspan="9">
					<?php // @echo $this->pagination->getListFooter();?>
				</td>
			</tr>
		</tfoot>
	</table>
</div>
