<?php

namespace Netresearch\Epayments\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\Element\Template;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * Payment Method code which Magento uses as an identifier for particular payment method
     */
    const CODE = 'ingenico';

    /**
     * Pattern which is used by payment configuration to fetch data like: title, is_available, sort_order etc
     */
    const PATH_PATTERN = '%s_epayments/general/%s';

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var Resolver
     */
    private $resolver;

    /**
     * @var Repository
     */
    private $assetRepo;

    /**
     * ConfigProvider constructor.
     *
     * @param UrlInterface $urlBuilder
     * @param ConfigInterface $config
     * @param Resolver $resolver
     * @param Repository $assetRepo
     */
    public function __construct(
        UrlInterface $urlBuilder,
        ConfigInterface $config,
        Resolver $resolver,
        Repository $assetRepo
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->config = $config;
        $this->resolver = $resolver;
        $this->assetRepo = $assetRepo;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return [
            'payment' => [
                'ingenico' => [
                    'hostedCheckoutPageUrl' => $this->urlBuilder->getUrl('epayments/hostedCheckoutPage'),
                    'inlineSuccessUrl' => $this->urlBuilder->getUrl('epayments/inlinePayment'),
                    'locale' => $this->resolver->getLocale(),
                    'paymentMethodGroupTitles' => $this->config->getProductGroupTitles(),
                    'useInlinePayments' => $this->config->isInlinePaymentsEnabled(),
                    'loaderImage' => $this->assetRepo->getUrlWithParams('images/loader-2.gif', []),
                ],
            ],
        ];
    }
}
