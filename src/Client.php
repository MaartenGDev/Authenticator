<?php

namespace MaartenGDev;

use DateInterval;
use GuzzleHttp\ClientInterface;

class Client
{
    /**
     * @var ClientInterface $client The client interface
     */
    protected $client;

    /**
     * @param CacheInterface $cache The cache interface
     */
    protected $cache;

    /**
     * @param AuthenticatorInterface $auth The authenticator interface
     */
    protected $auth;


    /**
     * Authenticator constructor.
     *
     * @param ClientInterface        $client        The client interface
     * @param CacheInterface         $cache         The cache interface
     * @param AuthenticatorInterface $authenticator The authenticator interface
     */
    public function __construct(ClientInterface $client, CacheInterface $cache, AuthenticatorInterface $authenticator)
    {
        $this->client        = $client;
        $this->cache         = $cache;
        $this->authenticator = $authenticator;

    }//end __construct()


    /**
     * Get the data for the week
     *
     * @param integer $year The year the week is in
     * @param integer $week The week to select data from
     *
     * @return mixed
     */
    protected function getData($year, $week)
    {
        $loginCredentials = $this->authenticator->getApiCredentials();

        $dateRange = $this->getStartAndEndForWeek($week, $year);

        $params = http_build_query(
            [
             'start_date' => $dateRange['start'],
             'end_date'   => $dateRange['end'],
            ]
        );

        $url = getenv('API_ENDPOINT').'?'.$params;

        $settings = (object) [
                              'loginSession' => $loginCredentials->loginSession,
                              'accessToken'  => $loginCredentials->accessToken,
                              'language'     => getenv('LANGUAGE'),
                              'clientToken'  => getenv('CLIENT_TOKEN'),
                              'version'      => getenv('VERSION'),
                             ];

        return $this->getWeekData($url, $week, $settings);

    }//end getData()


    /**
     *  Get the data of the week
     *
     * @param string  $url      The url to get data from
     * @param integer $week     The week to get
     * @param object  $settings The settings to send along
     *
     * @return mixed
     */
    protected function getWeekData($url, $week, $settings)
    {
        $key = 'rooster'.$week;

        $cache = $this->cache->has(
            $key,
            function ($cache) use ($key) {
                return $cache->get($key);
            }
        );

        if ($cache === true) {
            return $cache;
        }

        $data = $this->client->request(
            'GET',
            $url,
            [
             'headers' => [
                           'Cookie'      => 'laravel_session='.$settings->loginSession,
                           'Cookie2'     => '$Version='.$settings->version,
                           'accessToken' => $settings->accessToken,
                           'language'    => $settings->language,
                           'clientToken' => $settings->clientToken,
                          ],
            ]
        )->getBody();
        $this->cache->store($key, $data);
        return $this->cache->get($key);

    }//end getWeekData()


    /**
     * Get the start and end date for the specified week.
     *
     * @param integer $year The year
     * @param integer $week The week to get both from
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
        return [
                'start' => $start,
                'end'   => $end,
               ];

    }//end getStartAndEndForWeek()


    /**
     * Get the week by sending a post request.
     *
     * @param integer $week The week number
     * @param string  $year The year of the week.
     *
     * @return array
     */
    public function getWeek($week, $year = null)
    {
        if ($year === null) {
            $year = date('Y');
        }

        return $this->getData($week, $year);

    }//end getWeek()


}//end class
