# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## 2.7.1 - 2022-08-30

- Remove conlict entries from composer.json as they are implicitly defined by replace

## 2.7.0 - 2022-01-25

- Adds environment specific configuration fields for merchant ID, API key ID, API secret, webhooks key ID and webhooks secret key.
- Moved around configuration fields in the admin.
- Use schemeTransactionId when using a token that was merchant initiated.

## 2.6.1 - 2021-12-06

- Fixes saving of tokens when Payment Flow is set to Hosted Checkout.

## 2.6.0 - 2021-12-03

- Introduce Optimized Payment Flow: only redirect customer to hosted checkout when the payment requires additional information.
- Integrate with the Magento token vault: allow users to manage the tokens they created in their account. Allow admins to use these tokens for orders created in the admin.
- Fixes rendering of checkout when only cards are enabled. 

## 2.5.0 - 2021-08-26

- Fixes issue where non-valid state codes where sent to the Ingenico API.
- Optimizes admin configuration
- Fixes issue where a delayed settlement with PayPal could result in a "payment review" state.
- Fixes issue where an order that was not paid with Ingenico could not be offline refunded.
- Added a single webhook endpoint that can replace the other 2 webhook endpoints. Previously, you had to configure 2 separate webhook endpoints in the Ingenico Configuration Center, but now you only need to configure one. The webhook URL can be found in the administration section of the module (`Admin > Stores > Configuration > Sales > Ingenico ePayments > Settings`).
- Added composer suggestion to install the connect-extension-magento2-refund-queue module.

## 2.4.6 - 2021-01-27

- Fixes issue where updating the status of a refunded order in the admin would throw an error.

## 2.4.5 - 2020-12-24

- Added `fraudFields.customerIpAddress` property to outgoing payment request.

## 2.4.4 - 2020-11-02

- Fixes floating precision rounding error in JavaScript.

## 2.4.3 - 2020-10-12

- Fixes issue where PHP7.4 code was used by mistake, breaking compatibility with PHP7.3 environments.

## 2.4.2 - 2020-10-05

- Add a comment to the order for each multiple attempt on the RPP.
- Fix incorrect comment when performing an online refund.
- Fixes issue where rejected challenge would leave the order in a pending state.
- Fixes issue where a refund initiated by the WPC could break the processing of webhook events.
- Add logging of API requests and responses to the frontend (this is turned off by default and can be turned on in the admin).
- Add a new service contract to handle refund logic.
- Add a new service contract to handle payment logic.
- Add a new service contract to get meta data from order payments.
- Add a new service contract for frontend logging
- The following classes have been removed:
    - `Ingenico\Connect\Gateway\Command\IngenicoVoidCommand`
    - `Ingenico\Connect\Model\Ingenico\Action\UndoCapturePaymentRequest`
    - `Ingenico\Connect\Observer\UndoCapturePaymentObserver`

## 2.4.1 - 2020-09-17

- Added support for Magento 2.4.0
- Updated tooltip with credit card icons to grouped cards.
- Fixes issue where some webhooks were not properly processed.

## 2.4.0 - 2020-08-31

- Added a merchant reference validator.
- Added hosted checkout guest variant id.
- Added option to admin to disable offline refunds to prevent the accidentally creation of offline refunds.
- Fixes an issue with webhooks where a second payment attempt in the hosted checkout pages would accidentally cancel the order in Magento.
- Dispatch Magento events for status handlers. See [the readme](README.md) for more details on this.
- Status updates for refunds are now added the the comment history on the related credit memo. Earlier all comments where added to the order, unclear if it was about a payment or a refund.
- The following classes and interfaces have been removed:
    - `Ingenico\Connect\Model\Ingenico\RefundRequestBuilder`
    - `Ingenico\Connect\Model\Ingenico\Status\Resolver`
    - `Ingenico\Connect\Model\Ingenico\Status\ResolverInterface`
    - `Ingenico\Connect\Model\Ingenico\Status\Refund\RefundHandlerInterface`
    - `Ingenico\Connect\Model\Ingenico\StatusFactory`
- Add tooltip with credit card icons to grouped cards.

## 2.3.2 - 2020-08-02
- Fixed an issue with the token checkbox not working correctly

## 2.3.1 - 2020-08-10

- Marked the module as being a _'gateway'_ so a notice is shown in the admin if the merchant accidentally intents to create an offline refund.
- Fixed issue where "Save for later" was not possible for registered customers when "Group card payment methods" was set to "Yes".
- Added payment and refund webhooks endpoints to admin panel and updated README with webhooks configuration instructions.

## 2.3.0 - 2020-07-15

### Added

- Added a link to ePayments documentation in the admin panel.
- Added extra logging to the webhook controllers
- Added the option to group credit card payments in the checkout
- Added test API connection button to the admin panel.

### Changed

- Changed the scope of System Index Identifier config from global to website.
- Checkout session token gets regenerated when entering the checkout process, this prevents customers to end up with 
an expired session token and not seeing any payment methods.
- Dropped support for WX files.

### Removed

- Removed external Netresearch dependencies

