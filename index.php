<?php
use Dotenv\Dotenv;
use MaartenGDev\Authenticator;
use MaartenGDev\Cache;
use MaartenGDev\Client;
use MaartenGDev\LocalDriver;
use GuzzleHttp\Client as GuzzleHttp;

include_once 'vendor/autoload.php';
$dir = $_SERVER['DOCUMENT_ROOT'] .'/cache/';

$dotenv = new Dotenv(__DIR__);
$dotenv->load();

$drive = new LocalDriver($dir);
$cache = new Cache($drive,30);
$guzzle = new GuzzleHttp(['cookies' => true]);
$auth = new Authenticator($guzzle,$cache);
$client = new Client($guzzle,$cache,$auth);

echo $client->getWeek(40);