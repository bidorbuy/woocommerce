<?php
/**
 * Plugin Name: bidorbuy Store Integrator
 * Plugin URI: www.bidorbuy.co.za
 * @codingStandardsIgnoreStart
 * Description: The bidorbuy store integrator allows you to get products from your online store listed on bidorbuy quickly and easily.
 * @codingStandardsIgnoreEnd
 * Author: bidorbuy
 * Author URI: www.bidorbuy.co.za
 * Version: 2.1.2
 */

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

if (!defined('ABSPATH')) {
    die();
}
require_once(ABSPATH . '/wp-admin/includes/plugin.php');

// TODO: Unsupported features: scheduled sale, onsale tax support, disabled variations
define('BOBSI_PLUGIN_URL', WP_PLUGIN_URL . '/' . str_replace(basename(__FILE__), "", plugin_basename(__FILE__)));
define('BOBSI_ENDPOINT_NAMESPACE', 'bidorbuystoreintegrator');

define('BOBSI_WOOCOMMERCE_PLUGIN_PHP_FILE', 'woocommerce/woocommerce.php');
define('BOBSI_WOOCOMMERCE_PLUGIN_FILE', WP_PLUGIN_DIR . '/' . BOBSI_WOOCOMMERCE_PLUGIN_PHP_FILE);

define('BOBSI_WOOCOMMERCE_CURRENCY_CONVERTER_PLUGIN_PHP_FILE',
'woocommerce-currency-converter/woocommerce-currency-converter.php');
define('BOBSI_WOOCOMMERCE_CURRENCY_CONVERTER_PLUGIN_FILE', WP_PLUGIN_DIR . '/'
    . BOBSI_WOOCOMMERCE_CURRENCY_CONVERTER_PLUGIN_PHP_FILE);

require_once(dirname(__FILE__) . '/vendor/autoload.php');
require_once(dirname(__FILE__) . '/woo-triggers.php');

$platform = 'WordPress ' . strval(get_bloginfo('version'));
if (file_exists(BOBSI_WOOCOMMERCE_PLUGIN_FILE)) {
    $data = get_plugin_data(BOBSI_WOOCOMMERCE_PLUGIN_FILE);
    $platform .= ', ' . $data['Name'] . ' ' . $data['Version'];
}
if (file_exists(BOBSI_WOOCOMMERCE_CURRENCY_CONVERTER_PLUGIN_FILE)) {
    $data = get_plugin_data(BOBSI_WOOCOMMERCE_CURRENCY_CONVERTER_PLUGIN_FILE);
    $platform .= ', ' . $data['Name'] . ' ' . $data['Version'];
}

global $wpdb;

$dbSettings = array(
    bobsi\Db::SETTING_PREFIX => $wpdb->prefix, bobsi\Db::SETTING_SERVER => DB_HOST,
    bobsi\Db::SETTING_USER => DB_USER, bobsi\Db::SETTING_PASS => DB_PASSWORD,
    bobsi\Db::SETTING_DBNAME => DB_NAME
);

bobsi\StaticHolder::getBidorbuyStoreIntegrator()
    ->init(get_bloginfo('name'), get_bloginfo('admin_email'), $platform, get_option(bobsi\Settings::name), $dbSettings);

require_once(dirname(__FILE__) . '/utils.php');
require_once(dirname(__FILE__) . '/woo-currency-converter.php');
require_once(dirname(__FILE__) . '/woo-commerce.php');

if (isset($_POST[bobsi\Settings::nameLoggingFormAction])) {
    $data = array(bobsi\Settings::nameLoggingFormFilename => (isset($_POST[bobsi\Settings::nameLoggingFormFilename]))
        ? sanitize_text_field($_POST[bobsi\Settings::nameLoggingFormFilename]) : '');

    $result = bobsi\StaticHolder::getBidorbuyStoreIntegrator()
        ->processAction(sanitize_text_field($_POST[bobsi\Settings::nameLoggingFormAction]), $data);

    add_action('admin_notices', 'bobsi_show_message__updated');

    function bobsi_show_message__updated() {
        global $result;
        foreach ($result as $warn) {
            echo '<div class="updated"><p><strong>' . bobsi\Version::$name . ':</strong> ' . $warn . '.</p></div>';
        }
    }
}

