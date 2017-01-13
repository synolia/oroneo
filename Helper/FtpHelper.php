<?php

namespace Synolia\Bundle\OroneoBundle\Helper;

/**
 * Class FtpHelper
 * @package Synolia\Bundle\OroneoBundle\Helper
 */
class FtpHelper
{
    const REMOTE_TIMEOUT = 10;
    const FTP_PORT       = 21;

    /** @var null $connection */
    protected $connection;

    /** @var array $parameters */
    protected $parameters;

    /** @var null $handler */
    protected $handler = null;

    /** @var bool $opened */
    protected $opened = false;

    /** @var $status */
    protected $status;

    /**
     * @param string  $user
     * @param string  $password
     * @param string  $host
     * @param integer $port
     * @param string  $path
     * @param integer $timeout
     * @param boolean $passiveMode
     * @param boolean $forceSsl
     *
     * @return $this
     */
    public function setParameters(
        $user,
        $password,
        $host,
        $port = self::FTP_PORT,
        $path = '.',
        $timeout = self::REMOTE_TIMEOUT,
        $passiveMode = false,
        $forceSsl = false
    ) {
        $this->parameters = [
            'username'     => $user,
            'password'     => $password,
            'host'         => $host,
            'path'         => $path,
            'port'         => $port,
            'timeout'      => $timeout,
            'passive_mode' => $passiveMode,
            'force_ssl'    => $forceSsl,
        ];

        return $this;
    }

    /**
     * @return void
     */
    public function destruct()
    {
        if ($this->opened) {
            $this->getHandler()->close();
        }
    }

    /**
     * @param string $distantPath
     * @param string $localPath
     *
     * @return bool
     * @throws \Exception
     */
    public function isImportedFile($distantPath, $localPath)
    {
        while (strpos($localPath, '//') !== false) {
            $localPath = str_replace('//', '/', $localPath);
        }
        if (!file_exists(dirname($localPath))) {
            mkdir(dirname($localPath), 0777, true);
        }

        $fileData = $this->readFile($distantPath);
        if (!$fileData) {
            throw new \Exception('Can\'t get distant file : '.$this->parameters['path'].'/'.$distantPath, 2);
        }
        if (!@file_put_contents($localPath, $fileData)) {
            throw new \Exception('Can\'t write local file : '.$localPath, 3);
        }

        return true;
    }

    /**
     * @param array       $grep
     * @param string|null $path
     *
     * @return array
     * @throws \Exception
     */
    public function getFolderContent($grep = [], $path = null)
    {
        $ftpClient = $this->getHandler();
        if ($path) {
            if (!$ftpClient->cd($path)) {
                throw new \Exception('Can\'t open ftp folder : '.$path, 5);
            }
        }
        $folderContents       = $ftpClient->ls();
        $formatFolderContents = [];

        if (!is_array($grep)) {
            $grep = [$grep];
        }

        foreach ($folderContents as $folderContent) {
            $isMatch = empty($grep);

            foreach ($grep as $pattern) {
                if (fnmatch($pattern, $folderContent['text'])) {
                    $isMatch = true;
                    break;
                }
            }

            if ($isMatch) {
                $formatFolderContents[] = $folderContent['text'];
            }
        }

        return $formatFolderContents;
    }

    /**
     * @param string $filePath
     *
     * @return string
     */
    public function readFile($filePath)
    {
        $ftpClient = $this->getHandler();

        return $ftpClient->read($filePath);
    }

