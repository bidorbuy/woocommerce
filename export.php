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

require_once(dirname(__FILE__) . '/../../../wp-config.php');

bobsi_check_woocommers_plugin();

/**
 * Get breadcrumb
 *
 * @param integer $categoryId category id
 *
 * @return string
 */
function bobsi_get_breadcrumb($categoryId) {
    $names = array();

    while ($term = get_term_by('id', $categoryId, 'product_cat')) {
        $names[] = $term->name;
        $categoryId = $term->parent;

        $term = NULL;
    }

    $breadcrumb = implode(' > ', array_reverse($names));
    $names = NULL;

    return $breadcrumb;
}

/**
 * Calc product quantity
 *
 * @param WC_Product $product product
 * @param int $default default
 *
 * @return int
 */
function bobsi_calc_product_quantity(WC_Product &$product, $default = 0) {
    $qty = intval($product->get_stock_quantity());
    return ($product->managing_stock()) ? $qty : ($product->is_in_stock() ? $default : 0);
}

/**
 * Build export product
 *
 * @param WC_Product $product product
 * @param array $variations variations
 * @param array $categories categories
 *
 * @return array
 */
function &bobsi_build_export_product(&$product, $variations = array(), $categories = array()) {
    $exportedProduct = array();

    $sku = $product->id;
    if ($product instanceof WC_Product_Variation) {
        $exportedProduct[bobsi\Settings::paramVariationId] = $product->variation_id;
        $sku .= '-' . $product->variation_id;
    }
    $sku .= ($product->get_sku() != '') ? '-' . $product->get_sku() : '';

    $exportedProduct[bobsi\Tradefeed::nameProductId] = $product->id;
    $exportedProduct[bobsi\Tradefeed::nameProductName] = $product->get_title();
    $exportedProduct[bobsi\Tradefeed::nameProductCode] = $sku;

    if ($product->is_on_sale()) {
        $exportedProduct[bobsi\Tradefeed::nameProductPrice] = bobsi_convert_price(
            (double)$product->get_price_including_tax()
        );

        //Is it real sale?
        if ($product->get_price() !== $product->regular_price) {
            $price = $product->get_price();
            $product->set_price($product->regular_price);
            $exportedProduct[bobsi\Tradefeed::nameProductMarketPrice] = bobsi_convert_price(
                (double)$product->get_price_including_tax()
            );

            $product->set_price($price);
        } else {
            $exportedProduct[bobsi\Tradefeed::nameProductMarketPrice] = '';
        }
    } else {
        $exportedProduct[bobsi\Tradefeed::nameProductPrice] = bobsi_convert_price(
            (double)$product->get_price_including_tax()
        );

        $exportedProduct[bobsi\Tradefeed::nameProductMarketPrice] = '';
    }

    $exportedProduct[bobsi\Tradefeed::nameProductCondition] = bobsi\Tradefeed::conditionNew;
    //$product->id = $id; // It is 0 by default, besides $product->get_shipping_class()
    // returns "slug" instead of "name"
    //$exportedProduct[bobsi\Tradefeed::nameProductShippingClass] = $product->get_shipping_class();

    $exportedProduct[bobsi\Tradefeed::nameProductShippingClass] =
        ($product instanceof WC_Product_Variation) ?
            bobsi_get_shipping_class($product->variation_id, $product->parent->id)
            : bobsi_get_shipping_class($product->id);

    if (isset($variations['attributes'])) {
        foreach ($variations['attributes'] as $key => $value) {
            $exportedProduct[bobsi\Tradefeed::nameProductAttributes][$key] = $value;
        }
    }
    $exclAttr = get_post_meta($product->id, '_' . BOBSI_WOOCOMMERCE_ATTRIBUTE_FIELD);
    $excludedAttributes = array_shift($exclAttr) ?: array();
    $excludedAttributes = array_map('bobsi_attribute_label', $excludedAttributes);
    $exportedProduct[bobsi\Tradefeed::nameProductExcludedAttributes] = $excludedAttributes;

    if ($product->width) {
        $exportedProduct[bobsi\Tradefeed::nameProductAttributes][bobsi\Tradefeed::nameProductAttrWidth] =
            number_format($product->width, 2, '.', '');
        //        $exportedProduct[bobsi\Tradefeed::nameProductAttributes][bobsi\Tradefeed::nameProductAttrWidth] =
        // intval($product->width);
    }

    if ($product->height) {
        $exportedProduct[bobsi\Tradefeed::nameProductAttributes][bobsi\Tradefeed::nameProductAttrHeight] =
            number_format($product->height, 2, '.', '');
        //        $exportedProduct[bobsi\Tradefeed::nameProductAttributes][bobsi\Tradefeed::nameProductAttrHeight] =
        // intval($product->height);
    }

    if ($product->length) {
        $exportedProduct[bobsi\Tradefeed::nameProductAttributes][bobsi\Tradefeed::nameProductAttrLength] =
            number_format($product->length, 2, '.', '');
        //        $exportedProduct[bobsi\Tradefeed::nameProductAttributes][bobsi\Tradefeed::nameProductAttrLength] =
        // intval($product->length);
    }

    if ($product->has_weight()) {
        $exportedProduct[bobsi\Tradefeed::nameProductAttributes][bobsi\Tradefeed::nameProductAttrShippingWeight] =
            number_format($product->get_weight(), 2, '.', '') . get_option('woocommerce_weight_unit', '');
        //        $exportedProduct[bobsi\Tradefeed::nameProductAttributes][bobsi\Tradefeed::nameProductAttrWeight] =
        // intval($product->get_weight());
    }

    $exportedProduct[bobsi\Tradefeed::nameProductAvailableQty] = bobsi_calc_product_quantity(
        $product,
        bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getDefaultStockQuantity()
    );

    //Image of the variation has Priority 1. If there is no image in the variation - get the image of the product.
    $image = FALSE;
    if ($product instanceof WC_Product_Variation) {
        $image = wp_get_attachment_url(get_post_thumbnail_id($product->variation_id));
    }
    $image = $image ? $image : wp_get_attachment_url(get_post_thumbnail_id($product->id));

    if ($image) {
        $exportedProduct[bobsi\Tradefeed::nameProductImageURL] = $image;
    }
    $images = $image ? array($image) : array();

    $attachment_ids = $product->get_gallery_attachment_ids();
    foreach ($attachment_ids as $attachment_id) {
        //Get URL of Gallery Images - default wordpress image sizes
        $images[] = wp_get_attachment_url($attachment_id);
    }

    if (!empty($images)) {
        $exportedProduct[bobsi\Tradefeed::nameProductImages] = $images;
    }
    //Add categories to the product
    $categorie_names = array();
    $categorie_ids = array();

    foreach ($categories as $category_id) {
        $categorie_names[] = bobsi_get_breadcrumb($category_id);
        $categorie_ids[] = $category_id;
    }
    $exportedProduct[bobsi\Settings::paramCategoryId] = bobsi\Tradefeed::categoryIdDelimiter
        . join(bobsi\Tradefeed::categoryIdDelimiter, $categorie_ids)
        . bobsi\Tradefeed::categoryIdDelimiter;
    $exportedProduct[bobsi\Tradefeed::nameProductCategory] = join(
        bobsi\Tradefeed::categoryNameDelimiter,
        $categorie_names
    );

    return $exportedProduct;
}

