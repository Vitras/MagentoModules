# MagentoModules
Collection of modules for Magento, made by AzureKai (Vitras)


## Installation
Pull the entire AzureKai folder into your Magento2/app/code folder. Next, run `magento setup:upgrade` and `magento setup:di:compile`. The
commands should be available using `magento --list`. If this causes problems, try flushing the cache first.

## Export usage
The export commands should be available under `export:`.
The `magento export:orders` command will export the order ID, first name, middle + last name, street address (including number), 
postal code, city name, country code, grand total and tax amount of any orders with a 'processing status' in the last 24 hours based on Magento's time as a .csv file, with columns added. 

An optional parameter can be given resembling the earliest date that an exported order can have. The input argument can be written in any form that is acceptable by `strtotime()`
For example:
* `magento export:orders 12-12-2012` will export any orders made at 12 December 2012 or newer.
* `magento export:orders 19-02-201412:49:22` will export any orders made at 19 February 2013, 12:49 (and 22 seconds) or later.

Currently, the resulting .csv is saved under [magento]/OrderExports, where [magento] is where the magento file resides (default: bin). If
OrderExports does not exist, it will create the directory during export.

