<?php

/**
 * JCH Optimize - Performs several front-end optimizations for fast downloads
 *
 * @package   jchoptimize/core
 * @author    Samuel Marshall <samuel@jch-optimize.net>
 * @copyright Copyright (c) 2020 Samuel Marshall / JCH Optimize
 * @license   GNU/GPLv3, or later. See LICENSE file
 *
 * If LICENSE file missing, see <http://www.gnu.org/licenses/>.
 */

namespace JchOptimize\Core;

defined('_JCH_EXEC') or die('Restricted access');

use CURLFile;

class ImageOptimizer
{

        protected $auth = array();

        public function __construct($dlid, $secret)
        {
                $this->auth = array(
                        'auth' => array( 
                                'dlid' => $dlid,
                                'secret' => $secret
                        )
                );
        }

        public function upload($opts = array())
        {
                if (empty($opts['files'][0]))
                {
                        throw new \Exception('File parameter was not provided', 500);
                }

                //if (!files_exists($opts['files']))
                //{
                //        throw new Exception('File \'' . $opts['files'] . '\' does not exist', 404);
                //}

		$files = array();

		foreach($opts['files'] as $i => $file)
		{
			if (class_exists('CURLFile'))
			{
				$files['files[' . $i . ']'] = new CURLFile($file);
			}
			else
			{
				$files['files[' . $i . ']'] = '@' . $file;
			}
		}

                unset($opts['files']);

		$data = array_merge($files, array( "data" => json_encode(array_merge($this->auth, $opts))));

                $response = self::request($data, "https://api.jch-optimize.net/");

                return $response;
        }

        private function request($data, $url)
        {
		ini_set('upload_max_filesize', '50M');
		ini_set('post_max_size', '50M');
		ini_set('max_input_time', 300);
		ini_set('max_execution_time', 300);

		$aHeaders = array('Content-Type' => 'multipart/form-data');
		$oFileRetriever = FileRetriever::getInstance(array('curl'));

		$response = $oFileRetriever->getFileContents($url, $data, $aHeaders, '', 30);

		if($oFileRetriever->response_code === 0 && $oFileRetriever->response_error !== '')
		{
			return new Json(new \Exception($oFileRetriever->response_error), 500);
		}

		return json_decode($response);
	}

	public static function curlRequest($url, $data)
	{
                $curl = curl_init();

                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                curl_setopt($curl, CURLOPT_FAILONERROR, 1);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($curl, CURLOPT_CAINFO, dirname(__FILE__) . '/libs/cacert.pem');
                curl_setopt($curl, CURLOPT_TIMEOUT, 300);
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 300);

		$response = curl_exec($curl);
                $error    = curl_errno($curl);
                $message  = curl_error($curl);

                curl_close($curl);

                if ($error > 0)
                {
                        $curl_error = new \RuntimeException(sprintf('cURL returned with the following error: "%s"', $message), $error);
			$response = new Json($curl_error);
                }

		return array(
			'body' => $response,
			'code' => 200
		);
        }
}
