<?php

use \MediaWiki\MediaWikiServices;
use \MediaWiki\User\UserFactory;
use \Salesforce\OAuthConfig;
use \Salesforce\OAuth;
use \Salesforce\OAuthRequest;
use \Salesforce\RestApiRequest;


class SpecialOAuthEndpoint extends SpecialPage {

    private $oauthFlow = "webserver";

    private $defaultRedirect = "Main_Page";

    private $userInfoEndpoint = "/services/oauth2/userinfo?access_token=";

    public function __construct() {

        parent::__construct("OAuthEndpoint");
    }


    public function execute($parameter) {

	if(session_id() == '') wfSetupSession();
        
	global $oauth_config, $wgRequest;

        $config = new OAuthConfig($oauth_config);

        if($parameter == "login"){

            $response = OAuth::newOAuthResponse($config, $this->oauthFlow);

            $loginUrl = $response->getHeader("Location")->getValue();

            header("Location: $loginUrl");

            exit;
        }


        if($this->authorizationCodeGranted()) {

            $config->setAuthorizationCode($this->getRequest()->getVal("code"));

        } else {

            throw new Exception("OAUTH_ERROR: No authorization code granted");
        }

        // Build the request and send the authorization code returned in the previous step.
        $oauth = OAuthRequest::newAccessTokenRequest($config, $this->oauthFlow);

        $resp = $oauth->authorize();

	$_SESSION["access-token"] = $resp->getAccessToken();
	$_SESSION["instance-url"] = $resp->getInstanceUrl();
    
        $sfUserInfo = $this->getUserInfo($resp->getAccessToken(), $resp->getInstanceUrl());

	$contactId = $this->getContactId($resp->getInstanceUrl(), $resp->getAccessToken(), $sfUserInfo["user_id"]);

	$_SESSION["sf-contact-id"] = $contactId;


        $username = $this->formatMWUsername($sfUserInfo["preferred_username"]);
        $email = $sfUserInfo["email"];

        $user = !$this->userExists($username) ? $this->createUser($username, $email) : $this->loadUser($username);

	$this->getContext()->setUser($user);

        $this->logUserIn();

        $url = $this->getRedirect();

        header("Location: $url");
    }


    public function shouldRedirectToIdentityProvider(){

        return empty($_GET["code"]);
    }

    public function authorizationCodeGranted(){

        return !empty($_GET["code"]);
    }


    public function formatMWUsername($username) {

        return ucfirst($username);
    }


    public function getRedirect() {

        global $wgScriptPath;

        $sessionRedirect = $_SESSION["redirect"];

        $redirect = !empty($sessionRedirect) ? $sessionRedirect : $this->defaultRedirect;

        return "$wgScriptPath/index.php/$redirect";
    }


    public function userExists($username) {

        $user = User::newFromName($username);
        $user->load();

        return $user->getId() != 0;
    }


    public function loadUser($username){
        
        return User::newFromName($username);
    }


    public function createUser($username, $email) {

        $user = User::createNew($username, array());
        $user->setRealName($username);
        $user->setEmail($email);
        $user->setToken();

        return $user;
    }


    public function logUserIn(){

        global $wgUser;

	$user = $this->getUser();
        $user->setCookies();
        $user->saveSettings();
        $wgUser = $user;
    }

    public function getContactId($instanceUrl, $accessToken, $userId){
    
	$api = new RestApiRequest($instanceUrl, $accessToken);
	$query = "SELECT ContactId FROM User WHERE Id = '$userId'";
	$resp = $api->query($query);

	return $resp->getRecord()["ContactId"];
    }


    public function getUserInfo($accessToken, $instanceUrl){

        $req = new RestApiRequest($instanceUrl, $accessToken);

        $resp = $req->send($this->userInfoEndpoint . $accessToken);
            
        return $resp->getBody();
    }
}