    /**
     * Open a FTP connection to a remote site.
     *
     * @param array $args Connection arguments
     *                    $args[host]         Remote hostname
     *                    $args[port]         Remote port
     *                    $args[username]     Remote username
     *                    $args[password]     Connection password
     *                    $args[force_ssl]    force SSL connection, by default set to false
     *                    $args[passive_mode] Set passive mode, by default set to false
     *                    $args[timeout]      Connection timeout [=10]
     *
     * @throws \Exception
     */
    public function open(array $args = [])
    {
        if (!isset($args['timeout'])) {
            $args['timeout'] = self::REMOTE_TIMEOUT;
        }

        if (!isset($args['force_ssl'])) {
            $args['force_ssl'] = false;
        }

        if (!isset($args['passive_mode'])) {
            $args['passive_mode'] = false;
        }

        $host = $args['host'];
        $port = self::FTP_PORT;
        if (strpos($args['host'], ':') !== false) {
            list($host, $port) = explode(':', $args['host'], 2);
        } elseif ($args['port']) {
            $host = $args['host'];
            $port = $args['port'];
        }

        if ($args['force_ssl']) {
            if (!function_exists('ftp_ssl_connect')) {
                throw new \Exception(sprintf('Your server doesn\'t support FTPs connection', $host));
            }
            $this->connection = ftp_ssl_connect($host, $port, $args['timeout']);
        } else {
            $this->connection = ftp_connect($host, $port, $args['timeout']);
        }

        if (!$this->connection) {
            throw new \Exception(sprintf('Unable to open %s connection to %s', ($args['force_ssl'] ? 'FTPs' : 'FTP'), $host));
        }

        if (!ftp_login($this->connection, $args['username'], $args['password'])) {
            throw new \Exception(sprintf('Unable to open %s connection as %s@%s', ($args['force_ssl'] ? 'FTPs' : 'FTP'), $args['username'], $host));
        }

        ftp_pasv($this->connection, $args['passive_mode']);
    }

    /**
     * Close a connection
     *
     * @return boolean
     */
    public function close()
    {
        return @ftp_close($this->connection);
    }

    /**
     * Get current working directory
     *
     * @return string|boolean
     */
    public function pwd()
    {
        return ftp_pwd($this->connection).'/';
    }

    /**
     * Change current working directory
     *
     * @param string $dir
     *
     * @return boolean
     */
    public function cd($dir)
    {
        return ftp_chdir($this->connection, $dir);
    }

    /**
     * Read a file
     *
     * @param string $filename
     * @param string $dest
     *
     * @return string|boolean
     * @throws \Exception
     */
    public function read($filename, $dest = null)
    {
        if ($dest) {
            return ftp_get($this->connection, $filename, $dest, FTP_BINARY);
        }

        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        if ($sockets === false) {
            throw new \Exception('Unable to create socket pair');
        }

        list($socket, $socketData) = $sockets;
        stream_set_write_buffer($socket, 0);
        stream_set_timeout($socketData, 0);

        $this->startReading($socket, $filename);

        $content = '';
        while (!$this->isReadFinished()) {
            $currentContent = stream_get_contents($socketData);
            if ($currentContent !== false) {
                $content .= $currentContent;
            }
            $this->continueReading();
        }

        $content .= stream_get_contents($socketData);

        return $content;
    }

    /**
     * @throws \Exception
     */
    public function continueReading()
    {
        if ($this->isReadFinished()) {
            throw new \Exception('Cannot continue download; already finished');
        }

        $this->status = ftp_nb_continue($this->connection);
    }

    /**
     * Get list of cwd subdirectories and files
     *
     * @return array
     */
    public function ls()
    {
        $list = ftp_nlist($this->connection, '.');

        // If an error occured during nlist, try passive mode
        if (!$list) {
            ftp_pasv($this->connection, true);
            $list = ftp_nlist($this->connection, '.');
        }

        $pwd = $this->pwd();
        $result = [];
        foreach ($list as $name) {
            $result[] = array(
                'text' => $name,
                'id' => "{$pwd}{$name}",
            );
        }

        return $result;
    }

    /**
     * @return FtpHelper
     * @throws \Exception
     */
    protected function getHandler()
    {
        if (is_null($this->handler)) {
            $this->handler = clone $this;
            try {
                $this->handler->open($this->parameters);
                if ($this->parameters['path']) {
                    if (!$this->handler->cd($this->parameters['path'])) {
                        throw new \Exception('Can\'t open ftp folder : '.$this->parameters['path'], 4);
                    }
                }
            } catch (\Exception $e) {
                throw new \Exception('Can\'t open ftp connection', 1, $e);
            }
            $this->opened = true;
        }

        return $this->handler;
    }

    /**
     * @param resource $stream
     * @param string   $filename
     */
    protected function startReading($stream, $filename)
    {
        $this->status = ftp_nb_fget($this->connection, $stream, $filename, FTP_BINARY);
    }

    /**
     * @return boolean
     */
    protected function isReadFinished()
    {
        return $this->status !== FTP_MOREDATA;
    }
}
