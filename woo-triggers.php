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

use com\extremeidea\bidorbuy\storeintegrator\core as bobsi;

add_action('save_post_product', 'bobsi_product_updated', 10, 2);

add_action('woocommerce_update_product_variation', 'bobsi_product_variation_update');
add_action('woocommerce_create_product_variation', 'bobsi_product_variation_update');
add_action('woocommerce_product_bulk_edit_save', 'bobsi_product_bulk_or_quick_update');
add_action('woocommerce_product_quick_edit_save', 'bobsi_product_bulk_or_quick_update');

add_action('woocommerce_attribute_updated', 'bobsi_attribute_updated', 10, 2);
add_action('woocommerce_attribute_deleted', 'bobsi_attribute_updated', 10, 2);

add_action('woocommerce_tax_rate_added', 'bobsi_tax_update');
add_action('woocommerce_tax_rate_updated', 'bobsi_tax_update');
add_action('woocommerce_tax_rate_deleted', 'bobsi_tax_update');

//For categories
add_action('create_term', 'bobsi_term_create', 10, 3);//attributes and categories
add_action('edited_terms', 'bobsi_term_update', 10, 2);//attributes and categories
add_action('delete_term', 'bobsi_term_delete', 10, 4);//attributes and categories

/**
 * Product variation update
 *
 * @param integer $vid id
 *
 * @return void
 */
function bobsi_product_variation_update($vid) {
    global $wpdb, $bobsi_products_touched;

    $p = new WC_Product_Variation($vid);
    $pid = $p->parent->id;
    if (!isset($bobsi_products_touched[$pid])) {
        $bobsi_products_touched[$pid] = $pid;
        $wpdb->query(bobsi\StaticHolder::getBidorbuyStoreIntegrator()
            ->getQueries()->getAddJobQueries($pid, bobsi\Queries::STATUS_UPDATE));
    }
}

/**
 * Product updated
 * we can't easily catch difference between new/update events :(
 *
 * @param integer $post_ID id
 * @param mixed $post post
 *
 * @return void
 */
function bobsi_product_updated($post_ID, $post) {
    bobsi_process_product($post);
}

/**
 * Product bulk or quick update
 *
 * @param object $product product
 *
 * @return void
 */
function bobsi_product_bulk_or_quick_update($product) {
    bobsi_process_product($product);
}

/**
 * Attribute updated
 *
 * @param integer $attr_id attr id
 * @param string $attr attribute
 *
 * @return void
 */
function bobsi_attribute_updated($attr_id, $attr) {
    global $wpdb;
    $attr = isset($attr['attribute_name']) ? $attr['attribute_name'] : $attr;
    $pids = bobsi_get_products_ids_by_attr_values(
        $attr,
        get_terms('pa_' . $attr, array('fields' => 'names', 'hide_empty' => 0))
    );

    foreach ($pids as $pid) {
        $wpdb->query(bobsi\StaticHolder::getBidorbuyStoreIntegrator()
            ->getQueries()->getAddJobQueries($pid, bobsi\Queries::STATUS_UPDATE));
    }
}

/**
 * Get products ids by attr values.
 *
 * @param string $key key
 * @param string $values value
 *
 * @return mixed
 */
function bobsi_get_products_ids_by_attr_values($key, $values) {
    $args = array(
        'fields' => 'ids',
        'post_type' => 'product',
        'posts_per_page' => 10,
        'tax_query' => array(
            array(
                'taxonomy' => 'pa_' . $key,
                'terms' => $values,
                'field' => 'slug',
                'operator' => 'IN'
            )
        )
    );

    $r = new WP_Query($args);

    return $r->posts;
}

/**
 * Term create
 *
 * @param integer $term_id id
 * @param integer $tt_id id
 * @param string $taxonomy taxonomy
 *
 * @return void
 */
function bobsi_term_create($term_id, $tt_id, $taxonomy) {
//product attribute
    if (strpos($taxonomy, 'pa_') === 0) {
        bobsi_attribute_updated(0, array('attribute_name' => substr($taxonomy, 3)));
    }
}

/**
 * Term update
 *
 * @param integer $term_id id
 * @param string $taxonomy taxonomy
 *
 * @return void
 */
function bobsi_term_update($term_id, $taxonomy) {
    if ($taxonomy == 'product_cat') {
        bobsi_category_update($term_id, bobsi\Queries::STATUS_UPDATE);
    }

//product attribute
    if (strpos($taxonomy, 'pa_') === 0) {
        bobsi_attribute_updated(0, array('attribute_name' => substr($taxonomy, 3)));
    }
}

