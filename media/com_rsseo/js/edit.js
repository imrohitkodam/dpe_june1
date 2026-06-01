function rsseo_show_modal() {
	var modal = document.getElementById('rsseoEditModal');
	var span = document.getElementsByClassName('rsseo-close')[0];
	
	modal.style.display = 'block';
	span.onclick = function() {
		modal.style.display = 'none';
	}
	
	window.onclick = function(event) {
		if (event.target == modal) {
			modal.style.display = 'none';
		}
	}
}

function rsseo_close_modal() {
	document.getElementById('rsseoEditModal').style.display = 'none';
}

function rsseo_new_meta() {
	var table = document.getElementById('customMeta');
	var rowid = Math.round(Math.random() * 100000);
	
	var row = document.createElement('tr');
	row.id = 'meta' + rowid;
	
	var cell1 = document.createElement('td');
	var cell2 = document.createElement('td');
	var cell3 = document.createElement('td');
	var cell4 = document.createElement('td');
	
	var input1 = document.createElement('input');
	input1.type = 'text';
	input1.name = 'jform[custom][name][]';
	input1.className = 'form-control';
	
	var input2 = document.createElement('input');
	input2.type = 'text';
	input2.name = 'jform[custom][content][]';
	input2.className = 'form-control';
	
	var select = document.createElement('select');
	select.name = 'jform[custom][type][]';
	select.size = '1';
	select.className = 'custom-select';
	
	var option1 = document.createElement('option');
	option1.text = document.getElementById('remtn').innerHTML;
	option1.value = 'name';
	
	var option2 = document.createElement('option');
	option2.text = document.getElementById('remtp').innerHTML;
	option2.value = 'property';
	
	select.appendChild(option1);
	select.appendChild(option2);
	
	cell1.appendChild(select);
	cell2.appendChild(input1);
	cell3.appendChild(input2);
	cell4.innerHTML = '<a href="javascript:void(0)" class="btn btn-danger" onclick="rsseo_remove_meta(\''+rowid+'\')">' + document.getElementById('red').innerHTML + '</a>';
	
	row.appendChild(cell1);
	row.appendChild(cell2);
	row.appendChild(cell3);
	row.appendChild(cell4);
	
	table.appendChild(row);
}

function rsseo_remove_meta(id) {
	var elem = document.getElementById('meta' + id);
    return elem.parentNode.removeChild(elem);
}

function rsseo_save_page(root) {
	var elements = document.getElementById('rsseo-frontend-edit-form').elements;
	var params	 = [];
	
	for (var i = 0; i < elements.length; i++) {
		if (elements[i].type == 'button') {
			continue;
		}
		
		params.push(elements[i].name + '=' + encodeURIComponent(elements[i].value));
	}
	
	if (params.length) {
		document.getElementById('rsseo-frontend-edit-loader').style.display = '';
		params.push('task=save');
		
		var xhttp;
		
		if (window.XMLHttpRequest) {
			xhttp = new XMLHttpRequest();
		} else {
			xhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}
		
		xhttp.open('POST', root + 'index.php?option=com_rsseo', true);
		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhttp.setRequestHeader("Content-length", params.length);
		xhttp.setRequestHeader("Connection", "close");
		
		xhttp.onreadystatechange = function() {
			if (xhttp.readyState == 4 && xhttp.status == 200) {
				document.getElementById('rsseo-frontend-edit-loader').style.display = 'none';
				document.getElementById('rsseo-frontend-edit-message').style.display = '';
				document.getElementById('rsseo-frontend-edit-message').innerHTML = xhttp.responseText;
				
				window.setTimeout(function() {
					document.getElementById('rsseo-frontend-edit-message').innerHTML = '';
					document.getElementById('rsseo-frontend-edit-message').style.display = 'none';
					rsseo_close_modal();
					document.location.reload();
				},2000);
				
			}
		}
		
		xhttp.send(params.join('&'));
	}
}