// --- Global Data Variables ---
// CACHE BUST: Fixed Y-axis sequential integers and multiple chart data structure - v7.0
var reportChartData;
var reportChartDataTag;
var mainChart;
let globalCumulativeData = null;   
var checkedLogIds;  // Holds ApexCharts instance for #main
let tagCharts = [];
let tagChartBoxes = [];
// --- GLOBAL APEXCHARTS TOOLTIP MARKER MAPPING ---
const TOOLTIP_MARKER_MAP = {
    'SAR Open Logs': { color: '#28a745', symbol: '&#9679;' }, // Circle
    'SAR Closed Logs': { color: '#28a745', symbol: '&#9632;' }, // Square
    'Breach Open Logs': { color: '#dc3545', symbol: '&#9679;' }, // Circle
    'Breach Closed Logs': { color: '#dc3545', symbol: '&#9632;' }, // Square
    'FOI Open Logs': { color: '#007bff', symbol: '&#9679;' }, // Circle
    'FOI Closed Logs': { color: '#007bff', symbol: '&#9632;' }, // Square
    'Complaint Open Logs': { color: '#747676', symbol: '&#9679;' }, // Circle
    'Complaint Closed Logs': { color: '#747676', symbol: '&#9632;' } // Square
};

function formatSmartDate(dateStr) {
    if (!dateStr) return dateStr;

    const parts = dateStr.split("-").map(p => parseInt(p, 10));

    const year  = parts[0];
    const month = parts[1];
    const day   = parts[2];

    // Case 1: Only year & month → output M-YYYY
    if (parts.length === 2 || (parts.length === 3 && isNaN(day))) {
        return month + "-" + year;
    }

    // Case 2: Day exists → output D-M-YYYY
    if (parts.length === 3) {
        return day + "-" + month + "-" + year;
    }

    return dateStr;
}

// --- CUSTOM TOOLTIP BUILDER FUNCTION ---
function buildCustomTooltip(series, seriesIndex, dataPointIndex, w) {
    let month = '';
    
    // Try to get the date from globalCumulativeData if available (single chart mode)
    if (globalCumulativeData && typeof globalCumulativeData === 'object') {
        try {
            const firstKey = Object.keys(globalCumulativeData)[0];
            const sampleArray = globalCumulativeData[firstKey] || [];
            const dataObj = sampleArray[dataPointIndex];
            if (dataObj && dataObj.x) {
                month = formatSmartDate(dataObj.x);
            }
        } catch (e) {
            console.warn('Error accessing globalCumulativeData:', e);
        }
    }
    
    // Fallback: use the category label from the chart
    if (!month && w.globals.labels && w.globals.labels[dataPointIndex]) {
        month = formatSmartDate(w.globals.labels[dataPointIndex]);
    }

    let html = '<div class="apexcharts-tooltip-custom">';
    
    // 1. Add Date Header
    if (month) {
        html += '<div style="padding: 5px 10px; font-weight: bold; border-bottom: 1px solid #ddd; margin-bottom: 5px;">Date: ' + month + '</div>';
    }
    
    // 2. Add Series Data (with colored circle/square markers)
    html += '<div style="padding: 0 8px 8px;">';
    
    w.globals.series.forEach((seriesValues, i) => {
        const seriesName = w.globals.seriesNames[i];
        const value = seriesValues[dataPointIndex];
        
        // Only show series that have a non-zero value at this point
        if (value === null || typeof value === 'undefined' || Math.round(value) === 0) {
            return;
        }

        const markerInfo = TOOLTIP_MARKER_MAP[seriesName] || { color: '#000', symbol: '&#9679;' };

        // Custom marker HTML using Unicode symbol
        const markerHtml = `
            <span style="
                display: inline-block; 
                width: 12px; height: 12px; 
                line-height: 12px;
                text-align: center;
                color: ${markerInfo.color}; 
                font-size: 10px; 
                margin-right: 8px;
            ">
                ${markerInfo.symbol}
            </span>`;
            
        // Construct the row for the series
        html += `
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                <div style="display: flex; align-items: center;">
                    ${markerHtml}
                    <span style="font-size: 13px; color: #333; white-space: nowrap;">${seriesName}:</span>
                </div>
                <span style="font-size: 13px; font-weight: bold; margin-left: 15px;">${Math.round(value)}</span>
            </div>
        `;
    });
    
    html += '</div></div>';
    return html;
}
// --- JQUERY READY BLOCK ---
jQuery(document).ready(function(){
    jQuery('#filter_tags').on('change', function() {
        jQuery("#jform_cluster_id").val("").trigger("chosen:updated");
    });
    
    jQuery('#jform_cluster_id').on('change', function() {
        jQuery("#filter_tags").val("").trigger("chosen:updated");
    });
    
    // The PHP view calls showReport() on button click, but this is a safeguard for form submission
    jQuery('#reportForm').on('submit', showReport);
});

// --- HELPER FUNCTIONS ---
function messageDisplay(msg, type){
    jQuery('<div id="system-message-container"></div>').empty().appendTo('#system-message-container');
    Joomla.renderMessages({[type] : [msg]}); 
    jQuery('html, body').animate({ scrollTop: 0 }, 'slow');
    setTimeout(function() {
        jQuery('joomla-alert').fadeOut('slow', function() {
            jQuery(this).remove();
        });
    }, 10000); 
}

