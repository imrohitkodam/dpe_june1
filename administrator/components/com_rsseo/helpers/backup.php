<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\Filesystem\Folder;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Path;
use Joomla\Archive\Archive;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;

class RSPackage
{
	protected $_options = array();
	protected $db = null;
	protected $input = null;
	protected $_folder = null;
	protected $_extractfolder = null;
	
	public function __construct($options = array()) {
		$this->db		= Factory::getDBO();
		$this->input	= Factory::getApplication()->input;
		$config			= Factory::getConfig();		
		$this->_options = $options;
		
		$this->setFile();
		
		$tmp_path				= $config->get('tmp_path');
		$tmp_folder				= 'rsbackup_'.$this->getMD5();
		$extract_tmp_folder		= 'rsbackup_'.$this->getMD5File();
		$this->_folder			= Path::clean($tmp_path.'/'.$tmp_folder);
		$this->_extractfolder	= Path::clean($tmp_path.'/'.$extract_tmp_folder);
	}
	
	
	protected function setFile() {
		$file = $this->input->files->get('rspackage', null, 'raw');
		
		if (!empty($file) && $file['error'] == 0)
			$this->_options['file'] = $file;
	}
	
	protected function getMD5() {
		$string = '';
		$queries = $this->getQueries();
		foreach ($queries as $query)
			$string .= $query['query'].';';
		
		return md5($string);
	}
	
	protected function getMD5File() {
		if (isset($this->_options['file'])) {
			return md5($this->_options['file']['name']);
		}
	}
	
	protected function getQueries() {
		if (isset($this->_options['queries'])) {
			return $this->_options['queries'];
		}
		
		return array();
	}
	
	protected function getLimit() {
		$default = 300;
		
		if (isset($this->_options['limit'])) {
			return (int) $this->_options['limit'] <= 0 ? $default : $this->_options['limit'];
		}
			
		return $default;
	}
	
	protected function getFolder() {
		return $this->_folder;
	}
	
	protected function getExtractFolder() {
		return $this->_extractfolder;
	}
	
