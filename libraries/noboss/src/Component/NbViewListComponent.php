<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2024 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Component;

use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Noboss\Library\Component\NbViewToolbarComponent;
use Noboss\Library\Util\NbInstallScriptUtil;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 *  Classe a ser estendida em componentes para view de listagem de registros
 *  @author  Johnny Salazar Reidel
 * 
 *  Observacao: o funcionamento desta classe tem como requisito que o componente seja desenvolvido no modelo No Boss
 */

// FIXME: ver se HtmlView ja pode ser usado no j4
//class NbViewListComponent extends JViewLegacy {
class NbViewListComponent extends HtmlView {

	public $items;
	public $pagination;
	public $state;
    public $filterForm;
    public $activeFilters;
    public $isEmptyState = false;

    /**
     * Funcao principal
     */
	public function display($tpl = null) {
		$app = Factory::getApplication();
		$input = $app->input;

        // Pasta 'layouts/noboss' nao existe e nao foi possivel cria-la
        if(!NbInstallScriptUtil::checkLibraryLayoutFolder()){
            return;
        }

		$this->items		      = $this->get('Items');
		$this->pagination	      = $this->get('Pagination');
		$this->state		      = $this->get('State');
        $this->filterForm         = $this->get('FilterForm');
        $this->activeFilters      = $this->get('ActiveFilters');
        
        // Verifica se existe algum erro.
		if (!empty($errors = $this->get('Errors'))) {
            throw new Exception(implode("\n", $errors), 500);
		}

        // Model da view
        $this->model = $this->getModel();

        // Nome da view
        $this->viewName = $this->_name;

        // Alias do componente (ex: 'com_nobossfaq') que esta definido no arquivo principal do componente
        $this->componentAlias = Factory::getApplication()->input->get('option');
        
        // Executa funcao para exibicao das colunas
        if(method_exists($this, 'columnsDisplay')){
            $this->columnsDisplay();
        }
        else{
            throw new Exception('The columnsDisplay function that defines the columns to list is not defined.', 500);
        }

        // Carrega barra de navegacao padrao
        $this->addToolbar();

        // Executa funcao para tratamentos especificos desta view
        if(method_exists($this, 'specificTreatments')){
            $this->specificTreatments();
        }

        // Soh exibe listagem se nao estiver setado emptystate e que tenha dados a exibir
        if(!$this->displayEmptyState()){
            parent::display($tpl);
        }
	}

    /**
     * Metodo para exibir barra de navegacao padrao
     * 
     * OBS: para carregar uma barra personalizada no template, basta declarar esse mesma funcao dentro da view do componente e colocar o codigo personalizado
     */
    public function addToolbar(){
        // Carrega barra de navegacao padrao
        NbViewToolbarComponent::addToolbarListView($this->pageTitle, $this->pageIcon, $this->componentAlias, $this->viewName, $this->createViewAlias, $this->get('State'), $this->isEmptyState);
    }

    /**
     * Metodo para exibir conteudo centralizadno quando nenhum registro ainda foi cadastrado (conhecido como 'empty state')
     *      - Essa funcao eh valida somente para o Joomla 4
     */
    public function displayEmptyState($emptyState = array()){
        if(empty($emptyState)){
            return false;
        }

        // Nao ha item cadastrados e IsEmptyState esta definido
        if (!\count($this->items) && $this->get('IsEmptyState') && $emptyState['isEmptyState']){
            $emptyState['formURL'] = "index.php?option={$this->componentAlias}&view={$this->viewName}";               
            if (Factory::getApplication()->getIdentity()->authorise('core.create', $this->componentAlias)) {
                $emptyState['createURL'] = "index.php?option={$this->componentAlias}&task={$this->createViewAlias}.edit";
            }                
            echo LayoutHelper::render('joomla.content.emptystate', $emptyState);
            return true;
        }

        return false;
    }
}
