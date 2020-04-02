<?php

declare(strict_types=1);

namespace Ingenico\Connect\Locale;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\Resolver as MagentoLocaleResolver;
use Magento\Framework\Locale\ResolverInterface;

class Resolver extends MagentoLocaleResolver implements ResolverInterface
{
    /**
     * @return string|null
     * @throws LocalizedException
     */
    public function getLocale()
    {
        try {
            return $this->getBaseLocale(parent::getLocale());
        } catch (LocalizedException $exception) {
            // We also need to convert the configured locale since this can also be in xx_xxx_xx-format.
            $locale = $this->scopeConfig->getValue('general/locale/code');
        }

        return $this->getBaseLocale($locale);
    }

    /**
     * @param string $locale
     * @return string
     * @throws LocalizedException
     */
    private function getBaseLocale(string $locale): string
    {
        $locale = str_replace('-', '_', $locale);

        $parts = explode('_', $locale);
        if (count($parts) === 2 && strlen($locale) <= 6) {
            return $locale;
        }

        if (count($parts) > 2) {
            $region = $parts[count($parts) - 1];
            if (strlen($region) > 2) {
                $region = $parts[count($parts) - 2];
            }

            return $parts[0] . '_' . $region;
        }

        throw new LocalizedException(__('Invalid locale'));
    }
}
