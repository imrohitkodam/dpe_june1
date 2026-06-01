jQuery(document).ready(function () {
    const adminId = $('#admin-multiselect');
    // Initialize Chosen
    adminId.chosen().change(function () {
        const selectedValues = $(this).val() || [];

        if (selectedValues.includes("all") && selectedValues.length > 1) {
            alert("You have selected 'Send to All'. Do you want to select individual admins instead?");
        }
    });

    jQuery('#filter_tags').on('change', function () {
        jQuery("#jform_cluster_id").val("").trigger("chosen:updated");
    });

    // Reset tags when cluster changes
    jQuery('#jform_cluster_id').on('change', function () {
        jQuery("#filter_tags").val("").trigger("chosen:updated");
    });

    var $select = jQuery("#jform_cluster_id");

    // Initialize Chosen with a placeholder
    $select.chosen({
        placeholder_text_multiple: "Select Organisation",
        no_results_text: "No results found"
    });

    // Remove "Select Organisation" after selection
    $select.on("change", function () {
        jQuery("#jform_cluster_id_chosen .search-choice").each(function () {
            if (jQuery(this).text().trim() === "Select Organisation") {
                jQuery(this).remove();
            }
        });
    });

    // Ensure "Select Organisation" doesn't appear in the dropdown list
    jQuery("#jform_cluster_id option").each(function () {
        if (jQuery(this).text().trim() === "Select Organisation") {
            jQuery(this).remove();
        }
    });

    // Trigger Chosen update after modifying options
    $select.trigger("chosen:updated").css("width", "25%");
    jQuery('#filter_tags').trigger("chosen:updated").css("width", "50%");

    setTimeout(function () {
        jQuery('#jform_reportStatus_chosen').css('width', '50%');

    }, 1000)
    setTimeout(function () {
        jQuery('div#jform_cluster_id_chosen').eq(1).hide();
        if (window.innerWidth <= 768) {
            jQuery('#jform_cluster_id').css("width", "100% !important");

        }
    }, 2000); // 2000 ms = 2 seconds

    jQuery('.js-editor-tinymce').css('width', "90%");
})

function showReport(e) {

    let checkboxes = jQuery('#annualreporttmp input[type="checkbox"]:checked');

    if (checkboxes.length === 0) {
        messageDisplay('Please select at least one checkbox to generate the report.', 'warning');
        return;
    }
    let organistionList = jQuery('#jform_cluster_id').val();
    let tag = jQuery('#filter_tags').val();

    if ((!organistionList || organistionList.length === 0) && (tag.length == 0)) {
        messageDisplay('Please select at least one organization or tag to generate the report.', 'warning');
        return;
    }

    if (tag.length > 0 && organistionList.length > 0) {
        messageDisplay('You cannot select both tag and organisation at same time.', 'warning');
        return;
    }

    var dateRange = jQuery('#jform_date_range').val();
    var startDate = jQuery('#jform_start_date').val();
    var endDate = jQuery('#jform_end_date').val();
    var today = new Date();
    var dd = String(today.getDate()).padStart(2, '0');
    var mm = String(today.getMonth() + 1).padStart(2, '0'); // January is 0
    var yyyy = today.getFullYear();

    var todaydate = dd + '-' + mm + '-' + yyyy;

    stastartDatert = parseDate(startDate);
    endDate = parseDate(endDate);
    todaydate = parseDate(todaydate);

    if (startDate > todaydate) {

        messageDisplay("The start date cannot be in the future. Please select a valid date.", "warning");
        return false;
    }

    if (endDate > todaydate) {
        messageDisplay("The end date cannot be in the future. Please select a valid date.", "warning");
        return false;
    }

    if ((endDate != '') && (endDate < startDate)) {
        messageDisplay("The end date cannot be earlier than the start date. Please check your date range.", "warning");
        return false;
    }
    if ((!dateRange || dateRange.length === 0) && ((!startDate && !endDate) || (endDate == 'Invalid Date'))) {
        messageDisplay('Please select a date range, or specify both a start date and an end date to generate the report.', 'warning');
        return false;
    }
    let formData = jQuery('#annualreporttmp').serialize();
    document.getElementById('loader-overlay').style.display = 'block';

    jQuery.ajax({
        url: Joomla.getOptions('system.paths').base + "/index.php?option=com_dpe&task=annualreports.getAnnualReportData",
        type: 'POST',
        data: formData,
        success: function (response) {
            try {
                response = typeof response === "string" ? JSON.parse(response) : response;
                if (!response.data) throw new Error('Invalid response data');
                createLeadConsultantDropdown(response.data.lead_assistant_data);
                if (response.data.lead_assistant_data && response.data.lead_assistant_data.length > 0) {
                    delete response.data.lead_assistant_data;
                }
                renderTablesAndCharts(response.data);
                jQuery('#Dpo_Summary').removeClass('hide');
                jQuery('#savebtn').removeClass('hide');
                jQuery('#reportDetails').removeClass('hide');
                let selectedTexts = [];

                jQuery('.chosen-choices .search-choice span').each(function () {
                    selectedTexts.push(jQuery(this).text().trim());
                });

                let selectedText = '';

                if (response.data.clusterTitle) {
                    selectedText = response.data.clusterTitle.join(', ');
                } else {
                    selectedText = selectedTexts.join(', ');
                }

                // Set the text content of the Organisation_name element
                jQuery('.Organisation_name').html(selectedText);

                document.getElementById('loader-overlay').style.display = 'none';
                jQuery('.downloadpdf').removeClass('hide');
                document.querySelectorAll("*").forEach(element => {
                    if (element.childNodes.length === 1 && element.childNodes[0].nodeType === 3) { // Check if it's a text node
                        if (element.textContent.trim().toLowerCase() === "null") {
                            element.textContent = "0";
                        }
                    }
                });

                if ((!jQuery('#startDate').val() || jQuery('#jform_start_date').attr('data-local-value'))
                    || jQuery('#jform_date_range').val()) {
                    var dateRange = jQuery('#jform_date_range').val();

                    var startDate = jQuery('#jform_start_date').attr('data-local-value');
                    var endDate = jQuery('#jform_end_date').attr('data-local-value');

                    const urlParams = new URLSearchParams(window.location.search);
                    const reportId = urlParams.get('id');
                    var today = new Date();
                    var dd = String(today.getDate()).padStart(2, '0');
                    var mm = String(today.getMonth() + 1).padStart(2, '0'); // January is 0
                    var yyyy = today.getFullYear();
                    var formattedDate = dd + '-' + mm + '-' + yyyy;

                    if (dateRange) {
                        if (reportId) {
                            startDate = response.data.start_date;
                            endDate = response.data.end_date;
                            const diffMonths = monthDiff(startDate, endDate);

                            if ((diffMonths == dateRange) && (endDate == formattedDate)) {

                                jQuery('#reportCreatedDateRange').html(startDate + " - " + endDate);
                            }
                            else {

                                const dateRangeValue = getDateFormatedVlaue(dateRange);
                                jQuery('#reportCreatedDateRange').html(dateRangeValue.actualDate);

                            }
                        } else {
                            const dateRangeValue = getDateFormatedVlaue(dateRange);

                            jQuery('#reportCreatedDateRange').html(dateRangeValue.actualDate);
                        }

                    }
                    else if (startDate && !endDate) {


                        endDate = formattedDate;
                        jQuery('#jform_end_date').val(endDate);
                        jQuery('#reportCreatedDateRange').html(startDate + " - " + endDate);
                    }
                    else {
                        jQuery('#reportCreatedDateRange').html(startDate + " - " + endDate);
                    }
                }
                jQuery('#sp-bottom').hide();

                if (jQuery('#orgadmin').val() == '') {
                    jQuery('#leadConsultantDropdown_chosen').remove();
                    jQuery('#leadConsultantDropdown').remove();
                }

            } catch (error) {
                messageDisplay(error.message, 'error');
                console.error(error);
            }
        },
        error: function (xhr, status, error) {
            console.error('Error:', error);
            messageDisplay('An error occurred while generating the report.', 'error');
        }
    });
} function monthDiff(startDateStr, endDateStr) {
    const start = new Date(startDateStr);
    const end = new Date(endDateStr);

    let months = (end.getFullYear() - start.getFullYear()) * 12;
    months -= start.getMonth();
    months += end.getMonth();

    // If you want to count partial months, you can add logic here.
    return months <= 0 ? 0 : months;
}
function getDateFormatedVlaue(months) {
    let endDate = new Date(); // Today's date
    let startDate = new Date();

    startDate.setMonth(startDate.getMonth() - months); // Subtract months

    // Format date to dd-mm-yyyy
    const formatDate = (date) => {
        return date.toLocaleDateString('en-GB').split('/').join('-'); // Converts to dd-mm-yyyy
    };

    return {
        actualDate: formatDate(startDate) + " - " + formatDate(endDate),
    };
}
function convertToDDMMYYYY(dateString) {
    let date = new Date(dateString);
    let day = String(date.getDate()).padStart(2, '0');
    let month = String(date.getMonth() + 1).padStart(2, '0'); // Months are 0-based
    let year = date.getFullYear();

    return `${day}-${month}-${year}`;
}
function renderTablesAndCharts(responseData) {
    let chartDataArr = [];
    let container = document.getElementById("Logs_And_Incident_Management");
    container.innerHTML = "";
    Object.entries(responseData).forEach(([category, data], index) => {
        let tableHtml = createTable(data, category);
        let chartCanvasId = `chart_${index}`;
        chartDataArr.push(chartCanvasId);
        let section = document.createElement("div");
        section.classList.add("log-section", category);
        section.innerHTML = `
            <div class="table-chart-container">
                ${tableHtml}
                <div id='${chartCanvasId}' class="chart-size"></div>
            </div>`;
        container.appendChild(section);

        if (!['Making_The_Rounds', 'clusterTitle', 'start_date', 'end_date', 'Phishing_Simulations', 'User_Management',
            'DPO_SLA', 'Training_Courses', 'Compliance_Manager', 'Initial_Trust_Plan',
            'Accountability_Framework_Tracking', 'DfE_Cyber_Governance_Code_of_Practice', '_Checklists', 'DfE_Digital_Standard', 'DPIA_Lite'].includes(category)) {
            if (category === "To_Dos") delete data.completion_Percentage;
            if (category === "Risk_Register") delete data.Risk_register_by_risk_level;
            if (category === "Third_Parties") delete data.Number_by_risk_level;
            if (['Number_and_Status_of_SAR_Log', 'Number_and_Status_of_FOI_Log', 'Number_and_Status_of_Breach_Log', 'Number_and_Status_of_Complaints'].includes(category)) {
                delete data['Average_lifecycle_duration_initiation_to_resolution_(days)'];

                // Remove dynamic SUM aliases from pie chart
                Object.keys(data).forEach((key) => {
                    if (key.startsWith('breach_indicator__')) {
                        delete data[key];
                    }
                });
            }




            if (category === "To_Dos") delete data.completion_Percentage;
            if (category === "Risk_Register") delete data.Risk_register_by_risk_level;
            if (category === "Third_Parties") delete data.Number_by_risk_level;
            if (['Number_and_Status_of_SAR_Log', 'Number_and_Status_of_FOI_Log', 'Number_and_Status_of_Breach_Log', 'Number_and_Status_of_Complaints'].includes(category)) {
                delete data['Average_lifecycle_duration_initiation_to_resolution_(days)'];
                delete data['Reported_To_ICO'];
            }
            const allZero = Object.values(data).every(val => val === 0 || val === '0');

            if (!allZero) {
                renderPieChart(data, chartCanvasId);
            }
        }
    });
    jQuery('.canvasjs-chart-credit').css('display', 'none');
    document.querySelectorAll('div[id^="chart_"]').forEach(function (el) {
        if (el.innerHTML.trim() === '') {
            el.classList.remove('chart-size');
        }
    });

}

