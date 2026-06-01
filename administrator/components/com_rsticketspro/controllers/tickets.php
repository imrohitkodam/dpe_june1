<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproControllerTickets extends JControllerAdmin
{
    public function getModel($name = 'Tickets', $prefix = 'RsticketsproModel', $config = array('ignore_request' => true))
    {
        return parent::getModel($name, $prefix, $config);
    }

    public function writeCsv()
    {
        $model = $this->getModel('Tickets', 'RsticketsproModel', array('ignore_request' => false));
        $input = JFactory::getApplication()->input;

        try
        {
            $response = $model->writeCSV($input->getInt('start'), $input->get('filehash'));

            $this->showResponse(true, $response);
        }
        catch (Exception $e)
        {
            $this->showResponse(false, $e->getMessage());
        }
    }

    protected function showResponse($success, $data=null)
    {
        $app 		= JFactory::getApplication();
        $document 	= JFactory::getDocument();

        // set JSON encoding
        $document->setMimeEncoding('application/json');

        // compute the response
        $response = new stdClass();
        $response->success = $success;
        if ($data)
        {
            $response->response = $data;
        }

        // show the response
        echo json_encode($response);

        // close
        $app->close();
    }

    public function exportCsv()
    {
        require_once JPATH_ADMINISTRATOR . '/components/com_rsticketspro/helpers/export.php';

        $app      = JFactory::getApplication();
        $filename = JText::_('COM_RSTICKETSPRO_TICKETS');
	    $fileHash = $app->input->get('filehash');

        RsticketsExport::buildCSVHeaders($filename);

        echo RsticketsExport::getCSV($fileHash);

	    $app->close();
    }
}