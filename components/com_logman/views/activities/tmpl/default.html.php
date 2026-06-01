<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('KOOWA') or die;?>

<?= helper('ui.load', array('domain' => 'site')) ?>
<?= helper('behavior.logman') ?>
<?= helper('behavior.jquery') ?>
<?= helper('behavior.modal') ?>

<? // RSS feed ?>
<link href="<?=route('format=rss');?>" rel="alternate" type="application/rss+xml" title="RSS 2.0" />

<?= import('com://admin/logman.activities.export.html'); ?>

<? if (isset($next)): ?>
<script type="text/javascript">
kQuery(function($) {
    var layout_container = ".logman_activities_layout--default";

    var config = {
        "url": "<?= $next ?>",
        "button": $("#show-more")
    };

    var more = new Logman.More(config);

    more.bind("after.fetch", function (event, data) {
        var table_body = layout_container + " .koowa_table--activities tbody";
        var body = $(table_body, data.content);

        var rows = body.children("tr");

        if (rows.length) {
            rows.each(function (idx, row) {
                row = $(row);
                row.addClass('logman_more__row');

                if (row.attr('class').search("--header") !== -1) {
                    var dates = $(layout_container + " .logman_table_layout__item--header").text();

                    if (dates.search(row.text()) === -1) {
                        $(table_body).append(row);
                    }

                    return;
                }

                $(table_body).append(row);
                row.css("display", "table-row");
            });

            $('.logman_more__row').fadeIn('slow', function() {
                $(this).removeClass('logman_more__row');
            });

            if (!data.url) {
                more.button.hide();
            }
        }
    });
});
</script>
<? endif ?>

<? // Page Heading ?>
<? if ($params->get('show_page_heading')): ?>
    <h1 class="logman_page_heading">
        <?= escape($params->get('page_heading')); ?>
    </h1>
<? endif; ?>

<? if(count($activities)): ?>
    <? // Toolbar ?>
    <ktml:toolbar type="actionbar">

    <div class="logman_activities_layout logman_activities_layout--default">
        <?= import('list.html', array('activities' => $activities)) ?>
        <? if (parameters()->total > count($activities)): ?>
            <div class="logman_more p-3">
                <a id="show-more" data-loading="<?= escape(translate('Loading&hellip;')) ?>"
                   class="btn btn-outline-secondary logman_more__button <?= !isset($next) ? 'logman_more__button--disabled' : '' ?>">
                    <?= translate('Show more&hellip;') ?></a>
            </div>
        <? endif ?>
    </div>
<? else: ?>
    <div class="alert alert-info">
        <p><?= translate('There are no activities to stream') ?></p>
    </div>
<? endif ?>





