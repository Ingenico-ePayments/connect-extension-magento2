<?php

declare(strict_types=1);

namespace Worldline\Connect\Helper;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;

use function sprintf;

// phpcs:ignore PSR12.Files.FileHeader.SpacingAfterBlock

class MetaData
{
    /** @var ModuleListInterface */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $moduleListService;

    /** @var ProductMetadataInterface */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $magentoMetaDetaHelper;

    public function __construct(ModuleListInterface $moduleListService, ProductMetadataInterface $magentoMetaDetaHelper)
    {
        $this->moduleListService = $moduleListService;
        $this->magentoMetaDetaHelper = $magentoMetaDetaHelper;
    }

    public function getModuleName(): string
    {
        return 'Worldline_Connect';
    }

    public function getModuleVersion(): string
    {
        if ($moduleData = $this->moduleListService->getOne($this->getModuleName())) {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            return (string) $moduleData['setup_version'] ?? __('Unknown');
        }

        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        return __('Unknown');
    }

    public function getExtensionCreator(): string
    {
        return 'Worldline';
    }

    public function getExtensionName(): string
    {
        return 'M2.Connect';
    }

    public function getExtensionEdition(): string
    {
        return sprintf(
            'M%s %s',
            $this->magentoMetaDetaHelper->getVersion(),
            $this->magentoMetaDetaHelper->getEdition()
        );
    }

    public function getTechnicalPartnerName(): string
    {
        return 'ISAAC';
    }

    public function getTechnicalPartnerUrl(): string
    {
        return 'https://www.isaac.nl';
    }
}
