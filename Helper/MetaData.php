<?php

declare(strict_types=1);

namespace Ingenico\Connect\Helper;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;
use function sprintf;

class MetaData
{
    /** @var ModuleListInterface */
    private $moduleListService;
    
    /** @var ProductMetadataInterface */
    private $magentoMetaDetaHelper;
    
    public function __construct(ModuleListInterface $moduleListService, ProductMetadataInterface $magentoMetaDetaHelper)
    {
        $this->moduleListService = $moduleListService;
        $this->magentoMetaDetaHelper = $magentoMetaDetaHelper;
    }
    
    public function getModuleName(): string
    {
        return 'Ingenico_Connect';
    }
    
    public function getModuleVersion(): string
    {
        if ($moduleData = $this->moduleListService->getOne($this->getModuleName())) {
            return (string) $moduleData['setup_version'] ?? __('Unknown');
        }
        
        return __('Unknown');
    }
    
    public function getExtensionCreator(): string
    {
        return 'Ingenico';
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
