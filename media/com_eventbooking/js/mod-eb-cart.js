(function (document, Joomla) {
	document.addEventListener('onEBAfterCartChange', function () {
        Joomla.request({
            url: Joomla.getOptions('system.paths').root + '/index.php?option=com_eventbooking&view=cart&layout=module&format=raw',
            method: 'POST',
            onSuccess: function (resp) {
                document.getElementById('cart_result').innerHTML = resp;
            },
            onError: function (error) {
                alert(error.statusText);
            }
        });
	});
})(document, Joomla);