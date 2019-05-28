<?php

namespace Netresearch\Epayments\Controller\Adminhtml\DownloadLogFile;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Filesystem\Driver\File;
use Netresearch\Epayments\Model\ConfigInterface;

class Index extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Netresearch_Epayments::download_logfile';

    /** @var ConfigInterface */
    private $config;

    /** @var FileFactory */
    private $fileFactory;

    /** @var File */
    private $fileDriver;

    /** @var \Magento\Framework\Filesystem\Io\File */
    private $fileSystemIo;

    /**
     * @param Context $context
     * @param ConfigInterface $config
     * @param FileFactory $fileFactory
     * @param File $fileDriver
     * @param \Magento\Framework\Filesystem\Io\File $fileSystemIo
     */
    public function __construct(
        Context $context,
        ConfigInterface $config,
        FileFactory $fileFactory,
        File $fileDriver,
        \Magento\Framework\Filesystem\Io\File $fileSystemIo
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->fileFactory = $fileFactory;
        $this->fileDriver = $fileDriver;
        $this->fileSystemIo = $fileSystemIo;
    }

    /**
     * Initiates logging file download process
     *
     * @return ResponseInterface
     * @throws \LogicException
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function execute()
    {
        $fileToDownload = $this->config->getLogAllRequestsFile();
        if (!$this->fileDriver->isExists($fileToDownload)) {
            throw new \LogicException(__('Logging file is missing. Nothing to download.'));
        }
        $fileInfo = $this->fileSystemIo->getPathInfo($fileToDownload);

        return $this->fileFactory->create(
            $fileInfo['basename'],
            [
                'value' => $fileInfo['basename'],
                'type' => 'filename',
            ],
            DirectoryList::LOG
        );
    }
}
