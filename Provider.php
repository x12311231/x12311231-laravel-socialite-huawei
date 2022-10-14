<?php

namespace X12311231LaravelSocialite\huawei;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Log;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'HUAWEI';

    /**
     * @var string
     */
    protected $openId;

    /**
     * {@inheritdoc}.
     */
    protected $scopes = ['https://www.huawei.com/auth/account/base.profile'];

    /**
     * set Open Id.
     *
     * @param string $openId
     */
    public function setOpenId($openId)
    {
        $this->openId = $openId;
    }

    /**
     * {@inheritdoc}.
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://oauth-login.cloud.huawei.com/oauth2/v2/authorize', $state);
    }

    /**
     * {@inheritdoc}.
     */
    protected function buildAuthUrlFromBase($url, $state)
    {
        $query = http_build_query($this->getCodeFields($state), '', '&', $this->encodingType);

        return $url.'?'.$query;
    }

    /**
     * {@inheritdoc}.
     */
    protected function getCodeFields($state = null)
    {
        return [
            'client_id'         => $this->clientId, 'redirect_uri' => $this->redirectUrl,
            'response_type' => 'code',
            'scope'         => $this->formatScopes($this->scopes, $this->scopeSeparator),
            'state'         => $state,
        ];
    }

    /**
     * {@inheritdoc}.
     */
    protected function getTokenUrl()
    {
        return 'https://oauth-login.cloud.huawei.com/oauth2/v2/token';
    }

    /**
     * {@inheritdoc}.
     */
    protected function getUserByToken($token)
    {
        if (in_array('snsapi_base', $this->scopes, true)) {
            $user = ['openid' => $this->openId];
        } else {
            $response = $this->getHttpClient()->post('https://account.cloud.huawei.com/rest.php', [
                RequestOptions::FORM_PARAMS => [
                    'access_token' => $token,
                    'nsp_svc' => 'GOpen.User.getInfo',
                ],
                RequestOptions::HEADERS => ['Content-Type' => 'application/x-www-form-urlencoded'],
            ]);

            $user = json_decode($response->getBody(), true);
        }

        return $user;
    }

    /**
     * {@inheritdoc}.
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['openid'],
            'openid'  => isset($user['openID']) ? $user['openID'] : null,
            'nickname' => isset($user['displayName']) ? $user['displayName'] : null,
            'avatar'   => isset($user['headPictureURL']) ? $user['headPictureURL'] : null,
        ]);
    }

    /**
     * {@inheritdoc}.
     */
    protected function getTokenFields($code)
    {
        return [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code'  => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUrl,
        ];
    }

    /**
     * {@inheritdoc}.
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS => $this->getTokenHeaders($code),
            RequestOptions::FORM_PARAMS => $this->getTokenFields($code),
        ]);

        $this->credentialsResponseBody = json_decode($response->getBody(), true);
        if (isset($this->credentialsResponseBody['openid'])) {
            $this->openId = $this->credentialsResponseBody['openid'];
        }

        return $this->credentialsResponseBody;
    }

    protected function getTokenHeaders($code)
    {
        return ['Content-Type' => 'application/x-www-form-urlencoded'];
    }

    protected function getCode()
    {
        return $this->request->input('authorization_code');
    }
}