function parseDate(str) {
    if (!str) return null;
    // Assuming date format is DD-MM-YYYY from form input
    const parts = str.split('-');
    if (parts.length !== 3) return null;
    const [dd, mm, yyyy] = parts;
    // Correctly parse date string for cross-browser compatibility
    return new Date(`${yyyy}-${mm}-${dd}T00:00:00`); 
}

// ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// CORE: DATA TRANSFORMATION FUNCTION (CUMULATIVE CALCULATION)
// ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
function transformDataToTimeline(rawData) {
    const LOG_TYPES = ['sarlog', 'breachlog', 'foilog','dpcomplaintslog'];
    const monthlyEvents = {}; 

    // Parse start and end dates from form
    var dateRange = jQuery('#jform_date_range').val();
    let startDateInput;
    let endDateInput;
    let startDate;
    let endDate;
    if (dateRange && dateRange.length > 0) {
        ({ startDateInput, endDateInput, startDate, endDate } = getStartEndDateByDateRange(parseInt(dateRange)));
    } else {
        startDateInput = jQuery('#jform_start_date').val();
        endDateInput = jQuery('#jform_end_date').val();
        startDate = parseDate(startDateInput); // exact start date
        endDate = endDateInput ? parseDate(endDateInput) : new Date(); // today if end date missing
    }

    if (!startDate) {
        console.warn('Start date is invalid, using earliest available date from data.');
    }

    // 1. Collect all dates and events into a single map
    LOG_TYPES.forEach(type => {
        if (rawData[type] && Array.isArray(rawData[type])) {
            rawData[type].forEach(item => {
                if (!item || typeof item !== 'object' || !item.report_month && !item.report_day) return;

                let dateKey;
                if (item.report_day) {
                    dateKey = item.report_day;            // daily key
                } else if (item.report_month) {
                    dateKey = item.report_month;          // monthly key
                } else {
                    return;
                }

                if (!monthlyEvents[dateKey]) monthlyEvents[dateKey] = {};

                monthlyEvents[dateKey][type] = {
                    new: parseInt(item.New_logs_created_during_the_reporting_period) || 0,
                    closed: parseInt(item.Number_Of_logs_closed_during_the_period) || 0
                };
            });
        }
    });

    // 2. Sort dates
    const sortedDates = Object.keys(monthlyEvents).sort();
    const timelineData = {};
    let runningTotals = {};

    // Initialize
    LOG_TYPES.forEach(type => {
        runningTotals[type + '_open'] = 0;
        runningTotals[type + '_closed'] = 0;
        timelineData[type + '_open'] = [];
        timelineData[type + '_closed'] = [];
    });

    // 3. Fill missing start and end dates if no data exists exactly on those dates
    if (endDate && !sortedDates.includes(endDate.toISOString().slice(0,10))) {
        sortedDates.push(endDate.toISOString().slice(0,10));
    }

    // 4. Calculate cumulative totals
    sortedDates.forEach(date => {
        LOG_TYPES.forEach(type => {
            const events = monthlyEvents[date] ? monthlyEvents[date][type] : null;

            if (events) {
                runningTotals[type + '_open'] += events.new - events.closed;
                runningTotals[type + '_closed'] += events.closed;
            }
            
            timelineData[type + '_open'].push({ x: date, y: runningTotals[type + '_open'] });
            timelineData[type + '_closed'].push({ x: date, y: runningTotals[type + '_closed'] });
        });
    });

    return timelineData;
}

function getStartEndDateByDateRange(dateRange) {
      var today = new Date(); today.setHours(0,0,0,0);
    let endDate = new Date(today); // current date

    let startDate = new Date(today);
    startDate.setMonth(startDate.getMonth() - dateRange); // subtract months safely

    // Format to DD-MM-YYYY
    let formatDate = (d) => {
        let day = String(d.getDate()).padStart(2, '0');
        let month = String(d.getMonth() + 1).padStart(2, '0');
        let year = d.getFullYear();
        return `${day}-${month}-${year}`;
    };

    let startDateStr = formatDate(startDate);
    let endDateStr = formatDate(endDate);

    // Return all four values
    return { startDateStr, endDateStr, startDate, endDate };
}


