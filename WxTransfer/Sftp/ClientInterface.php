<?php

namespace Ingenico\Connect\WxTransfer\Sftp;

interface ClientInterface
{
    /**
     * Connect to given remote host with given credentials
     *
     * @param string $host
     * @param string $username
     * @param string $password
     * @throws \Exception
     * @return $this
     */
    public function connect($host, $username, $password);

    /**
     * Close all connections
     */
    public function disconnect();

    /**
     * Reads the file list of the remote directory and matches all regular files names against the given pattern
     *
     * @param string $pattern regex pattern to check the files against
     * @param string $remoteDir directory on remote host
     * @return string[][] list of files as ['fileName' => [metadata]]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFileCollection($pattern, $remoteDir);

    /**
     * Load and decompress remote file
     *
     * @param string $fileName
     * @param string $remoteDir
     * @return string
     */
    public function loadFile($fileName, $remoteDir = '');
}