	public function backup() {
		if ($this->_isRequest()) {
			$this->_parseRequest();
			return;
		}
		
		if ($this->_isDownload()) {
			$this->_startDownload();
			return;
		}
		
		$folder = $this->getFolder();
		if (is_dir(Path::clean($folder))) {
			File::delete(Folder::files($folder, '.xml$', 1, true));
			File::delete(Folder::files($folder, '.tar.gz$', 1, true));
		} else {
			Folder::create($folder);
		}

		$document = Factory::getDocument();
		
		$script = array();
		$script[] = 'var rspackage_queries = new Array();';
		
		$uri		= Uri::getInstance();
		$url		= $uri->toString();
		$limit		= $this->getLimit();
		$queries	= $this->getQueries();
		
		foreach ($queries as $query) {
			$this->db->setQuery($query['query']);
			$results = $this->db->getNumRows($this->db->execute());
			$pages = ceil($results / $limit);
			
			for ($i=0; $i<$pages; $i++) {
				$page				= $i * $limit;
				$query['offset'] 	= $page; 
				$query['limit'] 	= $limit;
				$script[] = 'rspackage_queries.push("'.$this->encode($query).'");';
			}
		}
		
		$script[] = 'var rspackage_requests = new Array();';
		$script[] = 'var totalbackup = 0;';
		$script[] = 'var totalsofarbackup = 0;';
		$script[] = "\n";
		$script[] = 'function rspackage_backup() {';
		$script[] = "\t".'for (var i=0; i<rspackage_queries.length; i++) {';
		$script[] = "\t\t".'var rspackage_query = rspackage_queries[i];';
		$script[] = "\t\t".'var rspackage_request = {query: rspackage_query, ajax: 1, type: "backup"};';
		$script[] = "\t\t".'rspackage_requests.push(rspackage_request);';
		$script[] = "\t\t".'totalbackup++;';
		$script[] = "\t".'}';
		$script[] = '}';
		$script[] = "\n";
		$script[] = 'function rspackage_next(response) {';
		$script[] = "\t".'var comrsseobar = jQuery("#com-rsseo-bar");';
		$script[] = "\t".'var rspackage_progress_bar_unit = 100 / totalbackup;';
		$script[] = "\t".'if (rspackage_requests.length < 1) {';
		$script[] = "\t\t".'if (comrsseobar != undefined) {';
		$script[] = "\t\t\t".'comrsseobar.html("100%");';
		$script[] = "\t\t\t".'comrsseobar.css("width","100%");';
		$script[] = "\t\t".'}';
		$script[] = "\t\t".'rspackage_pack();';
		$script[] = "\t\t".'return;';
		$script[] = "\t".'}';
		$script[] = "\n";
		$script[] = "\t".'if (comrsseobar != undefined) {';
		$script[] = "\t\t".'totalsofarbackup += rspackage_progress_bar_unit;';
		$script[] = "\t\t".'comrsseobar.html(number_format(totalsofarbackup,2) + "%");';
		$script[] = "\t\t".'comrsseobar.css("width", number_format(totalsofarbackup,2) + "%");';
		$script[] = "\t".'}';
		$script[] = "\n";
		$script[] = "\t".'var rspackage_request = rspackage_requests[rspackage_requests.length - 1];';
		$script[] = "\t".'rspackage_requests.pop();';
		$script[] = "\t".'jQuery.ajax({';
		$script[] = "\t\t".'url:"'.$url.'",';
		$script[] = "\t\t".'method: "post",';
		$script[] = "\t\t".'data: rspackage_request';
		$script[] = "\t".'}).done(function( response ) {';
		$script[] = "\t\t".'rspackage_next();';
		$script[] = "\t".'});';
		$script[] = '}';
		$script[] = "\n";
		$script[] = 'function rspackage_pack() {';
		$script[] = "\t".'jQuery.ajax({';
		$script[] = "\t\t".'url:"'.$url.'",';
		$script[] = "\t\t".'method: "post",';
		$script[] = "\t\t".'data: {ajax: 1, pack: 1}';
		$script[] = "\t".'}).done(function( response ) {';
		$script[] = "\t\t".'rspackage_download();';
		$script[] = "\t".'});';
		$script[] = '}';
		$script[] = "\n";
		$script[] = 'function rspackage_download() {';
		$script[] = "\t".'var form = document.createElement("form");';
		$script[] = "\t".'form.setAttribute("action", "'.$url.'");';
		$script[] = "\t".'form.setAttribute("method", "post");';
		$script[] = "\t".'var input = document.createElement("input");';
		$script[] = "\t".'input.setAttribute("type", "hidden");';
		$script[] = "\t".'input.setAttribute("name", "download");';
		$script[] = "\t".'input.setAttribute("value", "1");';
		$script[] = "\t".'form.appendChild(input);';
		$script[] = "\t".'var body = document.body.appendChild(form);';
		$script[] = "\t".'form.submit();';
		$script[] = '}';
		$script[] = "\n";
		$script[] = 'rspackage_backup();';
		$script[] = 'jQuery(document).ready(function (){ rspackage_next(); });';
		
		$document->addScriptDeclaration(implode("\n",$script));
	}
	
