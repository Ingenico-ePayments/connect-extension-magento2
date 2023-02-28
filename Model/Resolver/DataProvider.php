<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Resolver;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\QuoteGraphQl\Model\Cart\Payment\AdditionalDataProviderInterface;

class DataProvider implements AdditionalDataProviderInterface
{
    public const PATH_ADDITIONAL_DATA = 'worldline';

    /**
     * @param array $args
     * @return array
     * @throws GraphQlInputException
     */
    public function getData(array $args): array
    {
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        if (!array_key_exists(self::PATH_ADDITIONAL_DATA, $args)) {
            throw new GraphQlInputException(
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                __('Required parameter "worldline" for "payment_method" is missing.')
            );
        }
        return $args[self::PATH_ADDITIONAL_DATA];
    }
}