// Function to create an HTML table from JSON data
function createTable(data, title) {
    if (!['Making_The_Rounds', 'Phishing_Simulations', 'Third_Parties', 'User_Management',
        'Training_Courses', 'Compliance_Manager', 'Initial_Trust_Plan',
        'Accountability_Framework_Tracking', 'DfE_Cyber_Governance_Code_of_Practice',
        'DfE_Digital_Standard', 'Risk_Register', 'Record_of_Processing', 'DPO_SLA',
        '_Checklists', 'clusterTitle', 'start_date', 'end_date'].includes(title)) {
        let tableHtml = `<table border='1' style="width: ${title === 'DPIA_Lite' ? '100%' : '50%'}; margin-bottom: 10px; margin-top: 10px;">
    <tr>
        <th class="annualth" colspan="10">${title.replace(/_/g, ' ')}</th>
        <td class="annualth" align="right" colspan="5"></td>
       </tr>`;


        for (let key in data) {
            if (typeof data[key] !== 'object' || data[key] === null) {
                // Simple key-value rows
                tableHtml += `<tr>
                        <td colspan="10" style="font-weight: bold; padding: 5px;">

                             ${key.replace('breach_indicator__', '').replace(/_/g, ' ')}

                        </td>
                        <td colspan="5" align="right" style="padding:10px;">${(data[key] == 'null') ? '0' : data[key]}</td>
            </tr>`;
            } else if (typeof data[key] === 'object' && data[key] !== null) {
                // Nested object rows (assuming log table structure)
                const nestedKeys = Object.keys(data[key]);
                const numCols = nestedKeys.length;
                const totalColspan = 15;
                const cellColspan = Math.floor(totalColspan / numCols);

                // Nested headers
                tableHtml += `<tr>`;
                nestedKeys.forEach((nestedKey, index) => {
                    let currentColspan = (index === numCols - 1) ? totalColspan - (cellColspan * (numCols - 1)) : cellColspan;
                    tableHtml += `<th colspan="${currentColspan}" class="nested-th" style="padding: 10px; background: #f7f7f7; text-align: left; font-weight: bold;">${nestedKey.replace(/_/g, ' ')}</th>`;
                });
                tableHtml += `</tr>`;

                // Handle array-like nested keys
                const rowCount = data[key]?.Log_Date?.length || data[key][nestedKeys[0]]?.length || 0;
                for (let i = 0; i < rowCount; i++) {
                    tableHtml += `<tr>`;
                    nestedKeys.forEach((nestedKey, index) => {
                        let cellValue = data[key][nestedKey][i] ?? 'N/A';
                        let currentColspan = (index === numCols - 1) ? totalColspan - (cellColspan * (numCols - 1)) : cellColspan;
                        tableHtml += `<td colspan="${currentColspan}" style="padding: 8px;">${cellValue}</td>`;
                    });
                    tableHtml += `</tr>`;
                }
            }
        }

        tableHtml += `</table>`;
        return tableHtml;

    }
    else if (title == '_Checklists') {
        title = title.replace(/_/g, ' ');

        let finalHtml = '<table style="width:100%;margin-top:20px;margin-bottom:20px;"><tbody><tr><th class="annualth" colspan="4">' + title + '</th></tr></tbody></table>';
        let tableHtml = '';
        Object.values(data).forEach(org => {
            let orgHtml = `<table style="width:100%;margin-top:20px;"><tbody><tr><th class="orgth" colspan="4">${org.clusterTitle}</th></tr></tbody></table>`;
            let table = `<table border="1" style="width:100%; margin-bottom:15px; padding:5px;">`;
            table += `<tr style="background: #f2f2f2; font-weight: bold;"><th>CheckList Item</th><th>In Progress</th><th>To-Do</th><th>Done</th></tr>`;

            org.data.forEach(cluster => {
                table += `
            <tr>
                <td>${cluster.title}</td>
                <td>${cluster.inprogress ?? '0'}</td>
                <td>${cluster.todo ?? '0'}</td>
                <td>${cluster.done ?? '0'}</td>
            </tr>`;
            });

            table += `</table>`;
            orgHtml += table;
            tableHtml += orgHtml;
        });


        finalHtml += tableHtml + "<br>";

        return finalHtml;


    }
    else if (title === 'DfE_Cyber_Governance_Code_of_Practice') {
        title = title.replace(/_/g, ' ');
        let finalHtml = '';

        if (data.length === 0) {
            finalHtml += `<table border='1' style="width:100%; margin-bottom:20px;">
        <tr>
          <th class='annualth' style="font-size: 16px;" colspan='10'>${title}</th>
        </tr>
        <tr>
          <td colspan='10' style="padding:15px;">No data available for ${title} in this time period.</td>
        </tr>
      </table>`;
            return finalHtml;
        }

        finalHtml += `<h2 class="annualth" style="font-size: 16px;width:100%;margin-top:20px;margin-bottom:20px;">${title}</h2>`;

        // Prepare headers
        let headers = ['Log Date'];
        const firstItem = data[0];
        const fieldIds = [];

        Object.keys(firstItem).forEach(key => {
            if (key.startsWith('ftitle_')) {
                const id = key.split('_')[1];
                fieldIds.push(id);
                headers.push(firstItem[`ftitle_${id}`]);
            }
        });

        // Begin the table
        finalHtml += `<table border='1' style="width:100%; margin-bottom:20px;" cellspacing="0" cellpadding="5">
    <tr style="background:#f2f2f2;">${headers.map(h => `<th>${h}</th>`).join('')}</tr>`;

        // Add each row of data
        data.forEach(item => {
            const logDate = item["Log_Date"] || 'N/A';
            let rowHtml = `<td style="text-align:center;">${logDate}</td>`;

            fieldIds.forEach(id => {
                const val = item[`textValue_${id}`] || item[`Field_${id}`] || '';
                const color = item[`colorValue_${id}`] || '#ffffff';
                rowHtml += `<td style="background-color: ${color}; text-align: center;">${val}</td>`;
            });

            finalHtml += `<tr>${rowHtml}</tr>`;
        });

        finalHtml += `</table>`;
        return finalHtml;
    }

    else if (title == 'DfE_Digital_Standard') {

        title = title.replace(/_/g, ' ');
        let finalHtml = `<h2 class="annualth" style="font-size: 16px;width:100%;margin-top:20px;margin-bottom:20px;">${title}</h2>`;

        Object.keys(data).forEach(categoryKey => {
            let category = data[categoryKey];
            let title = category.title;
            let headers = new Set();
            let rows = [];

            Object.keys(category).forEach(entryKey => {
                if (Object.keys(category).length === 1 && category.hasOwnProperty("title")) {
                    if (title) {
                        finalHtml += `<table border='1' style=" width:100%;margin-bottom:20px;">
                <tr><th class='orgth' colspan='10'>
                ${title}</th></tr>
                <tr>
                <td colspan='5'  
                style="padding:15px;">No data available for ${title}
                 in this time period.</td>
                </tr>
                </table>`;
                        return finalHtml;
                    }

                }

                if ((Object.keys(category).length !== 1) && (entryKey !== 'title')) {
                    let entry = category[entryKey];
                    let row = [`<td>${entry.Log_Date || 'N/A'}</td>`];

                    Object.keys(entry).forEach(fieldKey => {
                        if (fieldKey.startsWith("ftitle_") && (fieldKey !== "ftitle_0")) {
                            let fieldId = fieldKey.split("_")[1];

                            // Add to headers
                            headers.add(entry[fieldKey]);

                            // Get corresponding values
                            let value = entry[`textValue_${fieldId}`] || entry[`Field_${fieldId}`];
                            let color = entry[`colorValue_${fieldId}`] || "white"; // Default color
                            // Store row data with color formatting
                            row.push(`<td style="background-color: ${color}; border-right: groove; padding: 10px; text-align: center;">${value}</td>`);

                        }
                    });

                    rows.push(`<tr>${row.join('')}</tr>`);
                }
            });

            // Convert headers to an array
            let headersArray = [...headers];

            // Skip empty tables
            if (headersArray.length === 0) return;

            // Build the table
            if (title) {
                finalHtml += `<table border='1' style=" width:100%;">
                <tr><th class='orgth' colspan='10'>
                ${title}</th></tr>
       <table style="width:100%;margin-bottom:20px;" border="1" cellspacing="0" cellpadding="5">`;

                // Filter out headers that are 0
                headersArray = headersArray.filter(h => h !== null);
                finalHtml += `<tr style="background: #f2f2f2;"><th>Log Date</th>${headersArray.map(h => `<th>${h}</th>`).join('')}</tr>`;
                finalHtml += rows.join('');
                finalHtml += `</table><br>`;
            }


        });

        return finalHtml;
    }
    else if (title == 'Training_Courses') {
        title = title.replace(/_/g, ' ');
        let finalHtml = '';

        const summaryData = {
            "Total Courses Assigned": data.Total_courses_Assigned,
            "Total Completion Percentage": data.Total_completion_percentage + "%",
            "Total Users Assigned": data.Total_users_assigned
        };

        // Generate Course Results Table dynamically
        let courseTableHtml = `<table border='1' style="width: 100%; margin-top: 20px;">

    <tr><th class="annualth" colspan="5">${title}</th></tr>
    <tr>`;
        Object.keys(summaryData).forEach(key => {
            courseTableHtml += `<th  style="background: #f2f2f2; font-weight: bold;padding:10px;">${key.replace(/_/g, ' ')}</th>`;
        });
        courseTableHtml += `</tr><tr>`;

        // Dynamic Data for Summary Table
        Object.values(summaryData).forEach(value => {
            courseTableHtml += `<td style="padding: 5px;">${value}</td>`;
        });

        courseTableHtml += `</tr>`;

        // Dynamic Headers for Course Table
        let courseHeaders = [];

        if (data && Array.isArray(data.courseResult) && data.courseResult.length > 0) {
            courseHeaders = Object.keys(data.courseResult[0]);

        } else {
            console.warn('courseResult is empty or undefined');
        }

        courseHeaders.forEach(header => {
            courseTableHtml += `<th  style="background: #f2f2f2; font-weight: bold;padding:10px;">${header.replace(/_/g, ' ')}</th>`;
        });
        courseTableHtml += `</tr>`;

        // Dynamic Data for Course Table
        data.courseResult.forEach(course => {
            courseTableHtml += `<tr>`;
            courseHeaders.forEach(header => {
                courseTableHtml += `<td style="padding: 5px;">${course[header]}</td>`;
            });
            courseTableHtml += `</tr>`;
        });

        courseTableHtml += `</table>`;

        finalHtml += courseTableHtml + "<br>";

        return finalHtml;


    } else if (title == "Compliance_Manager") {

        title = title.replace(/_/g, ' ');
        let finalHtml = '<table><tbody></tbody></table>';

        if ((data.document_list.length > 0) && (data.totalData.length > 0)) {
            let tableHtml = `
    <table border="1" style="width: 100%; margin-top: 20px; border-collapse: collapse; border-bottom: 0;">
        <tr><th class="annualth" colspan="4">${title}</th></tr>
        <tr>
            <th style="background: #f2f2f2; font-weight: bold; padding: 10px;">Organisation</th>
            <th style="background: #f2f2f2; font-weight: bold; padding: 10px;">Total Documents Added</th>
            <th style="background: #f2f2f2; font-weight: bold; padding: 10px;">Total Completion Percentage</th>
        </tr>`;

            // Loop through each item in totalData
            data.totalData.forEach(item => {
                tableHtml += `
        <tr>
            <td style="padding: 5px;">${item.Organisation}</td>
            <td style="padding: 5px;">${item.Total_documents_assigned}</td>
            <td style="padding: 5px;">${item.Total_percentage_completed}%</td>
        </tr>`;
            });

            tableHtml += `</table>`;

            // Check if document_list has data
            if (data.document_list && data.document_list.length > 0) {
                const docHeaders = Object.keys(data.document_list[0]);

                tableHtml += `
        <table border="1" style="width: 100%;border-collapse: collapse;border-top:0;">
                <tr>${docHeaders.map(header => `<th style="background: #f2f2f2; font-weight: bold;padding:10px;">${header.replace(/_/g, " ")}</th>`).join("")}</tr>
                ${data.document_list.map(doc => `
                    <tr>${docHeaders.map(header => `<td style="padding: 5px;">${doc[header] ?? "N/A"}</td>`).join("")}</tr>
                    `).join("")}
                </table>`;
            }

            finalHtml += tableHtml + "<br>";
        }
        else {
            finalHtml += `<table><tr><th class='annualth' colspan='4'>${title}</th></tr><tr><td colspan='4'style="padding:15px;">No data available for ${title} in this time period.</td></tr></table><br>`;
        }


        return finalHtml;

    }
    else if (title == "Phishing_Simulations") {

        title = title.replace(/_/g, ' ');

        let tableHtml = '<table style="width:100%;margin-top:20px;"><tbody><tr><th class="annualth" colspan="4">' + title + '</th></tr></tbody></table>';


        tableHtml += `<table border="1" style="width: 100%; border-collapse: collapse;">`;


        let validCampaigns = 0;
        let statsHeaders = new Set();


        const headerReplacements = {
            "sent": "Number of Emails Sent",
            "opened": "Percentage Opened",
            "clicked": "Percentage Clicked",
            "submitted_data": "Percentage Submitted Data",
            "total": "",
            "email_reported": "",
            "error": ""
        };
        Object.values(data).forEach(campaign => {
            if (campaign && campaign.name && campaign.stats) {
                Object.keys(campaign.stats).forEach(statKey => statsHeaders.add(statKey));
            }
        });

        tableHtml += `<tr style="background: #f2f2f2; font-weight: bold;padding:10px;"> <th class="" colspan="10">Campaign Name</th>
        <th  colspan="5">Status</th>`;

        // Loop through the statsHeaders to create the dynamic headers
        statsHeaders.forEach(stat => {
            if (headerReplacements[stat] !== "") {  // Exclude 'total' header
                let headerText = headerReplacements[stat] || stat.replace(/_/g, ' '); // Use replacement text or format normally
                tableHtml += `<th colspan="5">${headerText}</th>`;
            }
        });

        tableHtml += `</tr>`;

        // Second loop: Generate rows dynamically
        Object.values(data).forEach(campaign => {
            if (campaign && campaign.name && campaign.stats) {
                tableHtml += `<tr>
                            <td colspan="10">${campaign.name}</td>
                <td colspan="5">${campaign.status}</td>`;

                let total = campaign.stats.total; // Get total value

                // Fill in the stats dynamically
                statsHeaders.forEach(stat => {
                    let value = campaign.stats[stat] !== undefined ? campaign.stats[stat] : "N/A";

                    // Calculate percentage for specific stats
                    if (["opened", "clicked", "submitted_data"].includes(stat) && total > 0) {
                        let percentage = ((campaign.stats[stat] / total) * 100).toFixed(2);
                        value = `${campaign.stats[stat]} (${percentage}%)`;
                    }

                    // Exclude email_reported and error from the table
                    if (stat !== 'email_reported' && stat !== 'error' && stat !== 'sent') {
                        tableHtml += `<td colspan="5">${value}</td>`;
                    }
                });

                tableHtml += `</tr>`;
                validCampaigns++;
            }
        });

        // Add final row showing campaign count
        tableHtml += `
        <tr>
            <td colspan="${2 + statsHeaders.size}" style="text-align: center; font-weight: bold;">
                Total Campaigns: ${data.campaignscount || validCampaigns}
            </td>
            </tr>`;

        tableHtml += `</table>`;

        return tableHtml;


    }
    else if (title == 'Accountability_Framework_Tracking') {
        title = title.replace(/_/g, ' ');

        let finalHtml = '<table style="width:100%;margin-top:20px;margin-bottom:20px;"><tbody><tr><th class="annualth" colspan="5">' + title + '</th></tr></tbody></table><br />';


        var jsonData = data;
        Object.keys(jsonData).forEach(category => {
            let categoryData = jsonData[category];
            if (!categoryData.title) return;
            let dataEntries = Object.keys(categoryData)
                .filter(key => key !== "title")
                .map(key => categoryData[key]);

            if (dataEntries.length === 0) {

                finalHtml += `<table border='1' style=" width:100%;margin-bottom:20px;">
            <tr><th class='orgth' colspan='15'>
            ${categoryData.title}</th></tr>
            <tr>
            <td colspan='5'  
            style="padding:15px;">No data available for ${categoryData.title}
             in this time period.</td>
            </tr>
                    </table>`;
                return finalHtml;

            };

            let headers = ['Organisation', "Log Date", "Status"];


            let tableHtml = `<table border='1' style="width:100%;margin-bottom:20px;">
                            <tr>
                                <th class="orgth" colspan="15" style="width:100%;">${categoryData.title}</th>
                </tr>`;

            tableHtml += `<tr style="background: #f2f2f2; font-weight: bold;padding:10px;">`;

            if (dataEntries.length > 0) { // Ensure data is not empty
                let firstEntry = dataEntries[0]; // Get the first object

                Object.keys(firstEntry).forEach(key => {
                    // Convert key format: remove underscores and capitalize first letter
                    let formattedKey = key.replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase());
                    tableHtml += `<th colspan="5" style="padding: 8px; text-align: left;">${formattedKey}</th>`;
                });
            }

            tableHtml += `</tr>`;

            dataEntries.forEach(entry => {
                tableHtml += `<tr>`;
                headers.forEach(header => {
                    let key = header.replace(/ /g, '_'); // Convert "Log Date" → "log_date"
                    let cellValue = entry[key] ? entry[key] : "N/A"; // Handle missing/null values
                    tableHtml += `<td style="padding: 8px;" colspan='5'>${cellValue}</td>`;
                });
                tableHtml += `</tr>`;
            });

            tableHtml += `</table>`;

            // Append the table to the final output
            finalHtml += tableHtml + "<br>"; // Adds space between tables
        });

        return finalHtml;

    } else if (title == 'Third_Parties' || title == 'Risk_Register' || title == 'Record_of_Processing') {

        let tableHtml = '';

        tableHtml += `<table border='1' style="width:50%;"><th class="annualth" colspan="4">${title.replace(/_/g, ' ')}</th>`;

        Object.keys(data).forEach(key => {
            if (typeof data[key] === 'object' && data[key] !== null) {
                // If value is an object, create a nested table
                tableHtml += `<tr>
                <th colspan="2">${key.replace(/_/g, ' ')}</th>
                    </tr>`;

                tableHtml += `<tr><td colspan="2"><table class='nested-table'                  >`;

                Object.entries(data[key]).forEach(([nestedKey, value]) => {
                    tableHtml += `<tr>
                    <th class="nested-th" style="">${nestedKey.replace(/_/g, ' ')}</th>
                    <td>${value}</td>
                        </tr>`;
                });

                tableHtml += `</table></td></tr>`;
            } else {
                // Simple key-value pair
                tableHtml += `<tr>
                <th>${key.replace(/_/g, ' ')}</th>
                <td>${data[key]}</td>
                    </tr>`;
            }
        });

        tableHtml += `</table>`;

        return tableHtml;

    } else {
        if (['clusterTitle', 'start_date', 'end_date'].includes(title)) {
            return ' ';
        }

        let headers;
        if (data.length > 0) {
            headers = Object.keys(data[0]).filter(key => !key.startsWith('textValue_') && !key.startsWith('colorValue_')
                && !key.startsWith('params_') && !key.startsWith('ftitle_') && !key.startsWith('Field_'));
        } else {
            title = title.replace(/_/g, ' ');

            let finalHtml = '';

            finalHtml += `<table border='1' style="width:100%;"><tr><th class='annualth' colspan='8'>${title}</th></tr><tr><td colspan='4'style="padding:15px;">No data available for ${title} in this time period.</td></tr></table>`;


            return finalHtml;
        }
        let tableHtml = '';
        if (title == 'Initial_Trust_Plan') {

            tableHtml = `
              <table border='1' style="width: 100%; border-collapse: separate; border-spacing: 0 15px; margin-top: 20px;">
                <tr><th class="annualth" colspan="10">${title.replace(/_/g, ' ')}</th></tr>`;
        }
        else {
            tableHtml = `<table border='1' style="width: 100%; border-collapse: separate; border-spacing: 0 15px; margin-top: 20px; border-collapse: collapse;">
            <tr><th class="annualth" colspan="10">${title.replace(/_/g, ' ')}</th></tr>`;
        }

        // Add a dynamic row for column headers
        tableHtml += `<tr style="background: #f2f2f2; font-weight: bold;padding:10px;">`;
        headers.forEach(header => {
            if (!header.startsWith('textValue_') && !header.startsWith('colorValue_')
                && !header.startsWith('params_') && !header.startsWith('ftitle_') && !header.startsWith('Field_')) {
                tableHtml += `<th>${header.replace(/_/g, ' ')}</th>`;
            }
        });
        tableHtml += `</tr>`;

        // Populate table rows dynamically
        data.forEach(entry => {
            tableHtml += `<tr>`;

            headers.forEach(header => {
                if (!header.startsWith('textValue_') && !header.startsWith('colorValue_')
                    && !header.startsWith('params_') && !header.startsWith('ftitle_') && !header.startsWith('Field_')) {

                    let cellValue = entry[header] ? entry[header] : "N/A"; // Handle missing/null values
                    let colWidth = (title == 'Making_The_Rounds') ? (100 / headers.length).toFixed(2) + '%' : '25%';
                    let color = entry['colorValue_' + header] || '';
                    let style = color ? `background-color: ${color};` : '';

                    if (entry['textValue_' + header]) {
                        cellValue = entry['textValue_' + header];
                    }

                    let extraStyle = (style) ? ' border-left: groove; border-right: groove; border-bottom: groove; border-top: none; padding: 10px; text-align: center;' : ' padding: 5px;';
                    tableHtml += `<td style="width:${colWidth};${style}${extraStyle}">${cellValue}</td>`;
                }
            });

            tableHtml += `</tr>`;

        });
        tableHtml += `</table>`;

        return tableHtml;

    }

}

