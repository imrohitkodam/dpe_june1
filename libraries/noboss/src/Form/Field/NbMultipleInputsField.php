<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2024 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Noboss\Library\Util\NbJsConstantsUtil;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class NbMultipleInputsField extends FormField {
    
  	protected function getInput(){
        // Adiciona constantes padroes do JS
        NbJsConstantsUtil::addConstantsDefault();
       
        $html = "";
               
        // Valida se o usuário colocou um valor default no campo pai, algo que não pode e remove o valor
        if($this->value == $this->default){
            $this->value = '';
        }

        // Percorre os campos internos deste campo personalizado
        for($i = 0; $i < count($this->element->nbfield); $i++){
            $classType = ($this->element->nbfield[$i]->attributes()->type == 'checkbox') ? 'checkboxes' : '';

            // Para cada campo abre uma div com estilo inline-block
            $html .= "<div class='nobossmultipleinputs__input {$classType}'>";
            // Verifica se já existe um valor e seta no value
            $value = '';
            // Unidade do campo
            $unit = Text::_($this->element->attributes()->unit);
            
            // Valor ja salvo para o campo 
            if(isset($this->value[$i]) && !empty($this->value[$i])) {
                if(!empty($unit) && (substr($this->value[$i], -2) == $unit)){
                    $value = $this->value[$i]; 
                }else{
                    $value = $this->value[$i].$unit; 
                }
            }
            // Nao ha valor salvo
            else{
                // Se o default do elemento não estiver vazio
                if(!empty($this->element->nbfield[$i]->attributes()->default)){
                    $value = Text::_($this->element->nbfield[$i]->attributes()->default);
                // Se o default do elemento estiver vazio e o default geral não estiver vazio 
                } else if(!empty($this->default) && empty($value)){
                    $value = Text::_($this->default);
                // Caso ambos os default estejam vazios
                } else {
                    $value = '0';
                }
            }
            
            // Verifica se existe alguma label definida, e insere antes do campo
            if(isset($this->element->nbfield->attributes()->label) && !empty($this->element->nbfield->attributes()->label)){
                $html .= "<label>".Text::_($this->element->nbfield[$i]->attributes()->label)."</label>";
            }
            $type = "text";
            // Verifica um tipo definido e seta ele, caso contrário define como text
            if(isset($this->element->nbfield->attributes()->type) && !empty($this->element->nbfield->attributes()->type)){
                $type = $this->element->nbfield->attributes()->type;
            }

            $dateElements = array();

            // Percorre atributos do sub input
            foreach($this->element->nbfield[$i]->attributes() as $indiceAtt => $valueAtt){
                // Atributo inicia com 'data-': adiciona atributo junto ao input a ser carregado
                if (substr($indiceAtt, 0, 5) == 'data-'){
                    $dateElements[] = "{$indiceAtt}='{$valueAtt}'";
                }
                // Item 'checked' definido (campo checkbox)
                if($indiceAtt == 'checked'){
                    $dateElements[] = "{$indiceAtt}='{$valueAtt}'";
                }
            }
     
            // Definida unidade para o campo: sinaliza maskara
            if(isset($unit) && !empty($unit)){
                $dateElements[] = "data-nbmask='{$unit}'";
            }

            $attr = '';
        
            if ((string) $this->readonly == '1' || (string) $this->readonly == 'true'){
                $attr .= ' readonly';
            }

            $classInput = 'form-check-input';
          
            $html .= "<input class='nobossmultipleinputs--active-mask {$this->class} {$classInput}' {$attr} data-id='nobossmultipleinputs--active-mask' ".implode(' ', $dateElements)." type='{$type}' name='{$this->name}[]' value='{$value}' />\n";

            $html .= "</div>";
        }

        $app = Factory::getApplication();
        $doc = Factory::getDocument();
        $wa = $app->getDocument()->getWebAssetManager();

        //Verifica se já tem uma basenameUrl
        if ((version_compare(JVERSION, '4', '>=')) || @!strpos($doc->_script["text/javascript"], "baseNameUrl =")) {
            //Adiciona basenameurl
            $wa->addInlineScript("var baseNameUrl = '".Uri::root()."'");
        }

        $wa->registerAndUseScript('nobossmultipleinputs', Uri::root()."libraries/noboss/src/Form/Field/assets/js/min/nobossmultipleinputs.min.js");
        $wa->registerAndUseStyle('nobossmultipleinputs', Uri::root()."libraries/noboss/src/Form/Field/assets/stylesheets/css/nobossmultipleinputs.min.css");

        // Retorna o html do field gerado
        return $html;
    }
}