/**
 * Export products
 *
 * @param integer $id id
 * @param array $available_variations available variations
 *
 * @return array
 */
function bobsi_export_products($id, $available_variations = array()) {
    $exportedProducts = array();

    $exportQuantityMoreThan = bobsi\StaticHolder::getBidorbuyStoreIntegrator()
        ->getSettings()->getExportQuantityMoreThan();
    $defaultStockQuantity = bobsi\StaticHolder::getBidorbuyStoreIntegrator()
        ->getSettings()->getDefaultStockQuantity();
    $exportVisibilities = bobsi\StaticHolder::getBidorbuyStoreIntegrator()
        ->getSettings()->getExportVisibilities();

    $product = function_exists('wc_get_product') ? wc_get_product($id) : get_product($id);
    bobsi\StaticHolder::getBidorbuyStoreIntegrator()->logInfo('Processing product id: ' . $id);

    if (count(bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getExportStatuses()) == 0
        or !in_array(
            $product->post->post_status,
            bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getExportStatuses())
    ) {
        return $exportedProducts;
    }

    $allowedCategories = bobsi_get_export_categories_ids(bobsi\StaticHolder::getBidorbuyStoreIntegrator()
        ->getSettings()->getExcludeCategories());

    $productCategories = wp_get_object_terms(($product instanceof WC_Product_Variation)
        ? $product->parent->id
        : $id, 'product_cat', array('fields' => 'ids'));
    if (empty($productCategories)) {
        $productCategories[] = 0;
    }

    $categoriesMatching = array_intersect($allowedCategories, $productCategories);
    if (empty($categoriesMatching)) {
        return $exportedProducts;
    }

    $productVisibility = $product->post->post_password ? 'protected' : 'visible';
    if (!empty($exportVisibilities) && !in_array($productVisibility, $exportVisibilities)) {
        return $exportedProducts;
    }

    if (!($product instanceof WC_Product_Variation)) {
        $exportedProducts[bobsi\Tradefeed::nameProductSummary] = $product->get_post_data()->post_excerpt;
        $exportedProducts[bobsi\Tradefeed::nameProductDescription] = $product->get_post_data()->post_content;
    }

    if ($product instanceof WC_Product_Variable) {
        $available_variations = bobsi_woo_commerce_get_all_variations($id);

        $ids = $product->get_children();
        foreach ($ids as $vid) {
            $ps = bobsi_export_products($vid, $available_variations, TRUE);
            foreach ($ps as &$item) {
                $exportedProducts[] = $item;
            }
        }
    } else if (($product instanceof WC_Product_Simple || $product instanceof WC_Product_Variation)) {
        $attributes = $product instanceof WC_Product_Variation ?
            $product->parent->get_attributes() : $product->get_attributes();

        $attributes_variations = $product instanceof WC_Product_Variation ?
            $product->get_variation_attributes() : array();

        if (bobsi_calc_product_quantity($product, $defaultStockQuantity) > $exportQuantityMoreThan) {
            if (!empty($attributes_variations) && in_array('', array_values($attributes_variations))) {
                $mhash = bobsi\StaticHolder::getBidorbuyStoreIntegrator()->shash($attributes_variations);

                $available_variations_copy = $available_variations;

                foreach ($available_variations_copy as &$variation) {
                    $hash = bobsi\StaticHolder::getBidorbuyStoreIntegrator()->shash($variation);
                    if (preg_match('/^' . $mhash . '$/', $hash)) {
                        $variations = bobsi_get_sorted_attributes($variation, $attributes, $product);
                        bobsi_build_export_product_helper(
                            $product, $variations, $categoriesMatching, $exportedProducts
                        );
                    }
                }
            } else {
                $variations = bobsi_get_sorted_attributes($attributes_variations, $attributes, $product);
                bobsi_build_export_product_helper($product, $variations, $categoriesMatching, $exportedProducts);
            }
        } else {
            bobsi\StaticHolder::getBidorbuyStoreIntegrator()
                ->logInfo('QTY is not enough to export product id: ' . $id);
        }

        $product = NULL;
    }

    return $exportedProducts;
}

