var RSTicketsProConditions = {
	typeOptions: [
		{value: '', text: Joomla.JText._('RST_PLEASE_SELECT')},
		{value: 'department', text: Joomla.JText._('RST_DEPARTMENT')},
		{value: 'subject', text: Joomla.JText._('RST_TICKET_SUBJECT')},
		{value: 'message', text: Joomla.JText._('RST_TICKET_MESSAGE')},
		{value: 'priority', text: Joomla.JText._('RST_PRIORITY')},
		{value: 'status', text: Joomla.JText._('RST_TICKET_STATUS')},
		{value: 'custom_field', text: Joomla.JText._('RST_CUSTOM_FIELD')}
	],
	conditionOptions: [
		{value: '', text: Joomla.JText._('RST_PLEASE_SELECT')},
		{value: 'eq', text: Joomla.JText._('RST_IS_EQUAL')},
		{value: 'neq', text: Joomla.JText._('RST_IS_NOT_EQUAL')},
		{value: 'like', text: Joomla.JText._('RST_IS_LIKE')},
		{value: 'notlike', text: Joomla.JText._('RST_IS_NOT_LIKE')}
	],
	connectorOptions: [
		{value: 'AND', text: Joomla.JText._('RST_AND')},
		{value: 'OR', text: Joomla.JText._('RST_OR')}
	],
	addSpacing: function(childContainer, text) {
		if (!text) {
			text = '&nbsp;';
		}

		childContainer.append(jQuery('<span>').html(text));
	},
	getFormControlName: function(name) {
		return 'jform[' + name + '][]';
	},
	changeSelectCustomField: function() {
		var td = this.parentNode.parentNode;
		var children = td.childNodes;
		var responseSpan = false;
		var selectCondition;
		for (var i=0; i<children.length; i++)
		{
			if (children[i].className === 'responseSpan')
			{
				responseSpan = children[i];
				continue;
			}
			if (children[i].name == RSTicketsProConditions.getFormControlName('select_condition'))
			{
				selectCondition = children[i];
				continue;
			}
		}

		responseSpan.innerHTML = '';

		selectCondition.options.selectedIndex = 0;
		selectCondition.disabled = true;
		selectCondition.style.display = 'none';

		if (this.value != '')
		{
			selectCondition.disabled = false;
			selectCondition.style.display = '';

			var xmlHttp = new XMLHttpRequest();
			var url = 'index.php?option=com_rsticketspro&task=kbrules.showCustomFieldValues&cfid=' + this.value;

			xmlHttp.onreadystatechange = function() {
				if (xmlHttp.readyState === 4)
				{
					var has_options = false;

					var select = document.createElement('select');
					select.name = RSTicketsProConditions.getFormControlName('select_value');
					select.disabled = true;
					select.style.display = 'none';
					try {
						var options = JSON.parse(xmlHttp.responseText);
						if (options)
							for (var i=0; i<options.length; i++)
							{
								has_options = true;
								var option = document.createElement('option');
								option.value = options[i].id;
								option.text = options[i].name;
								select.options.add(option);
							}
					}
					catch (e)
					{
						alert(e);
					}

					if (has_options)
						responseSpan.appendChild(select);

					var textbox = document.createElement('input');
					textbox.type = 'text';
					textbox.name = RSTicketsProConditions.getFormControlName('select_value');
					textbox.disabled = true;
					textbox.style.display = 'none';
					textbox.value = '';

					responseSpan.appendChild(textbox);
				}
			};

			xmlHttp.open('GET', url, true);
			xmlHttp.send(null);
		}
	},
	changeSelectType: function() {
		var td = this.parentNode;
		var children = td.childNodes;
		var task, selectCondition, responseSpan, responseSpan2, url;
		var xmlHttp = new XMLHttpRequest();

		for (var i=0; i<children.length; i++)
		{
			if (children[i].name == RSTicketsProConditions.getFormControlName('select_condition'))
			{
				selectCondition = children[i];
				continue;
			}
			if (children[i].className == 'responseSpan')
			{
				responseSpan = children[i];
				continue;
			}
			if (children[i].className == 'responseSpan2')
			{
				responseSpan2 = children[i];
				continue;
			}
		}

		responseSpan.innerHTML = '';
		responseSpan2.innerHTML = '';

		selectCondition.options.selectedIndex = 0;
		selectCondition.disabled = true;
		selectCondition.style.display = 'none';

		switch (this.value)
		{
			case 'department':
			case 'priority':
			case 'status':
				selectCondition.disabled = false;
				selectCondition.style.display = '';

				if (this.value == 'department')
					task = 'showDepartments';
				else if (this.value == 'priority')
					task = 'showPriorities';
				else if (this.value == 'status')
					task = 'showStatuses';

				url = 'index.php?option=com_rsticketspro&task=kbrules.' + task;

				xmlHttp.onreadystatechange = function() {
					if (xmlHttp.readyState === 4)
					{
						var select = document.createElement('select');
						select.name = RSTicketsProConditions.getFormControlName('select_value');
						select.disabled = true;
						select.style.display = 'none';

						try {
							var options = JSON.parse(xmlHttp.responseText);
							if (options)
								for (var i=0; i<options.length; i++)
								{
									var option = document.createElement('option');
									option.value = options[i].id;
									option.text = options[i].name;
									select.options.add(option);
								}
						}
						catch (e)
						{
							alert(e);
						}

						responseSpan.appendChild(select);

						var textbox = document.createElement('input');
						textbox.type = 'text';
						textbox.name = RSTicketsProConditions.getFormControlName('select_value');
						textbox.disabled = true;
						textbox.style.display = 'none';
						textbox.value = '';

						responseSpan.appendChild(textbox);
					}
				};

				xmlHttp.open('GET', url, true);
				xmlHttp.send(null);
				break;

			case 'subject':
				selectCondition.disabled = false;
				selectCondition.style.display = '';

				var textbox = document.createElement('input');
				textbox.type = 'text';
				textbox.name = RSTicketsProConditions.getFormControlName('select_value');
				textbox.disabled = true;
				textbox.style.display = 'none';
				textbox.value = '';

				responseSpan.appendChild(textbox);
				break

			case 'message':
				selectCondition.disabled = false;
				selectCondition.style.display = '';

				var textarea = document.createElement('textarea');
				textarea.name = RSTicketsProConditions.getFormControlName('select_value');
				textarea.disabled = true;
				textarea.style.display = 'none';
				textarea.value = '';

				responseSpan.appendChild(textarea);
				break;

			case 'custom_field':
				url = 'index.php?option=com_rsticketspro&task=kbrules.showCustomFields';

				xmlHttp.onreadystatechange = function() {
					if (xmlHttp.readyState === 4)
					{
						var select = document.createElement('select');
						var option = document.createElement('option');
						option.value = '';
						option.text = Joomla.JText._('RST_PLEASE_SELECT');
						select.options.add(option);

						select.name = RSTicketsProConditions.getFormControlName('select_custom_field_value');

						try {
							var response = JSON.parse(xmlHttp.responseText);
							var departments = response.departments;
							var options = response.options;

							for (var i=0; i<departments.length; i++)
							{
								var group = document.createElement('optgroup');
								group.label = departments[i].name;

								for (var j=0; j<options.length; j++)
								{
									if (options[j].department_id != departments[i].id)
									{
										continue;
									}

									var option = document.createElement('option');
									option.value = options[j].id;
									if (typeof(option.innerText) != 'undefined')
										option.innerText = options[j].name;
									else
										option.text = options[j].name;

									group.appendChild(option);
								}

								select.appendChild(group);
							}
						}
						catch (e)
						{
							alert(e);
						}

						jQuery(select).change(RSTicketsProConditions.changeSelectCustomField);
						responseSpan2.appendChild(select);

						var textbox = document.createElement('input');
						textbox.type = 'text';
						textbox.name = RSTicketsProConditions.getFormControlName('select_value');
						textbox.disabled = true;
						textbox.style.display = 'none';
						textbox.value = '';

						responseSpan.appendChild(textbox);
					}
				};

				xmlHttp.open('GET', url, true);
				xmlHttp.send(null);
				break;
		}
	},
	changeSelectCondition: function() {
		var td = this.parentNode;
		var children = td.childNodes;
		var responseSpan = false;
		for (var i=0; i<children.length; i++)
			if (children[i].className == 'responseSpan')
			{
				responseSpan = children[i];
				break;
			}

		var children = responseSpan.childNodes;

		for (var i=0; i<children.length; i++)
		{
			children[i].disabled = true;
			if (typeof children[i].style != 'undefined')
				children[i].style.display = 'none';
		}

		if (!children.length)
			return;

		switch (this.value)
		{
			case 'neq':
			case 'eq':
				children[0].disabled = false;
				children[0].style.display = '';
				break;

			case 'like':
			case 'notlike':
				if (children.length == 2)
				{
					children[1].disabled = false;
					children[1].style.display = '';
				}
				else
				{
					children[0].disabled = false;
					children[0].style.display = '';
				}
				break
		}
	},
	add: function() {
		var childContainer = jQuery('<p>').html('<span class="rst_condition_if">' + Joomla.JText._('RST_IF') + '</span> ');

		var selectType = jQuery('<select>', {
			'name': RSTicketsProConditions.getFormControlName('select_type')
		});
		selectType.change(this.changeSelectType);
		for (var i=0; i<this.typeOptions.length; i++) {
			var typeOption = this.typeOptions[i];
			selectType.append(jQuery('<option>', {
				value: typeOption.value,
				text: typeOption.text
			}));
		}
		childContainer.append(selectType);

		this.addSpacing(childContainer);
		childContainer.append(jQuery('<span>', {
			'class': 'responseSpan2'
		}));
		this.addSpacing(childContainer);

		var selectCondition = jQuery('<select>', {
			'name': RSTicketsProConditions.getFormControlName('select_condition'),
			'disabled': true
		}).hide();
		selectCondition.change(this.changeSelectCondition);
		for (var i=0; i<this.conditionOptions.length; i++) {
			var typeOption = this.conditionOptions[i];
			selectCondition.append(jQuery('<option>', {
				value: typeOption.value,
				text: typeOption.text
			}));
		}
		childContainer.append(selectCondition);

		this.addSpacing(childContainer);
		childContainer.append(jQuery('<span>', {
			'class': 'responseSpan'
		}));
		this.addSpacing(childContainer);

		var selectConnector = jQuery('<select>', {
			'name': RSTicketsProConditions.getFormControlName('select_connector')
		});
		for (var i=0; i<this.connectorOptions.length; i++) {
			var typeOption = this.connectorOptions[i];
			selectConnector.append(jQuery('<option>', {
				value: typeOption.value,
				text: typeOption.text
			}));
		}

		childContainer.append(selectConnector);
		this.addSpacing(childContainer);

		var removeButton = jQuery('<button type="button" class="btn btn-danger deleteConditionLink"><span class="icon icon-minus"></span></button>');

		removeButton.click(function(){
			RSTicketsProConditions.remove(this);
		});

		childContainer.append(removeButton);

		jQuery('#rst_conditions').append(childContainer);
	},
	remove: function(btn) {
		jQuery(btn).parent().remove();
	}
}

jQuery(document).ready(function($) {
	$('.deleteConditionLink').click(function(){
		RSTicketsProConditions.remove(this);
	});
	$('#addConditionLink').click(function(){
		RSTicketsProConditions.add();
	});

	var selectType = RSTicketsProConditions.getFormControlName('select_type');
	var i;
	for (i = 0; i < document.getElementsByName(selectType).length; i++) {
		$(document.getElementsByName(selectType)[i]).change(RSTicketsProConditions.changeSelectType);
	}
	var selectCondition = RSTicketsProConditions.getFormControlName('select_condition');
	for (i = 0; i < document.getElementsByName(selectCondition).length; i++) {
		$(document.getElementsByName(selectCondition)[i]).change(RSTicketsProConditions.changeSelectCondition);
	}
	var selectCustomField = RSTicketsProConditions.getFormControlName('select_custom_field_value');
	for (i = 0; i < document.getElementsByName(selectCustomField).length; i++) {
		$(document.getElementsByName(selectCustomField)[i]).change(RSTicketsProConditions.changeSelectCustomField);
	}
});