	public function restore() {
		$app = Factory::getApplication();
		
		if ($this->_isRequest()) {
			$this->_parseRequest();
			return;
		}
		
		if (!isset($this->_options['file']) || $this->_options['file']['error'] != 0) 
			return;
		
		$db			= Factory::getDBO();
		$document	= Factory::getDocument();
		
		if (isset($this->_options['file']) && $this->_options['file']['error'] == 0) {
			$extract = $this->_extract();
			if ($extract == false) 
				$app->redirect('index.php?option=com_rsseo&view=backup&process=restore',Text::_('COM_RSSEO_RESTORE_ERROR'),'error');
		}
		
		
		$uri 	= Uri::getInstance();
		$url	= $uri->toString();
		$files	= $this->_getFiles();
		$script = array();
		
		$script[] = 'var rspackage_files = new Array();'."\n";
		
		if(!empty($files)) {
			foreach ($files as $file) {
				$script[] = 'rspackage_files.push("'.urlencode($db->escape($file)).'");';
			}
		}
		
		$script[] = 'var rspackage_requests = new Array();';
		$script[] = 'var thetotal = 0;';
		$script[] = 'function rspackage_restore() {';
		$script[] = "\t".'var clear = {ajax: 1, type: "clear", process: "restore"};';
		$script[] = "\t".'rspackage_requests.push(clear);';
		$script[] = "\t".'for (var i=0; i<rspackage_files.length; i++) {';
		$script[] = "\t\t".'var rspackage_file = rspackage_files[i];';
		$script[] = "\t\t".'var rspackage_request = {file: rspackage_file, ajax: 1, type: "restore", process: "restore"};';
		$script[] = "\t\t".'rspackage_requests.push(rspackage_request);';
		$script[] = "\t\t".'thetotal++;';
		$script[] = "\t".'}';	
		$script[] = '}';
		$script[] = "\n";
		$script[] = 'var totalsofar = 0;';
		$script[] = 'var parsedOption = 0;';
		$script[] = 'function rspackage_next(response) {';
		$script[] = "\t".'var rspackage_progress_bar_unit = 100 / thetotal;';
		$script[] = "\t".'var comrsseobar = jQuery("#com-rsseo-bar");';
		$script[] = "\t".'if (parsedOption >= rspackage_requests.length) {';
		$script[] = "\t\t".'if (comrsseobar != undefined) {';
		$script[] = "\t\t\t".'comrsseobar.html("100%");';
		$script[] = "\t\t\t".'comrsseobar.css("width","100%");';
		$script[] = "\t\t".'}';
		$script[] = "\t\t".'document.location = "'.$this->getRedirect().'";';
		$script[] = "\t\t".'return;';
		$script[] = "\t".'}';
		$script[] = "\n";
		$script[] = "\t".'if (comrsseobar != undefined) {';
		$script[] = "\t\t".'totalsofar += rspackage_progress_bar_unit;';
		$script[] = "\t\t".'comrsseobar.html(number_format(totalsofar,2) + "%");';
		$script[] = "\t\t".'comrsseobar.css("width",number_format(totalsofar,2) + "%");';
		$script[] = "\t".'}';
		$script[] = "\n";
		$script[] = "\t".'var rspackage_request = rspackage_requests[parsedOption];';
		$script[] = "\t".'parsedOption++;';
		$script[] = "\t".'jQuery.ajax({';
		$script[] = "\t\t".'url:"'.$url.'",';
		$script[] = "\t\t".'method: "post",';
		$script[] = "\t\t".'data: rspackage_request';
		$script[] = "\t".'}).done(function( response ) {';
		$script[] = "\t\t".'rspackage_next();';
		$script[] = "\t".'});';
		$script[] = '}';
		$script[] = "\n";
		$script[] = 'rspackage_restore();';
		$script[] = 'jQuery(document).ready(function (){ rspackage_next(); });';
		
		$document->addScriptDeclaration(implode("\n",$script));
	}
	
	protected function getRedirect() {
		if (isset($this->_options['redirect']))
			return $this->_options['redirect'].'&delfolder='.base64_encode($this->getExtractFolder());
		
		$uri = Uri::getInstance();
		$url = $uri->toString();
		
		return $url;
	}
	
	protected function _extract() {
		$folder		= $this->getExtractFolder();		
		$file		= $folder.'/'.$this->_options['file']['name'];
		
		//check to see if its a gzip file
		if (!preg_match('#zip#is',$this->_options['file']['name'])) {
			return false;
		}
		
		//upload the file in the tmp folder
		if (!File::upload($this->_options['file']['tmp_name'],$file, false, true)) {
			return false;
		}
		
		//ectract the archive
		$archive = new Archive;
		$extract = $archive->extract($file,$folder);
		
		//delete the archive
		if ($extract) {
			File::delete($file);
		}
		
		return true;
	}
	
	protected function _getFiles() {
		$xmls = array();
		
		if(isset($this->_options['file']) && $this->_options['file']['error'] == 0) {
			$folder = $this->getExtractFolder();
			$xmls = Folder::files($folder, '.xml$', true, true, array('.svn', 'CVS', '.DS_Store', '__MACOSX'), array('^\..*', '.*~'), true);
		}
		
		return $xmls;
	}
	
	
	protected function _isDownload() {
		return $this->input->getInt('download',0);
	}
	
	protected function _startDownload() {
		$file = $this->getFolder().'/package.zip';
		$fsize = filesize($file);
		header("Cache-Control: public, must-revalidate");
		header('Cache-Control: pre-check=0, post-check=0, max-age=0');
		header("Pragma: no-cache");
		header("Expires: 0"); 
		header("Content-Description: File Transfer");
		header("Expires: Sat, 01 Jan 2000 01:00:00 GMT");
		header("Content-Type: application/octet-stream");
		header("Content-Length: ".(string) ($fsize));
		header('Content-Disposition: attachment; filename="rsseo_backup_package_'.date('Y_m_d').'.zip"');
		header("Content-Transfer-Encoding: binary\n");
		@ob_end_flush();
		$this->readfile_chunked($file);
		exit();
	}
	
