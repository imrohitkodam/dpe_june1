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
use Joomla\CMS\MVC\View\HtmlView;
use Noboss\Library\Util\NbLoadextensionAssetsUtil;
use Noboss\Library\Component\NbViewToolbarComponent;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 *  Classe a ser estendida em componentes para view de edicao de registros
 *  @author  Johnny Salazar Reidel
 * 
 *  Observacao: o funcionamento desta classe tem como requisito que o componente seja desenvolvido no modelo No Boss
 */

// FIXME: ver se HtmlView ja pode ser usado no j4
//class NbViewEditComponent extends JViewLegacy {
class NbViewEditComponent extends HtmlView {
    public $item;
	public $form;
	public $state;
    public $document;

    /**
     * Metodo principal
     */
	public function display($tpl = null) {
		$app = Factory::getApplication();
		$input = $app->input;

        $this->form		= $this->get('Form');
		$this->item		= $this->get('Item');
		$this->state	= $this->get('State');

		// Verifica se existe algum erro.
		if (!empty($errors = $this->get('Errors'))) {
            throw new Exception(implode("\n", $errors), 500);
		}

        // Model da view
        $this->model = $this->getModel();

        // Nome da view
        $this->viewName = $this->_name;

        // Alias do componente (ex: 'com_nobossfaq') que esta definido no arquivo principal do componente
        $this->componentAlias = $input->get('option');

        // Nome dos fields de titulo e alias que sao exibidos no topo da pagina (qnd existir)
        $this->fieldName = $input->get('fieldName');
        $this->fieldAlias = $input->get('fieldAlias');

        // Alias do campo de id do componente
        $this->recordIdAlias = $input->get('recordIdAlias');

        // Url do post do formulario
        $this->actionForm = (empty($this->actionForm)) ? "index.php?option={$this->componentAlias}&view={$this->viewName}&layout=edit&{$this->recordIdAlias}=".(int) $this->item->{$this->recordIdAlias} : $this->actionForm;

        // Carrega barra de navegacao padrao
        $this->addToolbar();

        // Carrega variaveis e arquivos JS e CSS
        $this->loadExtensionAssets();

        // Executa funcao para tratamentos especificos desta view
        if(method_exists($this, 'specificTreatments')){
            $this->specificTreatments();
        }

		parent::display($tpl);
	}

    /**
     * Metodo para exibir barra de navegacao padrao
     * 
     * OBS: para carregar uma barra personalizada no template, basta declarar esse mesma funcao dentro da view do componente e colocar o codigo personalizado
     */
    public function addToolbar(){
        // Carrega barra de navegacao
        NbViewToolbarComponent::addToolbarEditView($this->pageTitle, $this->pageIcon, $this->recordIdAlias, $this->componentAlias, $this->viewName, $this->item);
    }
    /**
     * Funcao para carregamento de variaveis e arquivos JS e CSS no padrao No Boss
     * 
     * OBS: essa funcao soh eh executada se for chamada pela funcao display do componente
     */
    public function loadExtensionAssets(){       
        // Instancia objeto para carregamento de CSS e JS da extensao
        $assetsObject = new NbLoadextensionAssetsUtil($this->componentAlias);
        // Adiciona jquery, variaveis JS (baseNameUrl, majorVersionJoomla, completeVersionJoomla) e carrega arquivo JS da extensao (caso esteja definido um no caminho default. Ex: '/administrator/components/com_nobossfaq/assets/admin/js/com_nobossfaq.min.js')
        $assetsObject->loadJs(true, '', true, true, true);

        // Carrega arquivo CSS da extensao (caso esteja definido um no caminho default. Ex: '/administrator/components/com_nobossfaq/assets/admin/css/com_nobossfaq.min.css')
        $assetsObject->loadCss(true, '', true);
    }
}