// Function to render a Pie Chart using Chart.js
function renderPieChart(data, canvasId) {

    let values = Object.values(data).map(Number);
    let labels = Object.keys(data).map(key => key.replace(/_/g, ' '));
    let colors = ["#ff6384", "#36a2eb", "#ffcd56", "#4bc0c0", "#9966ff", "#ff9f40"];

    let filteredData = labels.map((label, i) => ({
        label: label,
        value: values[i]
    })).filter(item =>
        item.label.toLowerCase() !== 'status' &&
        item.label.toLowerCase() !== 'DPIA lite status'
    );


    let dataPoints = filteredData.map((item, i) => ({
        label: item.label,
        y: item.value,
        color: colors[i % colors.length]
    }));

    let chart = new CanvasJS.Chart(canvasId, {
        animationEnabled: true,
        title: {
            horizontalAlign: "center"
        },
        data: [{
            type: "pie",
            startAngle: 120,
            radius: 80, // Force same outer radius for all charts
            innerRadius: 40, // Optional: If using doughnut style
            indexLabelFontSize: 15,
            indexLabel: "{label} - {y}",
            toolTipContent: "<b>{label}:</b> {y}",
            dataPoints: dataPoints
        }]
    });

    chart.render();



}


function getDateRange() {
    jQuery('#jform_start_date').val('');
    jQuery('#jform_end_date').val('');

}
function resetDateRange() {
    jQuery('#jform_date_range').val('').trigger("chosen:updated");
}

