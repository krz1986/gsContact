<?php

require_once('class.gscontact.php');

$strApiKey = 'xxxxxxxxxxxxxxxxxxxxx';
$strApiUrl = 'http://meingsales.de/api/api.php?wsdl';

$strUsername = 'test';
$strPassword = 'test';

$objContact = new GS_CONTACT($strApiKey, $strApiUrl);
$objContact->setUsernamePassword($strUsername, $strPassword);
$objContact->dispatchRequest();