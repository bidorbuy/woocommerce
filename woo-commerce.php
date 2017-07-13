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

function &bobsi_woo_commerce_get_all_variations($post_id) {
    global $woocommerce;

    $post_id = intval($post_id);
    if (!$post_id) {
        return array();
    }

    $variations = array();

    $variations = array();
    $args = array('product_type' => 'variable');
    $_product = get_product($post_id, $args);

    // Put variation attributes into an array
    foreach ($_product->get_attributes() as $attribute) {
        if (!$attribute['is_variation']) {
            continue;
        }

        $attribute_field_name = 'attribute_' . sanitize_title($attribute['name']);

        if ($attribute['is_taxonomy']) {
            $post_terms = wp_get_post_terms($post_id, $attribute['name']);
            $options = array();
            foreach ($post_terms as $term) {
                $options[] = $term->slug;
            }
        } else {
            $options = explode('|', $attribute['value']);
        }

        $options = array_map('sanitize_title', array_map('trim', $options));

        $variations[$attribute_field_name] = $options;
    }

    // Quit out if none were found
    if (count($variations) == 0) {
        $_product = null;

        //only variables should be returned by reference
        $tempvar = array();
        return $tempvar;
    }

    // Get existing variations so we don't create duplicates
    $available_variations = array();

    foreach ($_product->get_children() as $child_id) {
        $child = $_product->get_child($child_id);

        if (!empty($child->variation_id)) {
            $available_variations[] = $child->get_variation_attributes();
        }
    }

    // Now find all combinations and create posts
    if (!function_exists('array_cartesian')) {
        function array_cartesian($input) {
            $result = array();

            while (list($key, $values) = each($input)) {
                // If a sub-array is empty, it doesn't affect the cartesian product
                if (empty($values)) {
                    continue;
                }

                // Special case: seeding the product array with the values from the first sub-array
                if (empty($result)) {
                    foreach ($values as $value) {
                        $result[] = array($key => $value);
                    }
                } else {
                    // Second and subsequent input sub-arrays work like this:
                    //   1. In each existing array inside $product, add an item with
                    //      key == $key and value == first item in input sub-array
                    //   2. Then, for each remaining item in current input sub-array,
                    //      add a copy of each existing array inside $product with
                    //      key == $key and value == first item in current input sub-array

                    // Store all items to be added to $product here; adding them on the spot
                    // inside the foreach will result in an infinite loop
                    $append = array();
                    foreach ($result as &$product) {
                        // Do step 1 above. array_shift is not the most efficient, but it
                        // allows us to iterate over the rest of the items with a simple
                        // foreach, making the code short and familiar.
                        $product[$key] = array_shift($values);

                        // $product is by reference (that's why the key we added above
                        // will appear in the end result), so make a copy of it here
                        $copy = $product;

                        // Do step 2 above.
                        foreach ($values as $item) {
                            $copy[$key] = $item;
                            $append[] = $copy;
                        }

                        // Undo the side effecst of array_shift
                        array_unshift($values, $product[$key]);
                    }

                    // Out of the foreach, we can add to $results now
                    $result = array_merge($result, $append);
                }
            }

            return $result;
        }
    }

    $variation_ids = array();
    $possible_variations = array_cartesian($variations);

    foreach ($possible_variations as $variation) {
        // Check if variation already exists
        if (in_array($variation, $available_variations)) {
            continue;
        }

        $attrs = array();
        foreach ($variation as $key => $value) {
            $attrs[$key] = $value;
        }
        $variation_ids[] = $attrs;
    }

    if (method_exists($woocommerce, 'clear_product_transients')) {
        $woocommerce->clear_product_transients($post_id);
    } else if (function_exists('wc_delete_product_transients')) {
        wc_delete_product_transients($post_id);
    }

    $_product = null;

    return $variation_ids;
}