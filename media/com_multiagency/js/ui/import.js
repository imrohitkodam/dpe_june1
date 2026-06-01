import { Base } from "../services/base";
import { METHODS } from "http";

export class Import {

    constructor(elementId, userImport) {
        let me = this;
        this.elementId = elementId;
        this.userImport = userImport;

        let r = new Resumable({
            target: 'index.php?option=com_multiagency&task=userimport.uploadCSV&tmpl=component&agency', fileType: ["csv"], maxFiles: 1, maxFileSize: 50 * 1024 * 1024, chunkSize: 1 * 1024 * 1024, setChunkTypeFromFile: 'application/json'
        });

        this.setResumable(r);

        // Resumable.js isn't supported, fall back on a different method
        // if (!r.support) location.href = '/some-old-crappy-uploader';
        r.assignBrowse(document.getElementById(this.elementId));
        r.on('fileSuccess', me.onFileSuccess.bind(this));
        r.on('fileProgress', me.onFileProgress.bind(this));
        r.on('fileAdded', me.onFileAdded.bind(this));
        r.on('fileRetry', me.onFileRetry.bind(this));
        r.on('fileError', me.onFileError.bind(this));
        r.on('uploadStart', me.onUploadStart.bind(this));
        r.on('complete', me.onComplete.bind(this));
        r.on('progress', me.onProgress.bind(this));
        r.on('error', me.onError.bind(this));
        r.on('pause', me.onPause.bind(this));
        r.on('cancel', me.onCancel.bind(this));
    }

    /***
     * Set result
     */
    setResult() {
        this.result = [];
    }

    /**
     * Get result
     */
    getResult() {
        return this.result;
    }

    /**
     * Set resumable obj ref
     */
    setResumable(r) {
        this.resumable = r;
    }

    /**
     * Get resumable Obj ref
     */
    getResumable() {
        return this.resumable;
    }

    setQuery()
    {
        let me = this;
        let r = me.getResumable();
        r.query ="client_id=1";
        this.setResumable(r);
    }

    onFileProgress(file) {
        console.debug('fileProgress', file);
    }

    onFileSuccess(file, message) {
        let me = this;
        let res = JSON.parse(message);

        if(res.success == true)
        {
            let fileName = res.data.fileUpload.fileName;

            // DPE HACK start
            // Only call importUsers for user import (userImport = 1)
            // For school import (userImport = 0), let the custom event handler handle it
            if (me.userImport === 1) {
                me.importUsers(fileName);
            }
            // DPE HACK END

        }
        else
        {
            jQuery("#infoText").html("");
            jQuery("#infoText").addClass("alert alert-error").show();
            jQuery("#infoText").append(res.message + "<br/>");
        }
    }

    importUsers(fileName) {
        let me = this;
        let baseObj = new Base;
        let agency = jQuery('.selectAgency option:selected').val();
        let notify_user = 0;

        if (jQuery('#notify_user_import').is(":checked")) {
            notify_user = 1;
        }

        baseObj.url = "index.php?option=com_multiagency&task=userimport.importUsers&tmpl=component&agency";
        baseObj.data = { fileName: fileName, client_id: agency, notify_user: notify_user };
        baseObj.config = {};

        let cb = function (error, res) {
            if (error) {
                console.log(error);
            }
            else if (res) {
                if (res.success === true)
                {
                    me.getResult().push(res.data);

                    if (res.data.feof == false) {
                        me.importUsers(fileName);
                    }
                    else {
                        let result = me.getResult();

                        let log = new Object();
    
                        // Combine chunk upload result into single object
                        log.total_records = result[0].total_records;
                        log.alreadyAssigned = me.sum(result, 'alreadyAssigned');
                        log.updated = me.sum(result, 'updated');
                        log.already_exist = me.sum(result, 'already_exist');
                        log.bad_users = me.sum(result, 'bad_users');
                        log.miss_cols = me.sum(result, 'miss_cols');
                        log.missing_data = me.sum(result, 'missing_data');
                        log.new_users = me.sum(result, 'new_users');
                        log.newlyAssigned = me.sum(result, 'newlyAssigned');
    
                        me.showLog(log);

                        me.downloadLogLink(fileName);
                    }    
                }
                else
                {
                    jQuery("#infoText").html("");
                    jQuery("#infoText").addClass("alert alert-error").show();
                    jQuery("#infoText").append(res.message + "<br/>");
                }
            }
        }

        baseObj.post(cb);
    }

