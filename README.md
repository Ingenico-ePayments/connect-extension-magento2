Netresearch Epayments Extension
=====================
Payment extension for processing the Magento order workflow via the Ingenico ePayments

Compatibility
-------------
- Magento >= 2.1.6

Installation Instructions
-------------------------

##### Purchase via Marketplace

Find and purchase the product via Magento Marketplace

Run `composer require nrepayments/module-epayments-m2` to install the extension.

##### Configure module 
1. In magento root dir run command **php bin/magento setup:upgrade** 
2. Open Magento Admin > Stores > Configuration > Sales > Ingenico ePayments 
3. Set values 
    * General > Enabled = Yes 
    * General > Title = Ingenico ePayments
    * Account Settings > API Endpoint and API Endpoint (Secondary) according to https://epayments-api.developer-ingenico.com/s2sapi/v1/en_US/php/endpoints.html
    * Account Settings > API Key = **Configuration Center provides the value**
    * Account Settings > API Secret = **Configuration Center provides the value**
    * Account Settings > MID (Merchant id) = **Configuration Center provides the value**
    * Account Settings > Hosted Checkout Subdomain = can be configured on Configuration Center. By default it is: **'https://payment.'**
4. Save Config 
5. In magento root dir run command **php bin/magento cache:clean**

##### Test module  

1. Open Magento frontend 
2. Add product to cart  
3. Proceed to checkout page 
4. On "Payment Method" page click radio button "Ingenico ePayments"
5. Available payment methods under the title 'Ingenico ePayments 'will be shown (PayPal, Visa, etc.) 
 
Support
-------
In case of questions or problems, have a look at the Support Portal (FAQ):

http://ingenico.support.netresearch.de/

If the problem cannot be resolved, you can contact the Ingenico support team: 

https://www.ingenico.com/epayments/support


Developer
---------
Netresearch GmbH &amp; Co. KG - 
[http://www.netresearch.de](http://www.netresearch.de)

Licence
-------
[MIT](https://opensource.org/licenses/MIT)

Copyright
---------
(c) 2018 Ingenico eCommerce Solutions Bvba