/**
 * Term delete
 *
 * @param string $term term
 * @param integer $tt_id id
 * @param string $taxonomy taxonomy
 * @param string $deleted_term deleted_term
 *
 * @return void
 */
function bobsi_term_delete($term, $tt_id, $taxonomy, $deleted_term) {
    if ($taxonomy == 'product_cat') {
        bobsi_category_update($tt_id, bobsi\Queries::STATUS_DELETE);
    }

//product attribute
    if (strpos($taxonomy, 'pa_') === 0) {
        bobsi_attribute_updated(0, array('attribute_name' => substr($taxonomy, 3)));
    }
}

/**
 * Get products
 *
 * @param array $exportConfiguration config
 *
 * @return array
 */
function &bobsi_get_products(&$exportConfiguration) {
    $itemsPerIteration = intval($exportConfiguration[bobsi\Settings::paramItemsPerIteration]);
    $iteration = intval($exportConfiguration[bobsi\Settings::paramIteration]);
    $categoryId = $exportConfiguration[bobsi\Settings::paramCategoryId];

    if ($categoryId == 0) {
//    TODO: tax_query starts from 3.1, and As of 3.5, a bug was fixed where
        // tax_query would inadvertently return all posts when a result was empty.
        $wpq = array(
            'post_type' => 'product',
            'fields' => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'id',
                    'terms' => bobsi_get_export_categories_ids(),
                    'include_children' => FALSE,
                    'operator' => 'NOT IN'
                )
            )
        );
    } else {
//    TODO: tax_query starts from 3.1, and As of 3.5, a bug was fixed where tax_query would
        // inadvertently return all posts when a result was empty.
        $wpq = array(
            'post_type' => 'product',
            'fields' => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'id',
                    'terms' => $categoryId,
                    'include_children' => FALSE,
                )
            )
        );
    }

    if (count(bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getExportStatuses()) == 0) {
        return array();
    } else {
        $wpq['post_status'] = bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getExportStatuses();
    }

    $wpq['posts_per_page'] = $itemsPerIteration;
    $wpq['offset'] = $iteration * $itemsPerIteration;

    $query = new WP_Query();
    $posts = $query->query($wpq);

    $query = NULL;

    return $posts;
}

/**
 * Category update
 *
 * @param integer $term_id id
 * @param string $action action
 *
 * @return void
 */
function bobsi_category_update($term_id, $action) {
    global $wpdb;

    $ec = array(
        bobsi\Settings::paramItemsPerIteration => PHP_INT_MAX,
        bobsi\Settings::paramIteration => 0,
        bobsi\Settings::paramCategoryId => $term_id
    );

    $pids = bobsi_get_products($ec);

    foreach ($pids as $pid) {
        $wpdb->query(bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getQueries()->getAddJobQueries($pid, $action));
    }
}

/**
 * Tax update
 *
 * @return void
 */
function bobsi_tax_update() {
    bobsi_refresh_all_products();
}

/**
 * Process product
 *
 * @param mixed $post post
 *
 * @return void
 */
function bobsi_process_product($post) {
    global $wpdb, $bobsi_products_touched;

    $productId = isset($post->ID) ? $post->ID : $post->id;
    $postStatus = isset($post->post->post_status) ? $post->post->post_status : $post->post_status;

    // do not response on autosave
    if (!isset($_POST['data']['wp_autosave']) and !isset($bobsi_products_touched[$productId])) {
        $bobsi_products_touched[$productId] = $productId;

        if (in_array($postStatus, bobsi\StaticHolder::getBidorbuyStoreIntegrator()
            ->getSettings()->getExportStatuses())) {
            $wpdb->query(bobsi\StaticHolder::getBidorbuyStoreIntegrator()
                ->getQueries()->getSetJobsRowStatusQuery($productId, bobsi\Queries::STATUS_DELETE, time()));
            $wpdb->query(bobsi\StaticHolder::getBidorbuyStoreIntegrator()

                ->getQueries()->getAddJobQueries($productId, bobsi\Queries::STATUS_UPDATE));
        } else {
            $wpdb->query(bobsi\StaticHolder::getBidorbuyStoreIntegrator()
                ->getQueries()->getAddJobQueries($productId, bobsi\Queries::STATUS_DELETE));
        }
    }
}

/**
 * Refresh all products
 *
 * @return void
 */
function bobsi_refresh_all_products() {
    global $wpdb;

    $wpdb->query(bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getQueries()->getTruncateJobsQuery());
    $wpdb->query(bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getQueries()->getTruncateProductQuery());
    bobsi_add_all_products_in_tradefeed_queue(TRUE);
}
