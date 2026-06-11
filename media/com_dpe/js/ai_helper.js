/**
 * @package    Com_Dpe
 * @author     Techjoomla
 * @copyright  Copyright (c) 2009-2026 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

function escapeHtml(text) {
    if (!text) return '';
    return text
        .toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Lightweight markdown parser for clean display of AI responses
function parseMarkdown(md) {
    if (!md) return '';
    var html = md;

    // Parse [CHART:type]...[/CHART]
    var chartIndex = 0;
    html = html.replace(/\[CHART:(pie|bar)\]([\s\S]*?)\[\/CHART\]/gi, function (match, type, jsonStr) {
        try {
            var cleanJson = jsonStr.replace(/```json|```/g, '').trim();
            var chartData = JSON.parse(cleanJson);
            var canvasId = 'ai-chart-' + Date.now() + '-' + (++chartIndex);

            var labelsAttr = encodeURIComponent(JSON.stringify(chartData.labels || []));
            var dataAttr = encodeURIComponent(JSON.stringify(chartData.data || []));
            var titleAttr = encodeURIComponent(chartData.title || '');

            return '<div class="ai-chart-container" style="margin: 15px 0; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column; align-items: center; justify-content: center;">' +
                '<h4 style="margin: 0 0 10px 0; font-size: 14px; font-weight: bold; color: #1e3a8a;">' + escapeHtml(chartData.title || '') + '</h4>' +
                '<div style="position: relative; width: 100%; max-width: 320px; height: 180px; display: flex; justify-content: center;">' +
                '<canvas class="ai-chart-canvas" id="' + canvasId + '" data-chart-type="' + type + '" data-chart-title="' + titleAttr + '" data-chart-labels="' + labelsAttr + '" data-chart-data="' + dataAttr + '" style="width: 100%; height: 180px;"></canvas>' +
                '</div>' +
                '</div>';
        } catch (e) {
            console.error('Failed to parse chart JSON', e, jsonStr);
            return '<div class="alert alert-warning" style="margin: 10px 0; font-size: 12px;">Failed to render chart.</div>';
        }
    });

    // Replace blockquotes
    html = html.replace(/^\>\s+(.+)$/gm, '<blockquote>$1</blockquote>');

    // Replace headers
    html = html.replace(/^### (.*$)/gim, '<h3>$1</h3>');
    html = html.replace(/^## (.*$)/gim, '<h2>$1</h2>');
    html = html.replace(/^# (.*$)/gim, '<h1>$1</h1>');

    // Replace bold/italic
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');

    // Replace lists (both - and * prefixes)
    html = html.replace(/^\s*[\-\*]\s+(.+)$/gm, '<li>$1</li>');

    // Group list elements
    html = html.replace(/(<li>.*<\/li>)/gms, '<ul>$1</ul>');

    // Replace double newlines with paragraphs
    html = html.replace(/\n\s*\n/g, '</p><p>');
    html = '<p>' + html + '</p>';

    // Clean empty tags
    html = html.replace(/<p><\/p>/g, '');
    html = html.replace(/<p>\s*<ul>/g, '<ul>');
    html = html.replace(/<\/ul>\s*<\/p>/g, '</ul>');

    return html;
}

function initAiCharts() {
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js is not loaded yet.');
        return;
    }
    jQuery('.ai-chart-canvas').each(function () {
        var $canvas = jQuery(this);
        if ($canvas.data('initialized')) {
            return;
        }
        $canvas.data('initialized', true);

        var type = $canvas.data('chart-type');
        var labels = JSON.parse(decodeURIComponent($canvas.data('chart-labels')));
        var data = JSON.parse(decodeURIComponent($canvas.data('chart-data')));
        var title = decodeURIComponent($canvas.data('chart-title'));

        var ctx = this.getContext('2d');
        var clrs = ["#4CB03B", "#F1CF0D", "#D84123", "#22b8f0", "#9c27b0", "#ff9800", "#009688", "#795548"];

        var config = {
            type: type === 'bar' ? 'bar' : 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    label: title,
                    backgroundColor: clrs.slice(0, labels.length),
                    data: data,
                    borderWidth: type === 'bar' ? 1 : 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: type !== 'bar',
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            font: { size: 11 }
                        }
                    },
                    tooltip: {
                        enabled: true,
                        callbacks: {
                            label: function (context) {
                                return ' ' + context.label + ': ' + context.raw;
                            }
                        }
                    }
                }
            }
        };

        if (type === 'bar') {
            config.options.scales = {
                y: {
                    beginAtZero: true,
                    ticks: { font: { size: 10 } }
                },
                x: {
                    ticks: { font: { size: 10 } }
                }
            };
        } else {
            config.options.cutout = '50%';
        }

        new Chart(ctx, config);
    });
}

// Automatically bind AI details page insights if panel exists
jQuery(document).ready(function ($) {
    var $panel = $('#ai-insights-panel');
    if ($panel.length === 0) {
        return;
    }

    var ucmItemId = $panel.data('item-id');
    var client = $panel.data('client');
    var loadingSteps = [
        "Analyzing UCM record data...",
        "Sanitizing and redacting personal info...",
        "Requesting Google Gemini AI insights...",
        "Structuring response details...",
        "Saving insights cache..."
    ];
    var stepInterval;

    // Load cached report on page load
    loadCachedReport();

    // Event handlers
    $('#ai-generate-btn').on('click', function () {
        generateReport();
    });

    $('#ai-regenerate-btn').on('click', function () {
        generateReport();
    });

    $('#ai-copy-btn').on('click', function () {
        var text = $('#ai-result-content').text();
        navigator.clipboard.writeText(text).then(function () {
            var originalText = $('#ai-copy-btn').text();
            $('#ai-copy-btn').text('✅ Copied!');
            setTimeout(function () {
                $('#ai-copy-btn').text(originalText);
            }, 2000);
        });
    });

    $('#ai-download-btn').on('click', function () {
        var btn = $(this);
        var originalText = btn.text();
        btn.text('⏳ Preparing PDF...');
        btn.prop('disabled', true);

        // 1. Trigger AJAX download tracking
        $.ajax({
            url: 'index.php?option=com_tjucm&task=item.trackDownload&id=' + ucmItemId + '&client=' + client,
            type: 'POST',
            dataType: 'json',
            success: function (response) {
                btn.text(originalText);
                btn.prop('disabled', false);

                // 2. Open printable window
                var reportContent = $('#ai-result-content').html();
                var reportTitle = 'AI Insights & Analysis Report';

                // Create a new window for printing
                var printWindow = window.open('', '_blank', 'width=900,height=800');
                printWindow.document.write('<ht' + 'ml><he' + 'ad><ti' + 'tle>' + reportTitle + '</ti' + 'tle>');

                // Add styling (split tags to avoid parser issues)
                printWindow.document.write('<st' + 'yle>');
                printWindow.document.write('body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; padding: 40px; color: #334155; line-height: 1.6; }');
                printWindow.document.write('h1, h2, h3, h4 { color: #1e3a8a; font-weight: bold; margin-top: 25px; margin-bottom: 12px; }');
                printWindow.document.write('h1 { border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; font-size: 24px; color: #1e3a8a; }');
                printWindow.document.write('h2 { font-size: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 4px; }');
                printWindow.document.write('h3 { font-size: 15px; }');
                printWindow.document.write('ul, ol { padding-left: 20px; margin-bottom: 15px; }');
                printWindow.document.write('li { margin-bottom: 6px; }');
                printWindow.document.write('.ai-chart-container { page-break-inside: avoid; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin: 20px 0; text-align: center; background: #ffffff; display: inline-flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; max-width: 450px; }');
                printWindow.document.write('blockquote { border-left: 4px solid #3b82f6; padding: 12px 18px; background: #f8fafc; margin: 15px 0; color: #475569; font-style: italic; }');
                printWindow.document.write('.meta-info { font-size: 12px; color: #64748b; margin-bottom: 30px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; }');
                printWindow.document.write('.disclaimer { font-size: 11px; color: #94a3b8; margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 12px; font-style: italic; }');
                printWindow.document.write('@media print { body { padding: 0; } .ai-chart-container { border: 1px solid #e2e8f0; } }');
                printWindow.document.write('</st' + 'yle>');
                printWindow.document.write('</he' + 'ad><bo' + 'dy>');
                printWindow.document.write('<h1>' + reportTitle + '</h1>');
                printWindow.document.write('<div class="meta-info">' + $('#ai-meta-info').text() + '</div>');
                printWindow.document.write('<div>' + reportContent + '</div>');
                printWindow.document.write('<div class="disclaimer">' + $('.ai-panel-footer div:last-child').text().trim() + '</div>');
                printWindow.document.write('</bo' + 'dy></ht' + 'ml>');

                printWindow.document.close();
                printWindow.focus();

                // Wait for assets/charts to finish rendering before printing
                setTimeout(function () {
                    // Re-draw any charts in the print window by copying the canvas content
                    var originalCanvases = $('#ai-result-content').find('canvas');
                    var printCanvases = printWindow.document.querySelectorAll('canvas');
                    originalCanvases.each(function (index, origCanvas) {
                        if (printCanvases[index]) {
                            var printCtx = printCanvases[index].getContext('2d');
                            printCanvases[index].width = origCanvas.width;
                            printCanvases[index].height = origCanvas.height;
                            printCtx.drawImage(origCanvas, 0, 0);
                        }
                    });
                    printWindow.print();
                }, 500);
            },
            error: function () {
                btn.text(originalText);
                btn.prop('disabled', false);
                alert('Failed to initialize download. Please try again.');
            }
        });
    });

    function loadCachedReport() {
        $.ajax({
            url: 'index.php?option=com_tjucm&task=item.getLatestReport&id=' + ucmItemId,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.success && response.report) {
                    renderReport(response.report, response.created_date, response.outdated);
                    $('#ai-insights-panel').slideDown();
                }
            }
        });
    }

    function generateReport() {
        var customPrompt = $('#ai-custom-prompt-input').val() || '';

        // Show panel, show loading, hide other states
        $('#ai-insights-panel').slideDown();
        $('#ai-loading').css('display', 'flex');
        $('#ai-result-content').hide();
        $('#ai-error').hide();
        $('#ai-outdated-warning').hide();
        $('#ai-copy-btn').hide();
        $('#ai-download-btn').hide();
        $('#ai-regenerate-btn').prop('disabled', true);
        $('#ai-generate-btn').prop('disabled', true).text('Generating...');

        // Animate step text
        var currentStep = 0;
        $('#ai-loading-step').text(loadingSteps[currentStep]);
        clearInterval(stepInterval);
        stepInterval = setInterval(function () {
            currentStep = (currentStep + 1) % loadingSteps.length;
            $('#ai-loading-step').text(loadingSteps[currentStep]);
        }, 2000);

        $.ajax({
            url: 'index.php?option=com_tjucm&task=item.generateAiReport&id=' + ucmItemId + '&client=' + client,
            type: 'POST',
            dataType: 'json',
            data: {
                custom_prompt: customPrompt
            },
            success: function (response) {
                clearInterval(stepInterval);
                $('#ai-loading').hide();
                $('#ai-regenerate-btn').prop('disabled', false);
                $('#ai-generate-btn').prop('disabled', false).html('Generate AI Insights');

                if (response.success && response.report) {
                    renderReport(response.report, response.created_date, false);
                } else {
                    showError(response.message || 'AI Generation failed. Please try again.');
                }
            },
            error: function (xhr, status, error) {
                clearInterval(stepInterval);
                $('#ai-loading').hide();
                $('#ai-regenerate-btn').prop('disabled', false);
                $('#ai-generate-btn').prop('disabled', false).html('Generate AI Insights');
                showError('Network error: ' + error);
            }
        });
    }

    function renderReport(markdownText, createdDate, isOutdated) {
        var html = parseMarkdown(markdownText);
        $('#ai-result-content').html(html).show();
        initAiCharts();
        $('#ai-copy-btn').show();
        $('#ai-download-btn').show();

        if (isOutdated) {
            $('#ai-outdated-warning').css('display', 'flex');
        } else {
            $('#ai-outdated-warning').hide();
        }

        // Format meta info date
        var formattedDate = new Date(createdDate).toLocaleString();
        $('#ai-meta-info').html('Generated on: <strong>' + formattedDate + '</strong>');
    }

    function showError(message) {
        $('#ai-error-message').text(message);
        $('#ai-error').css('display', 'flex');
        $('#ai-result-content').hide();
        $('#ai-copy-btn').hide();
        $('#ai-download-btn').hide();
    }
});
