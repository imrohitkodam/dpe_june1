<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2024 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Component;

use Joomla\String\StringHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Application\ApplicationHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 *  Trait a ser utilizada como apoio em componentes para model de edicao de registros
 *  @author  Johnny Salazar Reidel
 * 
 *  Observacoes: 
 *      - Traits servem apenas para reuso de codigo, mas nao para heranca. Ou seja, nao eh possivel estender funcoes aqui definidas.
 *           * Se houver necessidade de estender alguma funcao aqui definida no model do componente, copie a funcao para o model e edite conforme necessario. 
 *      - O funcionamento desta classe tem como requisito que o componente seja desenvolvido no modelo No Boss
 */

trait NbModelEditComponent {
    /**
     * Metodo para pegar instancia que representa a tabela da model.
     *
     * @param   string	$type       O tipo da table para instanciar.
     * @param   string 	$prefix	    Um prefixo para a classe da tabela. Opcional.
     * @param   array  	$config     Array de configuracao para a model. Opcional.
     * 
     * @return  Table	Um objeto representando a tabela.
     */
    public function getTable($type = '', $prefix = '', $config = array()){
        if (empty($type)){
            // Sufixo do nome da classe da Table de edicao dos registros. Ex: se a chasse se chamar 'NobossfaqTableFaqgroup' o valor sera 'Faqgroup' que eh o final do nome da classe
            $type = $this->tableClassNameSuffix;
        }
        
        if (empty($prefix)){
            $prefix = Factory::getApplication()->input->get('componentClassPrefix')."Table";
        }
        
        return Table::getInstance($type, $prefix, $config);
    }

    /**
     * Metodo que obtem um formulario definido em xml
     *
     * @param   array       $data		Uma matriz opcional de dados para o formulario a ser interrogado.
     * @param   boolean	    $loadData	Verdadeiro se o formulario eh para carregar seus proprios dados (caso padrao), false se nao.
     * 
     * @return  JForm	    Um objeto JForm em caso de sucesso, falso em falha.
     */
    public function getForm($data = array(), $loadData = true) {
        // Carrega o formulario.
        $form = $this->loadForm(Factory::getApplication()->input->get('option') . '.' .$this->name, $this->name, array('control' => 'jform', 'load_data' => $loadData));

        if (empty($form)) {
            return false;
        }

        // Aqui eh possivel manipular fields do form que serao carregados (trata-se apenas de um exemplo. Se precisar adicionar codigos, copie o metodo para a classe model do componente)
        
        // Exemplo de como setar que um campo seja carregado desabilitado
        //$form->setFieldAttribute('ordering', 'disabled', 'true');

        return $form;
    }

    /**
     * Metodo que carrega os dados do formulario
     *
     * @return  mixed  Os dados para o formulario.
     */
    protected function loadFormData() {
        $app = Factory::getApplication();
        $input = $app->input;

        // Obtem os dados da sessao (caso esteja definido. Ex de situacao: dados foram impedidos de serem salvos e pagina foi recarregada)
        $data = $app->getUserState($input->get('option').'.edit.'.$input->getString("view").'.data', array());

        // Dados nao forem obtidos na sessao: busca dados no banco
        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData($input->get('option').'.'.$input->getString("view"), $data);

        return $data;
    }

    /**
     * Metodo utilizado no clone de um registro para incrementar um numero no final do nome e alias
     *
     * @param   array       $data		 Dados do formulario
     * @param   String      $fieldName	 Nome dos fields de titulo
     * @param   String      $fieldAlias	 Nome dos fields de alias (opcional)
     * 
     * @return  array	    Dados do formulario com a alteracao do nome e alias
     */
    public function incrementNameAndAlias($data, $fieldName, $fieldAlias = ''){
        if(empty($fieldName)){
            return $data;
        }
        
        $table = $this->getTable();

        // Incrementa um numero no final do nome para nao ficar igual ao item copiado
        while ($table->load(array($fieldName => $data[$fieldName]))) {
            if ($data[$fieldName] === $table->{$fieldName})	{
                $data[$fieldName] = StringHelper::increment($data[$fieldName]);
            }

            // Componente nao possui campo de alias: incrementa numero no alias tambem
            if(empty($fieldAlias)){
                $data[$fieldAlias] = StringHelper::increment($data[$fieldAlias], 'dash');
            }
        }

        return $data;
    }

