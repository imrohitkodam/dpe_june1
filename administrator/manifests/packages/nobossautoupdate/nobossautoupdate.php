<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Autoupdate
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2019 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

// no direct access
defined( '_JEXEC' ) or die;

class plgSystemNobossautoupdate extends JPlugin {
    /**
     * Load the language file on instantiation. Note this is only available in Joomla 3.1 and higher.
     * If you want to support 3.0 series you must override the constructor
     *
     * @var    boolean
     * @since  3.1
     */
    protected $autoloadLanguage = true;

    /**
     * The update check and notification email code is triggered after the page has fully rendered.
     *
     * @return  void
     *
     * @since   3.5
     */
    public function onBeforeRender(){
        // Caso a forma de execução seja cron, não continua
        if ($this->params->get('execution_method', 'admin') == 'cron') {
            return;
        }
        // Compara o último registro de data e hora de execução armazenado nas opções do plugin com o atual
        $now = time();
        $last = (int) $this->params->get('lastrun', 0);
        $interval = (int) $this->params->get('interval_verify', 120);

        // Se faz menos tempo que o que foi definido como intervalo não executa
        if (abs($now - $last) < $interval*60){
            return;
        }

        //  Atualiza o ultima vez executado
        $this->params->set('lastrun', $now);

        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
                    ->update($db->qn('#__extensions'))
                    ->set($db->qn('params') . ' = ' . $db->q($this->params->toString('JSON')))
                    ->where($db->qn('type') . ' = ' . $db->q('plugin'))
                    ->where($db->qn('folder') . ' = ' . $db->q('system'))
                    ->where($db->qn('element') . ' = ' . $db->q('nobossautoupdate'));

        try {
            // Tranca as tabelas para previnir multiplas execuções de plugins
            $db->lockTable('#__extensions');
        } catch (Exception $e) {
            // Se não deu para trancar, paramos a execução
            return;
        }

        try	{
            // Atualiza os parametros do plugin
            $result = $db->setQuery($query)->execute();
        } catch (Exception $exc) {
            // Se falhar destranca as tabelas e retorna false
            $db->unlockTables();
            $result = false;
        }

        try {
            // Destranca as tabelas
            $db->unlockTables();
        } catch (Exception $e) {
            // Caso não dê, retorna erro
            $result = false;
        }

        // Aborta em caso de falha
        if (!$result) {
            return;
        }

        $doc = JFactory::getDocument();
        // Adicion um script para fazer a requisição de atualização
        $url = JUri::root().'index.php?option=com_nobossajax&library=noboss.util.nobossautoupdate&method=updateNobossExt&format=raw';
        $doc->addScriptDeclaration("jQuery.ajax('{$url}')");
    }
}
