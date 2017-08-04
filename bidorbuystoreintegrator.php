<?php
/**
 * Plugin Name: bidorbuy Store Integrator
 * Plugin URI: www.bidorbuy.co.za
 * @codingStandardsIgnoreStart
 * Description: The bidorbuy store integrator allows you to get products from your online store listed on bidorbuy quickly and easily.
 * @codingStandardsIgnoreEnd
 * Author: bidorbuy
 * Author URI: www.bidorbuy.co.za
 * Version: 2.0.11
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

$dbSettings = array(bobsi\Db::SETTING_PREFIX => $wpdb->prefix, bobsi\Db::SETTING_SERVER => DB_HOST,
    bobsi\Db::SETTING_USER => DB_USER, bobsi\Db::SETTING_PASS => DB_PASSWORD, bobsi\Db::SETTING_DBNAME => DB_NAME);

bobsi\StaticHolder::getBidorbuyStoreIntegrator()
    ->init(
        get_bloginfo('name'),
        get_bloginfo('admin_email'),
        $platform,
        get_option(bobsi\Settings::name),
        $dbSettings
    );

require_once(dirname(__FILE__) . '/utils.php');
require_once(dirname(__FILE__) . '/woo-currency-converter.php');
require_once(dirname(__FILE__) . '/woo-commerce.php');

if (isset($_POST[bobsi\Settings::nameLoggingFormAction])) {
    $data = array(bobsi\Settings::nameLoggingFormFilename => (isset($_POST[bobsi\Settings::nameLoggingFormFilename]))
        ? $_POST[bobsi\Settings::nameLoggingFormFilename] : '');
    $result = bobsi\StaticHolder::getBidorbuyStoreIntegrator()
        ->processAction($_POST[bobsi\Settings::nameLoggingFormAction], $data);

    add_action('admin_notices', 'bobsi_show_message__updated');

    function bobsi_show_message__updated() {
        global $result;
        foreach ($result as $warn) {
            echo '<div class="updated"><p><strong>' . bobsi\Version::$name . ':</strong> ' . $warn . '.</p></div>';
        }
    }
}

register_activation_hook(__FILE__, 'bobsi_plugin_activate');
register_uninstall_hook(__FILE__, 'bobsi_plugin_uninstall');

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

    if (!is_plugin_active(BOBSI_WOOCOMMERCE_PLUGIN_PHP_FILE)) {
        bobsi_exit_with_error(bobsi\Version::$name . ' requires <a href="http://www.woothemes.com/woocommerce/"
            target="_blank">WooCommerce</a> to be activated. Please install and activate <a href="'
            . admin_url('plugin-install.php?tab=search&type=term&s=WooCommerce')
            . '" target="_blank">WooCommerce</a> first.');
    }

    if (!($wpdb->query(bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getQueries()->getInstallAuditTableQuery())
        && $wpdb->query(bobsi\StaticHolder::getBidorbuyStoreIntegrator()
            ->getQueries()->getInstallTradefeedTableQuery())
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
    $wpq = array('post_type' => 'product', 'fields' => 'ids');

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
    $included_categories =
        '<select id="bobsi-inc-categories" class="bobsi-categories-select" name="bobsi_inc_categories[]" 
            multiple="multiple" size="9">';
    $excluded_categories =
        '<select id="bobsi-exc-categories" class="bobsi-categories-select" name="excludeCategories[]"
            multiple="multiple" size="9">';
    $categories = bobsi_get_categories();

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
    $export_link = BOBSI_PLUGIN_URL . 'export.php?t=' . bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()
            ->getTokenExport();
    $download_link =
        BOBSI_PLUGIN_URL . 'download.php?t=' . bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()
            ->getTokenDownload();
    $resetaudit_link =
        BOBSI_PLUGIN_URL . 'resetaudit.php?t=' . bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()
            ->getTokenDownload();
    $phpInfo_link =
        BOBSI_PLUGIN_URL . 'version.php?t=' . bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()
            ->getTokenDownload() . '&phpinfo=y';
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

        $settings_checklist = array(bobsi\Settings::nameUsername => 'strval', bobsi\Settings::namePassword => 'strval',
            bobsi\Settings::nameFilename => 'strval', bobsi\Settings::nameCompressLibrary => 'strval',
            bobsi\Settings::nameDefaultStockQuantity => 'intval',
            bobsi\Settings::nameEmailNotificationAddresses => 'strval',
            bobsi\Settings::nameEnableEmailNotifications => 'bool', bobsi\Settings::nameLoggingLevel => 'strval',
            bobsi\Settings::nameExportQuantityMoreThan => 'intval',
            bobsi\Settings::nameExcludeCategories => 'categories', bobsi\Settings::nameExportStatuses => 'categories');

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

            bobsi\StaticHolder::getBidorbuyStoreIntegrator()
                ->getSettings()
                ->unserialize(serialize($presaved_settings));

            $newSettings = bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->serialize(TRUE);

            update_option(bobsi\Settings::name, $newSettings);

            if (bobsi\StaticHolder::getBidorbuyStoreIntegrator()
                ->checkIfExportCriteriaSettingsChanged($previousSettings, $newSettings, TRUE)
            ) {
                $wpdb->query(bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getQueries()->getTruncateJobsQuery());

                $wpdb->query(bobsi\StaticHolder::getBidorbuyStoreIntegrator()
                    ->getQueries()
                    ->getTruncateProductQuery());

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
 * @param array $data data from $_POST
 * @param string $setting setting
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
    } else {
        plugin_update();
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

    $version = bobsi\Version::getVersionFromString(bobsi\Version::$version);
    update_option('bobsi_db_version', $version);
}

/**
 * Uninstall Update Setting
 *
 * @return mixed
 */
function uninstall_update_settings() {
    return delete_option('bobsi_db_version');
}
