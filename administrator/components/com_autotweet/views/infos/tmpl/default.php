<?php
/**
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see        https://www.extly.com
 */
defined('_JEXEC') || exit;

$rows = 0;

$logger = AutotweetLogger::getInstance();
$logfile = $logger->getLoggedFile();

?>
<div class="extly system-info">
	<div class="xt-body">
			<div class="xt-grid">
				<div class="xt-col-span-8">
					<h2>
						<?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_COMPONENTINFO_TITLE'); ?>
					</h2>
					<?php echo $this->comp['message']; ?>
					<table class="table table-striped table-bordered">
						<thead>
							<tr>
								<th width="200"><?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_VERSIONINFO_NAME'); ?>
								</th>
								<th width="100"><?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_VERSIONINFO_INSTALLED'); ?>
								</th>
								<th width="100"><?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_VERSIONINFO_NEWEST'); ?>
								</th>
								<th width="60">&nbsp;</th>
								<th><?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_VERSIONINFO_INFO'); ?>
								</th>
							</tr>
						</thead>
						<tbody>
							<tr
								class="row<?php
                                ++$rows;
                                echo $rows % 2;
                                ?>">
								<td><?php echo JText::_($this->comp['name']); ?>
								</td>
								<?php
                                $version_client = $this->comp['client_version'];
                                $version_color = null;
                                $version_server = $this->comp['server_version'];

                                if (version_compare($version_client, $version_server, '<')) {
                                    if (PERFECT_PUB_PRO) {
                                        $version_html = '<a class="btn btn-success" href="'.$this->comp['download'].'" target="_blank">'.JText::_('COM_AUTOTWEET_VIEW_ABOUT_VERSIONINFO_DOWNLOAD').'</a>';
                                    } else {
                                        $version_html = JText::_('COM_AUTOTWEET_UPDATE_TO_PERFECT_PUBLISHER_PRO_LABEL');
                                    }

                                    $version_message = $this->comp['message'];
                                } else {
                                    $version_html = '<button class="btn btn-success" type="button">Success</button>';
                                    $version_message = '';
                                }
                                ?>
								<td align="right"><?php echo $version_client; ?>
								</td>
								<td align="right"><?php echo $version_server; ?>
								</td>
								<td align="center"
									<?php

                                    echo $version_color ? 'style="background-color:'.$version_color.'"' : null;

                                    ?>><?php

                                echo $version_html;

                                ?></td>
								<td><?php echo $version_message; ?>
								</td>
							</tr>
						</tbody>
					</table>

					<h2>
						<?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_EXTENSIONINFO_TITLE'); ?>
					</h2>
                    <p class="text-center">
                        <a href="https://www.extly.com/downloads.html" class="btn btn-info" target="_blank">
                         <i class="xticon fas fa-download"></i>&nbsp;
                         <?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_EXTENSIONINFO_DOWNLOAD'); ?></a>
                        </p>
					<table class="table table-striped table-bordered">
						<thead>
							<tr>
								<th width="200"><?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_VERSIONINFO_NAME'); ?>
								</th>
								<th width="40"><?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_VERSIONINFO_STATE'); ?>
								</th>
								<th width="100"><?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_VERSIONINFO_INSTALLED'); ?>
								</th>
								<th width="100"><?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_VERSIONINFO_NEWEST'); ?>
								</th>
								<th width="60">&nbsp;</th>
								<th><?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_VERSIONINFO_INFO'); ?>
								</th>
							</tr>
						</thead>
						<tbody>
							<?php

                            $version_color = null;

                            foreach ($this->plugins as $plugin) {
                                ?>
							<tr
								class="row<?php
                                ++$rows;
                                echo $rows % 2;

                                $notinstalled = ('COM_AUTOTWEET_STATE_PLUGIN_NOTINSTALLED' === $plugin['state']);
                                $disabled = ('COM_AUTOTWEET_STATE_PLUGIN_DISABLED' === $plugin['state']);
                                $enabled = ((!$disabled) && (!$notinstalled)); ?>">
								<td><?php echo $plugin['name']; ?>
								</td>
								<td><span class="xt-badge <?php

                                    echo $enabled ? 'xt-badge-success' : ''; ?>"><?php

                                    echo ucfirst(JText::_($plugin['state'])); ?></span>
								</td>
								<td align="right"><?php echo $plugin['client_version']; ?>
								</td>
								<td align="right"><?php echo $plugin['server_version']; ?>
								</td>
								<?php

                                if ($notinstalled) {
                                    $version_html = '<a class="btn" href="'.$this->comp['download'].'" target="_blank">'.JText::_('COM_AUTOTWEET_VIEW_ABOUT_VERSIONINFO_DOWNLOAD').'</a>';
                                    $version_message = $plugin['message'];
                                } elseif (($enabled) && (version_compare($plugin['client_version'], $plugin['server_version'], '<'))) {
                                    $version_html = '<a class="btn btn-warning" href="'.$this->comp['download'].'" target="_blank">'.JText::_('COM_AUTOTWEET_VIEW_ABOUT_VERSIONINFO_DOWNLOAD').'</a>';
                                    $version_message = $plugin['message'];
                                } elseif ($disabled) {
                                    if (empty($plugin['config'])) {
                                        $version_html = '';
                                    } else {
                                        $version_html = '<a class="btn btn-warning" href="'.$plugin['config'].'">'.JText::_('COM_AUTOTWEET_VIEW_ABOUT_VERSIONINFO_ENABLE').'</a>';
                                    }

                                    $version_message = '';
                                } else {
                                    if (empty($plugin['config'])) {
                                        $version_html = '';
                                    } else {
                                        $version_html = '<a class="btn btn-success" href="'.$plugin['config'].'">'.JText::_('COM_AUTOTWEET_VIEW_ABOUT_VERSIONINFO_OPTIONS').'</a>';
                                    }

                                    $version_message = '';
                                } ?>
								<td align="center"
									<?php
                                echo $version_color ? 'style="background-color:'.$version_color.'"' : null; ?>><?php
                                echo $version_html; ?></td>
								<td><?php echo $version_message; ?>
								</td>
							</tr>
							<?php
                            }
                            ?>
						</tbody>
					</table>

					<h2>
						<?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_EXTENSIONTHIRDPARTYINFO_TITLE'); ?>
					</h2>
					<table class="table table-striped table-bordered">
						<thead>
							<tr>
								<th width="200"><?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_VERSIONINFO_NAME'); ?>
								</th>
								<th width="200"><?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_VERSIONINFO_SOURCE'); ?>
								</th>
								<th width="40"><?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_VERSIONINFO_STATE'); ?>
								</th>
								<th width="100"><?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_VERSIONINFO_INSTALLED'); ?>
								</th>
								<th width="60">&nbsp;</th>
								<th><?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_VERSIONINFO_INFO'); ?>
								</th>
							</tr>
						</thead>
						<tbody>
							<?php
                            foreach ($this->thirdparty as $thirdparty_item) {
                                ?>
							<tr
								class="row<?php
                                ++$rows;
                                echo $rows % 2;

                                $notinstalled = ('COM_AUTOTWEET_STATE_PLUGIN_NOTINSTALLED' === $thirdparty_item['state']);
                                $disabled = ('COM_AUTOTWEET_STATE_PLUGIN_DISABLED' === $thirdparty_item['state']);
                                $enabled = ((!$disabled) && (!$notinstalled)); ?>">
								<td><?php echo JText::_($thirdparty_item['name']); ?>
								</td>
								<td><?php echo '<a href="'.$thirdparty_item['download'].'" target="_blank">'.JText::_($thirdparty_item['source']).'</a>'; ?>
								</td>
								<td><span class="xt-badge <?php

                                    echo $enabled ? 'xt-badge-success' : ''; ?>"><?php

                                echo ucfirst(JText::_($thirdparty_item['state'])); ?></span>
								</td>
								<td align="right"><?php echo $thirdparty_item['client_version']; ?>
								</td>
								<?php

                                $version_color = null;

                                if ($notinstalled) {
                                    if (!empty($thirdparty_item['download'])) {
                                        $version_html = '<a class="btn" href="'.$thirdparty_item['download'].'" target="_blank">'.JText::_('COM_AUTOTWEET_VIEW_ABOUT_VERSIONINFO_DOWNLOAD').'</a>';
                                    } else {
                                        $version_html = JText::_('COM_AUTOTWEET_VIEW_ABOUT_VERSIONINFO_DOWNLOAD');
                                    }
                                } elseif ($disabled) {
                                    if (empty($thirdparty_item['config'])) {
                                        $version_html = '';
                                    } else {
                                        $version_html = '<a class="btn btn-warning" href="'.$thirdparty_item['config'].'">'.JText::_('COM_AUTOTWEET_VIEW_ABOUT_VERSIONINFO_ENABLE').'</a>';
                                    }
                                } else {
                                    if (empty($thirdparty_item['config'])) {
                                        $version_html = '';
                                    } else {
                                        $version_html = '<a class="btn btn-success" href="'.$thirdparty_item['config'].'">'.JText::_('COM_AUTOTWEET_VIEW_ABOUT_VERSIONINFO_OPTIONS').'</a>';
                                    }
                                } ?>
								<td align="center" style="background-color:<?php

                                echo $version_color; ?>"><?php

                                echo $version_html; ?>
								</td>
								<td><?php echo $thirdparty_item['message']; ?>
								</td>
							</tr>
							<?php
                            }
                            ?>
						</tbody>
					</table>

				</div>

				<div class="xt-col-span-4">
					<?php

                    require_once 'systeminfo.php';

                    $logger = AutotweetLogger::getInstance();
                    $log_level = $logger->isLogging();

                    switch ($log_level) {
                        case 8:
                            $msg = JText::_('COM_AUTOTWEET_COMPARAM_LOGLEVEL_ERROR_1');

                            break;
                        case 16:
                            $msg = '<span class="xt-badge xt-badge-info">'.JText::_('COM_AUTOTWEET_COMPARAM_LOGLEVEL_WARN_2').'</span>';

                            break;
                        case 64:
                            $msg = '<span class="xt-badge xt-badge-info">'.JText::_('COM_AUTOTWEET_COMPARAM_LOGLEVEL_ALL_3').'</span>';

                            break;
                        default:
                            $msg = JText::_('COM_AUTOTWEET_COMPARAM_LOGLEVEL_OFF_0');
                    }

                    if ($log_level) {
                        if ($logger->isFileMode()) {
                            $msg .= ' <i class="xticon fas fa-download"></i>';
                        }

                        if ($logger->isScreenMode()) {
                            $msg .= ' <i class="xticon fas fa-laptop"></i>';
                        }
                    }

                    echo '<p>'.JText::sprintf(
                        'COM_AUTOTWEET_TITLE_INFOS_LOGGING',
                        $msg
                    ).'</p>';

                    if ($logfile) {
                        ?>
					<p>
					<?php
                        echo JText::_('COM_AUTOTWEET_COMPARAM_LOGMODE_FILE').': '; ?>
					<a href="<?php

                        echo $logger->getLoggedUrl(); ?>"
						class="btn btn-mini btn-warning" target="_blank"> <i
						class="xticon fas fa-download"></i>
					</a></p>
					<?php
                    }

                    require_once JPATH_AUTOTWEET.'/views/cpanels/tmpl/links.php';

                    ?>
				</div>

			</div>

	</div>
</div>
