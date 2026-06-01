<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */
defined('_JEXEC') or die('Restricted access');

abstract class RsticketsExport
{
    public static function buildCSV($data, $fileHash = '')
	{
        // accepted headers and keys
        $accepted_headers = array(
            'date',
            'last_reply',
            'replies',
            'code',
            'subject',
            'message',
            'last_reply_message',
            'customer',
            'priority',
            'status',
            'staff'
        );

        $enable_time_spent = RSTicketsProHelper::getConfig('enable_time_spent');

		if ($enable_time_spent)
		{
			$accepted_headers[] = 'time_spent';
		}

        $enclosure = RSTicketsProHelper::getConfig('csv_enclosure');
        $delimiter = RSTicketsProHelper::getConfig('csv_delimiter');

        $rows = '';
        if ($fileHash == '')
        {
            $headers = array();
            foreach ($accepted_headers as $header)
            {
                if ($header == 'time_spent')
                {
                    $headers[] =  JText::_('RST_TIME_SPENT');
                }
                else
                {
                    $headers[] =   JText::_('RST_TICKET_'.strtoupper($header));
                }
            }

            // Add header to rows
            $rows .= $enclosure . implode($enclosure . $delimiter . $enclosure, $headers) . $enclosure . "\n";
        }

        // load the dbo object
        $db  = JFactory::getDbo();

        // Add the data to rows
        foreach ($data as $i => $entry)
        {
            $row = array_flip($accepted_headers);

            // get the ticket 1st message
            $query   = $db->getQuery(true)
                ->select($db->qn('message'))
                ->from($db->qn('#__rsticketspro_ticket_messages'))
                ->where($db->qn('ticket_id') . '=' . $db->q($entry->id))
                ->where($db->qn('user_id') . '!=' . $db->q('-1'))
                ->order($db->qn('date') . ' ' . $db->escape('asc'));

            $row['message'] = $db->setQuery($query, 0, 1)->loadResult();

            // get the last reply
            $query   = $db->getQuery(true)
                ->select($db->qn('message'))
                ->from($db->qn('#__rsticketspro_ticket_messages'))
                ->where($db->qn('ticket_id') . '=' . $db->q($entry->id))
                ->where($db->qn('user_id') . '!=' . $db->q('-1'))
                ->order($db->qn('date') . ' ' . $db->escape('desc'));

            $row['last_reply_message'] = $db->setQuery($query, 0, 1)->loadResult();

            // if they are the same, than the last reply does not exists
            if ($row['message'] === $row['last_reply_message'])
            {
                $row['last_reply_message'] = '';
            }

            // remake the array with the fields that we need to output and remodel for a human readable format
            foreach ((array) $entry as $key => $value)
            {
                //skip if is not supposed to be added
                if (!in_array($key,  $accepted_headers))
                {
                    continue;
                }

                if ($key == 'time_spent' && !$enable_time_spent)
                {
                    unset($row[$key]);
                    continue;
                }

                 switch ($key)
				 {
                     case 'date':
                     case 'last_reply':
                        $row[$key] = JHtml::_('date', $value, RSTicketsProHelper::getConfig('date_format'));
                     break;

                     case 'staff';
                         $row[$key] = $entry->staff_id ? $value : JText::_('RST_UNASSIGNED');
                     break;

                     case 'time_spent';
                         $row[$key] = RSTicketsProHelper::showTotal($value, true);
                     break;

                     default:
                         $row[$key] = $value;
                     break;
                 }
            }

            array_walk($row, array('RsticketsExport', 'fixValue'));

            $rows .= $enclosure . implode($enclosure . $delimiter . $enclosure, $row) . $enclosure;
            $rows .="\n";
        }

        return $rows;
    }

    public static function fixValue(&$string, $key)
    {
        $enclosure  = RSTicketsProHelper::getConfig('csv_enclosure');
        $stripTags  = RSTicketsProHelper::getConfig('csv_strip_tags');
        $stripLines = RSTicketsProHelper::getConfig('csv_strip_newlines');

		if (is_null($string))
		{
			$string = '';
		}

        if ($stripTags)
        {
            $string = strip_tags($string);
        }

        if ($stripLines)
        {
            $string = str_replace(array("\n", "\r\n", "\r"), ' ', $string);
        }

        if (strlen($string) && in_array(substr($string, 0, 1), array('=', '+', '-', '@')))
        {
            $string = ' ' . $string;
        }

        $string = str_replace(array($enclosure), array($enclosure.$enclosure), $string);
    }

    public static function writeCSV($query, $totalItems, $from, $fileHash, $filename)
	{
        if (!is_writable(JFactory::getApplication()->get('tmp_path'))) {
            throw new Exception(JText::sprintf('COM_RSTICKETSPRO_TMP_PATH_NOT_WRITABLE', JFactory::getApplication()->get('tmp_path')));
        }

        $db	= JFactory::getDbo();
        $db->setQuery($query, $from, RSTicketsProHelper::getConfig('export_limit'));
        $data = $db->loadObjectList();

        $fileContent = RsticketsExport::buildCSV($data, $fileHash);

        // build the file hash if not already created
        if (!$fileHash) {
            $now 		= JHtml::date('now','D, d M Y H:i:s');
            $date 		= JHtml::date('now','Y-m-d_H-i-s');
            $filename 	= $filename.'-'.$date.'.csv';
            $fileHash 	= md5($filename.$now);
        }

        // create or append the hashed file and put content
        if ($fileContent) {
            if (!file_put_contents(self::getTmpPath($fileHash), $fileContent, FILE_APPEND)) {
                throw new Exception(JText::sprintf('COM_RSTICKETSPRO_COULD_NOT_EXPORT_CSV_PATH', self::getTmpPath($fileHash)));
            }
        } else {
            throw new Exception(JText::_('COM_RSTICKETSPRO_EXPORT_NO_DATA'));
        }

        $newFrom 		= ($from + RSTicketsProHelper::getConfig('export_limit'));
        $checkRemaining = $totalItems - $newFrom;

        return (object) array(
            'newFrom' 	=> ($checkRemaining > 0 ? $newFrom : $totalItems),
            'fileHash'	=> $fileHash
        );
    }

    public static function getCSV($fileHash)
	{
        $file 		= self::getTmpPath($fileHash);
        $content 	= is_file($file) ? file_get_contents($file) : '';
        return $content;
    }

    protected static function getTmpPath($fileHash)
	{
        return JFactory::getApplication()->get('tmp_path').'/'.$fileHash;
    }

    public static function buildCSVHeaders($filename)
	{
        // disable caching
        $now = JHtml::date('now','D, d M Y H:i:s');
        $date = JHtml::date('now','Y-m-d_H-i-s');
        $filename = $filename.'-'.$date.'.csv';

        header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
        header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
        header("Last-Modified: ".$now." GMT");

        // force download
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");

        // disposition / encoding on response body
        header("Content-Disposition: attachment;filename=".$filename);
        header("Content-Transfer-Encoding: binary");
    }

}