    /**
     * Metodo utilizado antes de salvar um registro para tratar o nome e alias
     *
     * @param   object      $table		 Dados do formulario
     * @param   String      $fieldName	 Nome dos fields de titulo
     * @param   String      $fieldAlias	 Nome dos fields de alias (opcional)
     * 
     * @return  object	    Dados do formulario com a alteracao do nome e alias
     */
    public function treatmentNameAndAlias($table, $fieldName, $fieldAlias = ''){
        // Componente possui campo de titulo
        if(!empty($fieldName)){
            // Elimina caracteres especiais do campo nome
            $table->{$fieldName} = htmlspecialchars_decode($table->{$fieldName}, ENT_QUOTES);
        }

        // Componente possui campo de alias
        if(!empty($fieldAlias)){
            // Valor para o campo alias nao esta definido: copia o nome como alias
            if(empty($table->{$fieldAlias})){
                $table->{$fieldAlias} = $table->{$fieldName};
            }

            // Converte o valor do alias para formato aceito como alias (tudo minusculo e com '-' no lugar de espacos)
            $table->{$fieldAlias} = ApplicationHelper::stringURLSafe($table->{$fieldAlias});
        }

        return $table;
    }

    /**
     * Metodo utilizado antes de salvar um registro para setar uma ordenacao para o registro (quando existir o campo e valor nao estiver definido)
     *
     * @param   object      $table		     Dados do formulario
     * @param   String      $fieldOrdering	 Alias do campo de ordenacao
     * @param   String      $nameTable	     Nome da tabela do banco
     * 
     * @return  object	    Dados do formulario com a alteracao
     */
    public function setOrdering($table, $fieldOrdering, $nameTable){
        // Componente possui campo de ordenacao e valor dele nao foi definido: define valor
        if ((!empty($fieldOrdering)) && (empty($table->{$fieldOrdering}))) {
            // Obtem maior valor de ordenacao existente no banco
            $db = $this->getDbo();
            $query = $db->getQuery(true)
                ->select('MAX('.$fieldOrdering.')')
                ->from($db->quoteName($nameTable));
            $db->setQuery($query);
            $max = $db->loadResult();

            // Define ordenacao para o registro
            $table->{$fieldOrdering} = $max + 1;
        }

        return $table;
    }

    /**
     * Metodo utilizado antes de salvar um registro para definir informacoes para os campos 'created', 'created_by', 'modified' e 'modified_by'
     *
     * @param   object      $table		 Dados do formulario
     * @param   String      $recordIdAlias	 Alias do campo de id do componente
     * 
     * @return  object	    Dados do formulario com a alteracao
     */
    public function setCreatedModified($table, $recordIdAlias){
        $date = Factory::getDate();
		$user  = Factory::getApplication()->getIdentity();
        
        // Eh novo registro
		if (empty($table->{$recordIdAlias})) {
			// Seta data de criacao no campo 'created'
            if(property_exists($table, 'created')){
                $table->created = $date->toSql();
                $table->modified = $table->created;
            }
            // Seta id do usuario que criou o registro
            if(property_exists($table, 'created_by')){
                $table->created_by = $user->get('id');
                $table->modified_by = $table->created_by;
            }
		}
        // Edicao de registro
		else{
			// Seta data de modificacao no campo 'modified'
            if(property_exists($table, 'modified')){
                $table->modified = $date->toSql();
            }
            // Seta id do usuario que alterou o registro
            if(property_exists($table, 'modified_by')){
                $table->modified_by = $user->get('id');
            }
		}

        // Existe campo 'publish_in' e ele esta sem valor: remove ele do objeto table para nao gerar problema ao salvar
        if(isset($table->publish_in) && empty($table->publish_in)){
            unset($table->publish_in);
        }

        // Existe campo 'publish_down' e ele esta sem valor: remove ele do objeto table para nao gerar problema ao salvar
        if(isset($table->publish_down) && empty($table->publish_down)){
            unset($table->publish_down);
        }

        return $table;
    }
}
?>