jQuery(document).ready(function () {
    // Select/Deselect All Checkboxes
    jQuery('#jform_check_all').on('change', function () {
        let isChecked = jQuery(this).prop('checked');
        jQuery('#annualreporttmp input[type="checkbox"]').prop('checked', isChecked);
    });

    // If any checkbox is manually unchecked, uncheck the "Check All" box
    jQuery('#annualreporttmp input[type="checkbox"]').on('change', function () {
        if (!jQuery(this).prop('checked')) {
            jQuery('#jform_check_all').prop('checked', false);
        } else {
            // If all checkboxes are checked, then check the "Check All" box
            if (jQuery('#annualreporttmp input[type="checkbox"]:checked').length ===
                jQuery('#annualreporttmp input[type="checkbox"]').length) {
                jQuery('#jform_check_all').prop('checked', true);
            }
        }
    });
});

function createLeadConsultantDropdown(data) {
    // Get the target element
    const targetElement = document.getElementById('dpolist');
    if (!targetElement) {
        console.error("Target element not found");
        return;
    }
    // Create a select dropdown
    const select = document.createElement("select");
    select.id = "leadConsultantDropdown"; // Set an ID for reference
    select.name = "jform_leadConsultantDropdown";

    // Add default option
    const defaultOption = document.createElement("option");
    defaultOption.value = "";
    defaultOption.text = "Select Lead Consultant";
    select.appendChild(defaultOption);

    // Loop through data and add options
    data.forEach(item => {
        if (item.id && item.lead_consultant) { // Avoid null values
            const option = document.createElement("option");
            option.value = item.id;
            option.text = item.lead_consultant;
            select.appendChild(option);
        }
    });

    setTimeout(function () {
        jQuery('#leadConsultantDropdown_chosen').remove();
        jQuery('#leadConsultantDropdown').remove();
        console.log(targetElement);
        targetElement.insertAdjacentElement("beforeend", select);

        jQuery('#leadConsultantDropdown').chosen();
        jQuery('#dpolist').removeClass('hide');
        jQuery('#leadConsultantDropdown_chosen').trigger("chosen:updated").css("width", "50%");
    }, 1500)

}

