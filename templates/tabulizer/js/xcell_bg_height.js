function setXCellBackgroundHeight() {
    jtQuery('.xcell').each(function(){
        var el = jtQuery(this);
        var padding_top = parseInt(el.css('padding-top'));
        var padding_bottom = parseInt(el.css('padding-bottom'));
        var parent_height = el.parent('td').height();
        new_height = parent_height - padding_top - padding_bottom;
        el.css("height", new_height + "px");
    });
}
jtQuery(document).ready(function(){
    setXCellBackgroundHeight();
});
jtQuery( window ).resize(function() {
    setXCellBackgroundHeight();
});
