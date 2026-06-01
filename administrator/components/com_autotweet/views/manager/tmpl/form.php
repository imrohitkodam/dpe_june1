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

JHtml::_('behavior.formvalidator');

$config = \Joomla\CMS\Factory::getConfig();
$offset = $config->get('offset');

?>

<div class="extly">
    <div class="xt-body">

        <form name="adminForm" id="adminForm" action="index.php" method="post"
            class="form form-horizontal cronjob-expression-form form-validate">
            <input type="hidden" name="option" value="com_autotweet" />
            <input type="hidden" name="view" value="managers" />
            <input type="hidden" name="task" value="" />
            <?php
                echo EHtml::renderRoutingTags();
            ?>

            <div class="xt-grid">

                <div class="xt-col-span-12">

                    <fieldset class="basic">

                        <legend>
                            <?php echo JText::_('COM_AUTOTWEET_TITLE_MANAGERS_EDIT'); ?>
                        </legend>

<?php

if (EXTLY_J3) {
    ?>
                        <ul class="xt-nav-tabs-joomla3 nav nav-tabs" id="managerTabs">
                            <li class="active"><a data-toggle="tab" href="#workinghours">
                                <i class="xticon fas fa-wrench"></i>
                                <?php echo JText::_('COM_AUTOTWEET_VIEW_MANAGERS_WORKINGHOURS_TITLE'); ?></a>
                            </li>
                            <li><a data-toggle="tab" href="#evergreenstrategy">
                                <i class="xticon fas fa-leaf"></i>
                                <?php echo JText::_('COM_AUTOTWEET_VIEW_MANAGERS_EVERGREENSTRAT_TITLE'); ?></a>
                            </li>
                            <li><a data-toggle="tab" href="#advancedopts">
                                <i class="xticon fas fa-cogs"></i>
                                <?php echo JText::_('COM_AUTOTWEET_VIEW_MANAGERS_ADVANCEDOPTS_TITLE'); ?></a>
                            </li>
                        </ul>
                        <?php
}

if (EXTLY_J4) {
    ?>
                        <ul class="xt-nav xt-nav-tabs xt-nav-tabs-joomla4 nav nav-tabs" id="managerTabs">
                            <li class="nav-item" role="presentation"><a class="nav-link active" id="workinghours-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#workinghours" type="button" role="tab" aria-controls="workinghours"
                                aria-selected="true">
                                <i class="xticon fas fa-wrench"></i>
                                <?php echo JText::_('COM_AUTOTWEET_VIEW_MANAGERS_WORKINGHOURS_TITLE'); ?></a>
                            </li>
                            <li class="nav-item" role="presentation"><a class="nav-link" id="evergreenstrategy-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#evergreenstrategy" type="button" role="tab"
                                aria-controls="evergreenstrategy">
                                <i class="xticon fas fa-leaf"></i>
                                <?php echo JText::_('COM_AUTOTWEET_VIEW_MANAGERS_EVERGREENSTRAT_TITLE'); ?></a>
                            </li>
                            <li class="nav-item" role="presentation"><a class="nav-link" id="advancedopts-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#advancedopts" type="button" role="tab" aria-controls="advancedopts">
                                <i class="xticon fas fa-cogs"></i>
                                <?php echo JText::_('COM_AUTOTWEET_VIEW_MANAGERS_ADVANCEDOPTS_TITLE'); ?></a>
                            </li>
                        </ul>
    <?php
}

?>
                        <div class="tab-content" id="managerTabsContent">
<?php
                            require_once '1-workinghours.php';
                            require_once '2-evergreenstrategy.php';
                            require_once '3-advancedopts.php';

?>
                        </div>
                    </fieldset>

                </div>

            </div>
        </form>
    </div>
</div>