/**
 * Build export product helper
 *
 * @param WC_Product $product product
 * @param array $variations variations
 * @param array $categoriesMatching categories
 * @param array $exportedProducts exported product
 *
 * @return mixed
 */
function bobsi_build_export_product_helper($product, $variations, $categoriesMatching, &$exportedProducts) {
    $p = bobsi_build_export_product($product, $variations, $categoriesMatching);
    if (intval($p[bobsi\Tradefeed::nameProductPrice]) > 0) {
        $exportedProducts[] = $p;
    } else {
        bobsi\StaticHolder::getBidorbuyStoreIntegrator()
            ->logInfo('Product price <= 0, skipping, product id: ' . $product->id);
    }
}
/**
 * Puts in order all variations and attributes
 *
 * @param array $attributes_variations - variable attributes
 * @param array $attributes - simple attributes of the product
 * @param object $product product
 *
 * @return array
 */
function bobsi_get_sorted_attributes($attributes_variations, $attributes, &$product) {
    $variations = array();
    //Order of attrs: variations should come first in tradefeed.
    foreach ($attributes_variations as $attr_var_key => $attr_var_val) {
        $name = strstr($attr_var_key, 'attribute_')
            ? str_replace('attribute_', '', $attr_var_key)
            : $attr_var_key;

        $variations['attributes'][bobsi_attribute_label($name)] = '';
    }

    foreach ($attributes as $key => &$attribute) {
        //      if ((isset($attribute['is_visible']) && $attribute['is_visible'])) {
        $variations['attributes'][bobsi_attribute_label($attribute['name'])] =
            isset($attributes_variations['attribute_' . $key]) ? $attributes_variations['attribute_' . $key]
                : $product->get_attribute($attribute['name']);
        //      }
    }

    return $variations;
}

