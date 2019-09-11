<?php


namespace Ingenico\Connect\Model\Order;

use Magento\Framework\App\Area;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;

class EmailProcessor
{
    /** @var TransportBuilder */
    private $transportBuilder;

    /** @var StateInterface */
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