// --- AJAX CALL FUNCTION ---
function showReport(e) {
    if (e) e.preventDefault();

    let organistionList = jQuery('#jform_cluster_id').val();
    let tags = jQuery('#filter_tags').val();
    const isTagReport = tags && tags.length > 0;

    if ((!organistionList || organistionList.length === 0) && !isTagReport) {
        messageDisplay('Please select at least one organization or tag.', 'warning');
        return;
    }

    let ajaxurl = isTagReport 
    ? Joomla.getOptions('system.paths').base + "/index.php?option=com_dpe&task=matviewreport.getMatLogReportDataForTag"
    : Joomla.getOptions('system.paths').base + "/index.php?option=com_dpe&task=matviewreport.getMatLogReportData";

    var today = new Date(); today.setHours(0,0,0,0);
    var endDate;
    var startDate;
    var startDateStr;
    var endDateStr;
    var dateRange = jQuery('#jform_date_range').val();
    if (dateRange && dateRange.length > 0) {
        ({ startDateStr, endDateStr, startDate, endDate } = getStartEndDateByDateRange(parseInt(dateRange)));
    } else {
        startDateStr = jQuery('#jform_start_date').val();
        endDateStr = jQuery('#jform_end_date').val();
        startDate = parseDate(startDateStr);
        endDate = parseDate(endDateStr);
    }

    if ((!dateRange || dateRange.length === 0) && ((!startDateStr && !endDateStr) || (endDateStr == 'Invalid Date'))) {
        messageDisplay('Please select a date range, or specify both a start date and an end date to generate the report.', 'warning'); 
        return false;
    }
    // if (!startDateStr) { messageDisplay("The start date is empty.", "warning"); return; }
    if (startDate && startDate > today) { messageDisplay("Start date cannot be in future.", "warning"); return; }
    if (endDate && endDate > today) { messageDisplay("End date cannot be in future.", "warning"); return; }
    if (startDate && endDate && endDate < startDate) { messageDisplay("End date cannot be before start date.", "warning"); return; }

 const checkedLogs = [];

    $('#tagaverages input[type="checkbox"]:checked').each(function () {
        const logId = $(this).attr('id');               // e.g., "Breach_Log_"
        const logKey = $(this).closest('.log-row').data('log-key'); // e.g., "Breach Log"
        checkedLogs.push({ id: logId, key: logKey });
    });
    // Example: If you just need their IDs as array
    const checkedIds = checkedLogIds = checkedLogs.map(l => l.id);

  const logMapping = {
    'Breach_Log_': 'breachlog',
    'SAR___Data_Rights_Log': 'sarlog',
    'FOI___EIR_Log': 'foilog',
    'DP_Complaints_Log': 'dpcomplaintslog'
};

// Map checkedIds to log types
const mappedLogs = checkedIds.map(id => logMapping[id]).filter(Boolean);

// Now send in AJAX
let formData = jQuery('#reportForm').serializeArray(); // convert form to array

// Add mapped logs to formData
mappedLogs.forEach(logType => {
    formData.push({ name: 'checked_logs[]', value: logType });
});

    document.getElementById('loader-overlay').style.display = 'block';

    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: formData,
        success: function (response) {
            try {
                // Attempt to parse response if it's a string
                response = typeof response === "string" ? JSON.parse(response) : response;
                // Ensure we access the correct data property
                const data = response.data || response;
                if (!data) throw new Error('Invalid response data structure');

                if (isTagReport) {
                    // This handles the multiple charts case (if multiple tags are selected)
                    // Clear the single chart canvas first
                    if (mainChart) {
                        try {
                            mainChart.destroy();
                            mainChart = null;
                        } catch (e) {
                            console.warn('Error destroying main chart:', e);
                        }
                    }
                    const mainElement = document.getElementById("main");
                    if (mainElement) {
                        mainElement.innerHTML = '';
                        mainElement.style.display = 'none';
                    }
                    document.getElementById("chartsContainer").style.display = 'flex';
                    var orgName = data.orgname;
                    logCounts = data.Average_lifecycle_duration_initiation_to_resolution;
                    showAverageTime(logCounts,'tagaverages');
                    renderAllChartsChartJS(data.log_reports, startDateStr, endDateStr,orgName);

                } else {
                    // This handles the single chart case (cluster selected)
                    // Destroy all tag charts first
                    tagCharts.forEach(chart => {
                        if (chart) {
                            try {
                                chart.destroy();
                            } catch (e) {
                                console.warn('Error destroying tag chart:', e);
                            }
                        }
                    });
                    tagCharts = [];
                    tagChartBoxes = [];
                    
                    const chartsContainer = document.getElementById("chartsContainer");
                    if (chartsContainer) {
                        chartsContainer.innerHTML = "";
                        chartsContainer.style.display = 'none';
                    }
                    document.getElementById("main").style.display = 'block';
                    logCounts = data["Average_lifecycle_duration_initiation_to_resolution_(days)"];
                    showAverageTime(logCounts,'tagaverages');
                    drawChartChartJS(data, startDateStr, endDateStr);
                }

                // Show the Download PDF button after report is generated
                const downloadBtn = document.getElementById('downloadPdfBtn');
                if (downloadBtn) {
                    downloadBtn.style.display = 'inline-block';
                }

            } catch (err) {
                console.error("Parsing error:", err);
                messageDisplay('Invalid server response.', 'error');
            } finally {
                document.getElementById('loader-overlay').style.display = 'none';
            }
        },
        error: function (xhr, status, error) {
            console.error('Error:', error);
            document.getElementById('loader-overlay').style.display = 'none';
            messageDisplay('Error generating report.', 'error');
        }
    });
}

function getDateRange()
{
   jQuery('#jform_start_date').val('');
   jQuery('#jform_end_date').val('');

}
function resetDateRange()
{
   jQuery('#jform_date_range').val('').trigger("chosen:updated");
}

