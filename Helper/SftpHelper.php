<?php

namespace Synolia\Bundle\OroneoBundle\Helper;

use phpseclib\Net\SFTP;
use phpseclib\Crypt;
use phpseclib\Crypt\RSA;

/**
 * Class SftpHelper
 * @package Synolia\Bundle\OroneoBundle\Helper
 */
class SftpHelper
{
    const REMOTE_TIMEOUT = 10;
    const SSH2_PORT      = 22;

    /** @var SFTP $connection */
    protected $connection = null;

    protected $parameters;
    protected $handler;
    protected $opened = false;

    /**
     * @param string      $user
     * @param string      $password
     * @param string      $host
     * @param int         $port
     * @param null|string $path
     * @param null|int    $timeout
     * @param null|bool   $sshKey
     *
     * @return SftpHelper
     */
    public function setParameters($user, $password, $host, $port = self::SSH2_PORT, $path = null, $timeout = self::REMOTE_TIMEOUT, $sshKey = null)
    {
        $this->parameters = [
            'username'  => $user,
            'password'  => $password,
            'host'      => $host,
            'path'      => $path,
            'port'      => $port,
            'timeout'   => $timeout,
            'ssh_key'   => $sshKey,
        ];

        if ($port) {
            $this->parameters['host'] .= ':'.$port;
        }

        return $this;
    }

    /**
     * Disconnects the current connection
     */
    public function __destruct()
    {
        if ($this->opened) {
            if (!is_object($this->connection)) {
                $this->handler->connection->disconnect();
            } else {
                $this->connection->disconnect();
            }
            $this->opened = false;
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
     * @param null|string $path
     *
     * @return array
     * @throws \Exception
     */
    public function getFolderContent($grep = [], $path = null)
    {
        $sftpClient = $this->getHandler();
        if ($path) {
            if (!$sftpClient->cd($path)) {
                throw new \Exception('Can\'t open sftp folder : '.$path, 5);
            }
        }
        $folderContents       = $sftpClient->ls();
        $formatFolderContents = array();

        if (!is_array($grep)) {
            $grep = array($grep);
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
     * @return mixed
     */
    public function readFile($filePath)
    {
        $sftpClient = $this->getHandler();

        return $sftpClient->get($filePath, false);
    }

    /**
     * Open a SFTP connection to a remote site.
     *
     * @param array $args Connection arguments
     *                    string $args[host] Remote hostname
     *                    string $args[username] Remote username
     *                    string $args[password] Connection password or passphrase in case of ssh key file
     *                    int $args[timeout] Connection timeout [=10]
     *                    string $args[ssh_key] Set key file path to establish connection authentication
     *
     * @throws \Exception
     */
    public function open(array $args = [])
    {
        if (!isset($args['timeout'])) {
            $args['timeout'] = self::REMOTE_TIMEOUT;
        }
        if (strpos($args['host'], ':') !== false) {
            list($host, $port) = explode(':', $args['host'], 2);
        } else {
            $host = $args['host'];
            $port = self::SSH2_PORT;
        }

        if (!empty($args['ssh_key'])) {
            $this->connection = new SFTP($host, $port, $args['timeout']);
            $key = new RSA();
            if (strlen($args['password']) > 0) {
                $key->setPassword($args['password']);
            }

            $key->loadKey($args['ssh_key']);
            if (!$this->connection->login($args['username'], $key)) {
                throw new \Exception(sprintf('Unable to open SFTP connection as %s@%s using RSA key', $args['username'], $args['host']));
            }
        } else {
            $this->connection = new SFTP($host, $port, $args['timeout']);
            if (!$this->connection->login($args['username'], $args['password'])) {
                throw new \Exception(sprintf('Unable to open SFTP connection as %s@%s', $args['username'], $args['host']));
            }
        }
        $this->opened = true;
    }

    /**
     * Get current working directory
     * @return mixed
     */
    public function pwd()
    {
        return $this->connection->pwd();
    }

    /**
     * Change current working directory
     *
     * @param string $dir
     *
     * @return bool
     */
    public function cd($dir)
    {
        return $this->connection->chdir($dir);
    }

    /**
     * Read a file
     *
     * @param string      $filename
     * @param null|string $dest
     *
     * @return mixed
     */
    public function read($filename, $dest = null)
    {
        if (is_null($dest)) {
            $dest = false;
        }

        return $this->handler->connection->get($filename, $dest);
    }

    /**
     * @param string $remoteFile
     * @param bool   $localFile
     * @param int    $offset
     * @param int    $length
     *
     * @return mixed
     */
    public function get($remoteFile, $localFile = false, $offset = 0, $length = -1)
    {
        $result = $this->connection->get($remoteFile, $localFile, $offset, $length);

        return $result;
    }

    /**
     * Get list of cwd subdirectories and files
     * @return array
     */
    public function ls()
    {
        $list = $this->connection->nlist();
        $pwd = $this->pwd();
        $result = [];
        foreach ($list as $name) {
            $result[] = [
                'text' => $name,
                'id' => "{$pwd}{$name}",
            ];
        }

        return $result;
    }

    /**
     * @return bool|SftpHelper
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
                        throw new \Exception('Can\'t open sftp folder : '.$this->parameters['path'], 4);
                    }
                }
            } catch (\Exception $e) {
                throw new \Exception('Can\'t open sftp connection', 1, $e);
            }
            $this->opened = true;
        }

        return $this->handler;
    }
}
