<?php

/**
 * Copyright (c) 2014, 2015, 2016 Bidorbuy http://www.bidorbuy.co.za
 * This software is the proprietary information of Bidorbuy.
 *
 * All Rights Reserved.
 * Modification, redistribution and use in source and binary forms, with or without modification
 * are not permitted without prior written approval by the copyright holder.
 *
 * Vendor: EXTREME IDEA LLC http://www.extreme-idea.com
 */

use com\extremeidea\bidorbuy\storeintegrator\core as bobsi;

require_once(dirname(__FILE__) . '/../../../wp-config.php');

$token = isset($_REQUEST[bobsi\Settings::paramToken]) ? $_REQUEST[bobsi\Settings::paramToken] : false;

bobsi\StaticHolder::getBidorbuyStoreIntegrator()->showVersion($token, isset($_REQUEST['phpinfo']) && 'y' == $_REQUEST['phpinfo']);