// Converts DD-MM-YYYY to YYYY-MM-01 format for chart X-axis
function getMonthKeyFromInput(dateStr) {
    if (!dateStr) return null;
    const parts = dateStr.split('-');
    if (parts.length !== 3) return null;
    const [dd, mm, yyyy] = parts;
    return `${yyyy}-${mm}-01`;
}
// --- DRAW SINGLE CLUSTER CHART USING APEXCHARTS ---
function drawChartChartJS(data, startDateStr, endDateStr) {
    // Check if ApexCharts is loaded
    if (typeof ApexCharts === 'undefined') {
        console.error('ApexCharts library is not loaded!');
        messageDisplay('Chart library not loaded. Please refresh the page.', 'error');
        return;
    }

    // 1. TRANSFORM RAW DATA to cumulative totals
    const cumulativeData = transformDataToTimeline(data); 
    // Store globally so other functions can access
    globalCumulativeData = cumulativeData;    
    const chartContainer = document.getElementById('main');
    if (!chartContainer) {
        console.error("Chart container element with ID 'main' not found.");
        return;
    }

    // 2. PREPARE DATA for ApexCharts
    let labels = [...new Set([
        ...cumulativeData.sarlog_open.map(p => p.x),
        ...cumulativeData.breachlog_open.map(p => p.x),
        ...cumulativeData.foilog_open.map(p => p.x),
        ...cumulativeData.dpcomplaintslog_open.map(p => p.x)
    ])].sort();

    // --- HANDLE START & END DATE ---
    const startDate = startDateStr ? new Date(startDateStr.split('-').reverse().join('-')) : null;
    const endDate = endDateStr ? new Date(endDateStr.split('-').reverse().join('-')) : new Date();

    // Filter labels between start & end dates
    labels = labels.filter(label => {
        const labelDate = new Date(label);
        if (startDate && labelDate < startDate) return false;
        if (endDate && labelDate > endDate) return false;
        return true;
    });

    // Ensure first & last labels match start/end dates
    if (startDate && labels[0] !== startDate.toISOString().slice(0, 10)) {
        labels.unshift(startDate.toISOString().slice(0, 10));
    }
    if (endDate && labels[labels.length - 1] !== endDate.toISOString().slice(0, 10)) {
        labels.push(endDate.toISOString().slice(0, 10));
    }

    const series = [
        {
            name: 'SAR Open Logs',
            data: labels.map(l => cumulativeData.sarlog_open.find(p => p.x === l)?.y || 0),
            color: '#28a745'
        },
        {
            name: 'SAR Closed Logs',
            data: labels.map(l => cumulativeData.sarlog_closed.find(p => p.x === l)?.y || 0),
            color: '#28a745',
            strokeDashArray: 5
        },
        {
            name: 'Breach Open Logs',
            data: labels.map(l => cumulativeData.breachlog_open.find(p => p.x === l)?.y || 0),
            color: '#dc3545'
        },
        {
            name: 'Breach Closed Logs',
            data: labels.map(l => cumulativeData.breachlog_closed.find(p => p.x === l)?.y || 0),
            color: '#dc3545',
            strokeDashArray: 5
        },
        {
            name: 'FOI Open Logs',
            data: labels.map(l => cumulativeData.foilog_open.find(p => p.x === l)?.y || 0),
            color: '#007bff'
        },
        {
            name: 'FOI Closed Logs',
            data: labels.map(l => cumulativeData.foilog_closed.find(p => p.x === l)?.y || 0),
            color: '#007bff',
            strokeDashArray: 5
        },
        {
            name: 'Complaint Open Logs',
            data: labels.map(l => cumulativeData.dpcomplaintslog_open.find(p => p.x === l)?.y || 0),
            color: '#747676'
        },
        {
            name: 'Complaint Closed Logs',
            data: labels.map(l => cumulativeData.dpcomplaintslog_closed.find(p => p.x === l)?.y || 0),
            color: '#747676',
            strokeDashArray: 5
        }
    ];

    // 3. APEXCHARTS CONFIGURATION
    const options = {
        series: series,
        chart: {
            type: 'line',
            height: 500,
            toolbar: { show: false },
            zoom: { enabled: false}
        },
        stroke: { curve: 'smooth', width: 2 },
xaxis: {
    categories: labels, // labels in 'yyyy-mm-dd'
    labels: {
        rotate: -45,
        formatter: function(value) {
            if (value === labels[0] || value === labels[labels.length - 1]) {
                const parts = value.split('-'); // ['yyyy','mm','dd']
                return parts[2] + '-' + parts[1] + '-' + parts[0]; // dd-mm-yyyy
            }
            return ''; // hide middle 
        }
    }
}
,
        yaxis: {
            title: { text: 'Number of Logs' },
            min: 0,
            labels: { formatter: val => Math.round(val) }
        },
        title: {
            text: `${jQuery('#jform_cluster_id option:selected').text()} - Log Timeline`,
            align: 'left',
            style: { fontSize: '16px', fontWeight: 'bold' }
        },
        legend: {
            position: 'top',
            markers: {
                shape: ['circle', 'square','circle', 'square','circle', 'square','circle', 'square']
            }
        },
        tooltip: { 
            enabled: true,
            shared: true,
            intersect: false,
            custom: function({ series, seriesIndex, dataPointIndex, w }) {
                return buildCustomTooltip(series, seriesIndex, dataPointIndex, w);
            }
        },
        grid: { borderColor: '#f1f1f1' }
    };

    // 4. DESTROY EXISTING CHART AND CREATE NEW ONE
    try {
        if (mainChart) mainChart.destroy();

        chartContainer.innerHTML = '';
        chartContainer.style.display = 'block';
        chartContainer.style.width = '100%';
        chartContainer.style.height = '500px';
        chartContainer.style.border = '1px solid #ddd';
        chartContainer.style.backgroundColor = '#fff';

        mainChart = new ApexCharts(chartContainer, options);
        mainChart.render().then(() => {
        }).catch(err => console.error('Error rendering ApexCharts:', err));
    } catch (error) {
        console.error('Error creating ApexCharts:', error);
        messageDisplay('Error creating chart. Please check console for details.', 'error');
    }
}

