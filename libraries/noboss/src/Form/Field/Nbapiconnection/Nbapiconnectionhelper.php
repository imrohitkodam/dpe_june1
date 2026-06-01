<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Library
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2024 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

namespace Noboss\Library\Form\Field\Nbapiconnection;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Noboss\Library\Api\NbFacebookApi;
use Noboss\Library\Api\NbGoogleCalendarApi;
use Noboss\Library\Api\NbVimeoApi;
use Noboss\Library\Api\NbGoogleApi;
use Noboss\Library\Api\NbYoutubeApi;
use Noboss\Library\Util\NbModulesUtil;

defined('_JEXEC') or die;

/**
 * Classe de campo personalizado de conexao com api (utilizada diretamente pelos modulos Calendar e Video Gallery)
 */
class Nbapiconnectionhelper {

    /**
	 * Método recebe requisiçao pare gerar o code e o primeiro access token
     *     1º envia usuario para url da api que solicita autenticacao do usuario
     *     2º api externa redireciona de volta para essa funcao onde guardamos os tokens
	 */
	public static function generateToken() {
		$app = Factory::getApplication();
        $get = $app->input->get;
        
        $clientId = $get->get('client_id', '', "RAW");
        $clientSecret = $get->get('client_secret', '', "RAW");
        $code = $get->get('code', '', "RAW");
        $api = $get->get('api', '', "RAW");

        // Quando possui '+' o Joomla acaba trocando por ' ' e por isso temos que ajustar
        $clientSecret = str_replace(" ", "+", $clientSecret);

        if(empty($api)){
            exit('Api not defined.');
        }
        
        $session = Factory::getSession();

        // Seta o ID client na sessao durante primeira requisicao para obter novamente na segunda requisicao
        if ($clientId) {
            $session->set('client_id_'.$api, $clientId);
        } else {
            $clientId = $session->get('client_id_'.$api); 
        }  

        // Seta o secret na sessao durante primeira requisicao para obter novamente na segunda requisicao
        if ($clientSecret) {
            $session->set('client_secret_'.$api, $clientSecret);
        } else {
            $clientSecret = $session->get('client_secret_'.$api); 
        }

        $urlCurrent = Uri::getInstance()->toString();

        // Gera a url de redirecionamento (corta tudo que vem depois de &format=raw)
        $redirectURI = explode('&format=raw', $urlCurrent);
        $redirectURI = $redirectURI[0].'&format=raw';

        //FIXME: redirecionamento temporario para testes da api em localhost
        //$redirectURI = str_replace("http://localhost/", "https://desenv.noboss.com.br/", $redirectURI);

        $error = '';

        try{
            // Executa metodos conforme API requisitada
            switch ($api) {
                // Youtube
                case 'youtube':
                    $youtube = new NbYoutubeApi($redirectURI, array('client_id' => $clientId, 'client_secret' => $clientSecret, 'code' => $code));
                    // Abre tela de autenticacao e depois redireciona de volta para essa funcao aqui com os tokens
                    $tokenValues = $youtube->authorize(); 
                break;

                // Google Calendar
                case 'googlecalendar':
                    $googlecalendar = new NbGoogleCalendarApi($redirectURI, array('client_id' => $clientId, 'client_secret' => $clientSecret, 'code' => $code));
                    // Abre tela de autenticacao e depois redireciona de volta para essa funcao aqui com os tokens
                    $tokenValues = $googlecalendar->authorize(); 
                break;

                // Vimeo
                case 'vimeo':
                    $vimeo = new NbVimeoApi($redirectURI, array('client_id' => $clientId, 'client_secret' => $clientSecret, 'code' => $code));
                    // Abre tela de autenticacao e depois redireciona de volta para essa funcao aqui com os tokens
                    $tokenValues = $vimeo->authorize(); 
                    break;
                    
                // Facebook
                case 'facebook':
                    $facebook = new NbFacebookApi($redirectURI, array('client_id' => $clientId, 'client_secret' => $clientSecret, 'code' => $code));
                    // Abre tela de autenticacao e depois redireciona de volta para essa funcao aqui com os tokens
                    $tokenValues = $facebook->authorize(); 
                break;

                default:
                    exit('API type not defined in NobossNobossapiconnectionhelper:generateToken()');
                break;
            }

            if(empty($tokenValues)){
                $error = 'Token not obtained in NobossNobossapiconnectionhelper:generateToken()';
            }
        }catch (\Exception $e){
            $error .= $e->getMessage();
            if(!empty($e->getCode())){
                $error .= "<br> Error code: ".$e->getCode();
            }
        }

        if(!empty($error)){
            exit($error);
        }

        // Executa funcao do JS que salva os tokens no input hidden da pagina
        echo '<script type="text/javascript">window.opener.nobossapiconnection.saveApiToken(JSON.parse('.json_encode($tokenValues).'), "'.$api.'"); window.close();</script>';
        
        exit;
    }

