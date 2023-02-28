<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Controller\Adminhtml\DownloadLogFile;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Filesystem\Driver\File;
use Worldline\Connect\Model\ConfigInterface;

class Index extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Worldline_Connect::download_logfile';

    /** @var ConfigInterface */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $config;

    /** @var FileFactory */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $fileFactory;

    /** @var File */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $fileDriver;

    // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /** @var \Magento\Framework\Filesystem\Io\File */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $fileSystemIo;

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * @param Context $context
     * @param ConfigInterface $config
     * @param FileFactory $fileFactory
     * @param File $fileDriver
     * @param \Magento\Framework\Filesystem\Io\File $fileSystemIo
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    public function __construct(
        Context $context,
        ConfigInterface $config,
        FileFactory $fileFactory,
        File $fileDriver,
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
        \Magento\Framework\Filesystem\Io\File $fileSystemIo
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->fileFactory = $fileFactory;
        $this->fileDriver = $fileDriver;
        $this->fileSystemIo = $fileSystemIo;
    }

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * Initiates logging file download process
     *
     * @return ResponseInterface
     * @throws \LogicException
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    public function execute()
    {
        $fileToDownload = $this->config->getLogAllRequestsFile();
        if (!$this->fileDriver->isExists($fileToDownload)) {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName, SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
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
