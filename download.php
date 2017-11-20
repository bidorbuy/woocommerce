<?php
/**
 * Copyright (c) 2014, 2015, 2016 Bidorbuy http://www.bidorbuy.co.za
 * This software is the proprietary information of Bidorbuy.
 *
 * All Rights Reserved.
 * Modification, redistribution and use in source and binary forms, with or without
 * modification are not permitted without prior written approval by the copyright
 * holder.
 *
 * Vendor: EXTREME IDEA LLC http://www.extreme-idea.com
 */

if (!defined('ABSPATH')) {
    exit;// Exit if accessed directly
}

use com\extremeidea\bidorbuy\storeintegrator\core as bobsi;

$token = isset($_REQUEST[bobsi\Settings::paramToken]) ? 
    sanitize_text_field($_REQUEST[bobsi\Settings::paramToken]) : FALSE;

$exportConfiguration = array(
    bobsi\Settings::paramCategories => bobsi_get_export_categories_ids(bobsi\StaticHolder::getBidorbuyStoreIntegrator()
        ->getSettings()
        ->getExcludeCategories()),
);

bobsi\StaticHolder::getBidorbuyStoreIntegrator()->download($token, $exportConfiguration);