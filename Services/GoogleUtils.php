<?php

namespace Mayeco\GoogleBundle\Services;

use Lsw\MemcacheBundle\Cache\AntiDogPileMemcache;

class GoogleUtils
{

    protected $adwordsuser;
    protected $adwordsversion;
    protected $apiclient;
    protected $memcache;

    public function __construct(\AdWordsUser $adwordsuser, \Google_Client $apiclient, AntiDogPileMemcache $memcache, $adwordsversion) {

        $this->adwordsuser = $adwordsuser;
        $this->apiclient = $apiclient;
        $this->adwordsversion = $adwordsversion;
        $this->memcache = $memcache;
    }

    public function ConvertMicrosInCsvDataReport($csvdata, $title = null) {

        //inicia conversion micros
        $count = 0;
        $costpos = array();
        $tmpfname = tempnam(sys_get_temp_dir(), "ZZZ");
        $outstream = fopen($tmpfname, "r+");
        foreach(preg_split("/((\r?\n)|(\r\n?))/", $csvdata) as $line){

            if($count == 0) {
                $count++;
                if($title) {
                    fputcsv($outstream, array($title));
                }

                continue;
            }

            $linearray = str_getcsv($line);

            if($count == 1) {
                //this is header
                $postvalue = 0;

                foreach($linearray as $value){
                    if (strripos($value,'cost') !== false || strripos($value,'cpc') !== false) {
                        $costpos[] = $postvalue;
                    }
                    $postvalue++;
                }

                fputcsv($outstream, $linearray);
                $count++;
                continue;
            }

            if(!empty($costpos)) {

                foreach($costpos as $costpostval){

                    if(isset($linearray[$costpostval])) {
                        $linearray[$costpostval] = $linearray[$costpostval] / 1000000;
                    }

                }
            }

            fputcsv($outstream, $linearray);
            $count++;
        }

        fclose($outstream);

        return $tmpfname;
    }


    public function DownloadReportWithAwql($awql, $format="CSV") {

        if(!$this->ValidateAdwordsOAuth2Info())
            return;

        $report = null;
        try {

            $options = array('version' => $this->adwordsversion);
            $report = \ReportUtils::DownloadReportWithAwql($awql, NULL, $this->adwordsuser, $format, $options);

        } catch (\Exception $e) {

            return;
        }

        return $report;
    }

    public function setAdwordsOAuth2Validate($refreshToken, $accessToken) {

        $oauth = $this->adwordsuser->GetOAuth2Info();
        $oauth["refresh_token"] = $refreshToken;
        $oauth["access_token"] = $accessToken;

        $this->adwordsuser->SetOAuth2Info($oauth);

        return $this->ValidateAdwordsOAuth2Info();
    }

    public function ValidateAdwordsOAuth2Info() {

        try {

            $this->adwordsuser->ValidateOAuth2Info();

        } catch (\Exception $e) {

            return;
        }

        return true;
    }

    public function GetAdwordsService($service) {

        if(!$this->ValidateAdwordsOAuth2Info())
            return;


        try {

            $service = $this->adwordsuser->GetService($service, $this->adwordsversion);

        } catch (\Exception $e) {

            return;
        }

        return $service;
    }

    public function GetAdwordsUser() {

        if(!$this->ValidateAdwordsOAuth2Info())
            return;

        return $this->adwordsuser;
    }

    public function GetGoogleApi() {

        return $this->apiclient;
    }

    public function createAuthUrl() {

        return $this->apiclient->createAuthUrl();
    }

    public function authenticate($code) {

        try {

            $jsontoken = $this->apiclient->authenticate($code);

        } catch (\Exception $e) {

            return;
        }

        $fulltoken = json_decode($jsontoken, true);
        if(!isset($fulltoken["access_token"]) || empty(trim($fulltoken["access_token"])))
            return;

        $q = "https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token=" . $fulltoken["access_token"];
        $json = file_get_contents($q);
        if(false === $json)
            return;

        $userinfo = json_decode($json, true);
        if(isset($userinfo['error']))
            return;

        $this->memcache->set($userinfo['id'] . '_token', $jsontoken, $fulltoken["expires_in"] - 30);

        return array("jsontoken" => $jsontoken, "fulltoken" => $fulltoken, "userinfo" => $userinfo);
    }

    public function Relogin($googleid, $refreshToken) {

        if( !$jsontoken = $this->memcache->get($googleid . '_token')  ) {

            try {

                $this->apiclient->refreshToken($refreshToken);

            } catch (\Exception $e) {

                return;
            }

            $jsontoken = $this->apiclient->getAccessToken();
            $fulltoken = json_decode($jsontoken, true);

            $this->memcache->set($googleid . '_token', $jsontoken, $fulltoken["expires_in"] - 30);
        }


        try {

            $this->apiclient->setAccessToken($jsontoken);

        } catch (\Exception $e) {

            return;
        }


        if($this->apiclient->isAccessTokenExpired()) {

            return;
        }

        $fulltoken = json_decode($jsontoken, true);
        if(!isset($fulltoken["access_token"]) || empty(trim($fulltoken["access_token"]))) {

            return;
        }

        if(!$this->setAdwordsOAuth2Validate($refreshToken, $fulltoken["access_token"])) {

            return;
        }

        return $fulltoken;

    }