register_activation_hook(__FILE__, 'bobsi_plugin_activate');

/**
 * Check Woocommerce status plugin
 *
 * @return void  or exit if plugin doesn't install or disabled
 */
function bobsi_check_woocommers_plugin() {
    if (!is_plugin_active(BOBSI_WOOCOMMERCE_PLUGIN_PHP_FILE)) {
        bobsi_exit_with_error(bobsi\Version::$name . ' requires <a href="http://www.woothemes.com/woocommerce/"
            target="_blank">WooCommerce</a> to be activated. Please install and activate <a href="'
            . admin_url('plugin-install.php?tab=search&type=term&s=WooCommerce')
            . '" target="_blank">WooCommerce</a> first.');
    }
}

/**
 * Plugin activate hook
 *
 * @return void
 */
function bobsi_plugin_activate() {
    global $wpdb;

    $warnings = bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getWarnings();
    if (!empty($warnings)) {
        bobsi_exit_with_error(implode('. ', $warnings));
    }

    bobsi_check_woocommers_plugin();
    
    if (!($wpdb->query(bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getQueries()->getInstallAuditTableQuery())
        && $wpdb->query(bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getQueries()->getInstallTradefeedTableQuery())
        && $wpdb->query(bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getQueries()
            ->getInstallTradefeedDataTableQuery()))
    ) {
        bobsi_exit_with_error($wpdb->last_error);
    }

    bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->setExportStatuses(array('publish'));
    bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->setExportVisibilities(array('visible'));

    //add all products to the queue in case of first activation
    if (!get_option('bobsi_first_activate', FALSE)) {
        bobsi_add_all_products_in_tradefeed_queue();
        update_option('bobsi_first_activate', TRUE);
    }

    update_option(bobsi\Settings::name,
        bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->serialize(TRUE));

    bobsi_register_endpoint();
    flush_rewrite_rules();
}

/**
 * Add all products in tradefeed queue.
 *
 * @param bool $update flag to update all products
 *
 * @return bool
 */
function bobsi_add_all_products_in_tradefeed_queue($update = FALSE) {
    global $wpdb;

    $productsIds = array_chunk(bobsi_get_all_products(), 500);
    $productStatus = ($update) ? bobsi\Queries::STATUS_UPDATE : bobsi\Queries::STATUS_NEW;

    foreach ($productsIds as $page) {
        if (!$wpdb->query(bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getQueries()
            ->getAddJobQueries($page, $productStatus))
        ) {
            return FALSE;
        }
    }

    return TRUE;
}

/**
 * Get all products.
 *
 * @return mixed
 */
function bobsi_get_all_products() {
    //    TODO: tax_query starts from 3.1, and As of 3.5, a bug was fixed where tax_query would
    // inadvertently return all posts when a result was empty.
    $statuses = bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getExportStatuses();
    $wpq = array('post_type' => 'product', 'fields' => 'ids', 'post_status' => $statuses);
    $wpq['posts_per_page'] = PHP_INT_MAX;
    $wpq['offset'] = 0;

    $query = new WP_Query();
    $posts = $query->query($wpq);

    $query = NULL;

    return $posts;
}

/**
 * Plugin uninstall hook
 *
 * @return void
 */
function bobsi_plugin_uninstall() {
    global $wpdb;

    $wpdb->query(bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getQueries()->getDropTablesQuery());
    delete_option(bobsi\Settings::name);
    delete_option('bobsi_first_activate');
    uninstall_update_settings();
    bobsi_feature_4451_uninstall();
    flush_rewrite_rules();
}

add_action('admin_init', 'bobsi_register_setting');