## 2.2.1 - 2020-04-30

### Added

- A notice will now be shown in the admin area if a refund flow is used that requires approval from the merchant.

### Changed

- Changed version checker behavior from flag storage to cache storage.

### Fixed

- Fixes issue where the title of the payment method was not shown in the order grid if the order was paid with Ingenico.
- Fixes issue where a cancellation on the RPP would not cancel the order in Magento.

## 2.2.0.1 - 2020-04-09

### Fixed

- Obfuscated expiry dates in the webhooks caused some webhooks to fail authentication.

## 2.2.0 - 2020-04-02

### Added

- Added GitHub issue template
- Added GitHub link to admin configuration
- Added API endpoint to expose client token for headless checkout
- Added version checker
- Added a fallback for the fraud notifications, in case the fraud email is not set an admin notification will be created
- Added a technical partner link to system configuration

### Changed

- Changed the server meta data that is sent to Ingenico to include Magento and module version.
- Disallowed payment products 201, 302, 320, 705, 730 and 770 when using "Payment products and input fields on Hosted Checkout" or "Payment products in Magento checkout, input fields on Hosted Checkout" because they are not supported by the RPP. 
- Remove secondary API Endpoint
- Remove secondary Webhook Key
- Remove secondary Webhook Secret
- Made a separate section in the system configuration for advanced settings and moved a a lot of settings around.
- In the system configuration, the "Api Endpoint" is no longer a text field, but a dropdown where you can select the API Endpoint (since these are pre-defined).
- Changed layout for configuration settings
- Changes in the refund flow:
    - When a refund is requested for a processing order the order will be set "on hold" until the refund is approved or cancelled.
    - When a refund is cancelled the order will be set to it's original state before the refund was requested.
    - When a refund is approved the order will follow the default Magento flow for credit memos.
- Changed `Ingenico\Connect\Model\Ingenico\Webhooks`-namespace to `Ingenico\Connect\Model\Ingenico\Webhook`.
- Renamed `Ingenico\Connect\Model\Ingenico\Webhooks` to `Ingenico\Connect\Model\Ingenico\Webhook\Handler`.
- Made the `ingenico_epayments/fraud/manager_email` not mandatory

### Fixed

- Fixes issue where a refundable payment status from Ingenico would not allow online refunds in Magento.
- Locales that are unsupported by the Ingenico API will no longer throw an error. Instead they will be mapped. If a locale cannot be determined, the module will fall back to the default locale configured by the merchant in Magento.
- CC Expiry date are now obfuscated in webhook logs.
- Fixed issue where webhooks would no longer be processed if there were too many failed attempts in the database.

## 2.1.2 - 2020-02-17

### Changed

- Review the scope of the system configuration settings.
- Added required property to outgoing payments requests for merchants based in Brazil.
- Update PHP SDK to v6.5.0

### Fixed

- Fixes issue where company information VAT number was not populated in outgoing request.
- Fixes issue where tokens were not saved for registered customers when using the hosted checkout.
- Fixes issue that when a payment response contained more than one token it would not be stored correctly in Magento.
- Fixes issue where milliseconds from webhooks were not saved in the database.

## 2.1.1 - 2020-01-13

### Fixed

- Fixes issue in `composer.json` that would prevent the module from being installed.

## 2.1.0 - 2020-01-13

### Changed

- In previous versions, the "pending payment" status was ambiguous: it could either mean that the customer is still in the payment process or that an action of the merchant is required (like a capture for example). From this version onward:
    - If an action is required by the customer the default order status will be "pending".
    - If an action is required by the merchant the default order status will be "pending payment".
- Added a default configuration settings for "amount of days to cancel stale orders" (set to 3).
- Stale orders will now be cancelled if their status is "pending" instead of "pending payment".

### Fixed

- In case of a `REDIRECT` the order will now be set to "pending" instead of "processing". This goes for all redirect cases: challenges, hosted checkouts, payment methods that are redirect-based, etc.
- After a successful challenge the order will be set to "processing" for direct capture and "pending payment" for a delayed settlement.
- Previously, order amount paid and order amount due did not reflect the paid-status of the invoice / payment. This is now fixed.
- Previously, the invoice status did not reflect the payment status. This is now fixed.
    - A status of `PAID`, `CAPTURED` and `CAPTURE_REQUESTED` now mark an invoice as paid.
- Payment transactions get closed properly.
- Fixes a date formatting issue that would make the "cancel stale orders" feature too greedy.

## 2.0.0 - 2019-09-11

### Changed

- **BC Breaking:** the namespace of the module is changed from `Netresearch\Epayments` to `Ingenico\Connect`. See [the upgrade guide](doc/UPGRADE.md) for more details what this means for you.
- Updated JavaScript Client SDK from `3.9.0` to `3.13.2`

### Added

- Support for 3DSv2 by adding 18 properties to the payment request

### Fixed 

- In the payment request the shipping address took the street details from the billing address
- The hosted checkout did not take saved tokens into account

## 1.6.1 - 2019-06-06

### Fixed

- composer dependencies
- online capture not possible on inline payments that required a redirect action (3ds) after creation

