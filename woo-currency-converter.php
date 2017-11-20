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

if (!defined('ABSPATH')) {
    exit;// Exit if accessed directly
}

use com\extremeidea\bidorbuy\storeintegrator\core as bobsi;

function bobsi_is_woo_currency_converter_active() {
    return is_plugin_active(BOBSI_WOOCOMMERCE_CURRENCY_CONVERTER_PLUGIN_PHP_FILE);
}

function bobsi_woo_currency_converter_get_currencies() {
    if (bobsi_is_woo_currency_converter_active()) {
        if (function_exists('wccc_get_option')) {
            $out = array();
            $currencies = wccc_get_option( 'currency_list' );

            if (is_array($currencies)) {
                foreach ($currencies as $currency) {
                    $out[] = $currency['code'];
                }
                return $out;
            } else {
                bobsi\StaticHolder::getBidorbuyStoreIntegrator()->logError('Something wrong with WCCC.');
            }
        } else {
            bobsi\StaticHolder::getBidorbuyStoreIntegrator()->logError('Function wccc_get_option is undefined.');
        }
    }

    return array();
}

function bobsi_convert_price($price) {
    if (bobsi_is_woo_currency_converter_active()) {
        if (function_exists('wccc_convert_price')) {
            return wccc_convert_price($price);
        } else {
            bobsi\StaticHolder::getBidorbuyStoreIntegrator()->logError('Function wccc_convert_price is undefined.');
        }
    }

    return $price;
}