function showAverageTime(logCounts, tagId = '') {
    // --- 1. Define the Color Mapping ---
    const logColorMapping = {
        "Breach Log ": "orange",
        "SAR & Data Rights Log": "green",
        "FOI & EIR Log": "blue",
        "DP Complaints Log": "grey"
    };

    const container = tagId ? jQuery('#' + tagId) : jQuery('#tagaverage');
    container.empty();

    let htmlContent = '';

    if (logCounts && typeof logCounts === 'object') {
        jQuery.each(logCounts, function (logKey, logCount) {
            const color = logColorMapping[logKey] || 'gray';
            const displayLabel = logKey;
            const countValue = parseInt(logCount) || 0;
            const safeId = logKey.replace(/[^a-zA-Z0-9_-]/g, '_');

            // Determine initial checked state
            const isChecked = !checkedLogIds || checkedLogIds.length === 0 || checkedLogIds.includes(safeId);
            const initialClass = isChecked ? 'checked-color' : '';

            htmlContent += `
                <div class="log-row" data-log-key="${logKey}" style="display: flex; align-items: center; margin-bottom: 10px;">
                    
                    <span style="font-weight: bold; width: 200px; margin-right: 10px;">
                        ${displayLabel} 
                    </span>
                    
                    <span class="custom-checkbox ${initialClass}" 
                          data-color="${color}" 
                          data-safe-id="${safeId}"
                          style="
                              display: flex; justify-content: center; align-items: center;    
                              width: 15px; height: 15px; 
                              border: 1px solid ${color};
                              border-radius: 2px; margin-right: 15px;
                              cursor: pointer;
                              background-color: ${isChecked ? color : 'transparent'};
                          ">
                          <span class="checkmark" style="color: white; font-size: 10px; font-weight: bold; display: ${isChecked ? 'block' : 'none'};">
                             &#10003;
                          </span>
                    </span>
                    
                    <input type="checkbox" id="${safeId}" name="log_checkbox_${safeId}" value="1" style="display: none;">
                    
                    <span>
                        Average time spent: <strong>${countValue} days</strong>
                    </span>
                </div>
            `;
        });
    }

    container.html(htmlContent);

    // --- ATTACH CLICK LOGIC ---
    container.find('.custom-checkbox').each(function () {
        const $cb = jQuery(this);
        const color = $cb.data('color');

        const $hiddenInput = jQuery('#' + $cb.data('safe-id'));
        if ($cb.hasClass('checked-color')) {
            $hiddenInput.prop('checked', true);
        } else {
            $hiddenInput.prop('checked', false);
        }

        $cb.on('click', function () {
            const $self = jQuery(this);
            const $hiddenInput = jQuery('#' + $self.data('safe-id'));

            if ($hiddenInput.is(':checked')) {
                // UNCHECK
                $self.removeClass('checked-color').css('background-color', 'transparent');
                $self.find('.checkmark').hide();
                $hiddenInput.prop('checked', false);
                console.log(`Checkbox [${$hiddenInput.attr('id')}] is now: Unchecked`);
            } else {
                // CHECK
                $self.addClass('checked-color').css('background-color', color);
                $self.find('.checkmark').show();
                $hiddenInput.prop('checked', true);
                console.log(`Checkbox [${$hiddenInput.attr('id')}] is now: Checked`);
            }

            showReport();
        });
    });
}

