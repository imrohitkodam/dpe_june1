<?php
defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;

class JFormFieldAssetsloader extends FormField
{
    protected $type = 'assetsloader';

    protected function getInput()
    {
        HTMLHelper::_('script', 'plg_system_robotsmanager/js/batch.js', ['version' => 'auto', 'relative' => true]);

        $script = <<<JS
document.addEventListener('DOMContentLoaded', function(){
  var radios = document.querySelectorAll('input[name="jform[params][update_now]"]');
  function maybeOpen(){
    var val = null;
    radios.forEach(function(r){ if(r.checked){ val = r.value; }});
    if(val === '1'){
      var el = document.getElementById('rm-modal');
      if(el && window.bootstrap && window.bootstrap.Modal){ new window.bootstrap.Modal(el).show(); }
    }
  }
  radios.forEach(function(r){ r.addEventListener('change', maybeOpen); });
  // In case page loads with Yes selected
  maybeOpen();
});
JS;
        \Joomla\CMS\Factory::getDocument()->addScriptDeclaration($script);
        return '';
    }
} 