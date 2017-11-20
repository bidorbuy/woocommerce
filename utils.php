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

/**
 * Get Categories.
 *
 * @param array $args arguments
 *
 * @return array|WP_Error
 */
function &bobsi_get_categories($args = array()) {
    $taxonomies = array(
        'product_cat',
    );

    $terms = empty($args) ? get_terms($taxonomies) : get_terms($taxonomies, $args);

    if (is_object($terms) && ($terms instanceof WP_Error)) {
        bobsi\StaticHolder::getBidorbuyStoreIntegrator()->logError('Unable to get category terms: '
            . implode('. ', array_keys($terms->errors)));
        $terms = array();
    }

    return $terms;
}

/**
 * Exit with error.
 *
 * @param string $message error message
 * @param string $type error type
 * @param bool $exit flag to exit
 *
 * @return void
 */
function bobsi_exit_with_error($message, $type = 'error', $exit = TRUE) {
    $message = '<div class="' . $type . '"><p>' . $message . '</p></div>';
    if ($exit) {
        exit ($message);
    } else {
        echo $message;
    }
}

/**
 * Get export categories ids
 *
 * @param array $ids categories ids
 *
 * @return array
 */
function bobsi_get_export_categories_ids($ids = array()) {
    $uncategorized = in_array(0, $ids);

    $args = array('hide_empty' => 0);

    if (!empty($ids)) {
        $args['exclude'] = $ids;
    }

    $terms = bobsi_get_categories($args);

    $ids = array();
    foreach ($terms as &$term) {
        $ids[] = $term->term_id;
    }

    $terms = NULL;

    if (!$uncategorized) {
        $ids[] = 0;
    }

    return $ids;
}

/**
 * Get shipping class
 *
 * @param integer $post_id post id
 * @param int $getParentShipmentMethodById id
 *
 * @return string
 */
function bobsi_get_shipping_class($post_id, $getParentShipmentMethodById = 0) {
    $classes = get_the_terms($post_id, 'product_shipping_class');
    $shipping_class_name = ($classes && !is_wp_error($classes)) ? current($classes)->name : '';

    if (empty($shipping_class_name) && $getParentShipmentMethodById) {
        $classes = get_the_terms($getParentShipmentMethodById, 'product_shipping_class');
        $shipping_class_name = ($classes && !is_wp_error($classes)) ? current($classes)->name : '';
    }

    return $shipping_class_name;
}