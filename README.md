# magento2-taxrateupdater

## Installation

For now you have to add a private repo to your composer.json.

    composer config repositories.servicehome-taxupdater git https://github.com/servicehome/magento2-taxrateupdater.git

Then require the package.

    composer require servicehome/magento2-taxrateupdater

Now you can use the typical magento installation routines:

    ./bin/magento setup:upgrade
    
    ./bin/magento setup:static-content:deploy
    
    ./bin/magento cache:flush

Test if the module ist installed:

    ./bin/magento module:status

## General
The extension creates a cronjob in the default group. This cronjob is called every minute per default.

If there is no task with "was_processed" = 0 and "time_to_update" < NOW, no further operation is done.
If there are update tasks, they will be processed and logged in the debug-level "debug". 

## Usage

Currently there is no user interface. Sorry, will change this soon.

In your database there is a new table "servicehome_taxrate_tasks". 

There you can setup the update tasks. e.g.

    servicehome_taxrate_tasks_id	tax_rate_id	time_to_update	        rate_in_percent	was_processed
        1                             3	        2020-07-01 00:00:00	         16	      0
        
The first column is an autoindex, dont care.

**tax_rate_id** is a reference to the column "tax_calculation_rate_id" in "tax_calculation_rate". You can find this id
in the magento backend. Under "Tax Zones and Rates" you can hover over the tax-identifier entries and see the id in the url.
e.g. in https://demo.test/admin/tax/rate/edit/rate/3/key/31b28b1da272241d25b1acee809f476e090d7c3dad9a7e913b79671934d650da/
you see the rate/3. 

**time_to_update** is a datetime column. You can set the time when the new tax rate should be applied. 
You should understand this as the earliest point in time, since the time of change depends on the settings of
 the cron job. 

**rate_in_percent** is the new tax rate in percent. e.g. 16% => "16"

**was_processed** this is automatically updated when the cronjob is run.

## Configuration Options

/etc/config.xml defaults:

    <module_active>1</module_active>
    <reindex>0</reindex>
    <flush_cache>0</flush_cache>

To disable the module set **module_active** to 0.

To reindex the catalog_product_price index after an tax rate update occurs, set **reindex** to 1.

To flush the cache after an tax rate update occurs, set **flush_cache** to 1.

After changing this value you have to flush the config cache.

    ./bin/magento cache:flush config