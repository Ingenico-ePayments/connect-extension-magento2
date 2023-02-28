# Worldline Connect upgrade guide

Since v2.0.0, the namespace of the module has changed from `Netresearch\Epayments`
to `Worldline\Connect`. This upgrade guide explains what this means for you as a
merchant, and as a developer / implementing 3rd party.

## For merchants

As a merchant, you don't have to worry. All the configuration, orders (pending
and closed ones) and other settings will remain the same. There is no need
for further configuration. The following data will be kept intact:

- Configuration settings of the module
- Inline translations
- ACL Role configuration

## For developers / implementing 3rd party

This module is a drop-in replacement for the previous module. However, some
attention is required for this upgrade.

### Installation instructions

The first thing that needs to be done, is to disable & uninstall the old module
in Magento 2. This can be done using the command line:

    php bin/magento module:disable Netresearch_Epayments
    php bin/magento module:uninstall Netresearch_Epayments
    
> Please note, that due to a known bug in older versions of Magento 2, it
could be that your `module:uninstall` command is not working properly. If
that's the case, you have to manually remove the module key from the
`setup_module`-table: `DELETE FROM setup_module WHERE module = "Netresearch_Epayments"`

Next, you have to remove the code of the old module and install the new one.
First you have to remove the previous version of the module:

    composer remove nrepayments/module-epayments-m2

Next, see the [readme instructions](../README.md) on how to install the new version of the module.

For the rest, follow the default [Composer installation instructions provided by Magento from 'Verify the extension' onward](https://devdocs.magento.com/extensions/install/#verify-the-extension).

### Backward compatibility

Since this update includes a namespace change, it is breaking with backward 
compatibility. However, this will only affect you if you did some customization 
with the module. Please check if you did any of the following actions in your 
integration and follow the instructions if so:

- Set [a different preference](https://devdocs.magento.com/guides/v2.3/extension-dev-guide/build/di-xml-file.html#abstraction-implementation-mappings) for an existing class or interface in the `Netresearch\Epayments` namespace.
- Wrote [a plugin](https://devdocs.magento.com/guides/v2.3/extension-dev-guide/plugins.html) on an existing class or interface in the `Netresearch\Epayments` namespace.
- Wrote [a JavaScript mixin](https://devdocs.magento.com/guides/v2.3/javascript-dev-guide/javascript/js_mixins.html) on an existing JavaScript file in the `Netresearch_Epayments` namespace. 
- Used [dependencies](https://devdocs.magento.com/guides/v2.3/extension-dev-guide/depend-inj.html) of an existing class or interface in the `Netresearch\Epayments` namespace in your own custom code. 
- [Customized](https://devdocs.magento.com/guides/v2.3/frontend-dev-guide/templates/template-walkthrough.html) existing ([e-mail](https://devdocs.magento.com/guides/v2.3/frontend-dev-guide/templates/template-email.html)) templates of an existing template in the `Netresearch\Epayments` namespace.

If your code applies to any of the scenarios above, please use the following 
instructions to update your code:

#### Different preference for class or interface

Replace the `Netresearch\Epayments` namespace in the `<preference>` in `di.xml` with the `Worldline\Connect` namespace:
    
    // Old situation:
    <preference for="Netresearch\Epayments\Model\ConfigInterface" type="Custom\Module\Model\MyCustomConfig"/>
    // New situation:
    <preference for="Worldline\Connect\Model\ConfigInterface" type="Custom\Module\Model\MyCustomConfig"/>

Update your custom code to extend the class from the new namespace, or implement the interface from the new namespace:

    // Old situation:
    class MyCustomConfig extends \Netresearch\Epayments\Model\Config { ... }
    class MyCustomConfig implements \Netresearch\Epayments\Model\ConfigInterface { ... }
    // New situation:
    class MyCustomConfig extends \Worldline\Connect\Model\Config { ... }
    class MyCustomConfig implements \Worldline\Connect\Model\ConfigInterface { ... }

#### Plugin on existing class or interface

Replace the `Netresearch\Epayments` namespace in the `<type>` in `di.xml` with the `Worldline\Connect` namespace:

    // Old situation:
    <type name="Netresearch\Epayments\Model\Worldline\Status\CaptureRequested"> ...
    // New situation:
    <type name="Worldline\Connect\Model\Worldline\Status\CaptureRequested">

Update your plugin code so that the subject is from the new namespace:

    // Old situation:
    public function aroundResolveStatus(
        \Netresearch\Epayments\Model\Worldline\Status\CaptureRequested $subject,
        ...
    // New situation:
    public function aroundResolveStatus(
        \Worldline\Connect\Model\Worldline\Status\CaptureRequested $subject,
        ...

#### JavaScript mixin on an existing JavaScript file

Replace the `Netresearch_Epayments` namespace in the declaring `requirejs-config.js`-file with the `Worldline_Connect` namespace:

    // Old situation:
    var config = {
        config: {
            mixins: {
                'Netresearch_Epayments/js/model/client': {
                    'Custom_Module/js/model/client-mixin': true
                }
            }
        }
    };
    // New situation:
    var config = {
        config: {
            mixins: {
                'Worldline_Connect/js/model/client': {
                    'Custom_Module/js/model/client-mixin': true
                }
            }
        }
    };

Replace the `Netresearch_Epayments` namespace in the dependencies that are used in other JavaScript files:

    // Old situation:
    define([
        'Netresearch_Epayments/js/action/get-session'
    ], function (getSessionAction) {
        ...
    // New situation:
    define([
        'Worldline_Connect/js/action/get-session'
    ], function (getSessionAction) {
        ...

#### Used dependencies of an existing class or interface

Replace the dependencies of the `Netresearch\Epayments` namespace in your source code with the `Worldline\Connect` namespace:

    // Old situation:    
    public function __construct(\Netresearch\Epayments\Model\ConfigInterface $config) { ... }
    // New situation:
    public function __construct(\Worldline\Connect\Model\ConfigInterface $config) { ... }

This also applies to use-statements:

    // Old situation:
    use Netresearch\Epayments\Model\ConfigInterface;
    // New situation:
    use Worldline\Connect\Model\ConfigInterface;

#### Customized existing (email) templates

Change the name of the folder `app/design/(area)/Netresearch_Epayments` to `app/design/(area)/Worldline_Connect`.
Check your `.phtml`-files if there are references to the old namespace. For example:

Helpers:

    // Old situation:
    $this->helper('Netresearch\Epayments\Helper\Data')
    // New situation:
    $this->helper('Worldline\Connect\Helper\Data')

References in docblocks:

    // Old situation:
    /** @var \Netresearch\Epayments\Block\Info $block */
    // New situation:
    /** @var \Worldline\Connect\Block\Info $block */
