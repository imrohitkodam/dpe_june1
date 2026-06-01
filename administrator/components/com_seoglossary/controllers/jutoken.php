<?php
/**    
 * SEO Glossary Glossary controller
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

// No direct access
defined('_JEXEC') or die;
jimport( 'joomla.filesystem.folder' );

JLoader::register('JuupdaterHelper', JPATH_SITE.'/plugins/installer/juupdater/helper.php');
class SeoglossaryControllerJutoken extends JControllerForm{
 
    /**
     * Add token
     *
     * @return void
     * @since  version
     */
    public function juAddToken()
    {
        JuupdaterHelper::juAddToken();
    }

    /**
     * Remove token
     *
     * @return void
     * @since  version
     */
    public function juRemoveToken()
    {
        JuupdaterHelper::juRemoveToken();
    }

    /**
     * Display return
     *
     * @param boolean $status Response status
     * @param array   $datas  Response data
     *
     * @return void
     * @since  version
     */
    private function exitStatus($status, $datas = array())
    {
        JuupdaterHelper::exitStatus($status, $datas = array());
    }

    /**
     * Check config token
     *
     * @return integer
     * @since  version
     */
    public function checkConfigToken()
    {
        return JuupdaterHelper::checkConfigToken();
    }

    /**
     * Update config token
     *
     * @param string $token Token
     *
     * @return void
     * @since  version
     */
    public function juUpdateConfigToken($token)
    {
        JuupdaterHelper::juUpdateConfigToken($token);
    }

    /**
     * Update site token
     *
     * @param string $token Token
     *
     * @return void
     * @since  version
     */
    public function juUpdateSiteToken($token)
    {
        JuupdaterHelper::juUpdateSiteToken($token);
    }
}