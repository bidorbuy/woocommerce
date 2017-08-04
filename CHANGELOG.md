# WordPress bidorbuy Store Integrator

### Changelog

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