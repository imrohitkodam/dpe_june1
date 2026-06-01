<?php

/**
 * @package         Google Structured Data
 * @version         6.0.1 Pro
 * 
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2025 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Form\Field\UrlField;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class JFormFieldSchemaTest extends UrlField
{
    /**
     *  Disable input's label
     *
     *  @return  null
     */
    protected function getLabel()
    {
        return;
    }

    protected function getLayoutData()
    {
        return array_merge(parent::getLayoutData(), [
            'hint' => Text::_('GSD_SCHEMA_TEST_ENTER_URL'),
        ]);
    }

    /**
     * Method to get a list of options for a list input.
     *
     * @return   string
     */
    protected function getInput()
    {
        $script = <<<EOD
            document.addEventListener('DOMContentLoaded', function() {
                const testButton = document.querySelector('.schema_test .btn');
                const urlInput = document.querySelector('.schema_test input#jform_schematest');
                const toolSelect = document.querySelector('.schema_test select[name="tool"]');

                testButton.addEventListener('click', function(event) {
                    event.preventDefault();
                    const url = urlInput.value;
                    const tool = toolSelect.value;

                    if (url) {
                        let targetUrl = 'https://search.google.com/test/rich-results?url=' + encodeURIComponent(url);
                        if (tool === '2') {
                            targetUrl = 'https://validator.schema.org/#url=' + encodeURIComponent(url);
                        }
                        window.open(targetUrl, '_blank');
                    } else {
                        alert('Please enter a valid URL.');
                    }
                });
            });
        EOD;

        Factory::getDocument()->getWebAssetManager()->addInlineScript($script);

        $html = '
            <div class="schema_test">
                ' . parent::getInput() . '
                <select name="tool" class="form-control">
                    <option value="1">Google Rich Results Test</option>
                    <option value="2">Schema Markup Validator</option>
                </select>
                <a href="#" class="btn">' . Text::_('GSD_TEST') . '</a>
            </div>
        ';

        return $html;
    }
}