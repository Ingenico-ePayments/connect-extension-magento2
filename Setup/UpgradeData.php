<?php

declare(strict_types=1);

namespace Ingenico\Connect\Setup;

use Ingenico\Connect\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

use function version_compare;

class UpgradeData implements UpgradeDataInterface
{
    private const CONFIG_INGENICO_CHECKOUT_TYPE_INLINE = '1';
    private const CONFIG_INGENICO_CHECKOUT_TYPE_REDIRECT = '2';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '2.4.8') < 0) {
            $this->migrateRedirectToOptimizeFlow($setup);
        }
    }

    /**
     * The option to specify an inline checkout was moved to the payment product,
     * and the checkout types inline and redirect have been combined into optimized flow.
     *
     * To migrate the old to the new situation, we need to do the following:
     * * if the checkout type was inline, the card payment method checkout type is migrated to inline.
     * * if the checkout type was redirect, it is migrated to optimized flow.
     *
     * Checkout types:
     *
     * Old situation:
     * 0: Hosted checkout
     * 1: Inline
     * 2: Redirect
     *
     * New situation:
     * 0: Hosted checkout
     * 1: Optimized flow
     */
    private function migrateRedirectToOptimizeFlow(ModuleDataSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $checkoutType = $this->scopeConfig->getValue(Config::CONFIG_INGENICO_CHECKOUT_TYPE);
        if ($checkoutType === self::CONFIG_INGENICO_CHECKOUT_TYPE_INLINE) {
            $connection->insert($setup->getTable('core_config_data'), [
                'path' => Config::CONFIG_INGENICO_CREDIT_CARDS_PAYMENT_FLOW_TYPE,
                'value' => Config::CONFIG_INGENICO_CREDIT_CARDS_CHECKOUT_TYPE_INLINE
            ]);
        }

        $connection->update(
            $setup->getTable('core_config_data'),
            ['value' => Config::CONFIG_INGENICO_CHECKOUT_TYPE_OPTIMIZED_FLOW],
            [
                $connection->quoteInto('path = ?', Config::CONFIG_INGENICO_CHECKOUT_TYPE),
                $connection->quoteInto('value = ?', self::CONFIG_INGENICO_CHECKOUT_TYPE_REDIRECT)
            ]
        );
    }
}