	protected function readfile_chunked($filename, $retbytes = true) {
		$chunksize = 1*(1024*1024); // how many bytes per chunk
		$buffer = '';
		$cnt =0;
		$handle = fopen($filename, 'rb');
		if ($handle === false) {
			return false;
		}
		while (!feof($handle)) {
			$buffer = fread($handle, $chunksize);
			echo $buffer;
			if ($retbytes) {
				$cnt += strlen($buffer);
			}
		}
		$status = fclose($handle);
		if ($retbytes && $status) {
			return $cnt; // return num. bytes delivered like readfile() does.
		}
		return $status;
	}
	
	protected function _isRequest() {
		return $this->input->getInt('ajax',0);
	}
	
	protected function _parseRequest() {
		$folder = $this->getFolder();
		$type	= $this->input->getString('type');
		
		if ($type) {
			$query	= $this->input->getBase64('query');
			$start	= $this->input->getInt('start',0);
			
			switch ($type) 	{
				case 'clear':
					$tables	= array('#__rsseo_pages', 
						'#__rsseo_redirects', 
						'#__rsseo_keywords', 
						'#__rsseo_errors',
						'#__rsseo_gkeywords',
						'#__rsseo_gkeywords_data'
					);
					
					foreach ($tables as $table) {
						$this->db->truncateTable($table);
					}
					
					$this->db->setQuery("INSERT IGNORE INTO `#__rsseo_pages` SET `url` = '', `published` = 1, `level` = 0");
					$this->db->execute();
				break;
				
				case 'backup':
					$num	 = count(Folder::files($folder, '.xml$', 1, false));
					$buffer  = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
					$buffer .= '<query>'."\n";
					
					$query = $this->decode($query);
					
					$table = '';
					if (preg_match("/#__(\w+)/is", $query['query'], $matches))
						$table = trim($matches[0]);
					
					$buffer .= $this->addTag('table', $table);
					
					$this->db->setQuery($query['query'], $query['offset'], $query['limit']);
					$results = $this->db->loadObjectList();
					
					$buffer .= '<rows>'."\n";
					foreach ($results as $result) {
						$buffer .= '<row>'."\n";
						foreach ($result as $key => $value) {
							if (isset($query['primary']) && $key == $query['primary'])
								continue;
								
							$buffer .= $this->addTag('column',$value,$key);
						}
						$buffer .= '</row>'."\n";
					}
					$buffer .= '</rows>';
					
					$buffer .= "\n".'</query>';
					File::write($folder.'/package'.$num.'.xml', $buffer);
				break;
				
				case 'restore':
					try {
						$file = urldecode($this->input->getString('file'));
						$xml = new SimpleXMLElement($file, null, true);
						
						$table = (string) $xml->table;
						$rows  = $xml->rows->children();
						
						$table_fields = $name = $data = array();
						$fields = $this->db->getTableColumns($table);
						foreach($fields as $field => $type)
							$table_fields[] = $this->db->qn($field);
						
						$thequery = $this->db->getQuery(true);
					} catch (Exception $e) {
						$rows = array();
					}
					
					// Legacy
					$redirectReplace = array($this->db->qn('RedirectFrom') => 'from', $this->db->qn('RedirectTo') => 'to', $this->db->qn('RedirectType') => 'type');
					$pagesReplace = array($this->db->qn('PageURL') => 'url', $this->db->qn('PageTitle') => 'title', $this->db->qn('PageKeywords') => 'keywords', $this->db->qn('PageKeywordsDensity') => 'keywordsdensity', $this->db->qn('PageDescription') => 'description', $this->db->qn('PageSitemap') => 'sitemap', $this->db->qn('PageInSitemap') => 'insitemap', $this->db->qn('PageCrawled') => 'crawled', $this->db->qn('DatePageCrawled') => 'date', $this->db->qn('PageModified') => 'modified', $this->db->qn('PageLevel') => 'level', $this->db->qn('PageGrade') => 'grade');
					
					if (!empty($rows)) {
						foreach ($rows as $row) {
							$sql = array();
							$columns = $row->children();
							
							foreach ($columns as $column) {
								$properties = $column->children();
								foreach($properties as $prop) {
									if ($prop->getName() == 'name') $name[] = $this->db->qn((string) $prop);
									if ($prop->getName() == 'value') $data[] = $this->db->q((string) $prop);
								}
							}
							
							// Legacy
							if ($table == '#__rsseo_redirects') {
								foreach ($name as $j => $prop) {
									if (array_key_exists($prop,$redirectReplace)) {
										$name[$j] = $this->db->qn($redirectReplace[$prop]);
									}
								}
							}
							
							if ($table == '#__rsseo_pages') {
								foreach ($name as $j => $prop) {
									if (array_key_exists($prop,$pagesReplace))
										$name[$j] = $this->db->qn($pagesReplace[$prop]);
										
										if (isset($pagesReplace[$prop]) && $pagesReplace[$prop] == 'date') {
											if (strlen($data[$j]) == 12) {
												$data[$j] = str_replace("'",'',$data[$j]);
												$data[$j] = $this->db->q(Factory::getDate($data[$j])->toSql());
											}
										}
										
								}
							}
							
							foreach($name as $i => $val) {
								if (!in_array($val,$table_fields)) {
									unset($name[$i]);
									unset($data[$i]);
								}
							}
							
							// Remove duplicates
							$unique		= array_unique($name);
							$duplicates = array_diff_assoc($name, $unique);
							
							if (!empty($duplicates)) {
								foreach ($duplicates as $key => $value) {
									if (isset($name[$key])) unset($name[$key]);
									if (isset($data[$key])) unset($data[$key]);
								}
							}
							
							if (!empty($name) && !empty($data)) {
								$updateHome	= false;
								if ($table == '#__rsseo_pages') {
									foreach ($name as $key => $prop) {
										if ($prop == $this->db->qn('url')) {
											if ($data[$key] == $this->db->q('')) {
												$updateHome = true;
											}
										}
									}
								}
								
								$thequery->clear();
								
								if ($updateHome) {
									$updateFields = array();
									foreach ($name as $key => $prop) {
										$updateFields[] = $name[$key].' = '.$data[$key];
									}
									
									$thequery->update($this->db->qn($table))->set($updateFields)->where($this->db->qn('id').' = 1');
								} else {
									$thequery->insert($this->db->qn($table))->columns($name)->values(implode(',', $data));
								}
								
								$this->db->setQuery($thequery);
								$this->db->execute();
								unset($name);unset($data);
							}
						}
					}
					
				break;
			}
		}
		
		$pack = $this->input->getInt('pack', 0);
		if ($pack) {
			$jarchive = new Archive;
			$adapter  = $jarchive->getAdapter('zip');
			
			$archivefiles = array();
			$xmlfiles = Folder::files($folder, '.xml$', 1, true);
			foreach($xmlfiles as $xmlfile) {
				$data = file_get_contents($xmlfile);
				$archivefiles[] = array('name' => basename($xmlfile), 'data' => $data);
			}
			
			if ($adapter->isSupported()) {
				$archive = new RSZip;
				$archive->create($folder.'/package.zip', $archivefiles);
			}
		}
		
		die();
	}
	
	protected function encode($array) {
		return base64_encode(serialize($array));
	}
	
	protected function decode($array) {
		return unserialize(base64_decode($array));
	}
	
	public function displayProgressBar() {
		return '<div id="com-rsseo-import-progress" class="com-rsseo-progress"><div style="width: 1%;" id="com-rsseo-bar" class="com-rsseo-bar">0%</div></div>';
	}
	
	protected function addTag($tag, $value, $name = null) {
		if (is_null($name)) {
			return "\t".'<'.$tag.'>'.$this->xmlentities($value).'</'.$tag.'>'."\n";
		} else {
			return "\t".'<'.$tag.'>'."\n"."\t\t".'<name>'.$this->xmlentities($name).'</name>'."\n\t\t".'<value>'.$this->xmlentities($value).'</value>'."\n\t".'</'.$tag.'>'."\n";
		}
	}
	
	protected function xmlentities($string, $quote_style=ENT_QUOTES) {
		return htmlspecialchars($string,$quote_style,'UTF-8');
	}
}