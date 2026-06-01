<?php
/*------------------------------------------------------------------------
  Mail Catcher - Email logging extension for Joomla
  ------------------------------------------------------------------------
  @Author    Solidres Team
  @Website   https://www.solidres.com
  @Copyright Copyright (C) 2016 Solidres. All Rights Reserved.
  @License   GNU General Public License version 3, or later
------------------------------------------------------------------------*/
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('jquery.framework');
HTMLHelper::_('bootstrap.framework');
HTMLHelper::_('script', 'com_mailcatcher/assets/chart.min.js', ['relative' => true]);
HTMLHelper::_('stylesheet', 'com_mailcatcher/assets/main.min.css', ['relative' => true]);
?>
<div class="row-fluid row">
    <div class="span8 col-md-8">
        <div id="mc-main-chart">
            <div id="mc-canvas">
                <nav id="mc-stats-nav" class="navbar navbar-expand-lg navbar-light bg-light hub-navbar navbar-default my-3">
                    <?php echo MC_ISJ3 ? '<div class="navbar-inner">' : '' ?>
	                <?php if (MC_ISJ3) : ?>
                        <a class="btn btn-navbar" data-toggle="collapse" data-target="#mc-navbar">
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                        </a>
	                <?php elseif (MC_ISJ4): ?>
                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-target="#mc-navbar"
                                aria-controls="mc-navbar" aria-expanded="false" aria-label="Toggle navigation">
                            <span class="navbar-toggler-icon"></span>
                        </button>
	                <?php endif ?>
                    <div id="mc-navbar"
                            class="<?php echo MC_ISJ3 ? 'nav-collapse collapse navbar-responsive-collapse' : 'collapse navbar-collapse' ?>">
                        <ul class="mr-auto <?php echo MC_ISJ3 ? 'nav' : 'navbar-nav' ?>">
                            <li class="active nav-item">
                                <a class="nav-link" href="#" data-period="0">
									<?php echo JText::_('COM_MAILCATCHER_TODAY'); ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#" data-period="1">
									<?php echo JText::_('COM_MAILCATCHER_THIS_MONTH'); ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#" data-period="2">
									<?php echo JText::_('COM_MAILCATCHER_LAST_MONTH'); ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#" data-period="3">
									<?php echo JText::_('COM_MAILCATCHER_LAST_3_MONTHS'); ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#" data-period="4">
									<?php echo JText::_('COM_MAILCATCHER_LAST_6_MONTHS'); ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#" data-period="5">
									<?php echo JText::_('COM_MAILCATCHER_THIS_YEAR'); ?>
                                </a>
                            </li>
                        </ul>
                    </div>
	                <?php echo MC_ISJ3 ? '</div>' : '' ?><!-- /navbar-inner -->
                </nav>
                <div>
                    <canvas id="mc-chart" style="width: 100%; height: 350px; max-height: 350px"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="span4 col-md-4">
        <div id="mc-main-information">
            <table class="table table-bordered">
                <tbody>
                <tr>
                    <td colspan="2" class="center">
                        <img src="<?php echo JUri::root(true) . '/media/com_mailcatcher/assets/images/logo_mailcatcher.png'; ?>"
                             alt="Logo mailcatcher"/>
                    </td>
                </tr>
                <tr>
                    <th colspan="2" class="center">
						<?php echo JText::sprintf('COM_MAILCATCHER_VERSION_FORMAT', $this->manifest->get('version')); ?>
                    </th>
                </tr>
                <tr>
                    <th style="min-width: 110px;">
						<?php echo JText::_('COM_MAILCATCHER_TOTAL_SENT'); ?>
                    </th>
                    <td class="text-right">
						<?php echo $this->totalSent; ?>
                    </td>
                </tr>
                <tr>
                    <th style="min-width: 110px;">
						<?php echo JText::_('COM_MAILCATCHER_TOTAL_FAIL'); ?>
                    </th>
                    <td class="text-right">
						<?php echo $this->totalFail; ?>
                    </td>
                </tr>
                <tr>
                    <th style="min-width: 110px;">
						<?php echo JText::_('COM_MAILCATCHER_TODAY'); ?>
                    </th>
                    <td class="text-right">
						<?php echo $this->sentToday; ?>
                    </td>
                </tr>
                <tr>
                    <th style="min-width: 110px;">
						<?php echo JText::_('COM_MAILCATCHER_THIS_MONTH'); ?>
                    </th>
                    <td class="text-right">
						<?php echo $this->sentThisMonth; ?>
                    </td>
                </tr>
                <tr>
                    <th style="min-width: 110px;">
						<?php echo JText::_('COM_MAILCATCHER_AVERAGE_DAY'); ?>
                    </th>
                    <td class="text-right">
						<?php echo sprintf('%.2f', $this->averageDay); ?>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    jQuery(function ($) {
        // Chart.js
        var chart = new Chart(document.getElementById('mc-chart').getContext('2d'), {
            type: '<?php echo JComponentHelper::getParams('com_mailcatcher')->get('chartType', 'line'); ?>',
            data: {},
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    xAxes: [
                        {
                            ticks: {
                                beginAtZero: true,
                                min: 0,
                                maxTicksLimit: 20
                            }
                        }
                    ]
                }
            }
        });
        var drawChart = function () {
            $.ajax({
                url: '<?php echo JRoute::_('index.php?option=com_mailcatcher&task=dashboard.loadChartData', false); ?>',
                type: 'post',
                dataType: 'json',
                data: {
                    period: $('#mc-stats-nav .active>a').data('period'),
                    '<?php echo JSession::getFormToken(); ?>': 1
                },
                success: function (response) {
                    if (response.success) {
                        chart.data.labels = response.data.chartData.labels;
                        chart.data.datasets = response.data.chartData.datasets;
                        chart.update();
                    } else {
                        alert(response.message);
                    }
                }
            });
        };

        drawChart();

        $('#mc-stats-nav li>a').on('click', function (e) {
            e.preventDefault();
            $(this).parent().addClass('active').siblings().removeClass('active');
            drawChart();
        });
    });
</script>