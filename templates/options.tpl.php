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

$bobsi_settings = new bobsi\Settings();
$wordings = $bobsi_settings->getDefaultWordings();

$warnings = array_merge(bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getWarnings(),
    bobsi\StaticHolder::getWarnings()->getBusinessWarnings());
// @codingStandardsIgnoreStart

foreach ($warnings as $warning): ?>

    <div class="error">
        <p><?php echo $warning ?></p>
    </div>

<?php endforeach; ?>

<div id="bobsi-admin-header">
    <div id="bobsi-icon-trade-feed"
         style="background-image: url('<?php echo $bob_logo_url ?>');">
    </div>
    <h2><?php echo bobsi\Version::$name ?></h2>
    <div id="bobsi-adv">
        <!-- BEGIN ADVERTPRO CODE BLOCK -->
        <script type="text/javascript">
            document.write('<scr' + 'ipt src="http://nope.bidorbuy.co.za/servlet/view/banner/javascript/zone?zid=153&pid=0&random='
                + Math.floor(89999999 * Math.random() + 10000000)
                + '&millis=' + new Date().getTime()
                + '&referrer=' + encodeURIComponent(document.location) + '" type="text/javascript"></scr' + 'ipt>');
        </script>
        <!-- END ADVERTPRO CODE BLOCK -->
    </div>
</div>

