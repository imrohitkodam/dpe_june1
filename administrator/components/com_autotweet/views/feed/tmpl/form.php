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

$preview = null;

if ($this->item->id) {
    $url = $this->item->xtform->get('url');

    if (false !== filter_var($url, \FILTER_VALIDATE_URL)) {
        $previewResult = FeedLoaderHelper::getPreview($this->item);

        if ((isset($previewResult->preview)) && (count($previewResult->preview))) {
            $preview = $previewResult->preview[0];
        } else {
            ELog::showMessage('COM_AUTOTWEET_VIEW_FEEDS_PREVIEW_UNAVAILABLE', \Joomla\CMS\Log\Log::WARNING);
            ELog::showMessage('COM_AUTOTWEET_VIEW_FEED_PREVIEW_FREQWARN', \Joomla\CMS\Log\Log::INFO);
        }
    } else {
        ELog::showMessage('COM_AUTOTWEET_FEED_INVALID_URL', \Joomla\CMS\Log\Log::ERROR);
    }
}

?>

<div class="extly">
    <div class="xt-body">

        <?php

            if ($this->get('ajax_import')) {
                require_once JPATH_AUTOTWEET.'/views/feeds/tmpl/import_progress.php';
            }

            echo Extly::showInvalidFormAlert();

        ?>

        <form name="adminForm" id="adminForm" action="index.php" method="post" class="form form-horizontal form-validate">
            <input type="hidden" name="option" value="com_autotweet" />
            <input type="hidden" name="view" value="feeds" />
            <input type="hidden" name="task" value="" />
            <?php

                echo EHtml::renderRoutingTags();

            ?>

            <div class="xt-grid">

                <div class="xt-col-span-6">

                    <div id="feed_data">
                        <fieldset class="feed_data">

                            <legend>
                                <?php echo JText::_('COM_AUTOTWEET_VIEW_FEED_TITLE'); ?>
                            </legend>

<?php
if (EXTLY_J3) {
                ?>
                            <ul class="xt-nav-tabs-joomla3 nav nav-tabs" id="feedTabs">
                                <li class="active"><a data-toggle="tab" href="#feeddetails">
                                    <i class="xticon fas fa-wrench"></i>
                                    <?php echo JText::_('COM_AUTOTWEET_VIEW_FEED_TAB_FEED_DETAILS'); ?>
                                </a></li>
                                <li><a data-toggle="tab" href="#publishing">
                                    <i class="xticon far fa-arrow-alt-circle-up"></i>
                                    <?php echo JText::_('COM_AUTOTWEET_VIEW_FEED_TAB_PUBLISHING'); ?>
                                </a></li>
                                <li><a data-toggle="tab" href="#contentcreation">
                                    <i class="xticon far fa-file-alt"></i>
                                    <?php echo JText::_('COM_AUTOTWEET_VIEW_FEED_TAB_TXT_HANDLING'); ?>
                                </a></li>
                                <li><a data-toggle="tab" href="#filters">
                                    <i class="xticon fas fa-filter"></i>
                                    <?php echo JText::_('COM_AUTOTWEET_VIEW_FEED_TAB_FLTRS'); ?>
                                </a></li>
                            </ul>
                            <?php
            }

if (EXTLY_J4) {
    ?>
                            <ul class="xt-nav xt-nav-tabs xt-nav-tabs-joomla4 nav nav-tabs" id="feedTabs">
                                <li class="nav-item" role="presentation"><a class="nav-link active"
                                    id="feeddetails-tab" data-bs-toggle="tab"
                                    data-bs-target="#feeddetails" type="button" role="tab" aria-controls="feeddetails"
                                    aria-selected="true">
                                    <i class="xticon fas fa-wrench"></i>
                                    <?php echo JText::_('COM_AUTOTWEET_VIEW_FEED_TAB_FEED_DETAILS'); ?>
                                </a></li>
                                <li class="nav-item" role="presentation"><a class="nav-link"
                                    id="publishing-tab" data-bs-toggle="tab"
                                    data-bs-target="#publishing" type="button" role="tab" aria-controls="publishing">
                                    <i class="xticon far fa-arrow-alt-circle-up"></i>
                                    <?php echo JText::_('COM_AUTOTWEET_VIEW_FEED_TAB_PUBLISHING'); ?>
                                </a></li>
                                <li class="nav-item" role="presentation"><a class="nav-link"
                                    id="contentcreation-tab" data-bs-toggle="tab"
                                    data-bs-target="#contentcreation" type="button" role="tab"
                                    aria-controls="contentcreation">
                                    <i class="xticon far fa-file-alt"></i>
                                    <?php echo JText::_('COM_AUTOTWEET_VIEW_FEED_TAB_TXT_HANDLING'); ?>
                                </a></li>
                                <li class="nav-item" role="presentation"><a class="nav-link"
                                    id="filters-tab" data-bs-toggle="tab"
                                    data-bs-target="#filters" type="button" role="tab" aria-controls="filters">
                                    <i class="xticon fas fa-filter"></i>
                                    <?php echo JText::_('COM_AUTOTWEET_VIEW_FEED_TAB_FLTRS'); ?>
                                </a></li>
                            </ul>
    <?php
}
?>

                            <div class="tab-content" id="feedTabsContent">

                                <?php

                                require_once 'feed_feeddetails.php';
                                require_once 'feed_publishing.php';
                                require_once 'feed_contentcreation.php';
                                require_once 'feed_filters.php';

                                // Include_once 'feed_tagging.php';

                                ?>

                            </div>

                        </fieldset>
                    </div>

                </div>

                <div class="xt-col-span-6">
                    <?php

                            if ($preview) {
                                require_once 'feed_preview.php';
                            }

                    ?>
                </div>

            </div>
        </form>
    </div>
</div>
