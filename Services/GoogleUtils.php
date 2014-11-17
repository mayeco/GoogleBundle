<?php

namespace Mayeco\GoogleBundle\Services;

use Google_Client;
use AdWordsUser;
use Lsw\MemcacheBundle\Cache\MemcacheInterface;

class GoogleUtils
{

    protected $adwordsuser;
    protected $adwordsversion;
    protected $apiclient;
    protected $memcache;

    public function __construct(
        AdWordsUser $adwordsuser, 
        Google_Client $apiclient, 
        MemcacheInterface $memcache, 
        $adwordsversion
    ) {

        $this->adwordsuser = $adwordsuser;
        $this->apiclient = $apiclient;
        $this->memcache = $memcache;
        $this->adwordsversion = $adwordsversion;
    }
    
    public function DownloadReportWithAwql($reportQuery, $format="CSV", array $options = NULL) {
        
        $allowformats = array("CSV", "XML", "TSV", "GZIPPED_CSV", "GZIPPED_XML");
        if (!in_array($format, $allowformats))
            return;
        
        if(!$this->ValidateUser())
            return;
        
        $report = null;
        try {
            
            $report = \ReportUtils::DownloadReportWithAwql($reportQuery, null, $this->adwordsuser, $format, $options);
            
            if("GZIPPED_CSV" == $format || "GZIPPED_XML" == $format)
                $report = gzdecode ($report);

        } catch (\Exception $e) {
            
            return;
        }
        
        return $report;
    }
    
    public function setAdwordsOAuth2Validate($fulltoken) {
        
        if(!isset($fulltoken["access_token"]) || !isset($fulltoken["refresh_token"])) {
            throw new \Exception('No access token or refresh token.');
        }

        $oauth = $this->adwordsuser->GetOAuth2Info();
        $oauth["refresh_token"] = $fulltoken["refresh_token"];
        $oauth["access_token"] = $fulltoken["access_token"];

        $this->adwordsuser->SetOAuth2Info($oauth);

        return $this->ValidateUser();
    }

    public function ValidateUser() {

        try {

            $this->adwordsuser->ValidateUser();

        } catch (\Exception $e) {

            return;
        }

        return true;
    }

    public function GetAdwordsService($service) {

        if(!$this->ValidateUser())
            return;
            
        try {

            $service = $this->adwordsuser->GetService($service);

        } catch (\Exception $e) {

            return;
        }

        return $service;
    }

    public function GetAdwordsUser() {

        if(!$this->ValidateUser())
            return;

        return $this->adwordsuser;
    }

    public function GetGoogleApi() {

        return $this->apiclient;
    }

    public function createAuthUrl() {
        
        return $this->apiclient->createAuthUrl();
    }

    public function setAdwordsId($adwordsid) {
        
        $this->adwordsuser->SetClientCustomerId($adwordsid);
        
    }

    public function authenticate($code) {

        try {

            $jsontoken = $this->apiclient->authenticate($code);
            $verify_token = $this->apiclient->verifyIdToken();
            $user_id = $verify_token->getUserId();

            $fulltoken = json_decode($jsontoken, true);
            $this->setAdwordsOAuth2Validate($fulltoken);

        } catch (\Exception $e) {

            return;
        }
        
        $this->memcache->set($user_id . '_token', $jsontoken, $fulltoken["expires_in"] - 60);
        
        return array(
            "user_id" => $user_id, 
            "access_token" => $fulltoken["access_token"], 
            "refresh_token" => $fulltoken["refresh_token"], 
        );
    }

    public function relogin($id, $refreshToken) {

        if( !$jsontoken = $this->memcache->get($id . '_token')  ) {

            try {

                $this->apiclient->refreshToken($refreshToken);
                $verify_token = $this->apiclient->verifyIdToken();
                if($verify_token->getUserId() != $id)
                    return;
                
                $jsontoken = $this->apiclient->getAccessToken();
                
            } catch (\Exception $e) {

                $this->memcache->delete($id . '_token');
                return;
            }
            
            $fulltoken = json_decode($jsontoken, true);
            $this->memcache->set($id . '_token', $jsontoken, $fulltoken["expires_in"] - 60);
        }

        try {

            $this->apiclient->setAccessToken($jsontoken);

            $fulltoken = json_decode($jsontoken, true);
            $this->setAdwordsOAuth2Validate($fulltoken);
            
            $service = new \Google_Service_Oauth2($this->apiclient);
            $tokeninfo = $service->tokeninfo(
                array(
                    "access_token" => $fulltoken["access_token"]
                )
            );
				
        } catch (\Exception $e) {
            
            $this->memcache->delete($id . '_token');
            return;
        }

        return array(
            "accessType" => $tokeninfo->accessType, 
            "audience" => $tokeninfo->audience, 
            "email" => $tokeninfo->email, 
            "expiresIn" => $tokeninfo->expiresIn, 
            "issuedTo" => $tokeninfo->issuedTo, 
            "scope" => $tokeninfo->scope, 
            "userId" => $tokeninfo->userId, 
            "verifiedEmail" => $tokeninfo->verifiedEmail, 
        );
    }

}