// --- CUSTOM HTML CHART FUNCTION (Chart.js Bypass) ---
function createCustomHTMLChart(canvasElement, labels, datasets) {
    // Hide the canvas and create a custom chart container
    canvasElement.style.display = 'none';
    
    // Create a custom chart container
    const chartContainer = document.createElement('div');
    chartContainer.id = 'custom-chart-container';
    chartContainer.style.cssText = `
        width: 100%;
        height: 500px;
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 20px;
        margin: 10px 0;
        position: relative;
        font-family: Arial, sans-serif;
    `;
    
    // Add title
    const title = document.createElement('h3');
    title.textContent = `${jQuery('#jform_cluster_id option:selected').text()}`;
    title.style.cssText = 'text-align: center; margin-bottom: 20px; color: #333;';
    chartContainer.appendChild(title);
    
    // Create chart area
    const chartArea = document.createElement('div');
    chartArea.style.cssText = `
        width: 100%;
        height: 400px;
        position: relative;
        border: 1px solid #eee;
        background: #fafafa;
    `;
    chartContainer.appendChild(chartArea);
    
    // Insert the custom chart after the canvas
    canvasElement.parentNode.insertBefore(chartContainer, canvasElement.nextSibling);
    
    // Create a simple table-based chart
    const table = document.createElement('table');
    table.style.cssText = `
        width: 100%;
        height: 100%;
        border-collapse: collapse;
        font-size: 12px;
    `;
    
    // Create header row
    const headerRow = document.createElement('tr');
    const headerCell = document.createElement('th');
    headerCell.textContent = 'Date';
    headerCell.style.cssText = 'border: 1px solid #ddd; padding: 8px; background: #f5f5f5;';
    headerRow.appendChild(headerCell);
    
    // Add dataset headers
    datasets.forEach(dataset => {
        const th = document.createElement('th');
        th.textContent = dataset.label;
        th.style.cssText = 'border: 1px solid #ddd; padding: 8px; background: #f5f5f5; color: ' + dataset.borderColor + ';';
        headerRow.appendChild(th);
    });
    table.appendChild(headerRow);
    
    // Create data rows
    labels.forEach((label, index) => {
        const row = document.createElement('tr');
        
        // Date cell
        const dateCell = document.createElement('td');
        dateCell.textContent = label;
        dateCell.style.cssText = 'border: 1px solid #ddd; padding: 8px; background: white; font-weight: bold;';
        row.appendChild(dateCell);
        
        // Data cells
        datasets.forEach(dataset => {
            const cell = document.createElement('td');
            const value = dataset.data[index] ? dataset.data[index].y : 0;
            cell.textContent = value;
            cell.style.cssText = 'border: 1px solid #ddd; padding: 8px; background: white; text-align: center;';
            row.appendChild(cell);
        });
        
        table.appendChild(row);
    });
    
    chartArea.appendChild(table);
    
    // Add legend
    const legend = document.createElement('div');
    legend.style.cssText = 'margin-top: 15px; text-align: center;';
    
    datasets.forEach(dataset => {
        const legendItem = document.createElement('span');
        legendItem.style.cssText = `
            display: inline-block;
            margin: 0 15px;
            padding: 5px 10px;
            background: ${dataset.borderColor};
            color: white;
            border-radius: 3px;
            font-size: 11px;
        `;
        legendItem.textContent = dataset.label;
        legend.appendChild(legendItem);
    });
    
    chartContainer.appendChild(legend);
    
    // Add note about Chart.js issue
    const note = document.createElement('div');
    note.style.cssText = 'margin-top: 10px; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; font-size: 12px; color: #856404;';
    note.innerHTML = '📊 <strong>Note:</strong> Chart.js encountered an error, so this custom table chart is displayed instead.';
    chartContainer.appendChild(note);
}

// --- MULTIPLE TAG CHARTS ---
function renderAllChartsChartJS(allData, startDateStr, endDateStr,orgName) {
    
    const container = document.getElementById("chartsContainer");
    container.innerHTML = "";
    
    // Destroy all existing tag charts before creating new ones
    tagCharts.forEach(chart => {
        if (chart) {
            try {
                chart.destroy();
            } catch (e) {
                console.warn('Error destroying chart:', e);
            }
        }
    });
    tagCharts = [];
    tagChartBoxes = [];
    
    // Handle the new data structure - array of objects where each object represents a tag
    const datasets = Array.isArray(allData) ? allData : [allData];

    datasets.forEach((dataset, index) => {
        // Create a wrapper div for better layout control
        const chartDivContainer = document.createElement("div");
        chartDivContainer.className = 'col-md-6'; // Use Bootstrap columns for 2 charts per row
        chartDivContainer.style.padding = "10px";
        chartDivContainer.style.border = "1px solid #ddd";
        chartDivContainer.style.borderRadius = "8px";
        chartDivContainer.style.backgroundColor = "#fff";
        chartDivContainer.style.marginBottom = "20px";

        const heading = document.createElement("h5");
        // Extract tag name from the data structure or use default
        const tagName = dataset.tag_name || dataset.title || orgName[index] || `Logs Graph ${index+1}`;
        heading.textContent = tagName;
        heading.style.textAlign = 'center';
        heading.style.marginBottom = '15px';
        heading.style.color = '#333';
        
        // Create div for ApexCharts instead of canvas
        const chartDiv = document.createElement("div");
        chartDiv.id = `chart_${index}`;
        chartDiv.style.width = "100%";
        chartDiv.style.height = "300px";
        chartDiv.style.border = "1px solid #eee";
        chartDiv.style.borderRadius = "4px";

        chartDivContainer.appendChild(heading);
        chartDivContainer.appendChild(chartDiv);
        container.appendChild(chartDivContainer);

        drawChartTagChartJS(dataset, chartDiv.id, startDateStr, endDateStr);
    });
    
    // Adjust chartsContainer to use flexbox for proper column layout
    container.style.display = 'flex';
    container.style.flexWrap = 'wrap';
    container.style.justifyContent = 'space-between';
}

// Convert dd-mm-yyyy input to yyyy-mm-dd string
function ddmmyyyyToYyyymmdd(str) {
    const parts = str.split('-'); // dd-mm-yyyy
    return `${parts[2]}-${parts[1].padStart(2,'0')}-${parts[0].padStart(2,'0')}`;
}