/**
 * Attribute label
 *
 * @param string $name name
 *
 * @return mixed
 */
function bobsi_attribute_label($name) {
    if (strstr($name, 'pa_')) {
        $name = str_replace('pa_', '', $name);
    }
    return $name;
}

$token = isset($_REQUEST[bobsi\Settings::paramToken]) ? $_REQUEST[bobsi\Settings::paramToken] : FALSE;
$ids = isset($_POST[bobsi\Settings::paramIds]) ? $_POST[bobsi\Settings::paramIds] : FALSE;
$productStatus = isset($_POST[bobsi\Settings::paramProductStatus]) ?
    $_POST[bobsi\Settings::paramProductStatus] : FALSE;

$excludededAttributes = array('Width', 'Height', 'Length');
delete_transient('wc_attribute_taxonomies');
foreach (wc_get_attribute_taxonomies() as $attribute) {
    if (!$attribute->bobsi_attribute_flag) {
        $excludededAttributes[] = $attribute->attribute_name;
    }
}

$exportConfiguration = array(
    bobsi\Settings::paramIds => $ids,
    bobsi\Settings::paramProductStatus => $productStatus,
    bobsi\Tradefeed::settingsNameExcludedAttributes => $excludededAttributes,
    bobsi\Settings::paramCallbackGetProducts => 'bobsi_get_products',
    bobsi\Settings::paramCallbackGetBreadcrumb => 'bobsi_get_breadcrumb',
    bobsi\Settings::paramCallbackExportProducts => 'bobsi_export_products',
    bobsi\Settings::paramExtensions => array(),
    bobsi\Settings::paramCategories => bobsi_get_export_categories_ids(
        bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getExcludeCategories()
    ),

);

$plugins = FALSE;
// get active plugins
$plugins = get_option('active_plugins');
$extensions = &$exportConfiguration[bobsi\Settings::paramExtensions];
foreach ($plugins as $plugin) {
    $pluginData = get_plugin_data(dirname(__FILE__) . '/../' . $plugin);
    $extensions[$pluginData['Name']] = $pluginData['Name'] . 'Version: ' . $pluginData['Version'];
}


if (bobsi_is_woo_currency_converter_active()) {
    $currency = bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getCurrency();
    if (!empty($currency)) {
        //        setcookie('wccc-currency', $currency, time() + (86400 * 365), '/');
        $_COOKIE['wccc-currency'] = $currency;
    } else {
        unset($_COOKIE['wccc-currency']);
    }
}

bobsi\StaticHolder::getBidorbuyStoreIntegrator()->export($token, $exportConfiguration);