    function GetAdGroups($campaignId = null) {

        $adGroupService = $this->GetAdwordsService('AdGroupService');

        if (!$adGroupService) {
            return;
        }

        $selector = new \Selector();
        $selector->fields = array('Id', 'Name');
        $selector->ordering[] = new \OrderBy('Name', 'ASCENDING');
        $selector->predicates[] = new \Predicate('Status', 'EQUALS', "ENABLED");

        if(!empty(trim($campaignId))) {
            $selector->predicates[] = new \Predicate('CampaignId', 'IN', array($campaignId));
        }

        $selector->paging = new \Paging(0, \AdWordsConstants::RECOMMENDED_PAGE_SIZE);

        $adgrouparray = array();
        do {

            $page = $adGroupService->get($selector);
            if (isset($page->entries)) {
                foreach ($page->entries as $adGroup) {
                    $adgrouparray[] = $adGroup;
                }
            } else {
                return;
            }

            $selector->paging->startIndex += \AdWordsConstants::RECOMMENDED_PAGE_SIZE;
        } while ($page->totalNumEntries > $selector->paging->startIndex);

        return $adgrouparray;

    }

    public function GetCampaigns() {

        $campaignService = $this->GetAdwordsService('CampaignService');

        if (!$campaignService) {
            return;
        }

        // Create selector.
        $selector = new \Selector();
        $selector->fields = array('Id', 'Name');
        $selector->ordering[] = new \OrderBy('Name', 'ASCENDING');
        $selector->predicates[] = new \Predicate('Status', 'EQUALS', "ENABLED");
        $selector->paging = new \Paging(0, \AdWordsConstants::RECOMMENDED_PAGE_SIZE);

        $campaignarray = array();
        do {

            $page = $campaignService->get($selector);
            if (isset($page->entries)) {
                foreach ($page->entries as $campaign) {
                    $campaignarray[] = $campaign;
                }
            } else {
                return;
            }

            $selector->paging->startIndex += \AdWordsConstants::RECOMMENDED_PAGE_SIZE;
        } while ($page->totalNumEntries > $selector->paging->startIndex);

        return $campaignarray;

    }

    function GetTextAds($adGroupId = null, $onlyenabled = true, $bannertype = array("TEXT_AD")) {

        $adGroupAdService = $this->GetAdwordsService('AdGroupAdService');

        if (!$adGroupAdService) {
            return;
        }

        $selector = new \Selector();
        $selector->fields = array('Headline', 'Id');
        $selector->ordering[] = new \OrderBy('Headline', 'ASCENDING');

        if($onlyenabled) {
            $selector->predicates[] = new \Predicate('Status', 'EQUALS', "ENABLED");
        }

        if(!empty(trim($adGroupId))) {
            $selector->predicates[] = new \Predicate('AdGroupId', 'IN', array($adGroupId));
        }

        $selector->predicates[] = new \Predicate('AdType', 'IN', $bannertype);

        $selector->paging = new \Paging(0, \AdWordsConstants::RECOMMENDED_PAGE_SIZE);

        $ads = array();
        do {

            $page = $adGroupAdService->get($selector);
            if (isset($page->entries)) {
                foreach ($page->entries as $adGroupAd) {
                    $ads[] = $adGroupAd->ad;
                }
            } else {
                return;
            }

            $selector->paging->startIndex += \AdWordsConstants::RECOMMENDED_PAGE_SIZE;
        } while ($page->totalNumEntries > $selector->paging->startIndex);

        return $ads;

    }

    function GetKeywords($adGroupId = null) {

        $adGroupCriterionService = $this->GetAdwordsService('AdGroupCriterionService');

        if (!$adGroupCriterionService) {
            return;
        }

        $selector = new \Selector();
        $selector->fields = array('Id', 'KeywordText', 'KeywordMatchType');
        $selector->ordering[] = new \OrderBy('KeywordText', 'ASCENDING');

        if(!empty(trim($adGroupId))){
            $selector->predicates[] = new \Predicate('AdGroupId', 'IN', array($adGroupId));
        }

        $selector->predicates[] = new \Predicate('CriteriaType', 'IN', array('KEYWORD'));
        $selector->paging = new \Paging(0, 50);

        $keywords = array();
        $pagenumber = 0;
        do {

            $page = $adGroupCriterionService->get($selector);
            if (isset($page->entries)) {
                foreach ($page->entries as $adGroupCriterion) {
                    $keywords[] = $adGroupCriterion->criterion;
                }
            } else {
                return;
            }

            $pagenumber++;
            if($pagenumber > 0)
                return $keywords;

            $selector->paging->startIndex += \AdWordsConstants::RECOMMENDED_PAGE_SIZE;
        } while ($page->totalNumEntries > $selector->paging->startIndex);

        return $keywords;
    }

}
