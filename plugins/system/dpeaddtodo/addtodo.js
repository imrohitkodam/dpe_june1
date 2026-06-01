/*
 * @package     Plg_System_Tjlms
 * @subpackage  Plg_System_Tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

jQuery(document).ready(function() {

	tjutilityaddTodo.tjTdClass = '.td-addtodo';
	tjutilityaddTodo.tjTableId = 'report-table';
	tjutilityaddTodo.initialize();	
});

var tjutilityaddTodo = {
    initialize: function() { 
		var isAddTodo    = jQuery('body').find(tjutilityaddTodo.tjTdClass).length;
		var isCheckboxes = jQuery('body').find('input[name="cid[]"]').length;

		if (isAddTodo)
		{
			// If on list view already checkbox are present then avoid the add column function
			if (!isCheckboxes)
			{
				this.addColumn();
			}

			this.btnAddTodo();
		}
    },
    addColumn: function()
	{	
		var isCheckboxes = jQuery('body').find('input[name="cid[]"]').length;

		if (!isCheckboxes)
		{
				var tr = document.getElementById(tjutilityaddTodo.tjTableId).tHead.children[0];
		tr.insertCell(0).outerHTML = '<th><input type="checkbox" name="checkall-toggle" value="" class="hasTooltip" title="Check All Items" onclick="Joomla.checkAll(this)"></th>'

		var tblBodyObj = document.getElementById(tjutilityaddTodo.tjTableId).tBodies[0];
		for (var i=0; i < tblBodyObj.rows.length; i++) {
			var newCell = tblBodyObj.rows[i].insertCell(0);
			newCell.innerHTML = '<input type="checkbox" id="cb0" name="cid[]" value="' + i + '" onclick="Joomla.isChecked(this.checked); uncheck(this);">'
				}
			}
	},
	btnAddTodo: function () {
		try {
			let btnHtml = '<div class="btn-wrapper" id="tj-addtodo">';
					btnHtml += '<a id="" class="btn btn-primary" href="javascript:void(0);" onclick="openAddtodo(); " title=" '+Joomla.JText._("PLG_SYSTEM_ADDTODO_BTN")+'">';
						btnHtml += Joomla.JText._('PLG_SYSTEM_ADDTODO_BTN');
					btnHtml += '</a>';
				btnHtml += '</div>';
			jQuery('#addtodobtn').append(btnHtml);
		}
		catch (err) {
			/*console.log(err.message);*/
		}
	}
};
