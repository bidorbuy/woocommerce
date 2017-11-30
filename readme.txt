=== bidorbuy Store Integrator ===

Contributors: extremeidea
Tags:  products, export, catalog, xml, variables, items, store
Requires at least: 4.0
Requires PHP: 5.4
Tested up to: 4.9
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=LM25KRQVLRLDS
Contact Us: https://www.extreme-idea.com/

== Description ==

The bidorbuy Store Integrator allows you to get products from your online store listed on bidorbuy quickly and easily.
Expose your products to the bidorbuy audience - one of the largest audiences of online shoppers in South Africa Store updates will be fed through to bidorbuy automatically, within 24 hours so you can be sure that your store is in sync within your bidorbuy listings. All products will appear as Buy Now listings. There is no listing fee just a small commission on successful sales. View [fees](https://support.bidorbuy.co.za/index.php?/Knowledgebase/Article/View/22/0/fee-rate-card---what-we-charge). Select as many product categories to list on bidorbuy as you like. No technical requirements necessary.

To make use of this plugin, you'll need to be an advanced seller on bidorbuy.
 * [Register on bidorbuy](https://www.bidorbuy.co.za/jsp/registration/UserRegistration.jsp?action=Modify)
 * [Apply to become an advanced seller](https://www.bidorbuy.co.za/jsp/seller/registration/UserSellersRequest.jsp)
 * Once you integrate with bidorbuy, you will be contacted by a bidorbuy representative to guide you through the process.

Our entire team is ready to help you. Ask your questions in the support forum, or <a href="https://www.extreme-idea.com/">contact us directly</a>.

== Installation ==

System requirements

Supported PHP versions: 5.4 (5.6, 7.0 for WooCommerce 3.1.1 only)

PHP extensions: curl, mbstring

Installation

1. Log in to control panel as administrator.
2. Go to Plugins > Add New > press Upload Plugin button.
3. Upload `bidorbuy Store Integrator` archive (do not unpack the archive).
4. Activate the plugin through the 'Plugins' menu in WordPress.

== Uninstallation ==

To uninstall the plugin:

1. Log in to control panel as administrator.
2. Go to Plugins > Installed plugins > bidorbuy Store Integrator.
3. Deactivate the bidorbuy Store Integrator.
4. Delete the plugin.

== Upgrade Notice ==

To update the plugin:

1. Log in as administrator to WordPress Admin Panel.
2. Navigate to Plugins > Installed Plugins > bidorbuy Store Integrator.
3. Press Deactivate button. Press Delete button. The plugin should be successfully uninstalled.
4. Navigate to Plugins > Add New > Upload Plugin.
5. Browse for 'bidorbuy Store Integrator'.zip file > press Install Now button.

The plugin should be successfully re-installed.

== Configuration ==

1. Log in to control panel as administrator.
2. Navigate to Settings > bidorbuy Store Integrator.
3. Set the export criteria.
4. Press the `Save` button.
5. Press the `Export` button.
6. Press the `Download` button.
7. Share Export Links with "bidorbuy".
8. To display BAA fields on the setting page add '&baa=1' to URL in address bar.

To include attribute name to product title set an appropriate checkbox to Yes:

- For Global attributes - is configuring on attributes page (WooCoommerce > Products > Attributes page > Add new/Edit Attribute > Add this attribute to product name in bidorbuy Store Integrator tradefeed > Yes).
- For Custom attributes - is configuring on a product page (Woo Commerce > Product > Attributes > Add this attribute to product name in bidorbuy Store Integrator tradefeed > Yes).

Note please: this feature supports WooCommerce 3.0.0 and higher!

== Screenshots ==

1. screenshot-1.jpg

2. screenshot-2.jpg

3. screenshot-3.jpg

== Changelog ==

#### 2.1.1
* Added force CONVERT TO CHARACTER SET utf8_unicode_ci by default for next database tables: bobsi_tradefeed_product, bobsi_tradefeed_product_base, bobsi_tradefeed_audit.

_[Updated on November 30, 2017]_

#### 2.1.0
* Restored compatibility with WooCommerce < 3.0.0.
* Fixed dependence on pretty permalinks.

_[Updated on November 17, 2017]_

#### 2.0.15
* Adopted plugin according to wordpress.org rules.
* WooCommerce Store Integrator 2.0.15 supports only WooCommerce 3.0.0 and higher.

_[Updated on November 14, 2017]_

#### 2.0.14
* Reporting each Reset Audit effort as an extra notification in a log file (only for Debug logging level).

_[Updated on November 07, 2017]_

#### 2.0.13
* Added possibility to include the attributes in product titles for the WooCommerce integrator.
* Fixed an issue when export throws fatal error in case if WooCommerce is deactivated.
* Fixed an issue when WooCommerce throws warning in case of deleting the attributes.
* Fixed an issue when `Draft` and `Pending` products are missing in feed.
* Corrected headers processing in Store Integrator core.

_[Updated on September 30, 2017]_

#### 2.0.12
* Improved the logging strategy for Debug level.
* Added extra save button which was removed from Debug section (the settings page).

_[Updated on August 21, 2017]_

### 2.0.11

* EOL (End-of-life due to the end of life of this version) for PHP 5.3 support.
* Added support for WooCommerce 3.1.1.

_[Updated on August 02, 2017]_

### 2.0.10

* Fixed error in query (1292): Incorrect datetime value: '0000-00-00 00:00:00' for column 'row_modified_on' at row 1.
* Fixed error in query (1055): Expression #1 of SELECT list is not in GROUP BY clause and contains nonaggregated column.
* Fixed issue when "$this->dbLink->execute" hides the real error messages.
* Fixed issue when bobsi tables are created always with random charset instead of utf8_unicode_ci.
* Fixed issue when export process is interrupted by zlib extension.

_[Updated on June 06, 2017]_

### 2.0.9

* Added a flag to display BAA fields (to display BAA fields on the setting page add '&baa=1' to URL in address bar).
* Added an appropriate warning on the Store Integrator setting page about EOL(End-of-life) of export non HTTP URL to the tradefeed file.

_[Updated on March 07, 2017]_

### 2.0.8

* Improved the upgrade process.

_[Updated on December 29, 2016]_

### 2.0.7

* Added support of multiple images.
* Added support of images from product description.
* Added the possibility to open PHP info from store Integrator settings page.

_[Updated on December 20, 2016]_

### 2.0.6

* Added additional improvements for Store Integrator Settings page.
* Fixed an issue when Store Integrator cuts the long name of categories in Export Criteria section.

_[Updated on November 18, 2016]_

### 2.0.5

* Added new feature: if product has weight attribute, the product name should contain this attribute value.
* Fixed an issue when tradefeed is invalid to being parsed with Invalid byte 1 of 1-byte UTF-8 sequence.

_[Updated on November 02, 2016]_

### 2.0.4

* Fixed an issue of empty XML after changing the settings.
* Fixed an issue when it is impossible to download log after its removal.
* Fixed an issue when extra character & added to the export URL.
* Corrected the export link length: it was too long.
* Added an error message if "mysqli" extension is not loaded.

_[Updated on October 18, 2016]_

### 2.0.3

* Added warning in case if 'readfile' function is disabled.
* The PHP version has changed to 5.3.0.
* Fixed Settings Page styles.

_[Updated on August 19, 2016]_

### 2.0.2

* Added support for WordPress 4.5.x (WooCommerce 2.6.x and WooCommerce 2.5.x).

_[Updated on July 07, 2016]_

### 2.0.1

* Added Reset export data link to a plugin settings page.
* Added a possibility to check the plugin version.
* Fixed a bug when on certain occasions disabled products were still exported.

_[Updated on April 27, 2016]_

### 2.0.0

* Added optimization technology for huge data sets, which significantly improves integrator performance.
* Enhancements and bugs fixes.

_[Updated on September 12, 2015]_

### 1.0

* First release.
 
_[Released on April 07, 2014]_