function saveReportData(exitUrl = '') {
    let formData = jQuery('#annualreporttmp').serializeArray();
    let combinedValues = ''; // Declare variable

    if ((jQuery('#jform_reportStatus').val() == '') || (jQuery('#jform_reportStatus').val() == 'null')) {
        messageDisplay('Kindly select a status before proceeding to save the report.', 'warning');
    }
    formData.forEach(item => {
        combinedValues += item.value + ' '; // Append values to the variable

        if (item.name === 'jform[dpo_comment]') {
            let editorContent = tinymce.activeEditor.getContent();
            item.value = editorContent;
        }
    });

    jQuery.ajax({
        url: Joomla.getOptions('system.paths').base + "/index.php?option=com_dpe&task=annualreports.saveAnnualReportData",
        type: 'POST',
        data: formData,
        dataType: 'json',
        headers: { 'X-CSRF-Token': Joomla.getOptions('csrf.token', '') },
        success: function (response) {
            try {
                if (response.data != false) {
                    var id = response.data;
                    jQuery('#report_id').val(id);

                    let currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set("id", id);
                    window.history.pushState({}, "", currentUrl);
                    messageDisplay('Report Saved Succesfully', 'success');

                    setTimeout(function () {

                        if (exitUrl) {
                            window.location.href = exitUrl;
                        }

                    }, 1000)
                }
                const form = document.getElementById("annualreporttmp");
                sessionStorage.setItem("initialFormData", JSON.stringify(getFormData(form)));
            } catch (error) {
                // console.error(error);
                messageDisplay(error, 'warning');
            }
        },
        error: function (xhr, status, error) {
            messageDisplay(error, 'warning');
        }
    });
}

