<?php

declare(strict_types=1);

namespace Ingenico\Connect\Helper;

use Ingenico\Connect\Model\ConfigInterface;

class Format
{
    /**
     * @var ConfigInterface
     */
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
        return $this->config->getLimitAPIFieldLength() && $value !== null ? mb_substr($value, 0, $limit) : $value;
    }
}
