<?php
/**
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see        https://www.extly.com
 */
defined('_JEXEC') || exit;

use XTP_BUILD\Extly\Infrastructure\Service\Cms\Joomla\ScriptHelper;

$this->loadHelper('select');

$urlBase = \Joomla\CMS\Uri\Uri::root();
$isBackend = XTF0FPlatform::getInstance()->isBackend();

$composerLink = JRoute::_('index.php?option=com_autotweet&view=composer');
$postsLink = JRoute::_('index.php?option=com_autotweet&view=posts');
$requestsLink = JRoute::_('index.php?option=com_autotweet&view=requests');
$evergreensLink = JRoute::_('index.php?option=com_autotweet&view=evergreens');
$channelsLink = JRoute::_('index.php?option=com_autotweet&view=channels');
$rulesLink = JRoute::_('index.php?option=com_autotweet&view=rules');
$feedsLink = JRoute::_('index.php?option=com_autotweet&view=feeds');

if (((int) $this->data->get('requests')) || ((int) $this->data->get('p_success'))) {
    $requestsData = [
        (object) ['label' => JText::_('COM_AUTOTWEET_TITLE_REQUESTS'),
            'value' => (int) $this->data->get('requests'), ],
        (object) ['label' => JText::_('COM_AUTOTWEET_TITLE_POSTS'),
            'value' => (int) $this->data->get('posts'), ],
    ];
    ScriptHelper::addScriptDeclaration('window.requestsData = '.json_encode($requestsData).';');

    $postsData = [
        (object) ['label' => SelectControlHelper::getTextForEnum('success'),
            'value' => (int) $this->data->get('p_success'), ],
        (object) ['label' => SelectControlHelper::getTextForEnum('cronjob'),
            'value' => (int) $this->data->get('cronjob'), ],
        (object) ['label' => SelectControlHelper::getTextForEnum('approve'),
            'value' => (int) $this->data->get('p_approve'), ],
        (object) ['label' => SelectControlHelper::getTextForEnum('cancelled'),
            'value' => (int) $this->data->get('p_cancelled'), ],
        (object) ['label' => SelectControlHelper::getTextForEnum('error'),
            'value' => (int) $this->data->get('p_error'), ],
    ];
    ScriptHelper::addScriptDeclaration('window.postsData = '.json_encode($postsData).';');

    $timelineData = $this->data->get('timeline');
    ScriptHelper::addScriptDeclaration('window.timelineData = '.json_encode($timelineData).';');
}

?>
<div class="extly dashboard">
    <div class="xt-body">

            <div class="xt-grid">
                <div class="xt-col-span-8">

<?php
                if ($this->get('version_check')) {
                    ?>
                    <form name="adminForm" id="adminForm" action="index.php" method="post">
                        <input type="hidden" name="option" id="option" value="com_autotweet" />
                        <input type="hidden" name="view" id="view" value="cpanels" />
                        <input type="hidden" name="task" id="task" value="no-task" />
                        <?php

                            echo EHtml::renderRoutingTags(); ?>

                        <span class="loaderspinner72">
                            <?php echo JText::_('COM_AUTOTWEET_LOADING'); ?>
                        </span>
                        <div id="updateNotice">
                        </div>

                    </form>
<?php
                }
