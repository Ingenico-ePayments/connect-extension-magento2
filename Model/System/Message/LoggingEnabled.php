<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\System\Message;

use Ingenico\Connect\Helper\MetaData;
use Ingenico\Connect\Model\ConfigInterface;
use Magento\Backend\Model\Auth\Session;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\Authorization\PolicyInterface;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\Phrase;

class LoggingEnabled implements MessageInterface
{
    const MESSAGE_IDENTITY = 'connect_logging_enabled_message';
    
    const RESOURCE_STRING = 'Ingenico_Connect::epayments_config';
    
    /** @var MetaData */
    private $metaDataHelper;
    
    /** @var ConfigInterface */
    private $configHelper;
    
    /** @var UrlInterface */
    private $urlHelper;
    
    /** @var PolicyInterface */
    private $policyService;
    
    /** @var Session */
    private $backendSession;
    
    public function __construct(
        ConfigInterface $configHelper,
        MetaData $metaDataHelper,
        UrlInterface $urlHelper,
        PolicyInterface $policyService,
        Session $backendSession
    ) {
        $this->configHelper = $configHelper;
        $this->urlHelper = $urlHelper;
        $this->policyService = $policyService;
        $this->backendSession = $backendSession;
        $this->metaDataHelper = $metaDataHelper;
    }
    
    public function getIdentity(): string
    {
        return md5(self::MESSAGE_IDENTITY);
    }
    
    public function isDisplayed(): bool
    {
        if (!$this->configHelper->getLogAllRequests()) {
            return false;
        }
        
        $user = $this->backendSession->getUser();
        $userRole = $user->getRole();
        
        if (!$this->policyService->isAllowed($userRole->getId(), self::RESOURCE_STRING)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * @return Phrase|string
     */
    public function getText()
    {
        return __(
            $this->getTextTemplate(),
            str_replace('_', ' ', $this->metaDataHelper->getModuleName()),
            $this->urlHelper->getUrl('admin/system_config/edit/section/ingenico_epayments')
        );
    }
    
    public function getSeverity(): int
    {
        return self::SEVERITY_NOTICE;
    }
    
    private function getTextTemplate(): string
    {
        // phpcs:ignore Generic.Files.LineLength.TooLong
        return 'Logging is currently active for %1, this can be disabled in the <a href="%2">configuration settings</a>.';
    }
}
