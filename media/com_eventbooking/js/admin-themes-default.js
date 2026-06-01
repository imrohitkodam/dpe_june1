(function (document, Joomla) {
    document.addEventListener('DOMContentLoaded', function(){
        document.getElementById('btn-install-theme').addEventListener('click', function () {
            const form = document.adminForm;
            if (form.theme_package.value === "") {
                alert(Joomla.JText._('EB_CHOOSE_THEME'));
                return;
            }
            form.task.value = 'install';
            form.submit();
        });
    });
})(document, Joomla);