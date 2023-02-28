<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline\Client;

use Psr\Log\LoggerInterface;

// phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
class CommunicatorLogger implements \Ingenico\Connect\Sdk\CommunicatorLogger
{
    // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
    public const DATE_FORMAT_STRING = DATE_ATOM;

    /** @var LoggerInterface */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $logger;

    /**
     * CommunicatorLogger constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function log($message)
    {
        $this->logger->info($message);
    }

    /**
     * {@inheritdoc}
     */
    // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    public function logException($message, \Exception $exception)
    {
        $this->logger->error($message, ['exception' => $exception]);
    }
}
