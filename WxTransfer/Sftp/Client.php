<?php

namespace Ingenico\Connect\WxTransfer\Sftp;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\State\InitException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Filesystem\Io\Sftp;
use Magento\Framework\Filesystem\Io\SftpFactory;

/**
 * Class Client
 * @package Ingenico\Connect\WxTransfer\Sftp
 */
class Client implements ClientInterface
{
    /**
     * @var SftpFactory SftpFactory
     */
    private $sftpFactory;

    /**
     * @var Sftp
     */
    private $sftpClient;

    /**
     * Client constructor.
     *
     * @param SftpFactory $sftpFactory
     */
    public function __construct(SftpFactory $sftpFactory)
    {
        $this->sftpFactory = $sftpFactory;
    }

    /**
     * Connect to given remote host with given credentials
     *
     * @param $host
     * @param $username
     * @param $password
     * @throws \Exception
     * @return $this
     */
    public function connect($host, $username, $password)
    {
        $this->sftpClient = $this->sftpFactory->create();
        $this->sftpClient->open(
            [
                'host' => $host,
                'username' => $username,
                'password' => $password,
            ]
        );
        return $this;
    }

    public function disconnect()
    {
        $this->sftpClient->close();
    }


    /**
     * Reads the file list of the remote directory and matches all regular files names against the given pattern
     *
     * @param string $pattern regex pattern to check the files against
     * @param string $remoteDir directory on remote host
     * @return string[][] list of files as ['fileName' => [metadata]]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFileCollection($pattern, $remoteDir)
    {
        if (preg_match($pattern, null) === false) {
            throw new ValidatorException(
                __('Pattern %pattern is not a valid regular expression', ['pattern' => $pattern])
            );
        }

        if (!$this->sftpClient) {
            throw new InitException(__('Please connect the client first.'));
        }

        if (!$this->sftpClient->cd($remoteDir)) {
            throw new FileSystemException(__("Could not read directory '%dir' on remote host.", ['dir' => $remoteDir]));
        }

        $fileList = $this->sftpClient->rawls();
        $fileList = array_filter(
            $fileList,
            function ($element) use ($pattern) {
                // If we know the type, accept only regular files (see \phpseclib\Net\SFTP::file_types)
                if ($element['type'] && $element['type'] !== 1) {
                    return false;
                }
                // If it is a regular file, match against the pattern
                return preg_match($pattern, $element['filename']) > 0;
            }
        );

        return $fileList;
    }

    /**
     * @param string $fileName
     * @param string $remoteDir
     * @return string
     * @throws InputException
     */
    public function loadFile($fileName, $remoteDir = '')
    {
        if (!empty($remoteDir) && is_string($remoteDir)) {
            $this->sftpClient->cd($remoteDir);
        }

        $data = $this->sftpClient->read($fileName);
        $gzuncompress = gzdecode($data);
        if (!$gzuncompress) {
            throw new InputException(
                __('Could not decompress compressed WX file %filename', ['filename' => $fileName])
            );
        }
        return $gzuncompress;
    }
}
