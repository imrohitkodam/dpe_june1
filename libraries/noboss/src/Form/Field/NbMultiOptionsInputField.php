<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2021 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Noboss\Library\Util\NbJsConstantsUtil;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbMultiOptionsInputField extends ListField {

    protected function getInput(){
        $app = Factory::getApplication();
        $wa = $app->getDocument()->getWebAssetManager();

        // Adiciona constantes padroes do JS
        NbJsConstantsUtil::addConstantsDefault();

        $options = array();

        //Verifica se o valor não está vazio
		if (!empty($this->value)) {
			//Verifica se o campo está no formato JSON, se estiver transforma em array
			if($this->validate_json($this->value)){
				$this->value = json_decode($this->value);
			}

            // echo '<pre>';
            // var_dump($this->value);
            // exit;

			//Percorre os valores inserindo uma option para cada um deles
			foreach ($this->value as $item) {
                // Joomla 3
                if(version_compare(JVERSION, '4', '<')){
                    $options[] = '<option value="'.$item.'" selected="selected">'.$item.'</option>';

                }
                // Joomla 4
                else{
                    $option = new \stdClass;
                    $option->value=$item;
                    $option->text=$item;
                    $options[] = $option;
                }
			}
        }
        
        // JS da extensao
        $wa->registerAndUseScript('Nobossmultioptionsinput', Uri::root()."libraries/noboss/src/Form/Field/assets/js/min/nobossmultioptionsinput.min.js");
        
        // Joomla 3
        if(version_compare(JVERSION, '4', '<')){
            // Cria select na mao
            $html = '<select id="' . $this->id . '" name="' . $this->name . '[]" data-id="multi-options-input" multiple="multiple">';
            $html .= implode($options);
            $html .= '</select>';

            return $html;
        }
        // Joomla 4
        else{
            $data = $this->getLayoutData();

            // Adiciona '[]' no name para que fique como multiplo salvando dados como array
            $data['name'] = $data['name'].'[]';

            // Esses sao os valores que viram variaveis no layout (ex: $options)
            $data['options'] = $options;
            // forca que seja multiplo
            $data['multiple'] = 1;
            // Placeholder. Se nao for definido, coloca como espaço em branco para o layout nao pegar a constante da Tag
            $data['hint'] = (empty($this->hint)) ? ' ' : $data['hint'];
            
            // Seta allowCustom como 0 e depois coloca ele como atributo p/ que o joomla nao coloque 'new-item-prefix="#new#"'
            $data['allowCustom']  = 0;
            $data['dataAttribute'] .= ' allow-custom';

            $data['remoteSearch']  = 0;

            // Aproveitamos o layout do field Tag. Se precisar, um dia podemos criar nosso proprio layout em 'noboss.j4.form.field.nobossmultioptionsinput' e adaptar conforme for necessario
            $this->layout = 'joomla.form.field.tag';

            return $this->getRenderer($this->layout)->render($data);
        }
  	}

  	// Faz uma verificação para ver se a string é um json
  	private function validate_json($str=NULL) {
	    if (is_string($str)) {
	        @json_decode($str);
	        return (json_last_error() === JSON_ERROR_NONE);
	    }
	    return false;
	}
}
