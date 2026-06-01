<?php

/**
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see        https://www.extly.com
 */
defined('_JEXEC') || exit;

$this->loadHelper('select');

$config = \Joomla\CMS\Factory::getConfig();
$offset = $config->get('offset');

$browser = \Joomla\CMS\Factory::getApplication()->client->browser;

// IE (including Trident)
if ((11 === (int) $browser) || (17 === (int) $browser)) {
    \Joomla\CMS\Factory::getApplication()->enqueueMessage('You are navigating with Internet Explorer. The system is compatible and only tested on modern browsers: Edge, Chrome, Firefox, Opera or Safari.', 'error');
}

?>

<div ng-app="starter" class="extly ng-cloak">
	<div class="xt-body">

		<div class="xt-grid">
			<div class="xt-col-span-6">
<?php
            require_once '1-editor.php';
?>
			</div>
			<div class="xt-col-span-6">
<?php
            require_once '2-requests.php';
?>

			</div>
		</div>

	</div>
</div>
