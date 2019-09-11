<?php

namespace Ingenico\Connect\WxTransfer;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\RemoteServiceUnavailableException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Ingenico\Connect\Model\ConfigInterface;

/**
 * Class Client
 * @package Ingenico\Connect\WxTransfer
 */
class Client implements ClientInterface
{
    /**
     * @var ConfigInterface
     */
    private $epaymentsConfig;

    /**
     * @var Sftp\ClientInterface
     */
    private $sftpClient;

    /**
     * @var Store
     */
    private $store;

    /**
     * @var string
     */
    private $pattern;

    /**
     * Client constructor.
     *
     * @param ConfigInterface $epaymentsConfig
     * @param Sftp\ClientInterface $sftpClient
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ConfigInterface $epaymentsConfig,
        Sftp\ClientInterface $sftpClient,
        StoreManagerInterface $storeManager
    ) {
        $this->epaymentsConfig = $epaymentsConfig;
        $this->sftpClient = $sftpClient;
        $this->store = $storeManager->getStore();
    }

    /**
     * @param string $date
     * @param int $scopeId
     * @return \DOMDocument|false
     * @throws InputException
     * @throws LocalizedException
     */
    public function loadDailyWx($date, $scopeId)
    {
        if (!$this->epaymentsConfig->getSftpActive($scopeId)) {
            return false;
        }
        $this->init($scopeId);

        $timeString = date('Ymd', strtotime($date));
        if ($timeString === false) {
            throw new InputException(__("The string '%date' could not be parsed into a date.", ['date' => $date]));
        }

        $baseCurrency = $this->store->getBaseCurrencyCode();

        $this->pattern = sprintf(
            self::WX_FILE_PATTERN,
            $this->epaymentsConfig->getMerchantId($scopeId),
            $timeString,
            $baseCurrency
        );

        $fileList = $this->sftpClient->getFileCollection(
            $this->pattern,
            $this->epaymentsConfig->getSftpRemotePath($scopeId)
        );

        if (empty($fileList)) {
            return false;
        }

        $fileToLoad = $this->determineLatestVersion($fileList);

        $response = $this->sftpClient->loadFile($fileToLoad);
        $this->sftpClient->disconnect();

        return $this->parseResponse($response);
    }

    /**
     * Initialize transfer client
     *
     * @param int|null $scopeId
     * @throws LocalizedException
     */
    private function init($scopeId = null)
    {
        $host = $this->epaymentsConfig->getSftpHost($scopeId);
        $user = $this->epaymentsConfig->getSftpUsername($scopeId);
        $password = $this->epaymentsConfig->getSftpPassword($scopeId);
        try {
            $this->sftpClient->connect($host, $user, $password);
        } catch (\Exception $exception) {
            throw new RemoteServiceUnavailableException(__($exception->getMessage()));
        }
    }

    /**
     * Checks the list of matching files for the latest version and returns the corresponding filename
     *
     * @param $fileList
     * @return string filename to load
     */
    private function determineLatestVersion($fileList)
    {
        // append additional meta data (version) to the file metadata
        $pattern = $this->pattern;
        array_walk(
            $fileList,
            function (&$value, $key) use ($pattern) {
                $matches = [];
                preg_match($pattern, $key, $matches);
                $value = array_merge($value, $matches);
            }
        );

        $latestVersion = array_reduce(
            $fileList,
            function ($carry, $item) {
                if ($carry['version'] < $item['version']) {
                    return $item;
                }
                return $carry;
            },
            ['version' => '0']
        );

        return $latestVersion['filename'];
    }

    /**
     * @param $response
     * @return \DOMDocument
     * @throws InputException
     */
    private function parseResponse($response)
    {
        $dom = new \DOMDocument('1.0', 'utf-8');
        if (!$dom->loadXML($response, LIBXML_PARSEHUGE)) {
            throw new InputException(__('Could not load response XML.'));
        }
        return $dom;
    }
}