    /**
	 * Obtem access_token valido (recebe um verificando se eh valido ou gera um novo a partir de um refresh_token)
	 */
    public static function getValidAccessToken() {
        error_reporting(0);
		$app = Factory::getApplication();
        $input = $app->input;
       
        $clientId = $input->get('client_id', '', "RAW");
        $clientSecret = $input->get('client_secret', '', "RAW");
        $refreshToken = $input->get('refresh_token', '', "RAW");
        $accessToken = $input->get('access_token', '', "RAW");
        $api = $input->get('api', '', "RAW");

        $response = array();

        if(empty($api)){
            $response['success'] = 0;
            $response['status'] = '-1';
            $response['message'] = 'Api not defined.';
            exit(json_encode($response));
        }

        try{
            // Executa metodos conforme API requisitada
            switch ($api) {
                // Apis do google
                case 'youtube':
                case 'googlecalendar':                   
                    // Obtem access token valido (caso o atual nao seja ainda valido)
                    $validAccessToken = NbGoogleApi::getValidAccessToken($clientId, $clientSecret, $refreshToken, $accessToken);

                    // Access token nao era valido e nao foi possivel gerar um novo (refresh_token inválido)
                    if($validAccessToken == false) {
                        $response['success'] = 1;
                        $response['status'] = '0';
                        exit(json_encode($response));
                    }

                    // Nao foi alterado o access token
                    if($accessToken == $validAccessToken){
                        $response['success'] = 1;
                        $response['status'] = '1';
                        $response['new_access_token'] = '';
                        exit(json_encode($response));
                    }

                    // Alterado o access token
                    $response['success'] = 1;
                    $response['status'] = '1';
                    $response['new_access_token'] = $validAccessToken;
                    exit(json_encode($response));

                break;

                // Vimeo
                case 'vimeo':                   
                    // Obtem access token valido (caso o atual nao seja ainda valido)
                    $validAccessToken = NbVimeoApi::getValidAccessToken($clientId, $clientSecret, $refreshToken, $accessToken);

                    // Access token nao era valido e nao foi possivel gerar um novo (refresh_token inválido)
                    if($validAccessToken == false) {
                        $response['success'] = 1;
                        $response['status'] = '0';
                        exit(json_encode($response));
                    }

                    // Nao foi alterado o access token
                    if($accessToken == $validAccessToken){
                        $response['success'] = 1;
                        $response['status'] = '1';
                        $response['new_access_token'] = '';
                        exit(json_encode($response));
                    }

                    // Alterado o access token
                    $response['success'] = 1;
                    $response['status'] = '1';
                    $response['new_access_token'] = $validAccessToken;
                    exit(json_encode($response));

                    break;

            }
        } catch (\Exception $e){
            if(!empty($e->getCode())){
                $error = "Code: ".$e->getCode();
            }
            $error .= $e->getMessage();

            $response['success'] = 0;
            $response['status'] = '-1';
            $response['message'] = $error;
            exit(json_encode($response));
        }
    }

    /**
     * Atualiza no banco de dados o valor de access_token (considerando que campo esteja dentro de uma modal de um modulo)
	 *
     * @param   String      $accessToken    Token de acesso para requisicao na API
     * @param   Int         $idModule       Id do modulo
     * @param   String      $aliasField     Alias da modal (qnd tiver modal) ou alias do field de conexao (qnd nao tiver modal)
     * @param   Boolean     $isModal        Informa se os dados da conexao estao em uma modal
     * 
     * @return  Boolean     true ou false
	 */
    public static function updateAccessTokenDb($accessToken, $idModule, $aliasField, $isModal = true){
        // Obtem parametros do modulo
        $dataParams = NbModulesUtil::getDataModule($idModule, true); 

        // Campo de conexao esta dentro de modal
        if($isModal){
            // Extrai somente os parametros do campo de api carregado na modal
            $paramsModalApi = json_decode($dataParams->$modalName);
            if(empty($paramsModalApi)){
                return false;
            }

            $connection = $paramsModalApi->connection;
        }
        else{
            $connection = $dataParams->{$aliasField};
        }

        // Extrai dados da conexao da API
        $paramsApiConnection = json_decode($connection);
        if(empty($paramsApiConnection)){
            return false;
        }

        // Atualiza parametro do token conforme novo valor recebido
        $paramsApiConnection->access_token = $accessToken;

        // Encoda novamente os parametros
        $connection = json_encode($paramsApiConnection);

        // Campo de conexao esta dentro de modal
        if($isModal){
           $paramsModalApi->connection = $connection;
           $paramsModalApi = json_encode($paramsModalApi);
           $dataParams->$modalName = $paramsModalApi;
        }
        else{
            $dataParams->{$aliasField} = $connection;
        }

        // Atualiza o access_token no banco
        if(NbModulesUtil::setDataModule($idModule, $dataParams, true)){
            return true;
        }
        return false;
    }
}
