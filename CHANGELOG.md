# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).


## [Unreleased] - 2018-06/2018-07
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

### Security

## [1.3.1] - 2018-06-05
### Changed
- adjusted order item transmission to be compatible with more tax calculation settings

## [1.3.0] - 2018-05-30
### Added
- added compatibility with [OneStepCheckout](http://onestepcheckout.com) extension
### Changed
- changed handling of returning customers from HostedCheckout page to no longer rely on the checkout session
- replaced usages of deprecated Cart interface
### Removed
- removed transmission and reliance on Magento order entity id for identifying an order, using the increment id in all places instead
###Fixed
- fixed an issue which occured if the module code was already present during Magento installation

## [1.2.0] - 2018-04-20
### Added
- Inline payment workflow to allow direct payment creation without redirects for supporting payment products
- WX file polling automatically retrieves the daily transaction report file in xml format and parses the updates into Magentos orders

## [1.1.0] - 2018-03-21
### Changed
- updated order item transmission to allow better display on HostedCheckout page for shipping and discount amounts
- integrate Ingenicos Javascript SDK to fetch the available payment products through the client
- improved error handling during checkout to enable more graceful checkout expirience
- make API endpoints required in the configuration

### Fixed
- fixed wrong redirection for customers returning from HostedCheckout

## [1.0.1] - 2018-02-22
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

[Unreleased]: https://git.netresearch.de/ingenico/connect/module-epayments-m2/compare/1.3.1...develop
[1.3.1]: https://git.netresearch.de/ingenico/connect/module-epayments-m2/compare/1.3.0...1.3.1
[1.3.0]: https://git.netresearch.de/ingenico/connect/module-epayments-m2/compare/1.2.0...1.3.0
[1.2.0]: https://git.netresearch.de/ingenico/connect/module-epayments-m2/compare/1.1.0...1.2.0
[1.1.0]: https://git.netresearch.de/ingenico/connect/module-epayments-m2/compare/1.0.1...1.1.0
[1.0.1]: https://git.netresearch.de/ingenico/connect/module-epayments-m2/compare/1.0.0...1.0.1