function formatDate(dateStr) {
    if (!dateStr) return "";
    // Handle cases where date includes time (e.g., "2025-08-31 12:00:00")
    let d = new Date(dateStr.replace(" ", "T"));
    let day = String(d.getDate()).padStart(2, "0");
    let month = String(d.getMonth() + 1).padStart(2, "0");
    let year = d.getFullYear();
    return `${day}-${month}-${year}`;
}

function showReportData(reportData) {
    let formData = new FormData();

    // Convert JSON object to FormData
    for (let key in reportData) {
        if (typeof reportData[key] === 'object') {
            formData.append(key, JSON.stringify(reportData[key])); // Convert objects to JSON strings
        } else {
            formData.append(key, reportData[key]);
        }
    }
    let tag = jQuery('#filter_tags').val();
    document.getElementById('loader-overlay').style.display = 'block';
    jQuery.ajax({
        url: Joomla.getOptions('system.paths').base + "/index.php?option=com_dpe&task=annualreports.getAnnualReportData",
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function (response) {
            try {

                response = typeof response === "string" ? JSON.parse(response) : response;
                if (!response.data) throw new Error('Invalid response data');
                createLeadConsultantDropdown(response.data.lead_assistant_data);

                if (response.data.lead_assistant_data && response.data.lead_assistant_data.length > 0) {
                    delete response.data.lead_assistant_data;
                }
                renderTablesAndCharts(response.data);
                jQuery('#Dpo_Summary').removeClass('hide');
                jQuery('#savebtn').removeClass('hide');
                jQuery('#reportDetails').removeClass('hide');

                Object.keys(reportData).forEach(key => {
                    let checkbox = document.getElementById(key); // Find checkbox with the same ID as key
                    if (checkbox && checkbox.type === "checkbox") {
                        checkbox.checked = true; // Check the checkbox
                    }
                });

                var tagsVal = jQuery('#filter_tags').val();
                if (tagsVal && tagsVal.length > 0) {
                    jQuery('#jform_cluster_id').val('').trigger('chosen:updated');
                } else {
                    let organisationId = document.getElementById('jform_cluster_id');
                    if (organisationId) {
                        Array.from(organisationId.options).forEach(option => {

                            if (reportData.jform.cluster_id.includes(option.value)) {
                                option.selected = true;
                            }
                        });

                        // Trigger change event if using Chosen.js
                        $(organisationId).trigger("chosen:updated");
                    }

                }

                // Example usage:
                reportData.jform.start_date = formatDate(reportData.jform.start_date); // "06-09-2021"
                reportData.jform.end_date = formatDate(reportData.jform.end_date);   // "31-08-2025"


                if (reportData.jform.date_range.length > 0) {
                    var dbdate = jQuery('#reportCreatedDateRange').html();
                    dbdate = dbdate.split(' - ');
                    const startDate = dbdate[0];
                    const endDate = dbdate[1];
                    const today = new Date();
                    // Format today's date as DD-MM-YYYY to match the format of `end_date`
                    const day = String(today.getDate()).padStart(2, '0');
                    const month = String(today.getMonth() + 1).padStart(2, '0'); // Months are zero-based
                    const year = today.getFullYear();
                    const todayFormatted = `${day}-${month}-${year}`;
                    if (endDate === todayFormatted) {

                        jQuery('#jform_date_range').val(reportData.jform.date_range);
                        jQuery('#jform_date_range').trigger("chosen:updated");
                    }
                    else {
                        jQuery('#jform_start_date').val(startDate);
                        jQuery('#jform_end_date').val(endDate);
                    }

                } else {
                    jQuery('#jform_start_date').val(reportData.jform.start_date);
                    jQuery('#jform_end_date').val(reportData.jform.end_date);
                }

                jQuery('#jform_reportStatus').val(reportData.jform.reportStatus);
                jQuery('#jform_reportStatus').trigger("chosen:updated");

                if (jQuery('#orgadmin').val() == '1') {
                    jQuery('#leadConsultantDropdown').val(reportData.jform.leadConsultantDropdown);
                    jQuery('#leadConsultantDropdown').trigger("chosen:updated");
                } else {

                    jQuery('#leadConsultantDropdown_chosen').remove();
                    jQuery('#leadConsultantDropdown').remove();
                }

                document.getElementById('loader-overlay').style.display = 'none';
                document.querySelectorAll("*").forEach(element => {
                    if (element.childNodes.length === 1 && element.childNodes[0].nodeType === 3) { // Check text nodes
                        if (element.textContent.trim().toLowerCase() === "null") {
                            element.textContent = "0";
                        }
                    }
                });
                const form = document.getElementById("annualreporttmp");
                sessionStorage.setItem("initialFormData", JSON.stringify(getFormData(form)));
                jQuery('#sp-bottom').hide();
                if (jQuery('#orgadmin').val() != 1) {
                    jQuery('#leadConsultantDropdown_chosen').hide();
                }

            } catch (error) {
                messageDisplay(error.message, 'error');
                console.error(error);
            }

            document.querySelectorAll('div[id^="chart_"]').forEach(function (el) {
                if (el.innerHTML.trim() === '') {
                    el.classList.remove('chart-size');
                }
            });
            if (window.innerWidth <= 768) {
                jQuery('#jform_cluster_id').css("width", "100% !important");

            }
        },
        error: function (xhr, status, error) {
            console.error('Error:', error);
            messageDisplay('An error occurred while generating the report.', 'error');
        }
    });

}

function convertDDMMYYYYtoYMD(dateStr) {
    const [day, month, year] = dateStr.split('-');
    return `${year}-${month}-${day}`;
}


function prepareReportData(reportValue) {
    try {
        // Parse JSON string
        let data = JSON.parse(reportValue);

        let formData = new URLSearchParams();

        // Separate jform data from other data
        let jform = data.jform;
        delete data.jform; // Remove jform from main data

        // Process jform data
        if (jform) {
            for (let key in jform) {
                if (Array.isArray(jform[key])) {
                    // Convert arrays to multiple key-value pairs
                    jform[key].forEach(value => {
                        formData.append(`jform[${key}][]`, value);
                    });
                } else {
                    formData.append(`jform[${key}]`, jform[key]);
                }
            }
        }

        // Process other data (non-jform)
        for (let key in data) {
            formData.append(key, data[key]);
        }

        // Convert to string for AJAX submission
        let ajaxData = formData.toString();
        return ajaxData;

    } catch (error) {
        console.error("Error parsing JSON:", error);
        return null;
    }
}

function messageDisplay(msg, type) {
    jQuery('<div id="system-message-container"></div>').appendTo('#system-message-container');
    Joomla.renderMessages({ [type]: [msg] });
    jQuery('html, body').animate({ scrollTop: 0 }, 'slow');
    setTimeout(function () {
        jQuery('joomla-alert').fadeOut('slow', function () {
            $(this).remove();
        });
    }, 10000);
}


