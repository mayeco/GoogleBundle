<?php

namespace Mayeco\GoogleBundle\Services;

use Google_Client;
use AdWordsUser;
use Lsw\MemcacheBundle\Cache\MemcacheInterface;

/**
 * Class GoogleUtils
 * @package Mayeco\GoogleBundle\Services
 */
class GoogleUtils
{

    /**
     * @var AdWordsUser
     */
    protected $adwordsuser;
    
    /**
     * @var Google_Client
     */
    protected $googleclient;
    
    /**
     * @var MemcacheInterface
     */
    protected $memcache;

    /**
     * @param AdWordsUser $adwordsuser
     * @param Google_Client $googleclient
     * @param MemcacheInterface $memcache
     */
    public function __construct(
        AdWordsUser $adwordsuser,
        Google_Client $googleclient,
        MemcacheInterface $memcache
    )
    {
        $this->adwordsuser = $adwordsuser;
        $this->googleclient = $googleclient;
        $this->memcache = $memcache;
    }

    /**
     * @param $reportQuery
     * @param string $format
     * @param array $options
     * @return null|string|void
     */
    public function DownloadReportWithAwql($reportQuery, $format = "CSV", array $options = NULL)
    {
        $allowformats = array("CSV", "XML", "TSV", "GZIPPED_CSV", "GZIPPED_XML");
        if (!in_array($format, $allowformats))
            return;

        if (!$this->ValidateUser())
            return;

        $report = null;
        try {

            $report = \ReportUtils::DownloadReportWithAwql($reportQuery, null, $this->adwordsuser, $format, $options);

            if ("GZIPPED_CSV" == $format || "GZIPPED_XML" == $format)
                $report = gzdecode($report);

        } catch (\Exception $e) {

            return;
        }

        return $report;
    }

    /**
     * @param $fulltoken
     * @return bool|void
     * @throws \Exception
     */
    public function setAdwordsOAuth2Validate($fulltoken)
    {
        if (!isset($fulltoken["access_token"]) || !isset($fulltoken["refresh_token"])) {
            throw new \Exception('No access token or refresh token.');
        }

        $oauth = $this->adwordsuser->GetOAuth2Info();
        $oauth["refresh_token"] = $fulltoken["refresh_token"];
        $oauth["access_token"] = $fulltoken["access_token"];

        $this->adwordsuser->SetOAuth2Info($oauth);

        return $this->ValidateUser();
    }

    /**
     * @return bool|void
     */
    public function ValidateUser()
    {
        try {

            $this->adwordsuser->ValidateUser();

        } catch (\Exception $e) {

            return;
        }

        return true;
    }

    /**
     * @param $service
     */
    public function GetAdwordsService($service)
    {
        if (!$this->ValidateUser())
            return;

        try {

            $service = $this->adwordsuser->GetService($service);

        } catch (\Exception $e) {

            return;
        }

        return $service;
    }

    /**
     * @return AdWordsUser
     */
    public function GetAdwordsUser()
    {
        return $this->adwordsuser;
    }

    /**
     * @return Google_Client
     */
    public function GetGoogleClient()
    {
        return $this->googleclient;
    }

    /**
     * @return mixed
     */
    public function createAuthUrl()
    {
        return $this->googleclient->createAuthUrl();
    }

    /**
     * @param $adwordsid
     */
    public function setAdwordsId($adwordsid)
    {
        $this->adwordsuser->SetClientCustomerId($adwordsid);
    }

    /**
     * @param $code
     * @return array|void
     */
    public function Authenticate($code)
    {
        try {

            $jsontoken = $this->googleclient->authenticate($code);
            $verify_token = $this->googleclient->verifyIdToken();
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
            "expires_in" => $fulltoken["expires_in"],
        );
    }

    /**
     * @param $id
     * @param $refreshToken
     * @return array|void
     */
    public function RefreshAccess($id, $refreshToken)
    {
        if (!$jsontoken = $this->memcache->get($id . '_token')) {

            try {

                $this->googleclient->refreshToken($refreshToken);
                $verify_token = $this->googleclient->verifyIdToken();
                if ($verify_token->getUserId() != $id)
                    return;

                $jsontoken = $this->googleclient->getAccessToken();

            } catch (\Exception $e) {

                $this->memcache->delete($id . '_token');
                return;
            }

            $fulltoken = json_decode($jsontoken, true);
            $this->memcache->set($id . '_token', $jsontoken, $fulltoken["expires_in"] - 60);
        }

        try {

            $this->googleclient->setAccessToken($jsontoken);

            $fulltoken = json_decode($jsontoken, true);
            $fulltoken["refresh_token"] = $refreshToken;
            $this->setAdwordsOAuth2Validate($fulltoken);

            $service = new \Google_Service_Oauth2($this->googleclient);
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
