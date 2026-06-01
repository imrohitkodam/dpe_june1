<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2024 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Component;

use Joomla\CMS\Language\Text;
use Joomla\Utilities\ArrayHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 *  Trait a ser utilizada como apoio em componentes para Table de edicao de registros
 *  @author  Johnny Salazar Reidel
 * 
 *  Observacoes: 
 *      - Traits servem apenas para reuso de codigo, mas nao para heranca. Ou seja, nao eh possivel estender funcoes aqui definidas.
 *           * Se houver necessidade de estender alguma funcao aqui definida no model do componente, copie a funcao para o model e edite conforme necessario. 
 */

trait NbTableComponent {
    /**
	 * Metodo para alterar status de um ou mais registros na view de listagem de registros
     * 
     * OBS: esse metodo eh executado pelo Joomla a partir da funcao publish() do model
	 *
	 * @param   mixed	    $pks        Ids dos registros a mudar status
	 * @param   integer     $state      Status a aplicar
	 * @param   integer     $userId     Id do usuario que modificou status
     * 
	 * @return  boolean  True se sucesso.
	 */
	public function publish($pks = null, $state = 1, $userId = 0) {
		$k = $this->_tbl_key;

		ArrayHelper::toInteger($pks);
        
		$userId = (int) $userId;
		$state  = (int) $state;

		// If there are no primary keys set check to see if the instance key is set.
		if (empty($pks)){
			if ($this->$k){
				$pks = array($this->$k);
			}
			// Nothing to set publishing state on, return false.
			else {
				$this->setError(Text::_('JLIB_DATABASE_ERROR_NO_ROWS_SELECTED'));
				return false;
			}
		}

		// Build the WHERE clause for the primary keys.
		$where = $k.'='.implode(' OR '.$k.'=', $pks);

		// Determine if there is checkin support for the table.
		if (!property_exists($this, 'checked_out') || !property_exists($this, 'checked_out_time')){
			$checkin = '';
		}
		else{
			$checkin = ' AND (checked_out = 0 OR checked_out = '.(int) $userId.')';
		}

		// Update the publishing state for rows with the given primary keys.
		$this->_db->setQuery(
			'UPDATE '.$this->_db->quoteName($this->_tbl) .
			' SET '.$this->_db->quoteName('state').' = '.(int) $state .
			' WHERE ('.$where.')' .
			$checkin
		);

		try{
			$this->_db->execute();
		}
		catch (\RuntimeException $e){
			$this->setError($e->getMessage());
			return false;
		}

		// If checkin is supported and all rows were adjusted, check them in.
		if ($checkin && (count($pks) == $this->_db->getAffectedRows())){
			// Checkin the rows.
			foreach ($pks as $pk)
			{
				$this->checkin($pk);
			}
		}

		// If the Table instance value is in the list of primary keys that were set, set the instance.
		if (in_array($this->$k, $pks)){
			$this->state = $state;
		}

		$this->setError('');
		return true;
	}
}
?>
