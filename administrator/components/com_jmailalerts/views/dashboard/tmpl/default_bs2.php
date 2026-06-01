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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$params                    = ComponentHelper::getParams('com_jmailalerts');
$this->private_key_cronjob = $params->get('private_key_cronjob');
$cron = Route::_(
	Uri::root() .
	'index.php?option=com_jmailalerts&view=emails&tmpl=component&task=processMailAlerts&pkey=' . $this->private_key_cronjob
);
?>

<div class="<?php echo JMAILALERTS_WRAPPER_CLASS; ?>" id="jmailalerts-dashboard">
	<form action="<?php echo Route::_('index.php?option=com_jmailalerts&view=subscribers'); ?>" method="post" name="adminForm" id="adminForm">
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
			<div class="row-fluid">
				<div class="span7">
					<div class="well well-small">
						<div class="row-fluid">
							<div class="span12 module-title nav-header">
								<?php echo Text::_("COM_JMAILALERTS_WELCOME_JMA"); ?>
								<hr class="hr-condensed"/>
							</div>
						</div>

						<div class="row-fluid">
							<div class="span3">
								<div class="icon jma_icon">
									<a class="thumbnail btn" href="index.php?option=com_jmailalerts&view=frequencies">
									<img src="<?php echo Uri::base(); ?>components/com_jmailalerts/assets/images/l_frequencies.png" alt="frequencies"/>
									<span><?php echo Text::_("COM_JMAILALERTS_FREQ_MENU"); ?></span>
									</a>
								</div>
							</div>
							<div class="span3">
								<div class="icon jma_icon">
									<a class="thumbnail btn" href="index.php?option=com_jmailalerts&view=alerts">
									<img src="<?php echo Uri::base(); ?>components/com_jmailalerts/assets/images/l_alerts.png" alt="alerts"/>
									<span><?php echo Text::_("COM_JMAILALERTS_ALERTS"); ?></span>
									</a>
								</div>
							</div>
							<div class="span3">
								<div class="icon jma_icon">
									<a class="thumbnail btn" href="index.php?option=com_jmailalerts&view=sync">
									<img src="<?php echo Uri::base(); ?>components/com_jmailalerts/assets/images/l_sync.png" alt="alerts"/>
									<span><?php echo Text::_("COM_JMAILALERTS_SYNC"); ?></span>
									</a>
								</div>
							</div>
						</div>

						<div class="row-fluid">
							<div class="span3">
								<div class="icon jma_icon">
									<a class="thumbnail btn" href="index.php?option=com_jmailalerts&view=mailsimulate">
									<img src="<?php echo Uri::base(); ?>components/com_jmailalerts/assets/images/l_mailsimulate.png" alt="alerts"/>
									<span><?php echo Text::_("COM_JMAILALERTS_SIMULATE"); ?></span>
									</a>
								</div>
							</div>
							<div class="span3">
								<div class="icon jma_icon">
									<a class="thumbnail btn" href="index.php?option=com_jmailalerts&view=subscribers">
									<img src="<?php echo Uri::base(); ?>components/com_jmailalerts/assets/images/l_subscribers.png" alt="alerts"/>
									<span><?php echo Text::_("COM_JMAILALERTS_SUBS"); ?></span>
									</a>
								</div>
							</div>
							<div class="span3">
								<div class="icon jma_icon">
									<a class="thumbnail btn" href="index.php?option=com_jmailalerts&view=healthcheck">
									<img src="<?php echo Uri::base(); ?>components/com_jmailalerts/assets/images/l_healthcheck.png" alt="alerts"/>
									<span><?php echo Text::_("COM_JMAILALERTS_HELTHCHK"); ?></span>
									</a>
								</div>
							</div>
						</div>
					</div>
					<div class="well">
						<?php
						echo Text::_('COM_JMAILALERTS_CURR_CRON_URL');

						if ($this->private_key_cronjob)
						{
							?>
							<input type="text" class="input input-xxlarge" onclick="this.select();" value="<?php echo $cron; ?>" aria-invalid="false">
							<?php
						}
						else
						{
							?>
							<span class="alert alert-error">
								<?php echo Text::_('COM_JMAILALERTS_ENTER_CONFIG_KEY'); ?>
								</span>
							<?php
						}
						?>
					</div>
				</div>

				<div class="span5">
					<?php
					$versionHTML = '<span class="label label-info">' .
						Text::_('COM_JMAILALERTS_HAVE_INSTALLED_VER') . ': ' . $this->version .
					'</span>';

					if ($this->latestVersion)
					{
						if ($this->latestVersion->version > $this->version)
						{
							$versionHTML = '<div class="alert alert-error">' .
								'<i class="icon-puzzle install"></i>' .
								Text::_('COM_JMAILALERTS_HAVE_INSTALLED_VER') . ': ' . $this->version .
								'<br/>' .
								'<i class="icon icon-info"></i>' .
								Text::_("COM_JMAILALERTS_NEW_VER_AVAIL") . ': ' .
								'<span class="jma_latest_version_number">' .
									$this->latestVersion->version .
								'</span>
								<br/>' .
								'<i class="icon icon-warning"></i>' .
								'<span class="small">' .
									Text::_("COM_JMAILALERTS_LIVE_UPDATE_BACKUP_WARNING") . '
								</span>' . '
							</div>

							<div>
								<a href="index.php?option=com_installer&view=update" class="jma-btn-wrapper btn btn-small btn-primary">' .
									Text::sprintf('COM_JMAILALERTS_LIVE_UPDATE_TEXT', $this->latestVersion->version) . '
								</a>
								<a
									href="' . $this->latestVersion->infourl . '/?utm_source=clientinstallation&utm_medium=dashboard&utm_term=jmailalerts&utm_content=updatedetailslink&utm_campaign=jmailalerts_ci"
									target="_blank" rel="noopener" class="jma-btn-wrapper btn btn-small btn-info">' .
									Text::_('COM_JMAILALERTS_LIVE_UPDATE_KNOW_MORE') . '
								</a>
							</div>';
						}
					}
					?>

					<div class="row-fluid">
						<?php
						if (!$this->downloadid)
						{
							?>
							<div class="">
								<div class="clearfix pull-right">
									<div class="alert alert-warning">
										<?php
										echo Text::sprintf(
											'COM_JMAILALERTS_LIVE_UPDATE_DOWNLOAD_ID_MSG',
											'<a href="https://techjoomla.com/about-tj/faqs/#how-to-get-your-download-id" target="_blank" rel="noopener">' .
											Text::_('COM_JMAILALERTS_LIVE_UPDATE_DOWNLOAD_ID_MSG2') .
											'</a>'
										);
										?>
									</div>
								</div>
							</div>
							<?php
						}
						?>

						<div class="">
							<div class="clearfix pull-right">
								<?php echo $versionHTML; ?>
							</div>
						</div>
					</div>

					<div class="clearfix">&nbsp;</div>
					<div class="well well-small">
						<div class="module-title nav-header">
							<?php echo '<i class="icon-mail-2"></i>';?>
							<strong>
								<?php echo Text::_('COM_JMAILALERTS'); ?>
							</strong>
						</div>
						<hr class="hr-condensed"/>

						<div class="row-fluid">
							<div class="span12 alert alert-success"><?php echo Text::_('COM_JMAILALERTS_INTRO'); ?></div>
						</div>

						<div class="row-fluid">
							<div class="span12">
								<p class="pull-right"><span class="label label-info"><?php echo Text::_('COM_JMAILALERTS_LINKS'); ?></span></p>
							</div>
						</div>

						<div class="row-striped">
							<div class="row-fluid">
								<div class="span12">
									<a
										href="https://techjoomla.com/table/extension-documentation/documentation-for-jmailalerts/?utm_source=clientinstallation&utm_medium=dashboard&utm_term=jmailalerts&utm_content=textlink&utm_campaign=jmailalerts_ci"
										target="_blank" rel="noopener">
											<i class="icon-file"></i> <?php echo Text::_('COM_JMAILALERTS_DOCS'); ?>
									</a>
								</div>
							</div>
							<div class="row-fluid">
								<div class="span12">
									<a
										href="https://techjoomla.com/support-tickets/?utm_source=clientinstallation&utm_medium=dashboard&utm_term=jmailalerts&utm_content=textlink&utm_campaign=jmailalerts_ci"
										target="_blank" rel="noopener">
										<?php echo '<i class="icon-support"></i>'; ?>
										<?php echo Text::_('COM_JMAILALERTS_TECHJOOMLA_SUPPORT_CENTER'); ?>
									</a>
								</div>
							</div>
							<div class="row-fluid">
								<div class="span12">
									<a href="http://extensions.joomla.org/extensions/extension/marketing/mailing-a-distribution-lists/j-mailalerts" target="_blank" rel="noopener">
										<?php echo '<i class="icon-quote"></i>'; ?>
										<?php echo Text::_('COM_JMAILALERTS_LEAVE_JED_FEEDBACK'); ?></a>
								</div>
							</div>
						</div>

						<br/>
						<div class="row-fluid">
							<div class="span12">
								<p class="pull-right">
									<span class="label label-info"><?php echo Text::_('COM_JMAILALERTS_STAY_TUNNED'); ?></span>
								</p>
							</div>
						</div>

						<div class="row-striped">
							<div class="row-fluid">
								<div class="span4">
									<?php echo Text::_('COM_JMAILALERTS_FACEBOOK'); ?>
								</div>

								<div class="span8">
									<a
										href="https://www.facebook.com/techjoomla"
										target="_blank" rel="noopener">
											<span class="fa fa-facebook icon-facebook icon-fw"></span> <?php echo Text::_('COM_JMAILALERTS_FACEBOOK'); ?>
									</a>
								</div>
							</div>

							<div class="row-fluid">
								<div class="span4"><?php echo Text::_('COM_JMAILALERTS_TWITTER'); ?></div>
								<div class="span8">
									<a
										href="https://twitter.com/techjoomla"
										target="_blank" rel="noopener">
											<span class="fa fa-twitter icon-facebook icon-fw"></span> <?php echo Text::_('COM_JMAILALERTS_TWITTER'); ?>
									</a>
								</div>
							</div>
						</div>

						<br/>
						<div class="row-fluid">
							<div class="span12 center">
								<?php
								$logoPath = '<img src="' .
									Uri::base() .
									'components/com_jmailalerts/assets/images/techjoomla.png"
									alt="Techjoomla"
									class="jma_vertical_align_top"/>';
								?>
								<a
									href='https://techjoomla.com/?utm_source=clientinstallation&utm_medium=dashboard&utm_term=jmailalerts&utm_content=logolink&utm_campaign=jmailalerts_ci'
									target="_blank" rel="noopener"
									alt="Techjoomla">
									<?php echo $logoPath; ?>
								</a>
								<p><?php echo Text::sprintf('COM_JMAILALERTS_COPYRIGHT', date('Y')); ?></p>
							</div>
						</div>
					</div>
				</div><!--END span4 -->
			</div>
			<!--END outermost row-fluid -->
		</div>
	</form>
</div>
