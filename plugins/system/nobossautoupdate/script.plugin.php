<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2020 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// Importa classe de instalacao da library
jimport('noboss.util.installscript');

/**
 * Script de instalação da extensão
 */
class plgSystemNobossautoupdateInstallerScript{

    /**
     * Metodo executao após o término da instalação/update
     */
    public function postflight($type, $parent) {
        $this->activatePlugin();
    }

    /**
     * Busca no banco por todos os extras
     *
     * @return Array Retorna um array com informações dos extras
     */
    private function activatePlugin(){
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        //Monta a query para buscar na tabela extension
        $query->update('#__extensions')
            ->set('enabled = 1')
            ->where("element = 'nobossautoupdate'");

        $db->setQuery($query);
        $db->execute();
    }
}
