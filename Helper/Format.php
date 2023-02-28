<?php

declare(strict_types=1);

namespace Worldline\Connect\Helper;

use Worldline\Connect\Model\ConfigInterface;

class Format
{
    /**
     * @var ConfigInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $config;

    /**
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    public function limit(?string $value, int $limit): ?string
    {
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        return $this->config->getLimitAPIFieldLength() && $value !== null ? mb_substr($value, 0, $limit) : $value;
    }
}