function drawChartTagChartJS(dataset, chartId, startDateStr, endDateStr) {
    const ctx = document.getElementById(chartId);
    if (!ctx) {
        console.error(`Chart container with ID '${chartId}' not found.`);
        return;
    }

        const box = ctx.closest(".col-md-6");
        if (!box) return;

    // 1️Transform raw data
    const cumulativeData = transformDataToTimeline(dataset);
    const datasets = [];

    // 2️ Build series data helper
    const addSeries = (label, data, color, dash) => {
        if (data.length > 0) {
            datasets.push({
                label: label,
                data: data.map(p => ({ x: String(p.x.length === 7 ? p.x + '-01' : p.x), y: p.y })),
                borderColor: color,
                borderDash: dash,
                fill: false,
                tension: 0.4,
                pointStyle: dash ? 'square' : 'circle'
            });
        }
    };

    addSeries('SAR Open Logs', cumulativeData.sarlog_open, 'green', false);
    addSeries('SAR Closed Logs', cumulativeData.sarlog_closed, 'green', true);
    addSeries('Breach Open Logs', cumulativeData.breachlog_open, 'red', false);
    addSeries('Breach Closed Logs', cumulativeData.breachlog_closed, 'red', true);
    addSeries('FOI Open Logs', cumulativeData.foilog_open, 'blue', false);
    addSeries('FOI Closed Logs', cumulativeData.foilog_closed, 'blue', true);
    addSeries('Complaint Open Logs', cumulativeData.dpcomplaintslog_open, 'grey', false);
    addSeries('Complaint Closed Logs', cumulativeData.dpcomplaintslog_closed, 'grey', true);

    if (datasets.length === 0) {
        ctx.style.display = 'none';
        ctx.parentElement.innerHTML += '<p class="text-center text-muted">No log data found for this period.</p>';
        return;
    }

    // 3️Prepare x-axis labels
    let apexLabels = [...new Set(datasets.flatMap(ds => ds.data.map(p => p.x)))].sort();

    // Convert start/end to yyyy-mm-dd
    const startDate = startDateStr ? ddmmyyyyToYyyymmdd(startDateStr) : apexLabels[0];
    const endDate = endDateStr ? ddmmyyyyToYyyymmdd(endDateStr) : apexLabels[apexLabels.length - 1];

    if (!apexLabels.includes(startDate)) apexLabels.unshift(startDate);
    if (!apexLabels.includes(endDate)) apexLabels.push(endDate);

    apexLabels = [...new Set(apexLabels)].sort();

    // 4 Build series for ApexCharts
    const series = datasets.map(ds => ({
        name: ds.label,
        data: apexLabels.map(l => {
            const point = ds.data.find(p => p.x === l);
            return point ? point.y : 0;
        }),
        color: ds.borderColor,
        strokeDashArray: ds.borderDash ? 5 : 0
    }));

    // 5️ApexCharts options
    const options = {
        series: series,
        chart: { id: chartId, type: 'line', height: 300, toolbar: { show: false },zoom: { enabled: false} },
        stroke: { curve: 'smooth', width: 2 },
        xaxis: {
            type: 'category', // treat x-axis as category (string)
            categories: apexLabels,
            title: { text: 'Date' },
            labels: {
                rotate: -45,
                style: { fontSize: '10px' },
                formatter: function(value) {
                    if (value === apexLabels[0] || value === apexLabels[apexLabels.length - 1]) {
                        const parts = value.split('-'); // yyyy-mm-dd
                        return `${parts[2]}-${parts[1]}-${parts[0]}`;
                    }
                    return '';
                }
            }
        },
        yaxis: {
            title: { text: 'Number of Logs' },
            min: 0,
            labels: { formatter: val => Math.round(val) },
            tickPlacement: 'on'
        },
        title: { text: dataset.title || dataset.tag_name || '', align: 'left', style: { fontSize: '14px', fontWeight: 'bold' } },
        legend: { position: 'top', fontSize: '12px', markers: { shape: ['circle','square','circle','square','circle','square','circle','square'] } },
        tooltip: { 
            enabled: true,
            shared: true,
            intersect: false,
            custom: function({ series, seriesIndex, dataPointIndex, w }) {
                return buildCustomTooltip(series, seriesIndex, dataPointIndex, w);
            }
        },
        grid: { borderColor: '#f1f1f1' }
    };

    // 6️Render chart
    try {
        // Destroy existing chart with this ID if it exists
        const existingChart = ApexCharts.getChartByID(chartId);
        if (existingChart) {
            existingChart.destroy();
        }
        
        ctx.innerHTML = '';
        ctx.style.display = 'block';
        ctx.style.width = '100%';
        ctx.style.height = '300px';
        ctx.style.border = '1px solid #ddd';
        ctx.style.backgroundColor = '#fff';

        const tagChart = new ApexCharts(ctx, options);
        tagChart.render()
            .catch(err => console.error(`Error rendering ApexCharts for ${chartId}:`, err));
        tagCharts.push(tagChart);
        tagChartBoxes.push(box);
    } catch (error) {
        console.error('Error creating ApexCharts tag chart:', error);
        ctx.style.display = 'none';
        ctx.parentElement.innerHTML += '<p class="text-center text-danger">Error creating chart. Please check console for details.</p>';
    }
}

