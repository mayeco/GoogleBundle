<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 * MIT license.
 */
 
namespace Mayeco\GoogleBundle\Services;

use Google_Client;
use AdWordsUser;
use Lsw\MemcacheBundle\Cache\MemcacheInterface;

/**
 * @author Mario Young <maye.co@gmail.com>
 * @link   maye.co
 */
class GoogleUtils
{

    /**
     * @var AdWordsUser
     */
    private $adwordsuser;

    /**
     * @var Google_Client
     */
    private $googleclient;

    /**
     * @var MemcacheInterface
     */
    private $memcache;

    /**
     * @var boolean
     */
    private $isrunningmemcache;

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
        $this->isrunningmemcache = $this-checkMemcacheServers();
    }

    /**
     * @param $reportQuery
     * @param string $format
     * @param array $options
     * @return null|string|void
     */
    public function downloadReportWithAwql($reportQuery, $format, $path = null, array $options = null)
    {
        $allowformats = array("CSV", "XML", "TSV", "GZIPPED_CSV", "GZIPPED_XML");
        if (!in_array($format, $allowformats)) {
            return;
        }

        if (!$this->validateUser()) {
            return;
        }

        $report = null;
        try {

            $report = \ReportUtils::DownloadReportWithAwql($reportQuery, $path, $this->adwordsuser, $format, $options);

            if ("GZIPPED_CSV" == $format || "GZIPPED_XML" == $format) {
                $report = gzdecode($report);
            }

        } catch (\Exception $e) {

            return;
        }

        return $report;
    }
    
    private function isRunningMemcache() {
        return $this->isrunningmemcache;
    }

    /**
     * @param $fulltoken
     * @return bool|void
     * @throws \Exception
     */
    private function setAdwordsOAuth2Validate($fulltoken)
    {
        if (!isset($fulltoken["access_token"]) || !isset($fulltoken["refresh_token"])) {
            throw new \Exception('No access token or refresh token.');
        }

        $oauth = $this->adwordsuser->GetOAuth2Info();
        $oauth["refresh_token"] = $fulltoken["refresh_token"];
        $oauth["access_token"] = $fulltoken["access_token"];

        $this->adwordsuser->SetOAuth2Info($oauth);

        return $this->validateUser();
    }

    /**
     * @return bool|void
     */
    private function validateUser()
    {
        try {

            $this->adwordsuser->ValidateUser();

        } catch (\Exception $e) {

            return;
        }

        return true;
    }
    
    private function checkMemcacheServers()
    {
        $serverstats = $this->memcache->getStats();
        foreach($serverstats as $server) {
            if($server["pid"] < 1) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * @param $service
     */
    public function getAdwordsService($service)
    {
        if (!$this->validateUser()) {
            return;
        }

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
    public function getAdwordsUser()
    {
        if (!$this->validateUser()) {
            return;
        }

        return $this->adwordsuser;
    }

    /**
     * @return Google_Client
     */
    public function getGoogleClient()
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
    public function authenticateAccess($code)
    {
        if(!$this->isRunningMemcache()) {
            return;
        }

        try {

            $jsontoken = $this->googleclient->authenticate($code);
            $verify_token = $this->googleclient->verifyIdToken();
            $user_id = $verify_token->getUserId();

            $fulltoken = json_decode($jsontoken, true);
            $this->setAdwordsOAuth2Validate($fulltoken);

        } catch (\Exception $e) {

            return;
        }

        if(!$this->memcache->set($user_id . '_token', $jsontoken, $fulltoken["expires_in"] - 60)) {
            return;
        }

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
    public function refreshAccess($id, $refreshToken)
    {
        if(!$this->isRunningMemcache()) {
            return;
        }

        if (!$jsontoken = $this->memcache->get($id . '_token')) {

            try {

                $this->googleclient->refreshToken($refreshToken);
                $verify_token = $this->googleclient->verifyIdToken();
                if ($verify_token->getUserId() != $id) {
                    return;
                }

                $jsontoken = $this->googleclient->getAccessToken();

            } catch (\Exception $e) {

                $this->memcache->delete($id . '_token');
                return;
            }

            $fulltoken = json_decode($jsontoken, true);
            if(!$this->memcache->set($id . '_token', $jsontoken, $fulltoken["expires_in"] - 60)) {
                return;
            }
        }

        try {

            $this->googleclient->setAccessToken($jsontoken);
            if(!$fulltoken) {
                $fulltoken = json_decode($jsontoken, true);
            }

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
