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
		<div id="j-main-container" class="j-main-container">
			<div class="row">
				<div class="col-sm-12 col-sm-12 col-md-7 col-lg-8">
					<div class="row">
						<div class="col-sm-4 col-sm-4 col-md-4 col-lg-4">
							<div class="card text text-center">
								<a class="btn" href="index.php?option=com_jmailalerts&view=frequencies">
									<img src="<?php echo Uri::base(); ?>components/com_jmailalerts/assets/images/l_frequencies.png" alt="frequencies" class="thumbnail">
									<div class="card-body">
										<p class="card-text">
											<?php echo Text::_("COM_JMAILALERTS_FREQ_MENU"); ?>
										</p>
									</div>
								</a>
							</div>
						</div>

						<div class="col-sm-4 col-sm-4 col-md-4 col-lg-4">
							<div class="card text text-center">
								<a class="btn" href="index.php?option=com_jmailalerts&view=alerts">
									<img src="<?php echo Uri::base(); ?>components/com_jmailalerts/assets/images/l_alerts.png" alt="alerts" class="thumbnail">
									<div class="card-body">
										<p class="card-text">
											<?php echo Text::_("COM_JMAILALERTS_ALERTS"); ?>
										</p>
									</div>
								</a>
							</div>
						</div>

						<div class="col-sm-4 col-sm-4 col-md-4 col-lg-4">
							<div class="card text text-center">
								<a class="btn" href="index.php?option=com_jmailalerts&view=sync">
									<img src="<?php echo Uri::base(); ?>components/com_jmailalerts/assets/images/l_sync.png" alt="sync" class="thumbnail">
									<div class="card-body">
										<p class="card-text">
											<?php echo Text::_("COM_JMAILALERTS_SYNC"); ?>
										</p>
									</div>
								</a>
							</div>
						</div>
					</div>

					<div>&nbsp;</div>

					<div class="row">
						<div class="col-sm-4 col-sm-4 col-md-4 col-lg-4">
							<div class="card text text-center">
								<a class="btn" href="index.php?option=com_jmailalerts&view=mailsimulate">
									<img src="<?php echo Uri::base(); ?>components/com_jmailalerts/assets/images/l_mailsimulate.png" alt="mailsimulate" class="thumbnail">
									<div class="card-body">
										<p class="card-text">
											<?php echo Text::_("COM_JMAILALERTS_SIMULATE"); ?>
										</p>
									</div>
								</a>
							</div>
						</div>

						<div class="col-sm-4 col-sm-4 col-md-4 col-lg-4">
							<div class="card text text-center">
								<a class="btn" href="index.php?option=com_jmailalerts&view=subscribers">
									<img src="<?php echo Uri::base(); ?>components/com_jmailalerts/assets/images/l_subscribers.png" alt="subscribers" class="thumbnail">
									<div class="card-body">
										<p class="card-text">
											<?php echo Text::_("COM_JMAILALERTS_SUBS"); ?>
										</p>
									</div>
								</a>
							</div>
						</div>

						<div class="col-sm-4 col-sm-4 col-md-4 col-lg-4">
							<div class="card text text-center">
								<a class="btn" href="index.php?option=com_jmailalerts&view=healthcheck">
									<img src="<?php echo Uri::base(); ?>components/com_jmailalerts/assets/images/l_healthcheck.png" alt="healthcheck" class="thumbnail">
									<div class="card-body">
										<p class="card-text">
											<?php echo Text::_("COM_JMAILALERTS_HELTHCHK"); ?>
										</p>
									</div>
								</a>
							</div>
						</div>
					</div>

					<div>&nbsp;</div>

					<div class="row">
						<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
							<h2>
								<?php echo Text::_('COM_JMAILALERTS_CURR_CRON_URL'); ?>
							</h2>

							<?php
							if ($this->private_key_cronjob)
							{
								?>
								<input type="text" class="form-control" onclick="this.select();" value="<?php echo $cron; ?>" aria-invalid="false">
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
				</div>

				<div class="col-sm-12 col-sm-12 col-md-5 col-lg-4">
					<?php
					$versionHTML = '
					<div class="float-end">
						<span class="badge bg-info">' .
							Text::_('COM_JMAILALERTS_HAVE_INSTALLED_VER') . ': ' . $this->version .
						'</span>
					</div>';

					if ($this->latestVersion)
					{
						if ($this->latestVersion->version > $this->version)
						{
							$versionHTML = '
							<div class="alert alert-error">' .
								'<span class="icon-puzzle install icon-fw"></span> &nbsp;' .
								Text::_('COM_JMAILALERTS_HAVE_INSTALLED_VER') . ': ' . $this->version .
								'<br/>' .
								'<span class="icon icon-info icon-fw"></span> &nbsp;' .
								Text::_("COM_JMAILALERTS_NEW_VER_AVAIL") . ': ' .
								'<span class="jma_latest_version_number">' .
									$this->latestVersion->version .
								'</span>
								<br/>' .
								'<span class="icon icon-warning icon-fw"></span> &nbsp;' .
								'<span class="small">' .
									Text::_("COM_JMAILALERTS_LIVE_UPDATE_BACKUP_WARNING") . '
								</span>' . '
							</div>

							<div>
								<a href="index.php?option=com_installer&view=update" class="jma-btn-wrapper btn btn-primary">' .
									Text::sprintf('COM_JMAILALERTS_LIVE_UPDATE_TEXT', $this->latestVersion->version) . '
								</a>

								<a
									href="' . $this->latestVersion->infourl . '/?utm_source=clientinstallation&utm_medium=dashboard&utm_term=jmailalerts&utm_content=updatedetailslink&utm_campaign=jmailalerts_ci"
									target="_blank" rel="noopener" class="jma-btn-wrapper btn btn-info">' .
									Text::_('COM_JMAILALERTS_LIVE_UPDATE_KNOW_MORE') . '
								</a>
							</div>';
						}
					}
					?>

					<div class="row">
						<?php
						if (!$this->downloadid)
						{
							?>
							<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
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
							<?php
						}
						?>

						<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
							<?php echo $versionHTML; ?>
						</div>
					</div>

					<div class="clearfix">&nbsp;</div>

					<div class="row">
						<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
							<div class="card small">
								<div class="card-body">
									<div class="row">
										<div class="col-sm-12 col-sm-12 col-md-12 col-lg-12">
											<h5 class="card-title">
												<?php echo '<span class="icon-mail-2 icon-fw"></span>'; ?>
												<strong><?php echo Text::_('COM_JMAILALERTS'); ?></strong>
											</h5>

											<div class="alert alert-info"><?php echo Text::_('COM_JMAILALERTS_INTRO'); ?></div>
										</div>
									</div>

									<div class="row">
										<div class="col-sm-12 col-sm-12 col-md-12 col-lg-12">
											<p class="float-end">
												<span class="badge bg-info"><?php echo Text::_('COM_JMAILALERTS_LINKS'); ?></span>
											</p>
										</div>
									</div>

									<div class="row">
										<div class="col-sm-12 col-sm-12 col-md-12 col-lg-12">
											<ul class="list-group list-group-flush">
												<li class="list-group-item">
													<a
														href="https://techjoomla.com/table/extension-documentation/documentation-for-jmailalerts/?utm_source=clientinstallation&utm_medium=dashboard&utm_term=jmailalerts&utm_content=textlink&utm_campaign=jmailalerts_ci"
														target="_blank" rel="noopener">
															<span class="icon-file icon-fw"></span> <?php echo Text::_('COM_JMAILALERTS_DOCS'); ?>
													</a>
												</li>

												<li class="list-group-item">
													<a
													href="https://techjoomla.com/support-tickets/?utm_source=clientinstallation&utm_medium=dashboard&utm_term=jmailalerts&utm_content=textlink&utm_campaign=jmailalerts_ci"
													target="_blank" rel="noopener">
														<?php echo '<span class="icon-support icon-fw"></span>'; ?>
														<?php echo Text::_('COM_JMAILALERTS_TECHJOOMLA_SUPPORT_CENTER'); ?>
													</a>
												</li>

												<li class="list-group-item">
													<a href="http://extensions.joomla.org/extensions/extension/marketing/mailing-a-distribution-lists/j-mailalerts" target="_blank" rel="noopener">
														<?php echo '<span class="icon-quote icon-fw"></span>'; ?>
														<?php echo Text::_('COM_JMAILALERTS_LEAVE_JED_FEEDBACK'); ?>
													</a>
												</li>
											</ul>
										</div>
									</div>

									<div class="row">
										<div class="col-sm-12 col-sm-12 col-md-12 col-lg-12">
											<p class="float-end">
												<span class="badge bg-info"><?php echo Text::_('COM_JMAILALERTS_STAY_TUNNED'); ?></span>
											</p>
										</div>
									</div>

									<div class="row">
										<div class="col-sm-12 col-sm-12 col-md-12 col-lg-12">
											<ul class="list-group list-group-flush">
												<li class="list-group-item">
													<a
														href="https://www.facebook.com/techjoomla"
														target="_blank" rel="noopener">
															<span class="fa fa-facebook icon-facebook icon-fw"></span> <?php echo Text::_('COM_JMAILALERTS_FACEBOOK'); ?>
													</a>
												</li>

												<li class="list-group-item">
													<a
														href="https://twitter.com/techjoomla"
														target="_blank" rel="noopener">
															<span class="fa fa-twitter icon-facebook icon-fw"></span> <?php echo Text::_('COM_JMAILALERTS_TWITTER'); ?>
													</a>
												</li>
											</ul>
										</div>
									</div>

									<div class="row">
										<div class="col-sm-12 col-sm-12 col-md-12 col-lg-12 text text-center">
											<?php
											$logoPath = '<img src="' .
												Uri::base() .
												'components/com_jmailalerts/assets/images/techjoomla.png"
												alt="Techjoomla"
												class="jma_vertical_align_top"/>';
											?>
											<a
												href='https://techjoomla.com/?utm_source=clientinstallation&utm_medium=dashboard&utm_term=jmailalerts&utm_content=logolink&utm_campaign=jmailalerts_ci'
												target='_blank'
												alt="Techjoomla">
												<?php echo $logoPath; ?>
											</a>
											<p><?php echo Text::sprintf('COM_JMAILALERTS_COPYRIGHT', date('Y')); ?></p>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>
