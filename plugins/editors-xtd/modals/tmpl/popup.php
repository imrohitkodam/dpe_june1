<?php
/**
 * @package         Modals
 * @version         12.3.5PRO
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://regularlabs.com
 * @copyright       Copyright © 2023 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

use Joomla\CMS\Editor\Editor as JEditor;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Form\Form as JForm;
use Joomla\CMS\HTML\HTMLHelper as JHtml;
use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\Plugin\PluginHelper as JPluginHelper;

$xmlfile = dirname(__FILE__, 2) . '/forms/popup.xml';

$form = new JForm('modals');
$form->loadFile($xmlfile, 1, '//config');

$editor_plugin = JPluginHelper::getPlugin('editors', 'codemirror');

if (empty($editor_plugin))
{
    JFactory::getApplication()->enqueueMessage(JText::sprintf('MDL_ERROR_CODEMIRROR_DISABLED', '<a href="index.php?option=com_plugins&filter[folder]=editors&filter[search]=codemirror" target="_blank">', '</a>'), 'error');

    return '';
}

$user   = JFactory::getApplication()->getIdentity() ?: JFactory::getUser();
$editor = JEditor::getInstance('codemirror');
?>

<div class="container-fluid container-main">
    <form action="index.php" id="modalsForm" method="post" style="width:99%">
        <?php echo $form->renderFieldset('text'); ?>

        <?php echo JHtml::_('uitab.startTabSet', 'main', ['active' => 'items']); ?>

        <?php
        $tabs = [
            'main'     => 'RL_CONTENT',
            'styling'   => 'RL_STYLING',
            'settings'  => 'RL_OTHER_SETTINGS',
        ];

        foreach ($tabs as $id => $title)
        {
            echo JHtml::_('uitab.addTab', 'main', $id, JText::_($title));
            echo $form->renderFieldset($id);
            echo JHtml::_('uitab.endTab');
        }
        ?>

        <?php echo JHtml::_('uitab.endTabSet'); ?>
    </form>
</div>