## 1.6.0 - 2019-05-24

### Fixed

- Order "total paid" not 0 in CAPTURE_REQUESTED
- Discrepancies in invoice state
- Log file available from outside if secret keys in url are turned off

### Added

- support for status code 935

### Removed

- Magento 2.1 support

## 1.5.2 - 2019-03-21

### Fixed

- fraud status handling in direct capture doesn't behave properly
- webhook events sometimes don't update payment transactions correctly
- webhook controllers not accessible for POST requests in Magento 2.3

### Added

- transactions updates are now more robust when there previously was an error when returning from a Hosted Checkout
- new module dependency netresearch/module-compatibility-m2 to manage controller CSRF backwards compatibility

## 1.5.1 - 2019-02-27

### Changed

- updated documentation to be more specific in certain terms

### Fixed

- webhook event processing out of chronological order

## 1.5.0 - 2018-12-14

### Added

- config option to specify hosted checkout variant
- ability for a full redirect with payment product selection on hosted checkout
- ability for a custom prefix as system identifier for merchantReference used to identify orders
- specific logging for incoming webhook events
- minimal implementation for SEPA direct debit support
- support for asynchronous processing of webhook events
- support for Magento 2.3

### Changed

- webhook endpoints now always return a 200 code, if the event payload could be unwrapped
- ensure every order has a transaction with the reference to Ingenico's payId if a response was received
- Invoice for CAPTURE_REQUESTED status on CC is now in Pending state rather then Paid
- streamline Ingenico status processing notifications in the order history

### Fixed

- order status inconsistencies with credit card
- order payment accept action not available on fraud status
- order not progressing from payment review order state to processing

## 1.4.5 - 2018-11-02

### Fixed

- Orders in suspected fraud state could not be accepted/denied

## 1.4.4 - 2018-10-05

### Fixed

- Automatic Order Update does not retrieve payment ID correctly
- Payment methods not reloading when removing coupon in checkout (EE)
- Fatal error during order placement in Mage 2 EE
- Webhooks not recognizing test requests and using wrong order reference
- Order in status "pending payment" when it should be "processing"
- Invoice number not present in invoice mail in direct capture mode
- Payment update mails not respecting the order scope
- Multiple payment update mails sent for the same payment status

## 1.4.3 - 2018-09-12

### Fixed

- Inline card payments that result in a redirect will now be handled correctly

### Changed

- Extension copyright now lies with Ingenico eCommerce Solutions Bvba

## 1.4.2 - 2018-08-27

### Added

- automatically send invoice email after it is marked as paid

### Changed

- extension is now licensed under MIT (see LICENSE.txt)

### Fixed

- fixed issue with refilling carts where product was not loaded properly
- corrected formatting for customer date of birth

## 1.4.1 - 2018-08-06

### Fixed

- restored compatibility with Magento 2.1

## 1.4.0 - 2018-08-02

### Added

- automatic configuration validation against API when saving changed account settings

### Changed

- automatically update payment status from API after invoice cancellation
- status handling to be more flexible by implementing simple status handlers
- Credit Card orders on Global Collect can be shipped with status 800,900,975 
- Request CANCELLED_BY_CUSTOMER state from API for HostedCheckouts

### Deprecated

### Removed

- WX file import admin and cron configuration

### Fixed

- invoice cancellation not working properly
- customer gender not transmitted as string
- incompatibility with CheckoutAgreements core extension
- issue with new order email being sent upon a received canceled status

### Security

## 1.3.1 - 2018-06-05

### Changed

- adjusted order item transmission to be compatible with more tax calculation settings

## 1.3.0 - 2018-05-30

### Added

- added compatibility with OneStepCheckout](http://onestepcheckout.com) extension

### Changed

- changed handling of returning customers from HostedCheckout page to no longer rely on the checkout session
- replaced usages of deprecated Cart interface

### Removed

- removed transmission and reliance on Magento order entity id for identifying an order, using the increment id in all places instead

###Fixed

- fixed an issue which occured if the module code was already present during Magento installation

## 1.2.0 - 2018-04-20

### Added

- Inline payment workflow to allow direct payment creation without redirects for supporting payment products
- WX file polling automatically retrieves the daily transaction report file in xml format and parses the updates into Magentos orders

## 1.1.0 - 2018-03-21

### Changed

- updated order item transmission to allow better display on HostedCheckout page for shipping and discount amounts
- integrate Ingenicos Javascript SDK to fetch the available payment products through the client
- improved error handling during checkout to enable more graceful checkout expirience
- make API endpoints required in the configuration

### Fixed

- fixed wrong redirection for customers returning from HostedCheckout

## 1.0.1 - 2018-02-22

### Added

- handling for AUTHORIZATION_REQUESTED API status
- PHP 7.1 compatibility by removal of preserved keywords

### Changed

- fallback to fetch hostedCheckout status, if no paymentId is present at the order yet
- handle possible API errors in transaction information to be readable

### Fixed

- bug in automatic order cancellation to not fetch all necessary orders

## 1.0.0 - 2018-01-16

### Added

- Initial release 
