<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Order;

use Magento\Framework\App\Area;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;

class EmailProcessor
{
    /** @var TransportBuilder */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $transportBuilder;

    /** @var StateInterface */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $inlineTranslation;

    /**
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     */
    public function __construct(
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
    }

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * Initiate send email process
     *
     * @param int $storeId
     * @param string $templateId
     * @param string $emailTo
     * @param string $emailFrom
     * @param array $emailTemplateVariables
     * @throws \Magento\Framework\Exception\MailException
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    public function processEmail($storeId, $templateId, $emailTo, $emailFrom, array $emailTemplateVariables)
    {
        $this->inlineTranslation->suspend();

        $this->transportBuilder->setTemplateIdentifier($templateId)
            ->setTemplateOptions(
                [
                    // define area and store of template
                    'area'  => Area::AREA_FRONTEND,
                    'store' => $storeId,
                ]
            )
            ->setTemplateVars($emailTemplateVariables)
            ->setFrom($emailFrom)
            ->addTo($emailTo);

        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();

        $this->inlineTranslation->resume();
    }
}
