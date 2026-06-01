<?php
/**
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see        https://www.extly.com
 */
defined('_JEXEC') || exit;

if (!PERFECT_PUB_PRO) {
    echo '<p class="text-center">'.JText::_('COM_AUTOTWEET_UPDATE_TO_PERFECT_PUBLISHER_PRO_LABEL').'</p><p></p>';
}

?>
<h3>
	<?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_SYSTEMINFO_TITLE'); ?>
</h3>
<?php

if (is_array($this->sysinfo)) {
    ?>

<table class="table table-bordered table-condensed">
	<tbody>

		<tr class="<?php echo $this->sysinfo[InstallationInfoHelper::SYSINFO_PHP] ? 'info' : 'error'; ?>">
			<td>
				<a rel="tooltip" data-toggle="tooltip" data-original-title="<?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_SYSINFO_PHP_DESC'); ?>">
			<?php
            echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_SYSINFO_PHP'); ?>
				</a>
			</td>
			<td><?php
            echo $this->sysinfo[InstallationInfoHelper::SYSINFO_PHP] ? InstallationInfoHelper::SYSINFO_OK : InstallationInfoHelper::SYSINFO_FAIL; ?></td>
		</tr>

		<tr class="<?php echo $this->sysinfo[InstallationInfoHelper::SYSINFO_MYSQL] ? 'info' : 'error'; ?>">
			<td>
				<a rel="tooltip" data-toggle="tooltip" data-original-title="<?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_SYSINFO_MYSQL_DESC'); ?>">
			<?php
            echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_SYSINFO_MYSQL'); ?>
				</a>
			</td>
			<td><?php
            echo $this->sysinfo[InstallationInfoHelper::SYSINFO_MYSQL] ? InstallationInfoHelper::SYSINFO_OK : InstallationInfoHelper::SYSINFO_FAIL; ?></td>
		</tr>

		<tr class="<?php echo $this->sysinfo[InstallationInfoHelper::SYSINFO_UTF8MB4] ? 'info' : 'error'; ?>">
			<td>
				<a rel="tooltip" data-toggle="tooltip" data-original-title="<?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_SYSINFO_UTF8MB4_DESC'); ?>">
			<?php
            echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_SYSINFO_UTF8MB4'); ?>
				</a>
			</td>
			<td><?php
            echo $this->sysinfo[InstallationInfoHelper::SYSINFO_UTF8MB4] ? InstallationInfoHelper::SYSINFO_OK : InstallationInfoHelper::SYSINFO_FAIL; ?></td>
		</tr>

		<tr class="<?php echo $this->sysinfo[InstallationInfoHelper::SYSINFO_CURL] ? 'info' : 'error'; ?>">
			<td>
				<a rel="tooltip" data-toggle="tooltip" data-original-title="<?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_SYSINFO_CURL_DESC'); ?>">
			<?php
            echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_SYSINFO_CURL'); ?>
				</a>
			</td>
			<td><?php
            echo $this->sysinfo[InstallationInfoHelper::SYSINFO_CURL] ? InstallationInfoHelper::SYSINFO_OK : InstallationInfoHelper::SYSINFO_FAIL; ?></td>
		</tr>

		<tr class="<?php echo $this->sysinfo[InstallationInfoHelper::SYSINFO_SSL] ? 'info' : 'error'; ?>">
			<td>
				<a rel="tooltip" data-toggle="tooltip" data-original-title="<?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_SYSINFO_SSL_DESC'); ?>">
			<?php
            echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_SYSINFO_SSL'); ?>
				</a>
			</td>
			<td><?php
            echo $this->sysinfo[InstallationInfoHelper::SYSINFO_SSL] ? InstallationInfoHelper::SYSINFO_OK : InstallationInfoHelper::SYSINFO_FAIL; ?></td>
		</tr>

		<tr class="<?php echo $this->sysinfo[InstallationInfoHelper::SYSINFO_TIMESTAMP] < 2 ? 'info' : 'error'; ?>">
			<td>
				<a rel="tooltip" data-toggle="tooltip" data-original-title="<?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_SYSINFO_TIMESTAMP_DESC'); ?>">
			<?php
            echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_SYSINFO_TIMESTAMP'); ?>
				</a>
			</td>
			<td><?php
            echo $this->sysinfo[InstallationInfoHelper::SYSINFO_TIMESTAMP] < 2 ? InstallationInfoHelper::SYSINFO_OK : InstallationInfoHelper::SYSINFO_FAIL.' ('.$this->sysinfo[InstallationInfoHelper::SYSINFO_TIMESTAMP].')'; ?></td>
		</tr>

		<tr class="<?php echo $this->sysinfo[InstallationInfoHelper::SYSINFO_JSON] ? 'info' : 'error'; ?>">
			<td>
				<a rel="tooltip" data-toggle="tooltip" data-original-title="<?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_SYSINFO_JSON_DESC'); ?>">
			<?php
            echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_SYSINFO_JSON'); ?>
				</a>
			</td>
			<td><?php
            echo $this->sysinfo[InstallationInfoHelper::SYSINFO_JSON] ? InstallationInfoHelper::SYSINFO_OK : InstallationInfoHelper::SYSINFO_FAIL; ?></td>
		</tr>

		<tr class="<?php echo $this->sysinfo[InstallationInfoHelper::SYSINFO_HMAC] ? 'info' : 'error'; ?>">
			<td>
				<a rel="tooltip" data-toggle="tooltip" data-original-title="<?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_SYSINFO_HMAC_DESC'); ?>">
			<?php
            echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_SYSINFO_HMAC'); ?>
				</a>
			</td>
			<td><?php
            echo $this->sysinfo[InstallationInfoHelper::SYSINFO_HMAC] ? InstallationInfoHelper::SYSINFO_OK : InstallationInfoHelper::SYSINFO_FAIL; ?></td>
		</tr>

		<?php
            if (defined('PERFECT_PUB_PRO')) {
                ?>
		<tr class="<?php echo $this->sysinfo[InstallationInfoHelper::SYSINFO_TIDY] ? 'info' : 'error'; ?>">
			<td>
				<a rel="tooltip" data-toggle="tooltip" data-original-title="<?php echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_SYSINFO_TIDY_DESC'); ?>">
			<?php
            echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_SYSINFO_TIDY'); ?>
				</a>
			</td>
			<td><?php
            echo $this->sysinfo[InstallationInfoHelper::SYSINFO_TIDY] ? InstallationInfoHelper::SYSINFO_OK : InstallationInfoHelper::SYSINFO_FAIL; ?></td>
		</tr>
		<?php
            } ?>

	</tbody>
</table>

<?php
} else {
                echo '<p class="text-error">'.$this->sysinfo.'</p>';
            }
