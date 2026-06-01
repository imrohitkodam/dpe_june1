<?php
/**
 * @version     admin/views/default/tmpl/default.php 2021-12-12 zanardigit
 * @package     Watchful Client
 * @author      Watchful
 * @authorUrl   https://watchful.net
 * @copyright   Copyright (c) 2012-2023 Watchful
 * @license     GNU/GPL v3 or later
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;
defined('WATCHFULLI_PATH') or die;
?>
<h3><?php echo Text::_('COM_WATCHFULLI_AUTHENTICATION'); ?></h3>
<p>
	<?php echo Text::_('COM_WATCHFULLI_BEFORE_ADDSITE_FORM'); ?>
<form action="https://app.watchful.net/app/#/dashboard/" method="GET" target="_blank">
    <input type="hidden" name="name" value="<?php echo $this->sitename ?>">
    <input type="hidden" name="access_url" value="<?php echo Uri::root() ?>">
    <input type="hidden" name="secret_word" value="<?php echo $this->secret_key ?>">
    <input type="hidden" name="word_akeeba" value="<?php echo $this->akeeba_secret_key ?>">
    <input type="hidden" name="option" value="com_jmonitoring">
    <input type="hidden" name="task" value="save">
    <input type="hidden" name="controller" value="editsite">
    <input type="hidden" name="view" value="editsite">
    <input type="hidden" name="source" value="client">
    <input type="hidden" name="cms" value="joomla">
    <input style="<?php echo $this->style ?>" type="submit" value="<?php echo Text::_('COM_WATCHFULLI_ADDSITE') ?>"
           class="btn btn-primary">
</form>
</p>

<p>
	<?php echo Text::_('COM_WATCHFULLI_SECRET_KEY'); ?>:
    <input readonly="readonly" type="text" style="width:250px;" size="55" value="<?php echo $this->secret_key ?>"/>
</p>

<?php if (!empty($this->firewalls)): ?>
<div>
    <p><?php echo Text::_('COM_WATCHFULLI_WHITELIST_WATCHIP_INTRO'); ?></p>
    <a class="btn btn-primary" href="<?php echo JRoute::_('index.php?option=com_watchfulli&task=whitelist'); ?>" class="btn">
		<?php echo Text::_('COM_WATCHFULLI_WHITELIST_WATCHIP_BTN'); ?>
    </a>
</div>
<?php endif ?>

<?php if ($this->debug_mode && file_exists($this->log_file)): ?>
    <hr/>
    <h3>Debug info</h3>
    <pre>
        <?php echo file_get_contents($this->log_file); ?>
    </pre>
<?php endif ?>
