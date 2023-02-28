<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\System\Message;

use Magento\Backend\Model\Auth\Session;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\Authorization\PolicyInterface;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\Phrase;
use Worldline\Connect\Helper\MetaData;
use Worldline\Connect\Model\ConfigInterface;

class LoggingEnabled implements MessageInterface
{
    public const MESSAGE_IDENTITY = 'connect_logging_enabled_message';

    public const RESOURCE_STRING = 'Worldline_Connect::epayments_config';

    /** @var MetaData */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $metaDataHelper;

    /** @var ConfigInterface */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $configHelper;

    /** @var UrlInterface */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $urlHelper;

    /** @var PolicyInterface */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $policyService;

    /** @var Session */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
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
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
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
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        return __(
            $this->getTextTemplate(),
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            str_replace('_', ' ', $this->metaDataHelper->getModuleName()),
            $this->urlHelper->getUrl('admin/system_config/edit/section/worldline_connect')
        );
    }

    public function getSeverity(): int
    {
        return self::SEVERITY_NOTICE;
    }

    private function getTextTemplate(): string
    {
        // phpcs:ignore Generic.Files.LineLength.TooLong, SlevomatCodingStandard.Files.LineLength.LineTooLong
        return 'Logging is currently active for %1, this can be disabled in the <a href="%2">configuration settings</a>.';
    }
}