    /**
     * Link to download the CSV log
     */
    downloadLogLink(fileName) {
        let client_id = techjoomla.jQuery('.selectAgency option:selected').val();
        let link = "index.php?option=com_multiagency&task=userimport.downloadLog&tmpl=component&fileName=" + fileName + "&client_id=" + client_id;
        jQuery("#downloadCSVLog").show().attr("href", link);
    }

    /**
     * Show Log
     */
    showLog(log) {
        let me = this;
        jQuery("#infoText").html("");

        // Mandatory columns missing
        if (log.miss_cols >= 1) {
            jQuery("#infoText").addClass("alert alert-error");
            jQuery("#infoText").append(Joomla.Text._("COM_USER_CSV_IMPORT_COLUMN_MISSING") + "<br/>");
            return;
        }

        let logText = [];

        logText.push(me.sprintf(Joomla.Text._("COM_USER_MANAGEENROLLMENTS_IMPORT_TOTAL_ROWS_CNT_MSG"), log.total_records));

        if(log.alreadyAssigned)
        {
			logText.push(me.sprintf(Joomla.Text._("COM_USER_TITLE_MANAGEENROLLMENTS_IMPORT_ALREADY_ASSIGNED"), log.alreadyAssigned));
		}
        if(log.updated)
        {
            logText.push(me.sprintf(Joomla.Text._("COM_USER_TITLE_MANAGEENROLLMENTS_IMPORT_UPDATED"), log.updated));
        }
		if (log.new_users)
		{
			logText.push(me.sprintf(Joomla.Text._("COM_USER_TITLE_MANAGEENROLLMENTS_IMPORT_NEWLY_ASSIGNED"), log.new_users));
		}
		if (log.missing_data)
		{
			logText.push(me.sprintf(Joomla.Text._("COM_USER_MANAGEENROLLMENTS_MANDATORY_FIELDS"), log.missing_data));
		}

        logText.forEach(element => {
            jQuery("#infoText").addClass("alert alert-info");
            jQuery("#infoText").append(element + "<br/>");
        });
    }

    sprintf(constant, value = 0) {
        return constant.replace("%s", value);
    }

    sum(items, prop) {
        return items.reduce(function (a, b) {
            return a + b[prop];
        }, 0);
    }

    /**
     * Clear old messages and data
     */
    reset() {
        let me = this;
        jQuery("#downloadCSVLog").hide();
        jQuery("#file-name").html("");

        // Clear old data for new upload
        // 1. Hide info message block
        jQuery("#infoText").removeClass();
        jQuery("#infoText").html("");

        // 2. Clear result: Set results to blank array as it is new file uploaded;
        me.setResult();
    }

    onFileAdded(file, event) {
        let me = this;
        let schoolName = jQuery('.selectAgency option:selected').text();
        let client_id = jQuery('.selectAgency option:selected').val();
        me.reset();

        // Show selected file name
        jQuery("#file-name").html(file.fileName);

        // Validation
        let validation = this.validatate(file, 1);

        if (validation == false) {
            jQuery("#file-name").html('');
            return false;
        }

        let sure = window.confirm(me.sprintf(Joomla.Text._("COM_MULTIAGENCY_IMPORT_STAFF_CONFIRM"), schoolName));

        if (sure == true) {

            jQuery("#infoText").html("");
            // Show please wait message
            jQuery("#infoText").addClass("alert alert-warning").html(Joomla.JText._("COM_MULTIAGENCY_CSV_USER_IMPORTED")).show();
            this.getResumable().updateQuery({"client_id":client_id});
            this.getResumable().upload();
        } else {
            me.reset();
        }
    }

    onFileRetry(file) {
        console.debug('fileRetry', file);
    }

    onFileError(file, message) {
        console.debug('fileError', file, message);
    }

    onUploadStart() {
        console.debug('uploadStart');
    }

    onComplete() {
        console.debug('complete');
    }

    onProgress() {
        console.debug('progress');
    }

    onError(message, file) {
        console.debug('error', message, file);
    }

    onPause() {
        console.debug('pause');
    }

    onCancel() {
        console.debug('cancel');
    }

    validatate(thisfile, userImport) {

        // DPE HACK
        // Only validate agency selection for user import (userImport = 1)
        // Skip validation for school import (userImport = 0)
        if (userImport === 1) {
            let agency = techjoomla.jQuery('.selectAgency option:selected').val();

            if (techjoomla.jQuery.trim(agency) == '' || typeof (agency) == 'undefined') {
                alert(Joomla.JText._('COM_MULTIAGENCY_FORM_DESC_SELECT_AGENCY_ID'));
                techjoomla.jQuery("#tjlms-csv-upload").val('');
                return false;
            }
        }

        return true;
    }
}