/**
 * Register setting
 *
 * @return void
 */
function bobsi_register_setting() {
    register_setting('bobsi-settings', bobsi\Settings::name);
}

/**
 * Admin Settings Page
 *
 * @return void
 */
function bobsi_options() {

    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    foreach (bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getWarnings() as $warn) {
        bobsi_exit_with_error('<strong>' . bobsi\Version::$name . ':</strong> ' . $warn, 'error', FALSE);
    }

    bobsi_admin_submit_form();

    // Currency. Supported by WooCommerce Currency Converter
    $currencies = bobsi_woo_currency_converter_get_currencies();
    $bobsi_currency = '<select name="' . bobsi\Settings::nameCurrency . '">';
    $bobsi_currency .= '<option value="0"></option>';
    foreach ($currencies as $currency) {
        $selected = ($currency == bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getCurrency())
            ? 'selected="selected"' : '';
        $bobsi_currency .= '<option value="' . $currency . '" ' . $selected . '>' . $currency . '</option>';
    }
    $bobsi_currency .= '</select>';

    // statuses to include
    $export_statuses = bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getExportStatuses();
    $statuses = array('publish' => 'Published', 'pending' => 'Pending review', 'draft' => 'Draft');
    $included_statuses =
        '<select id="bobsi-inc-statuses" class="bobsi-select" name="exportStatuses[]" multiple="multiple" size="9">';
    $excluded_statuses =
        '<select id="bobsi-exc-statuses" class="bobsi-select" name="excludeStatuses[]" multiple="multiple" size="9">';

    foreach ($statuses as $key => $status) {
        $t = '<option  value="' . $key . '">' . $status . '</option>';
        if (in_array($key, $export_statuses)) {
            $included_statuses .= $t;
        } else {
            $excluded_statuses .= $t;
        }
    }
    $included_statuses .= '</select>';
    $excluded_statuses .= '</select>';

    //categories to include
    $export_categories = bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getExcludeCategories();
    $included_categories = '<select id="bobsi-inc-categories" class="bobsi-categories-select"
            name="bobsi_inc_categories[]" multiple="multiple" size="9">';
    $excluded_categories = '<select id="bobsi-exc-categories" class="bobsi-categories-select" 
            name="excludeCategories[]" multiple="multiple" size="9">';
    $categories = bobsi_get_categories(array('hide_empty' => 0));

    $uncat = new stdClass();
    $uncat->term_id = 0;
    $uncat->name = 'Uncategorized';
    array_unshift($categories, $uncat);    //adding Uncategorized

    foreach ($categories as $category) {
        $t = '<option  value="' . $category->term_id . '">' . $category->name . '</option>';
        if (in_array($category->term_id, $export_categories)) {
            $excluded_categories .= $t;
        } else {
            $included_categories .= $t;
        }
    }
    $included_categories .= '</select>';
    $excluded_categories .= '</select>';


    $zip_loaded = array_key_exists('zip',
        bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getCompressLibraryOptions());
    $export_link = bobsi_generate_action_url('export',
        bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getTokenExport()
    );
    $download_link = bobsi_generate_action_url('download',
        bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getTokenDownload()
    );
    $resetaudit_link =bobsi_generate_action_url('resetaudit',
        bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getTokenDownload()
    );
    $phpInfo_link = bobsi_generate_action_url('version',
        bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getTokenDownload() . '&phpinfo=y'
    );
    $logfiles_table = bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getLogsHtml();
    $tooltip_img_url = plugins_url('assets/images/tooltip.png', __FILE__);
    $bob_logo_url = plugins_url('assets/images/bidorbuy.png', __FILE__);
    $submit_button = get_submit_button(NULL, 'button-primary bobsi-save-settings');
    $bobsi_filename = bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getFilename();
    $bobsi_username = bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getUsername();
    $bobsi_password = bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getPassword();
    $compress_libs = '';
    foreach (bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getCompressLibraryOptions() as $lib =>
             $descr) {
        $selected = (bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getCompressLibrary() == $lib)
            ? 'selected="selected"' : '';
        $compress_libs .= '<option value="' . $lib . '" ' . $selected . '>' . $lib . '</option>';
    }
    $bobsi_default_quantity =
        bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getDefaultStockQuantity();
    $bobsi_email_notification_addresses =
        bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getEmailNotificationAddresses();
    $bobsi_enable_email_notification =
        (bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getEnableEmailNotifications())
            ? 'checked="checked"' : '';
    $logging_levels = '';
    foreach (bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getLoggingLevelOptions() as $level) {
        $selected = (bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getLoggingLevel() == $level)
            ? 'selected="selected"' : '';
        $logging_levels .= '<option value="' . $level . '" ' . $selected . '>' . ucfirst($level) . '</option>';
    }
    $bobsi_min_quantity = bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getExportQuantityMoreThan();
    $only_active_products =
        1; //bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getExportActiveProducts();

    include(dirname(__FILE__) . '/templates/options.tpl.php');
}

