<?
/**
 * @package     TEXTman
 * @copyright   Copyright (C) 2016 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('KOOWA') or die;

$datetime = new DateTime('now', new DateTimeZone('UTC')) ?>

<div class="k-js-filters k-dynamic-content-holder">
    <div data-filter data-title="<?= translate('Date'); ?>"
         data-count="<?= (isset($start_date)) ? 1 : 0 ?>"
    >
        <div class="k-form-group">
            <label for="start_date"><?= translate('Start') ?></label>
            <?= helper('behavior.calendar', array(
                'name'    => 'start_date',
                'id'      => 'start_date',
                'value'   => $start_date,
                'format'  => '%Y-%m-%d',
                'filter'  => 'user_utc',
                'options' => array(
                    'endDate' => $datetime->format('Y-m-d H:i:s'),
                ),
            ))?>
        </div>
        <div class="k-form-group">
            <label for="end_date"><?= translate('End') ?></label>
            <?= helper('behavior.calendar', array(
                'name'    => 'end_date',
                'id'      => 'end_date',
                'value'   => $end_date,
                'format'  => '%Y-%m-%d',
                'filter'  => 'user_utc',
                'options' => array(
                    'endDate' => $datetime->format('Y-m-d H:i:s'),
                ),
            ))?>
        </div>
    </div>
</div>

<div class="k-js-filters k-dynamic-content-holder">
    <div data-filter data-title="<?= translate('Component'); ?>"
         data-count="<?= (isset($package)) ? 1 : 0 ?>"
    >
        <?= helper('listbox.impressions_packages', array('selected' => $package)) ?>
    </div>
</div>

<!-- Scopebar -->
<div class="k-scopebar k-js-scopebar">

    <!-- Scopebar filters -->
    <div class="k-scopebar__item k-scopebar__item--filters">

        <!-- Filters wrapper -->
        <div class="k-scopebar__filters-content">

            <!-- Filters -->
            <div class="k-scopebar__filters k-js-filter-container">

                <!-- Filter -->
                <div style="display: none;" class="k-scopebar__item--filter k-scopebar-dropdown k-js-filter-prototype k-js-dropdown">
                    <button type="button" class="k-scopebar-dropdown__button k-js-dropdown-button">
                        <span class="k-scopebar__item--filter__title k-js-dropdown-title"></span>
                        <span class="k-scopebar__item--filter__icon k-icon-chevron-bottom" aria-hidden="true"></span>
                        <span class="k-scopebar__item-label k-js-dropdown-label"></span>
                    </button>
                    <div class="k-scopebar-dropdown__body k-js-dropdown-body">
                        <div class="k-scopebar-dropdown__body__buttons">
                            <button type="button" class="k-button k-button--default k-js-clear-filter"><?= translate('Clear') ?></button>
                            <button type="button" class="k-button k-button--primary k-js-apply-filter"><?= translate('Apply filter') ?></button>
                        </div>
                    </div>
                </div>

            </div><!-- .k-scopebar__filters -->

        </div><!-- .k-scopebar__filters-content -->

    </div><!-- .k-scopebar__item--filters -->

</div><!-- .k-scopebar -->
