jQuery.noConflict();

if (typeof RSTicketsPro === 'undefined') {
    var RSTicketsPro = {};
}

RSTicketsPro.exportCSV = {
    totalItems : 0,
    baseUrl : '',
    ordering : '',
    direction : '',

    getProgressBarObject: function() {
        return document.getElementById('com-rsticketspro-export-progress')
    },

    setProgress: function (current) {
        var bar = document.querySelector('.com-rsticketspro-bar');
        if (bar) {
            var currentProgress = (current * 100) / this.totalItems;
            bar.style.width = currentProgress + '%';
            bar.innerText = parseInt(currentProgress) + '%';
        }
    },

    setCSV : function(from, fileHash) {
        var progressBar = this.getProgressBarObject();
        if (this.totalItems > 0 && from >= this.totalItems) {
            progressBar.style.display = 'none';
            window.location.assign(Joomla.getOptions('system.paths').base + '/index.php?option=com_rsticketspro&task=tickets.exportcsv&filehash=' + fileHash);
        } else {
            var xmlHttp = new XMLHttpRequest();
            var params = [
                'option=com_rsticketspro',
                'task=' + 'tickets.writecsv',
                'start=' + from,
                'ordering=' + this.ordering,
                'direction=' + this.direction,
                'filehash=' + fileHash
            ];
            xmlHttp.open('POST', Joomla.getOptions('system.paths').base + '/index.php?option=com_rsticketspro', true);
            xmlHttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xmlHttp.send(params.join('&'));

            xmlHttp.onreadystatechange = function() {
                if (this.readyState === 4) {
                    try {
                        var data = JSON.parse(this.responseText);
                    } catch (err) {
                        data = {'success': false, 'response': err};
                    }
                    if (data.success === true) {
                        RSTicketsPro.exportCSV.setProgress(data.response.newFrom);

                        setTimeout(function(){
                            RSTicketsPro.exportCSV.setCSV(data.response.newFrom, data.response.fileHash);
                        },700);
                    } else {
                        progressBar.style.display = 'none';
                        Joomla.renderMessages({'error': [data.response]});
                    }
                }
            };

            Joomla.removeMessages();
            progressBar.style.display = 'block';
        }
    }
}
