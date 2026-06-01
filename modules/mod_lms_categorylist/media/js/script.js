function showChildCats(currentChild)
{
	var main_li = jQuery(currentChild).closest(".has-children");
	var main_id = jQuery(main_li).data("id")
	jQuery(main_li).toggleClass("open");
	jQuery(".list-group-item[data-parent='"+ main_id +"']").toggleClass('d-block');
}