jQuery(document).ready(function(){
    jQuery('#downloadPdfBtn').on('click', async function() {
        // 1 Ask user for file name
        let userFileName = prompt("Enter PDF file name:", "DPE_Report");

        if (!userFileName) {
            alert("File name is required!");
            return;
        }

        // remove invalid characters
        userFileName = userFileName.replace(/[^a-zA-Z0-9_\-]/g, "");

        // add extension if missing
        if (!userFileName.toLowerCase().endsWith(".pdf")) {
            userFileName += ".pdf";
        }
        const loader = document.getElementById('loader-overlay');
        if (loader) loader.style.display = 'block';

        try {
            let imageDataList = [];

            // SINGLE CHART MODE
            const mainChartElement = document.getElementById('main');
            if (mainChartElement && mainChartElement.style.display !== 'none' && typeof mainChart !== 'undefined' && mainChart) {
                try {
                    const chartImage = await mainChart.dataURI();
                    imageDataList.push(chartImage.imgURI);
                } catch (err) {
                    console.error('Error capturing main chart:', err);
                    messageDisplay('Failed to capture main chart image', 'error');
                    throw err;
                }
            }

            // MULTIPLE CHARTS MODE (Tag-based)
            const chartsContainer = document.getElementById('chartsContainer');
            const charts = chartsContainer && chartsContainer.style.display !== 'none' 
                ? document.querySelectorAll('#chartsContainer div[id^="chart_"]')
                : [];

            if (charts.length > 0) {
                for (const chartDiv of charts) {
                    try {
                        // Verify the chart div exists and is visible
                        if (!chartDiv || chartDiv.style.display === 'none') {
                            console.warn(`Chart div ${chartDiv?.id} is not visible, skipping`);
                            continue;
                        }

                        // First try to get from ApexCharts registry
                        let chartObj = ApexCharts.getChartByID(chartDiv.id);
                        
                        // If not found in registry, try to find in our tagCharts array
                        if (!chartObj) {
                            const chartIndex = parseInt(chartDiv.id.replace('chart_', ''));
                            if (!isNaN(chartIndex) && tagCharts[chartIndex]) {
                                chartObj = tagCharts[chartIndex];
                            }
                        }

                        if (!chartObj) {
                            console.warn("Chart not found in ApexCharts registry or tagCharts array: " + chartDiv.id);
                            continue;
                        }

                        // Verify chart has been rendered
                        if (!chartObj.w || !chartObj.w.globals) {
                            console.warn(`Chart ${chartDiv.id} not fully rendered yet, skipping`);
                            continue;
                        }

                        const img = await chartObj.dataURI();
                        imageDataList.push({
                            title: chartDiv.parentElement.querySelector("h5")?.innerText || "",
                            img: img.imgURI
                        });
                    } catch (err) {
                        console.error(`Error capturing chart ${chartDiv.id}:`, err);
                        // Continue with other charts instead of failing completely
                    }
                }
            }
            
            if (imageDataList.length === 0) {
                messageDisplay('No charts were captured. Please ensure charts are fully loaded before downloading PDF.', 'error');
                if (loader) loader.style.display = 'none';
                return;
            }

            //  Include metadata (cluster/tag info, date range)
            const startDate = jQuery('#jform_start_date').val();
            const endDate = jQuery('#jform_end_date').val();
            const clusterName = jQuery('#jform_cluster_id option:selected').text();
            const tags = jQuery('#filter_tags').val();


             const checkedLogsPdf = [];

                $('#tagaverages input[type="checkbox"]:checked').each(function () {
                    const logId = $(this).attr('id');               // e.g., "Breach_Log_"
                    const logKey = $(this).closest('.log-row').data('log-key'); // e.g., "Breach Log"
                    checkedLogsPdf.push({ id: logId, key: logKey });
                });
                // Example: If you just need their IDs as array
                const checkedIdsPdf = checkedLogIds = checkedLogsPdf.map(l => l.id);

                const logMappingPdf = {
                    'Breach_Log_': 'breachlog',
                    'SAR___Data_Rights_Log': 'sarlog',
                    'FOI___EIR_Log': 'foilog',
                    'DP_Complaints_Log': 'dpcomplaintslog'
                };

                // Map checkedIds to log types
                const mappedLogsPdf = checkedIdsPdf.map(id => logMappingPdf[id]).filter(Boolean);

                // Now send in AJAX
                let formData = jQuery('#reportForm').serializeArray(); // convert form to array

                // Add mapped logs to formData
                mappedLogsPdf.forEach(logType => {
                    formData.push({ name: 'checked_logs[]', value: logType });
                });

            // Send to backend via AJAX POST
            jQuery.ajax({
                url: Joomla.getOptions('system.paths').base + '/index.php?option=com_dpe&task=matviewreport.downloadPdf',
                type: 'POST',
                data: {
                    chart_images: JSON.stringify(imageDataList),
                    cluster_name: clusterName,
                    tags: JSON.stringify(tags),
                    start_date: startDate,
                    end_date: endDate,
                    formData : formData,
                    pdf_filename: userFileName
                },
                xhrFields: { responseType: 'blob' },
                success: function(blob) {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = userFileName;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    window.URL.revokeObjectURL(url);
                },
                complete: function() {
                    if (loader) loader.style.display = 'none';
                },
                error: function(err) {
                    console.error('PDF download failed:', err);
                    messageDisplay('Error downloading PDF.', 'error');
                }
            });
        } catch (error) {
            console.error('Error capturing chart:', error);
            messageDisplay('Error generating chart image.', 'error');
            if (loader) loader.style.display = 'none';
        }
    });
});