<div id="poststuff">
    <form id="bobsi-settings-form" name="bobsi-settings-form" method="POST"
          action="">
        <input type="hidden" name="submit_options" value="1"/>
        <div class="postbox postbox-left">
            <h3><span>Export Configuration</span></h3>
            <table class="form-table">

                <?php if ($currencies) : ?>
                    <tr>
                        <th scope="row">Currency</th>
                        <td>
                            <?php echo $bobsi_currency; ?>
                            <p class="description">Supported by WooCommerce Currency
                                Converter</p>
                        </td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <th scope="row">Export filename</th>
                    <td>
                        <input class="bobsi-input" type="text" size="50"
                               name="<?php echo bobsi\Settings::nameFilename; ?>"
                               value="<?php echo $bobsi_filename; ?>"/>

                        <p class="description">16 characters max. Must start with a
                            letter.<br>Can contain letters,
                            digits, "-" and "_"</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Compress Tradefeed XML</th>
                    <td>
                        <select class="bobsi-input"
                                name="<?php echo bobsi\Settings::nameCompressLibrary; ?>"><?php echo $compress_libs; ?></select>
                        <img class="help_tip"
                             data-tip="Choose a Compress Library to compress destination Tradefeed XML"
                             src="<?php echo $tooltip_img_url; ?>" height="16"
                             width="16"/>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Min quantity in stock</th>
                    <td>
                        <input class="bobsi-input" type="text"
                               name="<?php echo bobsi\Settings::nameDefaultStockQuantity; ?>"
                               value="<?php echo $bobsi_default_quantity; ?>"/>
                        <img class="help_tip"
                             data-tip="If you do not manage stock quantities for your products, you can set the default
                                stock quantity to be used for the XML feed. This quantity will apply to all your products"
                             src="<?php echo $tooltip_img_url; ?>" height="16"
                             width="16"/>

                        <p class="description">Set minimum quantity if quantity
                            management is turned OFF</p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="postbox postbox-right">
            <h3><span>Export Criteria</span></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">Export products with available quantity more than</th>
                    <td class="data-item">
                        <input class="bobsi-input" type="text"
                               name="<?php echo bobsi\Settings::nameExportQuantityMoreThan; ?>"
                               value="<?php echo $bobsi_min_quantity; ?>"/>
                        <img class="help_tip"
                             data-tip="Products with stock quantities lower than this value will be excluded from the XML feed"
                             src="<?php echo $tooltip_img_url; ?>" height="16"
                             width="16"/>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <fieldset>
                            <table width="100%">
                                <tr>
                                    <td id="cats-left">
                                        <span
                                            class="title-item">Included Categories</span>
                                        <br>
                                        <?php echo $included_categories; ?>
                                    </td>
                                    <td id="cats-middle">
                                        <?php
                                        echo get_submit_button('< Include', 'secondary', 'include')
                                            . get_submit_button('> Exclude', 'secondary', 'exclude');
                                        ?>
                                    </td>
                                    <td id="cats-right" class="last-item">
                                        <span
                                            class="title-item">Excluded Categories</span>
                                        <img class="help_tip"
                                             data-tip="Move categories to the \'Excluded Categories\' column if would like to exclude any of your categories."
                                             src="<?php echo $tooltip_img_url; ?>"
                                             height="16" width="16"/>
                                        <?php echo $excluded_categories; ?>
                                    </td>
                            </table>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <fieldset>
                            <table width="100%">
                                <tr>
                                    <td id="cats-left">
                                        <span
                                            class="title-item">Included Statuses</span>
                                        <br>
                                        <?php echo $included_statuses; ?>
                                    </td>
                                    <td id="cats-middle">
                                        <?php
                                        echo get_submit_button('< Include', 'secondary', 'include-stat')
                                            . get_submit_button('> Exclude', 'secondary', 'exclude-stat');
                                        ?>
                                    </td>
                                    <td id="cats-right" class="last-item">
                                        <span
                                            class="title-item">Excluded Statuses</span>
                                        <img class="help_tip"
                                             data-tip="Move statuses to the \'Excluded Statuses\' column if would like to exclude any of your statuses."
                                             src="<?php echo $tooltip_img_url; ?>"
                                             height="16" width="16"/>
                                        <?php echo $excluded_statuses; ?>
                                    </td>
                            </table>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row" colspan="2">

                    </th>
                </tr>
            </table>
        </div>
        <input type="hidden" name="<?php echo bobsi\Settings::nameTokenDownload; ?>"
               value="<?php echo bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()
                   ->getTokenDownload(); ?>">
        <input type="hidden" name="<?php echo bobsi\Settings::nameTokenExport; ?>"
               value="<?php echo bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getTokenExport(); ?>">
        <select style="display: none;"
                name="<?php echo bobsi\Settings::nameExportVisibilities . '[]'; ?>">
            <?php foreach (bobsi\StaticHolder::getBidorbuyStoreIntegrator()->getSettings()->getExportVisibilities() as
                           $visibility) : ?>
                <option value="<?php echo $visibility; ?>"/>
            <?php endforeach; ?>
        </select>

        <p class="button-item">
            <?php echo $submit_button; ?>
        </p>


        <div class="postbox debug postbox-inner">
            <h3><span>Debug</span></h3>
            <table class="form-table">

                <!-- Feature 3910 -->
                <?php
                $baa = isset($_REQUEST['baa']) ? (int)$_REQUEST['baa'] : FALSE;
                if ($baa == 1) :

                    ?>
                    <tr>
                        <td colspan="2">
                            <b>Basic Access Authentication</b>
                            <br>(if necessary)<br>
                <span style="color: red">
                    Do not enter username or password of ecommerce platform, please read carefully about this kind of authentication!
                </span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Username</th>
                        <td>
                            <input class="bobsi-input" type="text" size="50"
                                   name="<?php echo bobsi\Settings::nameUsername; ?>"
                                   value="<?php echo $bobsi_username; ?>"/>

                            <p class="description">
                                <?php echo $wordings
                                [bobsi\Settings::nameUsername]
                                [bobsi\Settings::nameWordingsDescription]; ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Password</th>
                        <td>
                            <input class="bobsi-input" type="password" size="50"
                                   name="<?php echo bobsi\Settings::namePassword; ?>"
                                   value="<?php echo $bobsi_password; ?>"/>

                            <p class="description">
                                <?php echo $wordings
                                [bobsi\Settings::namePassword]
                                [bobsi\Settings::nameWordingsDescription]; ?>
                            </p>
                        </td>
                    </tr>

                <?php else: ?>

                    <input type="hidden" name="<?php echo bobsi\Settings::nameUsername; ?>"
                           value="<?php echo $bobsi_username; ?>"/>
                    <input type="hidden" name="<?php echo bobsi\Settings::namePassword; ?>"
                           value="<?php echo $bobsi_password; ?>"/>

                <?php endif; ?>

                <tr>
                    <td>
                        <span style="font-size: 14px;"><b>Logs</b></span>
                    </td>
                </tr>
                <tr>
                    <td>Send logs to email address</td>
                    <td>
                        <input class="bobsi-input" type="text"
                               name="<?php echo bobsi\Settings::nameEmailNotificationAddresses; ?>"
                               value="<?php echo $bobsi_email_notification_addresses; ?>"/>
                        <img class="help_tip"
                             data-tip="Specify email address(es) separated by comma to send the log entries to"
                             src="<?php echo $tooltip_img_url; ?>" height="16"
                             width="16"/>
                        <br>
                        <input type="checkbox" id="bobsi_enable_email_notification"
                               name="<?php echo bobsi\Settings::nameEnableEmailNotifications; ?>"
                               value="1" <?php echo $bobsi_enable_email_notification; ?> />
                        <label for="bobsi_enable_email_notification">Turn on/off email notifications</label>
                    </td>
                </tr>

                <tr>
                    <td>Logging Level</td>
                    <td>
                        <select class="bobsi-input"
                                name="<?php echo bobsi\Settings::nameLoggingLevel; ?>"><?php echo $logging_levels; ?></select>
                        <img class="help_tip"
                             data-tip="A level describes the severity of a logging message.
                                There are six levels, show here in descending order of severity"
                             src="<?php echo $tooltip_img_url; ?>" height="16"
                             width="16"/>
                    </td>
                </tr>
            </table>
        </div>
        <input type="button" onclick="jQuery('#submit').click()"
               class="button-primary bobsi-save-settings" value="<?php _e('Save Changes')?>">
    </form>

    <div class="postbox logfiles postbox-inner">
        <h3><span>Logs</span></h3>
        <?php echo $logfiles_table; ?>
    </div>

    <div id="linksblock">
        <div id="ctrl-c-message">Press Ctrl+C</div>

        <form name="bobsi-export-form" method="POST" action="">
            <div class="postbox links postbox-inner">
                <input class="bobsi-input" type="hidden"
                       id="<?php echo bobsi\Settings::nameActionReset; ?>"
                       name="<?php echo bobsi\Settings::nameActionReset; ?>"
                       value="1"/>

                <h3><span>Links</span></h3>
                <table class="form-table export-links">
                    <tr>
                        <td><label for="tokenExportUrl">Export</label></td>
                        <td>
                            <input type="text" id="tokenExportUrl" class="bobsi-url"
                                   title="Click to select"
                                   value="<?php echo $export_link; ?>" readonly/>
                        </td>
                        <td>
                            <button type="button" class="button button-primary"
                                    onclick="window.open('<?php echo $export_link; ?>&r=' + new Date().getTime(),'_blank');"><?php echo __('Launch'); ?></button>
                            <button type="button"
                                    class="button copy-button"><?php echo __('Copy'); ?></button>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="tokenDownloadUrl">Download</label></td>
                        <td>
                            <input type="text" id="tokenDownloadUrl" class="bobsi-url"
                                   title="Click to select"
                                   value="<?php echo $download_link; ?>" readonly/>
                        </td>
                        <td class="button-section">
                            <button type="button" class="button button-primary"
                                    onclick="window.open('<?php echo $download_link; ?>&r=' + new Date().getTime(),'_blank');"><?php echo __('Launch'); ?></button>
                            <button type="button"
                                    class="button copy-button"><?php echo __('Copy'); ?></button>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="resetaudit">Reset export data</label></td>
                        <td>
                            <input type="text" id="resetaudit" class="bobsi-url"
                                   title="Click to select"
                                   value="<?php echo $resetaudit_link; ?>" readonly/>
                            <p class="description">Clicking on this link will reset all
                                exported data in your tradefeed. This is done by clearing
                                all exported product data, before re-adding all products
                                to the export and completing the query. Please note, you
                                will still need to run the export link once this process
                                completes in order to update the download file.</p>
                        </td>
                        <td class="bobsi-top">
                            <button type="button" class="button button-primary"
                                    onclick="window.open('<?php echo $resetaudit_link; ?>&r=' + new Date().getTime(),'_blank');"><?php echo __('Launch'); ?></button>
                            <button type="button"
                                    class="button copy-button"><?php echo __('Copy'); ?></button>
                        </td>
                    </tr>
                </table>
            </div>
            <p class="button-item">
                <button
                    class="button button-primary"><?php echo __('Reset Tokens'); ?></button>
            </p>
        </form>
    </div>


    <div class="postbox version postbox-inner">
        <h3>Version</h3>
        <h3>
        <span>
            <a href="<?php echo $phpInfo_link ?>" target="_blank">@See PHP
                information</a><br>
            <?php echo bobsi\Version::getLivePluginVersion(); ?>
        </span>
        </h3>
    </div>
