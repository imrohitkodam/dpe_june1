<?php
/**
* @package RSSeo!
* @copyright (C) 2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

use Dompdf\Dompdf;
use Dompdf\Options;

class RsseoPDF
{
	public $dompdf;
	
	public function __construct() {
		if (!isset($this->dompdf)) {
			require_once JPATH_ADMINISTRATOR.'/components/com_rsseo/helpers/dompdf/vendor/autoload.php';
			
			$data = $this->getData();
			
			$options = new Options();
			$options->set('defaultFont', $data['font']);
			$options->set('defaultPaperSize', $data['paper']);
			$options->set('defaultPaperOrientation', $data['orientation']);
			
			$this->dompdf = new Dompdf($options);
		}
	}
	
	public static function getInstance() {
		return new RsseoPDF();
	}
	
	public function render($filename, $html) {
		// suppress errors
		if (strlen($html) > 0) {
			$dompdf	= &$this->dompdf;
			
			if (preg_match_all('#[^\x00-\x7F]#u', $html, $matches)) {
				foreach ($matches[0] as $match) {
					$html = str_replace($match, $this->_convertASCII($match), $html);
				}
			}
			
			$dompdf->load_html($html, 'utf-8');
			$dompdf->render();
			
			$dompdf->stream($filename);
		}
	}
	
	public function output($filename, $html) {
		// suppress errors
		if (strlen($html) > 0) {
			$dompdf	= &$this->dompdf;
			
			if (preg_match_all('#[^\x00-\x7F]#u', $html, $matches)) {
				foreach ($matches[0] as $match) {
					$html = str_replace($match, $this->_convertASCII($match), $html);
				}
			}
			
			$dompdf->load_html($html, 'utf-8');
			$dompdf->render();
			
			return $dompdf->output();
		}
	}
	
	protected function _convertASCII($str) {
		$count	= 1;
		$out	= '';
		$temp	= array();
		
		for ($i = 0, $s = strlen($str); $i < $s; $i++) {
			$ordinal = ord($str[$i]);
			if ($ordinal < 128) {
				$out .= $str[$i];
			} else {
				if (count($temp) == 0) {
					$count = ($ordinal < 224) ? 2 : 3;
				}
			
				$temp[] = $ordinal;
			
				if (count($temp) == $count) {
					$number = ($count == 3) ? (($temp['0'] % 16) * 4096) + (($temp['1'] % 64) * 64) + ($temp['2'] % 64) : (($temp['0'] % 32) * 64) + ($temp['1'] % 64);

					$out .= '&#'.$number.';';
					$count = 1;
					$temp = array();
				}
			}
		}
		
		return $out;
	}
	
	protected function getData() {
		$config		= rsseoHelper::getConfig();
		$default	= array('font' => 'times', 'paper' => 'a4', 'orientation' => 'portrait');
		
		if (isset($config->report)) {
			if ($data = json_decode($config->report)) {
				return array('font' => $data->font, 'paper' => $data->paper, 'orientation' => $data->orientation);
			}
		}
		
		return $default;
	}
}