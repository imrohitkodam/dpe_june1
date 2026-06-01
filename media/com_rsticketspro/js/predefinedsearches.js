window.addEventListener('DOMContentLoaded', function() {
    var toggleDelete = function() {
        var btn = document.getElementById('rst_delete_btn');

        if (document.getElementsByName('boxchecked')[0].value === '0') {
            btn.setAttribute('disabled', 'disabled');
        } else {
            btn.removeAttribute('disabled');
        }
    }

    document.getElementsByName('checkall-toggle')[0].addEventListener('click', toggleDelete);

    var elements = document.getElementsByName('cid[]');
    for (var i = 0; i < elements.length; i++) {
        elements[i].addEventListener('click', toggleDelete);
    }
});