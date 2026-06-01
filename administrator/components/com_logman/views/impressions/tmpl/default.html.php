<?
/**
 * @package     TEXTman
 * @copyright   Copyright (C) 2017 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('KOOWA') or die; ?>

<?= helper('ui.load', array(
    'domain' => 'admin'
)); ?>

<? // Add template class to visually enclose the forms ?>
<script>document.documentElement.className += " k-frontend-ui";</script>

<!-- Wrapper -->
<div class="k-wrapper k-js-wrapper">

    <!-- Overview -->
    <div class="k-content-wrapper">

        <!-- Sidebar -->
        <?= import('default_sidebar.html'); ?>

        <!-- The content -->
        <div class="k-content k-js-content">

            <!-- Title when on mobile -->
            <ktml:toolbar type="titlebar" title="<?php echo ucfirst(JFactory::getApplication()->input->get('view')); ?>" mobile>

            <!-- Component wrapper -->
            <div class="k-component-wrapper">

                <!-- Component -->
                <form class="k-component k-js-component k-js-grid-controller" action="" method="get">
                    <!-- Scopebar -->
                    <?= import('default_scopebar.html'); ?>

                    <!-- Container -->
                    <div class="k-container">

                        <!-- Main information -->
                        <div class="k-container__main">

                            <fieldset class="k-form-block">
                                <div class="k-form-block__header">
                                    <?= translate('Top pages') ?>
                                </div>
                                <div class="k-form-block__content">
                                    <div class="k-table-container">
                                        <div class="k-table">
                                            <table class="k-js-fixed-table-header k-js-responsive-table">
                                                <thead>
                                                    <tr>
                                                        <th><?= translate('URL') ?></th>
                                                        <th width="5%">Views</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <? foreach ($articles as $article): ?>
                                                    <tr>
                                                        <td><?= helper('impression.impression', array('row' => $article)) ?></td>
                                                        <td><?= $article->total ?></td>
                                                    </tr>
                                                    <? endforeach ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                            </fieldset>


                            <fieldset class="k-form-block">
                                <div class="k-form-block__header">
                                    <?= translate('Top referrers') ?>
                                </div>
                                <div class="k-form-block__content">
                                    <div class="k-table-container">
                                        <div class="k-table">
                                            <table class="k-js-fixed-table-header k-js-responsive-table">
                                                <thead>
                                                <tr>
                                                    <th><?= translate('URL') ?></th>
                                                    <th width="5%">Views</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <? foreach ($referrers as $referrer): ?>
                                                    <tr>
                                                        <td><?= helper('impression.referrer', array('row' => $referrer)) ?></td>
                                                        <td><?= $referrer->total ?></td>
                                                    </tr>
                                                <? endforeach ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                            </fieldset>

                        </div><!-- .k-container__main -->

                        <!-- Other information -->
                        <div class="k-container__sub">

                            <fieldset class="k-form-block">

                                <div class="k-form-block__header">
                                    <?= translate('Statistics') ?>
                                </div>

                                <div class="k-form-block__content">
                                    <dl>
                                        <dt><?= translate('Views') ?>:</dt>
                                        <dd><?= $views ?></dd>
                                    </dl>

                                    <dl>
                                        <dt><?= translate('Visitors') ?>:</dt>
                                        <dd><?= $visitors ?></dd>
                                    </dl>

                                    <dl>
                                        <dt><?= translate('Views per visit') ?>:</dt>
                                        <dd><?= $views_per_visit ?></dd>
                                    </dl>
                                </div>

                            </fieldset>

                        </div>

                    </div><!-- .k-container -->

                </form><!-- .k-component -->

            </div><!-- .k-component-wrapper -->

        </div><!-- .k-content -->

    </div><!-- .k-content-wrapper -->

</div><!-- .k-wrapper -->