?>

                    <h2>
                        <?php echo JText::_('COM_AUTOTWEET_ICON_CPANELS'); ?>
                        <?php echo JText::_('COM_AUTOTWEET_JOOCIAL_METER'); ?>
                    </h2>

                    <div class="xt-grid">
                        <div class="xt-col-span-12">
                            <h3>
                                <?php echo JText::_('COM_AUTOTWEET_ICON_POSTS'); ?>
                                <?php echo JText::_('COM_AUTOTWEET_TITLE_POSTS_TIMELINE'); ?>
                            </h3>
                            <div id="posts-timeline">
                                <svg style="width:100%; height:300px">
                            </div>
                        </div>
                    </div>

                    <div class="xt-grid">
                        <div class="xt-col-span-6">
                            <h3>
                                <?php echo JText::_('COM_AUTOTWEET_ICON_REQUESTS'); ?>
                                <?php echo JText::_('COM_AUTOTWEET_TITLE_PROCESSED_REQUESTS'); ?>
                            </h3>
                            <div id="requests-chart">
                                <svg style="width:100%; height:175px">
                            </div>
                        </div>
                        <div class="xt-col-span-6" style="margin-left: 0px;">
                            <h3>
                                <?php echo JText::_('COM_AUTOTWEET_ICON_POSTS'); ?>
                                <?php echo JText::_('COM_AUTOTWEET_TITLE_PROCESSED_POSTS'); ?>
                            </h3>
                            <div id="posts-chart">
                                <svg style="width:100%; height:175px">
                            </div>
                        </div>
                    </div>

                    <hr/>

                    <?php

                    if ($isBackend) {
                        ?>

                    <h2>
                        <?php echo JText::_('COM_AUTOTWEET_SHORTCUTS'); ?>
                    </h2>
                    <p>
                        <a href="<?php echo $composerLink; ?>" class="btn btn-large">
                            <?php echo JText::_('COM_AUTOTWEET_ICON_COMPOSER'); ?>
                            <?php echo JText::_('COM_AUTOTWEET_TITLE_COMPOSERS'); ?>
                        </a>
                        <a href="<?php echo $postsLink; ?>" class="btn btn-large">
                            <?php echo JText::_('COM_AUTOTWEET_ICON_POSTS'); ?>
                            <?php echo JText::_('COM_AUTOTWEET_TITLE_POSTS'); ?>
                        </a>
                        <a href="<?php echo $requestsLink; ?>" class="btn btn-large">
                            <?php echo JText::_('COM_AUTOTWEET_ICON_REQUESTS'); ?>
                            <?php echo JText::_('COM_AUTOTWEET_TITLE_REQUESTS'); ?>
                        </a>
<?php
                        if (PERFECT_PUB_PRO) {
                            ?>
                        <a href="<?php echo $evergreensLink; ?>" class="btn btn-large">
                            <?php echo JText::_('COM_AUTOTWEET_ICON_EVERGREENS'); ?>
                            <?php echo JText::_('COM_AUTOTWEET_TITLE_EVERGREENS'); ?>
                        </a>
<?php
                        } else {
                            ?>
                        <a href='https://www.extly.com/perfect-publisher.html' target='_blank' class="btn btn-large disabled">
                            <?php echo JText::_('COM_AUTOTWEET_ICON_EVERGREENS'); ?>
                            <?php echo JText::_('COM_AUTOTWEET_TITLE_EVERGREENS'); ?>
                        </a>
<?php
                        } ?>
                        <a href="<?php echo $channelsLink; ?>" class="btn btn-large">
                            <?php echo JText::_('COM_AUTOTWEET_ICON_CHANNELS'); ?>
                            <?php echo JText::_('COM_AUTOTWEET_TITLE_CHANNELS'); ?>
                        </a>
                        <a href="<?php echo $rulesLink; ?>" class="btn btn-large">
                            <?php echo JText::_('COM_AUTOTWEET_ICON_RULES'); ?>
                            <?php echo JText::_('COM_AUTOTWEET_TITLE_RULES'); ?>
                        </a>
                        <a href="<?php echo $feedsLink; ?>" class="btn btn-large">
                            <?php echo JText::_('COM_AUTOTWEET_ICON_FEEDS'); ?>
                            <?php echo JText::_('COM_AUTOTWEET_TITLE_FEEDS'); ?>
                        </a>
                    </p>

                    <?php
                    }

                    ?>
                </div>
                <div class="xt-col-span-4">

                    <?php

                    if ($isBackend) {
                        if (PERFECT_PUB_PRO) {
                            $manager = EExtensionHelper::getExtensionId('system', 'autotweetautomator');

                            $url = 'index.php?option=com_autotweet&view=managers&task=edit&id='.$manager;
                            $url = JRoute::_($url);

                            echo '<p class="xt-text-right lead"><i class="xticon far fa-user"></i> <a class="btn btn-primary" href="'.$url.'">'.
                                JText::_('COM_AUTOTWEET_VIEW_ABOUT_VIRTUALMANAGER_TITLE')
                                .'</a></p><p class="xt-text-right">';

                            if (VirtualManager::isWorking()) {
                                echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_VIRTUALMANAGER_WORKING');
                                echo ' <i class="xticon fas fa-sun"></i>';
                            } else {
                                echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_VIRTUALMANAGER_RESTING');
                                echo ' <i class="xticon far fa-moon"></i>';
                            }

                            echo '</p>';
                        } else {
                            echo '<p class="xt-text-right lead"><i class="xticon far fa-user muted"></i> <a class="btn disabled" href="https://www.extly.com/perfect-publisher.html" target="_blank">'.
                                    JText::_('COM_AUTOTWEET_VIEW_ABOUT_VIRTUALMANAGER_TITLE')
                                    .'</a></p><p class="xt-text-right muted">';
                            echo JText::_('COM_AUTOTWEET_VIEW_ABOUT_VIRTUALMANAGER_RESTING');
                            echo ' <i class="xticon far fa-moon"></i>';
                            echo '</p>';

                            echo '<p></p><p class="text-center">'.JText::_('COM_AUTOTWEET_UPDATE_TO_PERFECT_PUBLISHER_PRO_LABEL').'</p>';
                        }

                        include 'links.php';
                    }

                    ?>
                </div>
            </div>

    </div>
<?php
    // require_once 'style.php';
?>
</div>
