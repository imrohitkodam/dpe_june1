(function (document, Joomla) {
    document.addEventListener('DOMContentLoaded', function(){
        document.getElementById('btn-install-plugin').addEventListener('click', function () {
            const form = document.adminForm;

            if (form.plugin_package.value === "") {
                alert(Joomla.JText._('EB_CHOOSE_PLUGIN'));
                return;
            }

            form.task.value = 'install';
            form.submit();
        });
    });
})(document, Joomla);