/**
 * Settings submit action
 *
 * @return void
 */
function bobsi_admin_submit_form() {
    global $wpdb;

    if (isset($_POST[bobsi\Settings::nameActionReset])) {
        bobsi\StaticHolder::getBidorbuyStoreIntegrator()->processAction(bobsi\Settings::nameActionReset);
        update_option(bobsi\Settings::name,
            bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->serialize(TRUE));
    }

    if (isset($_POST['submit_options']) && $_POST['submit_options'] == 1) {
        unset($_POST['submit_options']);
        unset($_POST['submit']);

        //**************
        $wordings = bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getDefaultWordings();

        $presaved_settings = array();
        $prevent_saving = FALSE;

        $settings_checklist = array(bobsi\Settings::nameUsername => 'strval',
                                    bobsi\Settings::namePassword => 'strval',
                                    bobsi\Settings::nameFilename => 'strval',
                                    bobsi\Settings::nameCompressLibrary => 'strval',
                                    bobsi\Settings::nameDefaultStockQuantity => 'intval',
                                    bobsi\Settings::nameEmailNotificationAddresses => 'strval',
                                    bobsi\Settings::nameEnableEmailNotifications => 'bool',
                                    bobsi\Settings::nameLoggingLevel => 'strval',
                                    bobsi\Settings::nameExportQuantityMoreThan => 'intval',
                                    bobsi\Settings::nameExcludeCategories => 'categories',
                                    bobsi\Settings::nameExportStatuses => 'categories');

        $data = $_POST;

        foreach ($settings_checklist as $setting => $prevalidation) {
            $presaved_settings[$setting] = bobsi_admin_prevalidation_settings($data, $setting, $prevalidation);

            if (!call_user_func($wordings[$setting][bobsi\Settings::nameWordingsValidator],
                $presaved_settings[$setting])
            ) {
                $field = $wordings[$setting][bobsi\Settings::nameWordingsTitle];
                _e("<div class=\"error notice\">
                        <p>
                        <strong>
                            invalid value: \" $presaved_settings[$setting]\" 
                            in the field: $field
                        </strong>
                        </p>
                    </div>");

                $prevent_saving = TRUE;

            }
        }

        if (!$prevent_saving) {
            //Saving tokens
            $presaved_settings[bobsi\Settings::nameTokenExport] =
                bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getTokenExport();

            $presaved_settings[bobsi\Settings::nameTokenDownload] =
                bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getTokenExport();

            $previousSettings = bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->serialize(TRUE);

            bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->unserialize(serialize($presaved_settings));

            $newSettings = bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->serialize(TRUE);

            update_option(bobsi\Settings::name, $newSettings);

            if (bobsi\StaticHolder::getBidorbuyStoreIntegrator()
                ->checkIfExportCriteriaSettingsChanged($previousSettings, $newSettings, TRUE)
            ) {
                $wpdb->query(bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getQueries()->getTruncateJobsQuery());

                $wpdb->query(bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getQueries()->getTruncateProductQuery());

                bobsi_add_all_products_in_tradefeed_queue(TRUE);
            }
        }
        if (bobsi\StaticHolder::getBidorbuyStoreIntegrator()
            ->checkIfExportCriteriaSettingsChanged($previousSettings, $newSettings, TRUE)
        ) {
            bobsi_refresh_all_products();
            //            $notes = array('The export criteria settings have been successfully changed.
            // Please re-export your products.');
        }
    }
}

/**
 * Prevalidation settings
 *
 * @param array  $data          data from $_POST
 * @param string $setting       setting
 * @param string $prevalidation rule
 *
 * @return mixed
 */
function bobsi_admin_prevalidation_settings($data, $setting, $prevalidation) {
    switch ($prevalidation) {
        case ('strval'):
            $presaved_settings = isset($data[$setting]) ? strval($data[$setting]) : '';
            break;
        case ('intval'):
            $presaved_settings = isset($data[$setting]) ? $data[$setting] : 0;
            break;
        case ('bool'):
            $presaved_settings = isset($data[$setting]) ? (bool)($data[$setting]) : FALSE;
            break;
        case ('categories'):
            $presaved_settings = isset($data[$setting]) ? (array)$data[$setting] : array();
    }

    return $presaved_settings;
}

add_action('admin_menu', 'bobsi_admin_menu');

/**
 * Admin Menu Hook
 *
 * @return void
 */
function bobsi_admin_menu() {
    $page_hook_suffix =
        add_options_page(bobsi\Version::$name, bobsi\Version::$name, 'manage_options', bobsi\Version::$id,
            'bobsi_options');

    add_action('admin_print_scripts-' . $page_hook_suffix, 'bobsi_required_scripts');

    /**
     * Add JS Scripts To Settings page.
     *
     * @return void
     */
    function bobsi_required_scripts() {
        wp_enqueue_script('bobsi_admin',
            plugins_url('vendor/com.extremeidea.bidorbuy/storeintegrator-core/assets/js/admin.js', __FILE__),
            array('jquery', 'jquery-tiptip', 'woocommerce_admin'));
        wp_enqueue_script('bobsi_copy_button', plugins_url('/assets/js/copy-button.js', __FILE__), array('jquery'));
        wp_enqueue_style('woocommerce_admin_styles', WP_PLUGIN_URL . '/woocommerce/assets/css/admin.css');
        wp_enqueue_style('bobsi_admin_styles', plugins_url('assets/css/styles.css', __FILE__));
    }
}

/**
 * Update functions
 */
// Add hook when plugins loaded, call update function.
add_action('plugins_loaded', 'bobsi_plugin_check_update');

/**
 * Check update for plugin
 *
 * @return void
 */
function bobsi_plugin_check_update() {

    $database_version = get_option('bobsi_db_version');
    
    if ($database_version) {
        if (version_compare($database_version, '2.0.7', '<')) {
            plugin_update();
        }
        if (version_compare($database_version, '2.0.12', '<')) {
            bobsi_feature_4451_update();
        }
        if (version_compare($database_version, '2.0.15', '<')) {
            update_option('bobsi_show_admin_notices', 1);
        }
        if (version_compare($database_version, '2.1.1', '<')) {
            bobsi_update_tables_collation();
        }
    } else {
        /* First install or old plugin version < 2.0.7 */
        plugin_update();
        bobsi_feature_4451_update();
        bobsi_update_tables_collation();
    }

    $version = bobsi\Version::getVersionFromString(bobsi\Version::$version);
    
    if ($database_version !== $version) {
        update_option('bobsi_db_version', $version);
    }
}

/**
 * Plugin update
 *
 * @return void
 */
function plugin_update() {
    global $wpdb;

    bobsi_add_all_products_in_tradefeed_queue(TRUE);
    $query =
        "ALTER TABLE " . $wpdb->prefix . bobsi\Queries::TABLE_BOBSI_TRADEFEED . " ADD `images` text AFTER `image_url`";
    $wpdb->query($query);
}

/* Defect #4031 */
add_filter('style_loader_src', 'bobsi_delete_css');

/**
 * Delete css from header.
 *
 * @param string $href url to css
 *
 * @return mixed
 */
function bobsi_delete_css($href) {
    if (strpos($href, "menu.css") !== FALSE) {
        return FALSE;
    }

    return $href;
}


/* View */
add_action('woocommerce_after_add_attribute_fields', 'bobsi_woocommerce_after_add_attribute_fields');
add_action('woocommerce_after_edit_attribute_fields', 'bobsi_woocommerce_after_edit_attribute_fields');
add_action('woocommerce_after_product_attribute_settings', 'bobsi_woocommerce_after_product_attribute_settings', 10, 2);
/* Actions */
add_action('woocommerce_attribute_added', 'bobsi_woocommerce_attribute_added');
add_action('woocommerce_attribute_updated', 'bobsi_woocommerce_attribute_updated');
add_action('wp_ajax_woocommerce_save_attributes', 'bobsi_wp_ajax_woocommerce_save_attributes');
define('BOBSI_WOOCOMMERCE_ATTRIBUTE_COLUMN', 'bobsi_attribute_flag');
define('BOBSI_WOOCOMMERCE_ATTRIBUTE_FIELD', 'bobsi_attribute_field');

/**
 * Add checkbox on WooCommerce: Product->Attributes for new attributes
 *
 * @return void
 */
function bobsi_woocommerce_after_add_attribute_fields() {
    echo "<div class='form-field'>
          <label for='bobsi_attribute_field'>
          <input name='" . BOBSI_WOOCOMMERCE_ATTRIBUTE_COLUMN . "' id='bobsi_attribute_field' type='checkbox' 
                value='1' checked> Add this attribute to product name in bidorbuy tradefeed?</label>
          <p class='description'>Enable if you want add this attribute to bidorbuy tradefeed product name.</p>
          </div>
         ";
}

/**
 * Add checkbox on WooCommerce: Product->Attributes on edit page(update attribute)
 *
 * @return void
 */
function bobsi_woocommerce_after_edit_attribute_fields() {
    global $wpdb;
    $edit = absint($_GET['edit']);
    $attribute = $wpdb->get_row("SELECT " . BOBSI_WOOCOMMERCE_ATTRIBUTE_COLUMN . " FROM " . $wpdb->prefix
        . "woocommerce_attribute_taxonomies WHERE attribute_id = '$edit'");
    $param = BOBSI_WOOCOMMERCE_ATTRIBUTE_COLUMN;
    $flag = $attribute->$param;
    echo "
        <tr class='form-field form-required'>
            <th scope='row' valign='top'>
                <label for='bobsi_attribute_field'>Add this attribute to product name in bidorbuy tradefeed?</label>
            </th>
            <td>
                 <input name='" . BOBSI_WOOCOMMERCE_ATTRIBUTE_COLUMN
        . "' id='bobsi_attribute_field' type='checkbox' value='1' " . checked($flag, 1, 0) . ">
                 <p class='description'>Enable if you want add this attribute to bidorbuy tradefeed product name.
</p>
            </td>
        </tr>
         ";
}

/**
 * Add value for bobsi in woocommerce table after attribute added
 *
 * @param int $attribute_id attribute id
 *
 * @return void
 */
function bobsi_woocommerce_attribute_added($attribute_id) {
    global $wpdb;
    $attributeFlag = isset($_POST[BOBSI_WOOCOMMERCE_ATTRIBUTE_COLUMN]) ? $_POST[BOBSI_WOOCOMMERCE_ATTRIBUTE_COLUMN] : 0;
    $wpdb->update($wpdb->prefix . 'woocommerce_attribute_taxonomies',
        array(BOBSI_WOOCOMMERCE_ATTRIBUTE_COLUMN => $attributeFlag), array('attribute_id' => $attribute_id));
}

/**
 * Change value for bobsi in woocommerce table after attribute updated
 *
 * @param int $attribute_id attribute id
 *
 * @return void
 */
function bobsi_woocommerce_attribute_updated($attribute_id) {
    global $wpdb;
    $attributeFlag = isset($_POST[BOBSI_WOOCOMMERCE_ATTRIBUTE_COLUMN]) ? $_POST[BOBSI_WOOCOMMERCE_ATTRIBUTE_COLUMN] : 0;
    $wpdb->update($wpdb->prefix . 'woocommerce_attribute_taxonomies',
        array(BOBSI_WOOCOMMERCE_ATTRIBUTE_COLUMN => $attributeFlag), array('attribute_id' => $attribute_id));
}

/* Custom Attributes */

/**
 * Add checkbox on WooCommerce: Product->Edit_Product->Attributes
 *
 * @param object $attribute Woocommerce attribute
 * @param  int $i attribute array index
 *
 * @return void
 */
function bobsi_woocommerce_after_product_attribute_settings($attribute, $i) {
    global $post;
    $postMeta = get_post_meta($post->ID, '_' . BOBSI_WOOCOMMERCE_ATTRIBUTE_FIELD);
    $excludedAttributes = array_shift($postMeta) ?: array();

    $checked = !in_array($attribute->get_name(), $excludedAttributes);
    echo "
        <tr>
            <td>
                <label>
                    <input type='checkbox' class='checkbox' name='" . BOBSI_WOOCOMMERCE_ATTRIBUTE_FIELD . "[$i]' 
                    value='1' " . checked($checked, 1, 0) . "> Add this attribute to product name in bidorbuy tradefeed
                </label>
            </td>
        </tr>
    ";
}

/**
 * Update bobsi attribute field for product
 *
 * @return void
 */
function bobsi_wp_ajax_woocommerce_save_attributes() {
    parse_str($_POST['data'], $data);
    $product_id = absint($_POST['post_id']);
    $exclutedAttributes = array();
    $attributes = $data['attribute_names'];
    foreach ($attributes as $key => $attribute) {
        if (!isset($data[BOBSI_WOOCOMMERCE_ATTRIBUTE_FIELD][$key])) {
            $exclutedAttributes[] = $attribute;
        }
    }
    update_post_meta($product_id, '_' . BOBSI_WOOCOMMERCE_ATTRIBUTE_FIELD, $exclutedAttributes);
}

/**
 * Update function for plugin.
 * Add new bobsi column for woocommerce table
 *
 * @return mixed
 */
function bobsi_feature_4451_update() {
    global $wpdb;
    $result = $wpdb->query("ALTER TABLE `" . $wpdb->prefix . "woocommerce_attribute_taxonomies` ADD `"
        . BOBSI_WOOCOMMERCE_ATTRIBUTE_COLUMN . "` TINYINT(1) NULL DEFAULT '1' AFTER `attribute_public`");
    return $result;
}

/**
 * Delete bobsi column in woocommerce table
 *
 * @return mixed
 */
function bobsi_feature_4451_uninstall() {
    global $wpdb;
    $result = $wpdb->query("ALTER TABLE `" . $wpdb->prefix . "woocommerce_attribute_taxonomies` DROP `"
        . BOBSI_WOOCOMMERCE_ATTRIBUTE_COLUMN . "`");

    return $result;
}

/**
 * Register endpoint
 *
 * @return void
 */
function bobsi_register_endpoint() {
    add_rewrite_endpoint(BOBSI_ENDPOINT_NAMESPACE, EP_ROOT);
}

add_action('init', 'bobsi_register_endpoint');

/**
 * Endpoint controller
 *
 * @param object $query request
 *
 * @return void
 */
function bobsi_init_endpoint($query) {
    $request = $query->get(BOBSI_ENDPOINT_NAMESPACE);
    if ($query->is_main_query() && $request) {
        $params = explode('/', $request);
        $action = sanitize_text_field($params[0]);
        $token = isset($params[1]) ? substr($params[1], 0, 32) : '';
        $token = sanitize_text_field($token);
        $_REQUEST[bobsi\Settings::paramToken] = $token;
        switch ($action) {
            case 'export':
                include_once 'export.php';
                break;
            case 'download':
                include_once 'download.php';
                break;
            case 'resetaudit':
                include_once 'resetaudit.php';
                break;
            case 'version':
                $phpInfoParam = isset($params[1]) ? sanitize_text_field($params[1]) : '';
                $phpInfo = strpos($phpInfoParam, 'phpinfo=y') !== FALSE;
                if ($phpInfo) {
                    $_REQUEST['phpinfo'] = 'y';
                }
                include_once 'version.php';
                break;
            case 'downloadl':
                include_once 'downloadl.php';
                break;
        }
    }
}

add_action('pre_get_posts', 'bobsi_init_endpoint');

/**
 * Generate action url
 *
 * @param string $action action
 * @param string $token token
 *
 * @return string
 */
function bobsi_generate_action_url($action, $token) {
    $siteUrl = site_url();
    if (get_option('permalink_structure')) {
        // pretty links
        return  "{$siteUrl}/" . BOBSI_ENDPOINT_NAMESPACE . "/{$action}/{$token}";
    }
    return "$siteUrl?" . BOBSI_ENDPOINT_NAMESPACE . "={$action}/{$token}";
}

/**
 * Show Dashboard Warning.
 *
 * @return void
 */
function bobsi_new_urls_warning() {
    if (isset($_POST['bobsi_close_admin_notice'])) {
        delete_option('bobsi_show_admin_notices');
        delete_transient('bobsi_show_admin_notices');
        return;
    }
    $message = '<h3><b style="color: red">bidorbuy Store Integrator warning:</b>
               to improve plugin security the export/download link structure will be changed from 
               Store Integrator 2.0.15 version and higher. 
               <b>Please ensure you have provided updated links to bidorbuy.</b></h3>';
    echo "
        <div id='bobsi_admin_warning' class='error notice'>
            $message
            <p>
            <div align='right'>
            <form method='post'>
                <input type='submit' name='bobsi_close_admin_notice' value='Close'>            
            </form> 
            </div>
            </p>
        </div>
        <script>
        jQuery(document).ready(function() {
          (function blink() { 
                jQuery('#bobsi_admin_warning').fadeOut(500).fadeIn(5000, blink); 
            })();  
        })
            
        </script>        
    ";
}


if (get_option('bobsi_show_admin_notices')) {
    add_action('admin_notices', 'bobsi_new_urls_warning');
}

/**
 * Update table collation if collation isn't utf8_unicode_ci
 * 
 * @return void
 */
function bobsi_update_tables_collation() {
    global $wpdb;
    $showTableInfoSql = "SHOW TABLE STATUS WHERE name='{$wpdb->prefix}%s'";
    $alterTableSql = "ALTER TABLE {$wpdb->prefix}%s CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci";
    $tableNames = [
        bobsi\Queries::TABLE_BOBSI_TRADEFEED_AUDIT,
        bobsi\Queries::TABLE_BOBSI_TRADEFEED,
        bobsi\Queries::TABLE_BOBSI_TRADEFEED_TEXT
    ];
    foreach ($tableNames as $tableName) {
        $showTableInfoQuery = sprintf($showTableInfoSql, $tableName);
        $result = $wpdb->get_results($showTableInfoQuery, ARRAY_A);
        $result = array_shift($result);

        if ($result['Collation'] !== 'utf8_unicode_ci') {
            $alterTableQuery = sprintf($alterTableSql, $tableName);
            $wpdb->query($alterTableQuery);
        }
    }
}
