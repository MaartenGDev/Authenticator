<?php

namespace MaartenGDev;


interface AuthenticatorInterface
{


    /**
     * Send the authorisation header
     * and store the api tokens from the response.
     *
     * @return object
     */
    public function getApiCredentials();


}//end interface
