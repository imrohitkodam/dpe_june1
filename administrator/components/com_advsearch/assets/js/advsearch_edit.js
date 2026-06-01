function getUrlVars()
{
	var vars = {};
	var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
	vars[key] = value;
	});
	return vars;
}
$('document').ready(function()
{

	/*$("#client_name").change(function()
	{*/

		$("#fields").empty();

		var client = $('#client_name').val();

		if(client == 0)
		{
			$("#types").empty();
			$("#fields").empty();
			return false;

		}
		var id = getUrlVars()["id"];

		$.ajax
			({
				type : 'POST',
                url: 'index.php?option=com_advsearch&task=get_types&id='+id+'&client='+client,
                success: function(data)
                {

					if(data)
					{
						$("#types").empty();
						$("#types").append(data);


						var type = $('#select_types').val();
						var client = $('#client_name').val();
						$.ajax
							({
								type : 'POST',
								url: 'index.php?option=com_advsearch&task=getFields&type='+type+'&client='+client+'&id='+id,
								success: function(data)
								{
									if(data == 0)
									{
										$("#fields").empty();
										$("#fields").append('<b>You have already created Search Indexer for this type. Please check Search Indexer list.</b>');
									}
									else
									{
										$("#fields").empty();
										$("#fields").append(data);
									}
								}
							});
					}

				}

            });



	$("#client_name").change(function()
	{

		$("#fields").empty();

		var client = $('#client_name').val();


		if(client == 0)
		{
			$("#types").empty();
			$("#fields").empty();
			return false;

		}

		$.ajax
			({
				type : 'POST',
                url: 'index.php?option=com_advsearch&task=get_types&client='+client,
                success: function(data)
                {

					if(data)
					{

						$("#types").empty();
						$("#types").append(data);
					}

				}

            });

	});

	$("#types").change(function()
	{

		var type = $('#select_types').val();
		var client = $('#client_name').val();
		var id = getUrlVars()["id"];
		if(type == 0)
		{
			$("#fields").empty();
			return false;

		}
		$.ajax
			({
				type : 'POST',
                url: 'index.php?option=com_advsearch&task=getFields&type='+type+'&client='+client+'&id='+id,
                success: function(data)
                {

					if(data == 0)
					{
						$("#fields").empty();
						$("#fields").append('<b>You have already created Search Indexer for this type. Please check Search Indexer list.</b>');


					}
					else
					{
						//alert(data);
						$("#fields").empty();
						$("#fields").append(data);

					}

				}

            });
	});



});
