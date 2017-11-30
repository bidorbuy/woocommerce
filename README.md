# WordPress bidorbuy Store Integrator

bidorbuy Store Integrator warning: to improve plugin security the export/download link structure will be changed from Store Integrator 2.0.15 version and higher. Please ensure you have provided updated links to bidorbuy.

### Compatibility

   Product         | PHP version   |     WordPress 4.3      |      WordPress 4.4     |    WordPress 4.5.3     |    WordPress 4.7.2      |    WordPress 4.8.3   |  
|         -------         |       ---     |          ---           |         ---            |       ---              |        ---              |      ---             |     
| Store Integrator-2.1.1  |5.4,  5.6      | ✓ WooCommerce 2.4.6 + | ✓ WooCommerce 2.4.8 +  | ✓ WooCommerce 2.5.5 +  | ✓ WooCommerce 3.1.1   | ✓ WooCommerce 3.2.1 |  
| Store Integrator-2.1.0  |5.4,  5.6      | ✓ WooCommerce 2.4.6 + | ✓ WooCommerce 2.4.8 +  | ✓ WooCommerce 2.5.5 +  | ✓ WooCommerce 3.1.1   | ✓ WooCommerce 3.2.1 |  
| Store Integrator-2.0.15 |5.4,  5.6      |  ×   | ×     | ×     | ✓ WooCommerce  3.0 +  | ×                    |
| Store Integrator-2.0.14 |5.4,  5.6      | ✓ WooCommerce 2.4.6   | ✓ WooCommerce 2.4.8    | ✓ WooCommerce 2.5.5    | ✓ WooCommerce  3.0 +  | ×                    |
| Store Integrator-2.0.13 |5.4,  5.6      | ✓ WooCommerce 2.4.6   | ✓ WooCommerce 2.4.8    | ✓ WooCommerce 2.5.5    | ✓ WooCommerce  3.0 +  | ×                    |  
| Store Integrator-2.0.12 |5.4,  5.6      | ✓ WooCommerce 2.4.6   | ✓ WooCommerce 2.4.8    | ✓ WooCommerce 2.5.5    | ✓ WooCommerce  3.0 +  | ×                    |
| Store Integrator-2.0.11 |5.4            | ✓ WooCommerce 2.4.6   | ✓ WooCommerce 2.4.8    | ✓ WooCommerce 2.5.5    |         ×              | ×                    |
| Store Integrator-2.0.10 |5.3            | ✓ WooCommerce 2.4.6   | ✓ WooCommerce 2.4.8    | ✓ WooCommerce 2.5.5    |         ×              | ×                    |

### Description

The bidorbuy Store Integrator allows you to get products from your online store listed on bidorbuy quickly and easily.
Expose your products to the bidorbuy audience - one of the largest audiences of online shoppers in South Africa Store updates will be fed through to bidorbuy automatically, within 24 hours so you can be sure that your store is in sync within your bidorbuy listings. All products will appear as Buy Now listings. There is no listing fee just a small commission on successful sales. View [fees](https://support.bidorbuy.co.za/index.php?/Knowledgebase/Article/View/22/0/fee-rate-card---what-we-charge). Select as many product categories to list on bidorbuy as you like. No technical requirements necessary.

To make use of this plugin, you'll need to be an advanced seller on bidorbuy.
 * [Register on bidorbuy](https://www.bidorbuy.co.za/jsp/registration/UserRegistration.jsp?action=Modify)
 * [Apply to become an advanced seller](https://www.bidorbuy.co.za/jsp/seller/registration/UserSellersRequest.jsp)
 * Once you integrate with bidorbuy, you will be contacted by a bidorbuy representative to guide you through the process.

### System requirements

Supported PHP versions: 5.4 (5.6, 7.0 for WooCommerce 3.1.1 only)

PHP extensions: curl, mbstring

WooCommerce: 2.4.6 and higher

### Installation

1. Log in to control panel as administrator.
2. Go to Plugins > Add New > press Upload Plugin button.
3. Upload `bidorbuy Store Integrator` archive (do not unpack the archive).
4. Activate the plugin through the 'Plugins' menu in WordPress.

### Uninstallation

1. Log in to control panel as administrator.
2. Go to Plugins > Installed plugins > bidorbuy Store Integrator.
3. Deactivate the bidorbuy Store Integrator.
4. Delete the plugin.

### Upgrade

Remove all old files of previous installation:

1. Root folder > wp-content > plugins > bidorbuystoreintegrator and re-install the archive. Please look through the installation chapter.

### Configuration

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