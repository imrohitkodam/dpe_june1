/*
 * @package    Com_Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2022 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

calculateNumeriFieldData = (configFieldNames, configColorCombination, processdFieldName, fieldId, defaultText) => {


    setTimeout(function () {

        let fieldName = new Array();
        let jsonFieldName = configFieldNames.replace(/-/g, '_');


        let colorcombination = configColorCombination;
        let colorcombinations = Object.keys(colorcombination).map(function (key) { return colorcombination[key]; });
        let calculation = [];
        let countData = '';
        let fieldReplacewithValue = '';
        let nonValue = [];




        jQuery.each(processdFieldName, function (index, value) {
            fieldName.push(value);
        })


        // Calculate the value and display the data
        jQuery.each(fieldName, function (index, value) {

            if (value) {
                // Fallback for fields without standard ID wrapper (like DPE Checklist)
                if (jQuery('#jform_' + value).length === 0) {
                    var $fallbackInput = jQuery('input[name="jform[' + value + ']"]:checked');
                    if ($fallbackInput.length > 0) {
                        calculation[index] = $fallbackInput.val();
                        return; // Continue to next field
                    } else if (jQuery('input[name="jform[' + value + ']"]').length > 0) {
                        // Field exists but nothing checked
                        nonValue[index] = 0;
                        return;
                    }
                }

                var typeAttributeValueRadio = jQuery('#jform_' + value).attr('type');
                var listTypeValue = (jQuery('#jform_' + value).prop('tagName')) ? jQuery('#jform_' + value).prop('tagName').toLowerCase() : 'radio';

                if (listTypeValue == 'select') {
                    if (jQuery('#jform_' + value).parent().parent().attr('style') != 'display: none;') {
                        calculation[index] = (jQuery('#jform_' + value).find("option:selected").val()) ? jQuery('#jform_' + value).find("option:selected").val() : 0;
                    }
                    else {
                        nonValue[index] = 0;
                    }

                }
                else if (listTypeValue == 'fieldset') {
                    var radioIDs = jQuery('#jform_' + value + ' div').length - 1;

                    for (i = 0; i < radioIDs; i++) {
                        var radioElement = jQuery('#jform_' + value + i);
                        var notHidden = radioElement.parent().parent().parent().parent().parent().attr('style');

                        if ((radioElement.is(':checked')) && ((notHidden !== 'display: none;'))) {

                            calculation[index] = radioElement.val();

                        }
                        else {
                            nonValue[index] = 0;
                        }
                    }

                }
                else if (listTypeValue == 'input') {
                    var $input = jQuery('#jform_' + value);
                    var $formGroup = $input.closest('.form-group');
                    if ($input.length && $input.attr('type') === 'hidden') {

                        var hasNumericSpan = $input.closest('.form-group').find('span.numericcalculation').length > 0;

                        if (hasNumericSpan) {
                            calculation[index] = $input.val();
                        }
                    }

                }
            }
        });

        jQuery.each(calculation, function (index, value) {
            // Check if value is numeric, otherwise default to 0 (handles 'na', empty, undefined)
            let valToUse = value;
            if (valToUse == undefined || isNaN(valToUse) || valToUse === '') {
                valToUse = 0;
            }

            fieldReplacewithValue = jsonFieldName.replace(fieldName[index], valToUse);
            jsonFieldName = fieldReplacewithValue;
        })

        jQuery.each(nonValue, function (index, value) {
            fieldReplacewithValue = jsonFieldName.replace(fieldName[index], value);
            jsonFieldName = fieldReplacewithValue;
        })

        jsonFieldName = jsonFieldName.replace('{', '');
        jsonFieldName = jsonFieldName.replace('}', '');
        total = (jsonFieldName != undefined) ? safeEval(jsonFieldName) : 0;

        if (isNaN(total)) {
            total = 0;

        }

        var conditionMet = false;
        jQuery.each(colorcombinations, function (index, value) {
            var targetId = (typeof fieldId === 'object') ? fieldId.id : fieldId;

            if (!conditionMet && (total >= value.min) && (total <= value.max)) {

                jQuery('#' + targetId).val(total);
                jQuery('#' + targetId + '_span').text(value.value);
                jQuery('#' + targetId + '_span').css('color', value.color);
                jQuery('#' + targetId + '_span').addClass('subformnumericcalculation');
                conditionMet = true; // Set the flag to true to halt further execution
            } else if (!conditionMet && (total === 0) && (value.min != 0)) {

                jQuery('#' + targetId + '_span').text(defaultText);
                jQuery('#' + targetId + '_span').css({
                    'font-size': '',
                    'color': '#ffffff',
                    'font-weight': '',
                    'margin-left': '0px'
                });
                conditionMet = true; // Set the flag to true to halt further execution
            }
        });



    }, 500)


}

calculateNumeriFieldDataSubform = (configColorcombinations, processdFieldName, configFieldNames, currentFieldName, defaultText) => {
    let subFormCount = jQuery('.subform-repeatable-group').length;
    let subformFieldName = jQuery('.subform-repeatable-group').attr('data-base-name');
    let fieldName = new Array();
    let symbol = new Array();
    let colorcombination = configColorcombinations;
    let colorcombinations = Object.keys(colorcombination).map(function (key) { return colorcombination[key]; });
    let total = '';
    let fieldReplacewithValue = '';
    let calculationFieldName = currentFieldName;

    jQuery.each(processdFieldName, function (index, value) {
        fieldName.push(value);
    })
    for (let indexCount = 0; indexCount < subFormCount; indexCount++) {
        let calculation = [];
        let symbolValue = '';
        var countData = '';
        var nonValue = [];

        jQuery.each(fieldName, function (index, value) {

            var listTypeValue = jQuery('#jform_' + subformFieldName + '__' + subformFieldName + indexCount + '__' + value).prop('tagName').toLowerCase();

            if (listTypeValue == 'select') {

                var fieldriskValue = jQuery('#jform_' + subformFieldName + '__' + subformFieldName + indexCount + '__' + value).chosen().val();

                if (jQuery('#jform_' + subformFieldName + '__' + subformFieldName + indexCount + '__' + value).parent().parent().attr('style') != 'display: none;') {
                    calculation[index] = (fieldriskValue) ? fieldriskValue : 0;
                } else {

                    nonValue[index] = 0;
                }


            } else {
                var radioIDs = jQuery('#jform_' + subformFieldName + '__' + subformFieldName + indexCount + '__' + value + ' div').length - 1;
                for (i = 0; i < radioIDs; i++) {
                    var radioElement = jQuery('#jform_' + subformFieldName + '__' + subformFieldName + indexCount + '__' + value + i);
                    var notHidden = radioElement.parent().parent().parent().parent().parent().attr('style');

                    if ((radioElement.is(':checked')) && ((notHidden !== 'display: none;'))) {

                        calculation[index] = radioElement.val();

                    }
                    else {
                        nonValue[index] = 0;
                    }
                }
            }
        });

        let jsonFieldName = configFieldNames;

        jQuery.each(calculation, function (index, value) {
            let valToUse = value;
            if (valToUse == undefined || isNaN(valToUse) || valToUse === '') {
                valToUse = 0;
            }
            fieldReplacewithValue = jsonFieldName.replace(fieldName[index], valToUse);
            jsonFieldName = fieldReplacewithValue;
        })

        jQuery.each(nonValue, function (index, value) {
            fieldReplacewithValue = jsonFieldName.replace(fieldName[index], value);
            jsonFieldName = fieldReplacewithValue;
        })

        jsonFieldName = jsonFieldName.replace('{', '');
        jsonFieldName = jsonFieldName.replace('}', '');

        total = (jsonFieldName != undefined) ? eval(jsonFieldName) : 0;
        var conditionMet = false;

        jQuery.each(colorcombinations, function (index, value) {
            if (!conditionMet && (total >= value.min) && (total <= value.max)) {
                jQuery('#jform_' + subformFieldName + '__' + subformFieldName + indexCount + '__' + calculationFieldName + '_span').html(value.value);
                jQuery('#jform_' + subformFieldName + '__' + subformFieldName + indexCount + '__' + calculationFieldName).val(total);
                jQuery('#jform_' + subformFieldName + '__' + subformFieldName + indexCount + '__' + calculationFieldName + '_span').css('color', value.color);
                jQuery('#jform_' + subformFieldName + '__' + subformFieldName + indexCount + '__' + calculationFieldName + '_span').addClass('subformnumericcalculation');
                conditionMet = true; // Set the flag to true to halt further execution
            }
            else if (!conditionMet && (total === 0) && (value.min != 0)) {
                jQuery('#' + subformFieldName + indexCount).val("0");
                jQuery('#' + subformFieldName + indexCount + '_span').html(defaultText);
                conditionMet = true; // Set the flag to true to halt further execution
            }
        });
    }
}

calculationOnchangeSubform = (event, colorcombinations, processdFieldName, configFieldNames, currentFieldName, defaultText) => {
    let colorValues = colorcombinations;
    let colorValue = Object.keys(colorValues).map(function (key) { return colorValues[key]; });
    let fieldName = new Array();
    let newId = '';
    let subformFieldName = jQuery('.subform-repeatable-group').attr('data-base-name');
    let jsonFieldName = configFieldNames;
    let fieldReplacewithValue = '';
    let calculation = [];
    var nonValue = [];
    let countData = '';
    let calculationFieldName = currentFieldName;
    var listValue = '';
    jQuery.each(processdFieldName, function (index, value) {
        fieldName.push(value);
    })

    newId = jQuery(event.target).closest('.subform-repeatable-group').attr('data-group');

    var fieldIndex = (newId) ? newId[newId.length - 1] : '';
    var calculationFieldId = (subformFieldName) ? 'jform_' + subformFieldName + '__' + subformFieldName + 'X__' + calculationFieldName : '';
    var existingCalculationFieldId = (subformFieldName) ? 'jform_' + subformFieldName + '__' + subformFieldName + fieldIndex + '__' + calculationFieldName : '';

    jQuery.each(fieldName, function (index, value) {

        // var fieldValue = jQuery('#jform_'+ subformFieldName +'__'+ subformFieldName +fieldIndex+'__'+ value ).chosen().val();
        // calculation[index] = (fieldValue)?fieldValue:0;   

        var listTypeValue = (jQuery('#jform_' + subformFieldName + '__' + subformFieldName + fieldIndex + '__' + value).length > 0) ? jQuery('#jform_' + subformFieldName + '__' + subformFieldName + fieldIndex + '__' + value).prop('tagName').toLowerCase() : '';
        listValue = listTypeValue;

        if (listTypeValue == 'select') {
            var fieldriskValue = jQuery('#jform_' + subformFieldName + '__' + subformFieldName + fieldIndex + '__' + value).chosen().val();

            if (jQuery('#jform_' + subformFieldName + '__' + subformFieldName + fieldIndex + '__' + value).parent().parent().attr('style') != 'display: none;') {
                calculation[index] = (fieldriskValue) ? fieldriskValue : 0;
            } else {

                nonValue[index] = 0;
            }
        }
        else {
            var subformdataid = jQuery(event.target).closest('.subform-repeatable-group').attr('data-group');

            var nameAttribute = jQuery('.subform-repeatable:first').attr('name').replace('jform[', '').replace(']', '');

            if (subformdataid != undefined) {
                var subformCount = subformdataid.replace(nameAttribute, '');
            }

            if (jQuery('#jform_' + subformFieldName + '__' + subformFieldName + subformCount + fieldIndex + '__' + value).length != 0) {
                var radioIDs = jQuery('#jform_' + subformFieldName + '__' + subformFieldName + subformCount + fieldIndex + '__' + value + ' div').length - 1;
            }
            else {
                var radioIDs = jQuery('#jform_' + subformFieldName + '__' + subformFieldName + 'X' + fieldIndex + '__' + value + ' div').length - 1;

            }

            if (radioIDs > 0) {
                for (i = 0; i < radioIDs; i++) {
                    var radioElement = jQuery('#jform_' + subformFieldName + '__' + subformFieldName + subformCount + fieldIndex + '__' + value + i);
                    var notHidden = radioElement.parent().parent().parent().parent().parent().attr('style');

                    if ((radioElement.is(':checked')) && ((notHidden !== 'display: none;'))) {

                        calculation[index] = radioElement.val();
                    }
                    else {
                        nonValue[index] = 0;
                    }
                }
            }
        }

    });

    if (calculation.length > 0) {


        jQuery.each(calculation, function (index, value) {
            let valToUse = value;
            if (valToUse == undefined || isNaN(valToUse) || valToUse === '') {
                valToUse = 0;
            }
            fieldReplacewithValue = jsonFieldName.replace(fieldName[index], valToUse);
            jsonFieldName = fieldReplacewithValue;
        })

        jQuery.each(nonValue, function (index, value) {
            fieldReplacewithValue = jsonFieldName.replace(fieldName[index], value);
            jsonFieldName = fieldReplacewithValue;
        })

        jsonFieldName = jsonFieldName.replace('{', '');
        jsonFieldName = jsonFieldName.replace('}', '');
        total = (jsonFieldName != undefined) ? safeEval(jsonFieldName) : 0;

        var conditionMet = false;
        jQuery.each(colorValue, function (index, value) {

            if (listValue == 'select') {
                if (!conditionMet && (total >= value.min) && (total <= value.max)) {
                    jQuery('.subform-repeatable-group[data-group="' + newId + '"]').find('input[type=hidden][id$=' + calculationFieldId + ']').val(total);
                    jQuery('.subform-repeatable-group[data-group="' + newId + '"]').find('span[id$=' + calculationFieldId + '_span]').text(value.value);

                    jQuery('.subform-repeatable-group[data-group="' + newId + '"]').find('input[type=hidden][id$=' + existingCalculationFieldId + ']').val(total);
                    jQuery('.subform-repeatable-group[data-group="' + newId + '"]').find('span[id$=' + existingCalculationFieldId + '_span]').text(value.value);

                    jQuery('.subform-repeatable-group[data-group="' + newId + '"]').find('span[id$=' + existingCalculationFieldId + '_span]').css('color', value.color);
                    jQuery('.subform-repeatable-group[data-group="' + newId + '"]').find('span[id$=' + existingCalculationFieldId + '_span]').addClass('subformnumericcalculation');
                    jQuery('.subform-repeatable-group[data-group="' + newId + '"]').find('span[id$=' + calculationFieldId + '_span]').css('color', value.color);
                    jQuery('.subform-repeatable-group[data-group="' + newId + '"]').find('span[id$=' + calculationFieldId + '_span]').addClass('subformnumericcalculation');
                    conditionMet = true; // Set the flag to true to halt further execution
                }
                else if (!conditionMet && total == 0 || total < 0) {
                    jQuery('.subform-repeatable-group[data-group="' + newId + '"]').find('span[id$=' + calculationFieldId + '_span]').text(defaultText);
                    jQuery('.subform-repeatable-group[data-group="' + newId + '"]').find('span[id$=' + existingCalculationFieldId + '_span]').text(defaultText);
                    jQuery('.subform-repeatable-group[data-group="' + newId + '"]').find('span[id$=' + calculationFieldId + '_span]').css({ 'font-size': '', 'color': 'white', 'font-weight': '', 'border': '0px solid #ccc !important', 'margin-left': '0px' });
                    jQuery('.subform-repeatable-group[data-group="' + newId + '"]').find('span[id$=' + existingCalculationFieldId + '_span]').css({ 'font-size': '', 'color': 'white', 'font-weight': '', 'border': '0px solid #ccc !important', 'margin-left': '0px' });
                    conditionMet = true; // Set the flag to true to halt further execution
                }
            }
            else {
                if ((total >= value.min) && (total <= value.max)) {

                    var nextControlsDiv = jQuery('#' + event.target.id).closest('.control-group').next('.control-group');
                    var numericCalculationSpan = nextControlsDiv.find('.numericcalculation');

                    var containerDiv = jQuery(event.target).closest('.subform-repeatable-group');
                    var numericCalculationSpan = containerDiv.find('.numericcalculation');
                    var nId = numericCalculationSpan.attr('id');
                    jQuery('#' + nId).text(value.value);
                    jQuery('#' + nId).css('color', value.color);
                    jQuery('#' + nId).addClass('subformnumericcalculation');
                    conditionMet = true; // Set the flag to true to halt further execution
                }
            }

        })
    }
}

function safeEval(expression) {
    if (!expression || typeof expression !== 'string') return 0;

    // Clean up line breaks and whitespace
    let cleanExpression = expression.replace(/[\r\n]+/g, ' ').trim();
    cleanExpression = cleanExpression.replace(/\s+/g, ' ');

    // Remove invalid characters if needed (optional strict sanitization)
    // cleanExpression = cleanExpression.replace(/[^0-9+\-*/().\s]/g, '');

    // Check if it has at least one number or variable
    if (!/[0-9a-zA-Z]/.test(cleanExpression)) {
        console.warn('Expression contains no valid operands:', cleanExpression);
        return 0;
    }

    try {
        return math.evaluate(cleanExpression);
    } catch (e) {
        console.warn('Invalid expression, trying partial recovery:', cleanExpression);

        // Try to recover by trimming trailing operators like +, -, *, /
        const recovered = cleanExpression.replace(/[\+\-\*/\s]+$/, '');
        if (recovered && /[0-9]/.test(recovered)) {
            try {
                return math.evaluate(recovered);
            } catch (e2) {
                console.error('Recovery failed:', recovered, e2);
                return 0;
            }
        }

        return 0;
    }
}
