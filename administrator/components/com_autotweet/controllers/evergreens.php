<?php

/*
 * @package     Perfect Publisher
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2022 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see         https://www.extly.com
 */

defined('_JEXEC') || exit;

require_once 'default.php';

/**
 * AutotweetControllerEvergreens.
 *
 * @since       1.0
 */
class AutotweetControllerEvergreens extends AutotweetControllerDefault
{
    /**
     * batchEvergreen.
     */
    public function batchEvergreen()
    {
        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        $model = $this->getThisModel();

        if (!$model->getId()) {
            $model->setIDsFromRequest();
        }

        $batch_evergreen = $this->input->get('batch_evergreen', null, 'int');
        $status = true;
        $status = $model->moveToEvergeen($batch_evergreen);

        // Check if i'm using an AJAX call, in this case there is no need to redirect
        $format = $this->input->get('format', '', 'string');

        if ('json' === $format) {
            echo json_encode($status);

            return;
        }

        // Redirect
        if ($customURL = $this->input->get('returnurl', '', 'string')) {
            $customURL = base64_decode($customURL, true);
        }

        $url = !empty($customURL) ? $customURL : 'index.php?option='.$this->component.'&view='.XTF0FInflector::pluralize($this->view);

        if (!$status) {
            $this->setRedirect($url, $model->getError(), 'error');
        } else {
            $this->setRedirect($url);
        }
    }

    protected function onBeforeBatchEvergreen()
    {
        return $this->onBeforeAccessspecial();
    }

    /**
     * Creates a new model object.
     *
     * @param string $name   The name of the model class, e.g. Items
     * @param string $prefix The prefix of the model class, e.g. FoobarModel
     * @param array  $config The configuration parameters for the model class
     *
     * @return XTF0FModel The model object
     */
    protected function createModel($name, $prefix = '', $config = [])
    {
        $result = XTF0FModel::getAnInstance('Advancedattrs', $prefix, $config);
        $result->setState('evergreentype_id', PostShareManager::POSTTHIS_YES);
        $result->setState('browse', true);

        return $result;
    }
}