function sendToDpoForReview(id = null) {
    var url = window.location.href;
    var reportStatus = jQuery('#jform_reportStatus').val();

    if (reportStatus != 'Send_to_DPO') {
        messageDisplay('Please update the status to "Send to DPO" to forward the file for review.', 'warning');
        return false;
    }

    let urlParams = new URLSearchParams(new URL(url).search);
    let reportId = urlParams.get("id");

    if (((reportId != '') && (reportId == '#'))) {
        messageDisplay('The report has not been saved yet. Please save the report before sending it for review.', 'warning');
        return false;
    }

    if (jQuery('#leadConsultantDropdown').val().length < 1) {
        messageDisplay('Please select a DPO from the list to review the report.', 'warning');
        return false;
    }

    // Save Before send the review.
    saveReportData();

    jQuery.ajax({
        url: Joomla.getOptions('system.paths').base + "/index.php?option=com_dpe&task=annualreports.sendToDpoForReview",
        type: 'POST',
        data: { 'url': url },
        dataType: 'json',
        headers: { 'X-CSRF-Token': Joomla.getOptions('csrf.token', '') },
        success: function (response) {
            try {

                if (response.data.success) {
                    messageDisplay(response.data.message, 'success');
                }
            } catch (error) {
                // console.error(error);
                messageDisplay(error, 'warning');
            }
        },
        error: function (xhr, status, error) {
            messageDisplay(error, 'warning');
        }
    });
}

function getPDFreport(reportCoverData = null) {

    var url = window.location.href;
    let urlParams = new URLSearchParams(new URL(url).search);
    let reportId = urlParams.get("id");
    var id = jQuery('#report_id').val();

    if (((id == '') || (id == null)) || (reportId == '')) {
        messageDisplay('The report has not been saved yet. Please save the report first.', 'warning');
        return false;
    }


    setTimeout(() => {
        let chartImages = [];


        // Capture all CanvasJS charts
        document.querySelectorAll(".canvasjs-chart-container canvas").forEach((canvas) => {
            if (canvas.width > 0 && canvas.height > 0) {
                let container = canvas.closest('.canvasjs-chart-container').parentElement;
                let containerId = container.getAttribute('id');
                chartImages.push({ id: containerId, src: canvas.toDataURL("image/png") });
            }
        });

        // Work on cloned HTML to keep DOM untouched
        let reportHtmlContainer = document.querySelector(".tablepie-section");
        let reportHtml = reportHtmlContainer ? reportHtmlContainer.outerHTML : '';

        if (!reportHtml) {
            console.error("Report section not found");
            return;
        }
        document.getElementById('loader-overlay').style.display = 'block';

        //  Remove unwanted elements from reportHtml string (NO DOM change)

        reportHtml = reportHtml
            .replace(/<div[^>]*id="savebtn"[^>]*>[\s\S]*?<\/div>/gi, '') // remove #savebtn div
            .replace(/<select[^>]*id="leadConsultantDropdown"[^>]*>[\s\S]*?<\/select>/gi, '') // remove leadConsultantDropdown select
            .replace(/<div[^>]*id="leadConsultantDropdown_chosen"[^>]*>[\s\S]*?<\/div>/gi, '') // remove chosen dropdown
            .replace(/<select[^>]*id="jform_reportStatus"[^>]*>[\s\S]*?<\/select>/gi, '') // remove reportStatus select
            .replace(/<div[^>]*id="jform_reportStatus_chosen"[^>]*>[\s\S]*?<\/div>/gi, '') // remove chosen report status
            .replace(/<div[^>]*id="reportDetails"[^>]*>[\s\S]*?<\/div>/gi, '') // remove reportDetails div
            .replace(/<div[^>]*class="chosen-drop"[^>]*>[\s\S]*?<\/div>/gi, '') // remove chosen-drop panel
            .replace(/<div[^>]*class="toggle-editor[^"]*"[^>]*>[\s\S]*?<\/div>/gi, '') // more strict class match
            .replace(/<div[^>]*class="controls"[^>]*>\s*<ul[^>]*class="chosen-results"[^>]*>[\s\S]*?<\/ul>\s*<\/div>/gi, ''); // remove .controls containing .chosen-results

        reportHtml = reportHtml
            .replace(/<div[^>]*id="savebtn"[^>]*>[\s\S]*?<\/div>/gi, '')
            .replace(/<ul[^>]*class="chosen-results"[^>]*>[\s\S]*?<\/ul>/gi, '')
        // add more as needed...
        //  Inject status & DPO values into reportHtml string

        let statusText = jQuery('#jform_reportStatus').val().replace(/_/g, ' ');

        let dpoText = jQuery("#leadConsultantDropdown_chosen .chosen-single span").text().trim();

        reportHtml = reportHtml.replace(
            /(<label[^>]*id="jform_reportStatus-lbl"[^>]*>[\s\S]*?<\/label>)/i,
            `$1 : ${statusText}`
        );

        if ((dpoText == 'Select Lead Consultant') && (jQuery('#orgadmin').val() == '')) {
            dpoText = 'No DPO Selected';
        }

        reportHtml = reportHtml.replace(
            /(<div[^>]*id="dpolist"[^>]*>[\s\S]*?)(<\/div>)/i,
            '<br>' + `$1` + ': ' + `${dpoText} $2`
        );

        reportHtml = reportHtml.replace(
            /(<div[^>]*class="dpofeedback"[^>]*>)(\s*<\/div>)/i,
            `$1 No feedback present $2`
        );

        //  Get extra report meta info

        let reportTitle = jQuery('.reportTitle').html();
        let organisationName = jQuery('.Organisation_name').html();
        let conductedBy = jQuery('.reportCreatedBy').html();
        let createdDate = jQuery('.reportCreatedDate').html();
        let joomlaRoot = Joomla.getOptions("system.paths").root;

        reportHtml = processReportHtml(reportHtml);
        reportHtml = cleanReportHtml(reportHtml);


        if (jQuery('#orgadmin').val() === '') {
            let editorContent = tinymce.activeEditor.getContent();
            const $reportDom = $('<div>').html(reportHtml);
            const $targetLabel = $reportDom.find('#jform_dpo_comment-lbl');
            $targetLabel.nextAll().remove(); // Removes all siblings after the label
            $targetLabel.after('<div>' + editorContent + '</div>');
            reportHtml = $reportDom.html();
        }

        //  Send to Joomla endpoint

        $.ajax({
            url: joomlaRoot + "/index.php?option=com_dpe&task=annualreports.getAnnualReportPdfDownload",
            method: "POST",
            data: JSON.stringify({
                htmlContent: reportHtml,
                charts: chartImages,
                title: reportTitle,
                orgname: organisationName,
                conductedBy: conductedBy,
                date: createdDate
            }),
            headers: { "Content-Type": "application/json" },
            xhrFields: { responseType: "blob" },
            success: function (response) {
                let blob = new Blob([response], { type: "application/pdf" });

                // Prompt user for filename
                let customFilename = prompt("Enter file name for the PDF:", reportTitle);

                // Use custom filename or fallback to reportTitle
                let finalFileName = (customFilename && customFilename.trim() !== "") ? customFilename.trim() : reportTitle;

                // Ensure it ends with .pdf
                if (!finalFileName.toLowerCase().endsWith('.pdf')) {
                    finalFileName += '.pdf';
                }

                let link = document.createElement("a");
                link.href = URL.createObjectURL(blob);
                link.download = finalFileName;
                link.click();

                document.getElementById('loader-overlay').style.display = 'none';
            },
            error: function () {
                messageDisplay("Failed to generate PDF", 'warning');
            }
        });


    }, 2000);

}

