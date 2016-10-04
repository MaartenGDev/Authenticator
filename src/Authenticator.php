<?php

namespace MaartenGDev;

use GuzzleHttp\ClientInterface;

class Authenticator implements AuthenticatorInterface
{
    protected $client;
    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * Authenticator constructor.
     * @param ClientInterface $client
     * @param Cache $cache
     */
    public function __construct(ClientInterface $client, CacheInterface $cache)
    {
        $this->client = $client;
        $this->cache = $cache;
    }

    public function getApiCredentials()
    {
        // 534240 = ((24*60) * 7) * 53 = 1 year
        $lifetime = 534240;
        $hasToken = $this->cache->has('accessToken',null, $lifetime);
        $hasUserId = $this->cache->has('userId', null,$lifetime);
        $hasLoginSession = $this->cache->has('loginSession',null,$lifetime);

        if($hasToken && $hasUserId && $hasLoginSession){
            $accessToken = $this->cache->get('accessToken');
            $userId = $this->cache->get('userId');
            $loginSession = $this->cache->get('loginSession');

            return (object) ['accessToken' => $accessToken,'userId' => $userId,'loginSession' => $loginSession];
        }

        $apiCredentials = $this->sendAuthHeader();

        $accessToken = $apiCredentials['access_token'];
        $userId = $apiCredentials['user_id'];
        $loginSession = $apiCredentials['login_session'];

        $this->cache->store('accessToken',$accessToken);
        $this->cache->store('userId',$userId);
        $this->cache->store('loginSession',$loginSession);

        return (object) ['accessToken' => $accessToken,'userId' => $userId,'loginSession' => $loginSession];
    }

    protected function sendAuthHeader()
    {
        echo 'send request';
        $userAgent = '';
        $url = getenv('LOGIN_ENDPOINT');
        $username = getenv('LOGIN_USER');
        $password = getenv('LOGIN_PASSWORD');
        $token = getenv('CLIENT_TOKEN');
        $session = getenv('LOGIN_SESSION');
        $language = getenv('LANGUAGE');
        $version = getenv('VERSION');

        $response = $this->client->request('GET', $url, [
            'headers' => [
                'User-Agent' => $userAgent,
                'Authorization' => 'Basic ' .base64_encode($username . ':' . $password),
                'Cookie' => 'laravel_session=' . $session,
                'Cookie2' => '$Version=' . $version,
                'language' => $language,
                'clientToken' => $token,
            ]
        ]);

        $data = json_decode($response->getBody());
        $loginSession = $response->getHeader('Set-Cookie');

        $loginSession = $loginSession[0];


        $loginSession = str_replace('laravel_session=','',$loginSession);
        $loginSession =substr($loginSession,0,strpos($loginSession,';'));

        return ['access_token' => $data->access_token, 'user_id' => $data->user_id,'login_session' => $loginSession];
    }
}