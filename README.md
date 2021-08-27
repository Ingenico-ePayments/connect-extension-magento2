# Ingenico ePayments Connect Extension for Magento 2

Payment extension for processing the Magento order workflow via the Ingenico ePayments Connect API

## Requirements

To use this extension you need to have:

- an Ingenico ePayments account.
- Webhook events for payment & refund configured for the account. The webhook endpoints used by the Magento store are shown in the Magento admin. (`Admin > Stores > Configuration > Sales > Ingenico ePayments > Settings`)

## Compatibility

This module is compatible with the following versions of Magento:

- **Magento 2.3**
- **Magento 2.4**

If you want to install the module in an older version of Magento, you can
choose the correct version from the following list:

- **Magento 2.2** : Version 2.4.0 of this module
- **Magento 2.1** : Not supported
- **Magento 2.0** : Not supported

## Installation Instructions

### Install module 

Installation via Composer requires the [Magento Composer Installer](https://github.com/Cotya/magento-composer-installer) to be in place.

To install the module, run the following command:

    composer require ingenico-epayments/connect-extension-magento2

### Configure module 

1. In the Magento root directory execute `php bin/magento module:enable Ingenico_Connect`
2. In the Magento root directory execute `php bin/magento setup:upgrade` 
3. Open Magento Admin > Stores > Configuration > Sales > Ingenico ePayments 
4. Set values:
    * General section:
        * Enabled = Yes 
        * Title = _(Enter your preferred name to display in the checkout)_
    * Account Settings section:
        * API Endpoint and API Endpoint (Secondary) according to <https://epayments-api.developer-ingenico.com/s2sapi/v1/en_US/php/endpoints.html>
        * API Key = **Configuration Center provides the value**
        * API Secret = **Configuration Center provides the value**
        * MID (Merchant ID) = **Configuration Center provides the value**
        * Hosted Checkout Subdomain = can be configured on Configuration Center. By default it is: **'https://payment.'**
    * Webhook Settings section:
        * Webhooks Key ID = **Configuration Center provides the value**
        * Webhooks Secret Key = **Configuration Center provides the value**
5. Save Config 
6. In the Magento root directory execute `php bin/magento cache:clean`

### Test module  

1. Open the Magento frontend 
2. Add a product to the cart  
3. Proceed to the checkout page 
4. On the "Payment Method" section select "Ingenico ePayments"
5. The available payment methods (PayPal, Visa, etc.) should be shown under the title  
 
## Information for integrators

### Upgrade instructions

If you are upgrading from a version prior to 2.0.0, please read the [upgrade instructions](doc/UPGRADE.md).

### Hooking into the module

Each time a status change from Ingenico is processed, an event is 
dispatched where you can hook in to. The name of these events are:

    ingenico_connect_[payment/refund/hosted_checkout]_[ingenico_status]
    
Some examples:

    ingenico_connect_payment_capture_requested
    ingenico_connect_refund_refund_requested
    ingenico_connect_hosted_checkout_cancelled_by_consumer

A list of all possible statuses from Ingenico can be found [in the documentation](https://epayments-api.developer-ingenico.com/s2sapi/v1/en_US/java/statuses.html?paymentPlatform=ALL).

## Support

In case of questions or problems, you can contact the Ingenico support team: <https://www.ingenico.com/epayments/support>

## License

Please refer to the included [LICENSE.txt](LICENSE.txt) file.

## Copyright

(c) 2019 Ingenico eCommerce Solutions Bvba

