window.addEventListener('DOMContentLoaded', function() {
    var toggleBulk = function() {
        document.getElementById('bulk_actions').style.display = document.getElementsByName('boxchecked')[0].value === '0' ? 'none' : 'block';
    }

    document.getElementsByName('checkall-toggle')[0].addEventListener('click', toggleBulk);

    var elements = document.getElementsByName('cid[]');
    for (var i = 0; i < elements.length; i++) {
        elements[i].addEventListener('click', toggleBulk);
    }
});