function processReportHtml(reportHtml) {
    const excludedSections =
        ['_Checklists', 'Training_Courses', 'Making_The_Rounds', 'Initial_Trust_Plan', "User_Management",
            "Accountability_Framework_Tracking", "Phishing_Simulations", "DPO_SLA", "DPIA_Lite"]; // Sections to skip
    const parser = new DOMParser();
    const doc = parser.parseFromString(reportHtml, 'text/html');

    doc.querySelectorAll('.log-section').forEach(section => {
        const sectionClasses = section.className.split(' ');

        // Skip _Checklists section
        if (sectionClasses.some(c => excludedSections.includes(c))) {
            return;
        }

        const container = section.querySelector('.table-chart-container');
        if (container) {

            // Remove all inline widths before applying styles
            container.querySelectorAll('table, table *').forEach(el => {
                el.style.width = '';
                el.removeAttribute('width');
            });

            container.querySelectorAll('div[id^="chart_"]').forEach(el => {
                el.style.width = '';
                el.removeAttribute('width');
            });

            // Special case for Accountability_Framework_Tracking
            if (sectionClasses.includes('Accountability_Framework_Tracking')) {
                const tables = container.querySelectorAll('table');
                if (tables.length > 0) {
                    tables.forEach(tbl => {
                        tbl.style.display = 'inline-block';
                        tbl.style.width = '48%';
                        tbl.style.verticalAlign = 'top';
                        tbl.style.marginRight = '1%';
                    });
                }
            }
            if (sectionClasses.includes('DfE_Digital_Standard') ||
                sectionClasses.includes('DfE_Cyber_Governance_Code_of_Practice')) {

            }
            // Default case for other sections
            else {
                const table = container.querySelector('table');
                const chartDiv = container.querySelector('div[id^="chart_"]');

                if (table && chartDiv) {
                    const wrapperTable = doc.createElement('table');
                    wrapperTable.style.width = '100%';
                    wrapperTable.style.borderCollapse = 'collapse';

                    const wrapperRow = doc.createElement('tr');

                    const tableCell = doc.createElement('td');
                    tableCell.style.width = '45%';
                    tableCell.style.verticalAlign = 'top';
                    tableCell.appendChild(table);

                    const chartCell = doc.createElement('td');
                    chartCell.style.width = '40%';
                    chartCell.style.verticalAlign = 'top';
                    chartCell.appendChild(chartDiv);

                    wrapperRow.appendChild(tableCell);
                    wrapperRow.appendChild(chartCell);
                    wrapperTable.appendChild(wrapperRow);

                    container.innerHTML = '';
                    container.appendChild(wrapperTable);
                }
            }
        }
    });

    return doc.body.innerHTML;
}

function cleanReportHtml(reportHtml) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(reportHtml, 'text/html');


    // 2. Add width: 80% to nested tables
    doc.querySelectorAll('table.nested-table').forEach(table => {
        let style = table.getAttribute('style') || '';
        // Remove any existing width
        style = style.replace(/width\s*:\s*[^;]+;/i, '');
        style += ' width: 100%;';
        table.setAttribute('style', style.trim());
    });

    return doc.body.innerHTML;
}

function checkvalidUser(userId, reportCreatedUserId, reportId) {
    if (userId != reportCreatedUserId && (reportId)) {
        messageDisplay('You are not the creator of this report, therefore you do not have permission to edit it', 'error');
        return false;
    }

    return true;
}

function sendToOrgAdminForReview(id = null) {
    var url = window.location.href;
    let urlParams = new URLSearchParams(new URL(url).search);
    let reportId = urlParams.get("id");
    var reportStatus = jQuery('#jform_reportStatus').val();

    if (reportStatus != 'DPO_Finalised') {
        messageDisplay('Please update the status to "Dpo Finalized" to forward the file for review.', 'warning');
        return false;
    }

    let editorContent = tinymce.activeEditor.getContent();
    var adminIds = jQuery('#admin-multiselect').val();

    if (adminIds.length < 1) {
        alert('Please select at least one admin before proceeding.');
        return false;
    }
    jQuery.ajax({
        url: Joomla.getOptions('system.paths').base + "/index.php?option=com_dpe&task=annualreports.sendToDpoForReview",
        type: 'POST',
        data: { 'url': url, 'to': 'Admin', 'id': reportId, 'reportStatus': reportStatus, 'editorContent': editorContent, 'adminIds': adminIds },
        dataType: 'json',
        headers: { 'X-CSRF-Token': Joomla.getOptions('csrf.token', '') },
        beforeSend: function () {
            jQuery("#ajax-preloader").show(); // Show preloader before request
        },
        success: function (response) {
            try {

                if (response.data.success) {
                    messageDisplay(response.data.message, 'success');
                    $('#popup-container').fadeOut();
                }
            } catch (error) {
                // console.error(error);
                messageDisplay(error, 'warning');
            }
        },
        complete: function () {
            jQuery("#ajax-preloader").fadeOut(); // Hide preloader after request
        },
        error: function (xhr, status, error) {
            messageDisplay(error, 'warning');
        }
    });
}

function sendToOrgAdmin(id = null) {

    var reportStatus = jQuery('#jform_reportStatus').val();

    if (reportStatus != 'DPO_Finalised') {
        messageDisplay('Please update the status to "Dpo Finalized" to forward the file for review.', 'warning');
        return false;
    }
    saveReportData();

    var url = window.location.href;
    let urlParams = new URLSearchParams(new URL(url).search);
    let reportId = urlParams.get("id");
    var reportStatus = jQuery('#jform_reportStatus').val();

    if (reportStatus != 'DPO_Finalised') {
        messageDisplay('Please update the status to "Dpo Finalized" to forward the file for review.', 'warning');
        return false;
    }

    let editorContent = tinymce.activeEditor.getContent();

    setTimeout(function () {
        jQuery.ajax({
            url: Joomla.getOptions('system.paths').base + "/index.php?option=com_dpe&task=annualreports.getAdminList",
            type: 'POST',
            data: { 'url': url, 'id': reportId },
            dataType: 'json',
            headers: { 'X-CSRF-Token': Joomla.getOptions('csrf.token', '') },
            success: function (response) {
                try {
                    const admins = response?.data?.list || [];
                    const $select = $('#admin-multiselect');

                    $select.empty(); // Clear previous options
                    $select.append('<option value="all">Send to All</option>');

                    // Populate options
                    const seen = new Set(); // To remove duplicates
                    admins.forEach(admin => {
                        if (!seen.has(admin.id)) {
                            seen.add(admin.id);
                            $select.append(`<option value="${admin.id}">${admin.name} (${admin.email})</option>`);
                        }
                    });

                    $select.chosen('destroy').chosen({ width: '100%' });
                    $('#popup-container').fadeIn(); // Show the popup
                    setTimeout(function () {
                        jQuery('div#admin_multiselect_chosen').eq(1).hide();
                    }, 2000);

                } catch (error) {
                    messageDisplay(error, 'warning');
                }
            },
            error: function (xhr, status, error) {
                messageDisplay(error, 'warning');
            }
        }); return false;

    }, 1000)


}
document.addEventListener("DOMContentLoaded", function () {

    setTimeout(function () {
        const form = document.getElementById("annualreporttmp");

        // Store initial form data in session storage
        sessionStorage.setItem("initialFormData", JSON.stringify(getFormData(form)));
    }, 1500);

    // Button click event listener
    document.getElementById("downloadImage").addEventListener("click", function () {
        if (isFilterChanged(document.getElementById("annualreporttmp"))) {

            var url = window.location.href;
            let urlParams = new URLSearchParams(new URL(url).search);
            let reportId = urlParams.get("id");
            var id = jQuery('#report_id').val();

            if (((id == '') || (id == null)) || (reportId == '')) {
                messageDisplay('The report has not been saved yet. Please save the report first.', 'warning');
                return false;
            } else {


                messageDisplay('Filters have changed. Please save the form before proceeding.', 'warning');
                return false;
            }


        } else {
            getPDFreport();
        }
    });
});

// Function to get all form data
function getFormData(form) {
    let data = {};

    form.querySelectorAll("input, select, textarea").forEach((field) => {
        if (field.type === "checkbox") {
            data[field.name] = field.checked;
        } else {
            data[field.name] = field.value;
        }
    });

    return data;
}

// Function to compare new form data with stored session data
function isFilterChanged(form) {
    let initialData = JSON.parse(sessionStorage.getItem("initialFormData"));
    let newData = getFormData(form);

    return JSON.stringify(initialData) !== JSON.stringify(newData);
}

function parseDate(str) {
    const [dd, mm, yyyy] = str.split('-');
    return new Date(`${yyyy}-${mm}-${dd}`); // Format: yyyy-mm-dd
}