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
use Joomla\CMS\Component\ComponentHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 *  Trait a ser utilizada como apoio em componentes para model de listagem de registros
 *  @author  Johnny Salazar Reidel
 * 
 *  Observacoes: 
 *      - Traits servem apenas para reuso de codigo, mas nao para heranca. Ou seja, nao eh possivel estender funcoes aqui definidas.
 *           * Se houver necessidade de estender alguma funcao aqui definida no model do componente, copie a funcao para o model e edite conforme necessario. 
 *      - O funcionamento desta classe tem como requisito que o componente seja desenvolvido no modelo No Boss
 */

trait NbModelListComponent {
    /**
	 * Metodo para preencher automaticamente o estado da model.
     *  - Na pratica esse metodo define a ordenacao default ao carregar a pagina e tb salva em sessao os valores dos filtros 'search' e 'state'
	 *
	 */
	protected function populateState($ordering = '', $direction = '') {
        // Obtem valores definidos no construtor do model
        $ordering = $this->orderingDefault;
        $direction = $this->directionDefault;

        $app = Factory::getApplication();
        
        // Load the filter state.
        $this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'));
        $this->setState('filter.state', $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string'));
        
        // Adjust the context to support modal layouts.
        if ($layout = $app->input->get('layout')) {
            $this->context .= '.' . $layout;
        }
        
        // Load the parameters.
        $params = ComponentHelper::getParams($app->input->get('option'));
        $this->setState('params', $params);
        
        parent::populateState($ordering, $direction);
 
        /**
         * O Joomla nao seta 'direction' quando nao temos definido o field 'fullordering' no xml de filtros. Por isso fazemos a solucao de contorno abaixo para setar corretamente qnd nao usarmos 'fullordering'
         */
        if (!empty(Factory::getApplication()->getUserStateFromRequest($this->context . '.list', 'list')) || ((!empty(Factory::getApplication()->getUserStateFromRequest($this->context . '.list', 'list'))) && (substr(Factory::getApplication()->getUserStateFromRequest($this->context . '.list', 'list')['fullordering'], 0, 4) == 'null'))){
            $this->setState('list.direction', $direction);
        }        
	}

    /**
	 * Metodo para obter um id com base no state da configuracao da model.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return  string  A store id.
	 */
	protected function getStoreId($id = '') {
		$id	.= ':'.$this->getState('filter.search');
		$id	.= ':'.$this->getState('filter.state');

		return parent::getStoreId($id);
	}
}
