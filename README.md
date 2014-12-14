MayecoGoogleBundle
============

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/0fb81cde-508c-4c86-a4e8-9f8e9ac0f715/big.png)](https://insight.sensiolabs.com/projects/0fb81cde-508c-4c86-a4e8-9f8e9ac0f715)

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mayeco/GoogleBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mayeco/GoogleBundle/?branch=master) [![Build Status](https://scrutinizer-ci.com/g/mayeco/GoogleBundle/badges/build.png?b=master)](https://scrutinizer-ci.com/g/mayeco/GoogleBundle/build-status/master) [![Code Climate](https://codeclimate.com/github/mayeco/GoogleBundle/badges/gpa.svg)](https://codeclimate.com/github/mayeco/GoogleBundle) [![Latest Stable Version](https://poser.pugx.org/mayeco/google-bundle/v/stable.svg)](https://packagist.org/packages/mayeco/google-bundle) [![Latest Unstable Version](https://poser.pugx.org/mayeco/google-bundle/v/unstable.svg)](https://packagist.org/packages/mayeco/google-bundle) [![License](https://poser.pugx.org/mayeco/google-bundle/license.svg)](https://packagist.org/packages/mayeco/google-bundle)

`THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.`

add your settings to config.yml

    mayeco_google:
        oauth_info:
            client_id: client_id_de_proyecto_en_google_developer_console
            client_secret: client_secret_de_proyecto_en_google_developer_console
            redirect_url: 'http://www.YOUR_URL.com/authenticate'
        adwords:
            dev_token: google_adwords_api_dev_token

Add your redirect URLs for dev enviroment, in config_dev.yml

    mayeco_google:
        oauth_info:
            redirect_url: 'http://www.YOUR_URL.com/app_dev.php/authenticate'
