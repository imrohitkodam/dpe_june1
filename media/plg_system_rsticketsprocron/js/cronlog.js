Joomla.submitbutton = function(task)
{
    if (task === 'cronlog.deleteAll' && !confirm(Joomla.JText._('RST_CONFIRM_DELETE_ALL')))
    {
        return false;
    }

    Joomla.submitform(task, document.getElementById('adminForm'));
}