<?php

namespace MaartenGDev;

use DateInterval;
use GuzzleHttp\ClientInterface;

class Client
{
    protected $client;
    /* @param Cache $cache */
    protected $cache;
    protected $auth;

    /**
     * Authenticator constructor.
     * @param ClientInterface $client
     * @param CacheInterface $cache
     * @param AuthenticatorInterface $authenticator
     */
    public function __construct(ClientInterface $client, CacheInterface $cache, AuthenticatorInterface $authenticator)
    {
        $this->client = $client;
        $this->cache = $cache;
        $this->authenticator = $authenticator;
    }

    /**
     * @param integer $year
     * @param integer $week
     * @return mixed
     */
    protected function getData($year, $week)
    {
        $loginCredentials = $this->authenticator->getApiCredentials();

        $dateRange = $this->getStartAndEndForWeek($week, $year);

        $params = http_build_query(
            [
                'start_date' => $dateRange['start'],
                'end_date' => $dateRange['end'],
            ]
        );

        $url = getenv('API_ENDPOINT') . '?' . $params;

        $settings = (object) [
            'loginSession' => $loginCredentials->loginSession,
            'accessToken' => $loginCredentials->accessToken,
            'language' => getenv('LANGUAGE'),
            'clientToken' => getenv('CLIENT_TOKEN'),
            'version' => getenv('VERSION')
        ];

        return $this->getWeekData($url, $week, $settings);

    }

    /**
     * @param string $url
     * @param integer $week
     * @param object $settings
     * @return mixed
     */
    protected function getWeekData($url, $week, $settings){
        $key = 'rooster' . $week;

        $cache = $this->cache->has($key, function ($cache) use ($key) {
            return $cache->get($key);
        });

        if ($cache) {
            return $cache;
        }

        $data = $this->client->request(
            'GET',
            $url,
            [
                'headers' => [
                    'Cookie' => 'laravel_session=' . $settings->loginSession,
                    'Cookie2' => '$Version=' .$settings->version,
                    'accessToken' => $settings->accessToken,
                    'language' => $settings->language,
                    'clientToken' => $settings->clientToken
                ]
            ]
        )->getBody();
        $this->cache->store($key, $data);
        return $this->cache->get($key);
    }

    /**
     * Get the start and end date for the specified week.
     *
     * @return array
     */
    protected function getStartAndEndForWeek($year, $week)
    {
        $startDate = new \DateTime();
        $startDate->setISODate(date('Y'), $week);
        $startDate->setTime(00, 46, 0);

        $start = $startDate->getTimestamp();

        $endDate = new \DateTime();
        $endDate->setISODate($year, $week);
        $endDate->setTime(23, 46, 59);
        $endDate->add(DateInterval::createFromDateString('this week this sunday'));

        $end = $endDate->getTimestamp();
        return ['start' => $start, 'end' => $end];
    }

    /**
     * Get the week by sending a post request.
     *
     * @param integer $week The week number
     * @param string $year The year of the week.
     *
     * @return array
     */
    public function getWeek($week, $year = null)
    {
        if ($year === null) {
            $year = date('Y');
        }

        return $this->getData($week,$year);
    }
}