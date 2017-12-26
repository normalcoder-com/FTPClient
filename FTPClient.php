<?php

namespace normalcoder;

/**
 * FTP客户端
 * Class FTPClient
 * @package normalcoder
 */
class FTPClient
{

    /**
     * 常量声明
     */
    const ASCII = FTP_ASCII;
    const BINARY = FTP_BINARY;
    const TIMEOUT_SEC = FTP_TIMEOUT_SEC;
    const AUTOSEEK = FTP_AUTOSEEK;

    /**
     * FTP connection,FTP连接
     * @var Resource
     */
    private $connection = null;

    /**
     * 异常错误信息
     * @var null|string
     */
    private $error = null;


    /**
     * FTPClient constructor.
     * 初始化检查FTP扩展是否加载
     */
    public function __construct()
    {
        if (!extension_loaded('ftp')) {
            return $this->setError('FTP extension is not loaded!');
        }
    }

    /**
     * 创建一个FTP连接
     * @param string $host
     * @param bool   $ssl
     * @param int    $port
     * @param int    $timeout
     * @return FTPClient|boolean
     */
    public function connect($host, $ssl = false, $port = 21, $timeout = 90)
    {
        if ($ssl) {
            $this->connection = @ftp_ssl_connect($host, $port, $timeout);
        } else {
            $this->connection = @ftp_connect($host, $port, $timeout);
        }
        if ($this->connection == null) {
            return $this->setError("Unable to connect");
        } else {
            return $this;
        }
    }

    /**
     * 登陆FTP
     * @param string $username
     * @param string $password
     * @return FTPClient|Boolean
     */
    public function login($username = 'anonymous', $password = '')
    {
        $result = @ftp_login($this->connection, $username, $password);
        if ($result === false) {
            return $this->setError("Login incorrect");
        } else {
            return $this;
        }
    }

    /**
     * 关闭FTP连接
     * @return boolean
     */
    public function close()
    {
        $result = @ftp_close($this->connection);
        if ($result === false) {
            return $this->setError("Unable to close connection");
        }
    }

    /**
     * 改变被动模式
     * @param bool $passive
     * @return FTPClient|boolean
     */
    public function passive($passive = true)
    {
        $result = @ftp_pasv($this->connection, $passive);
        if ($result === false) {
            return $this->setError("Unable to change passive mode");
        }
        return $this;
    }

    /**
     * 将当前目录更改为指定的目录
     * @return FTPClient|boolean
     */
    public function changeDirectory($directory)
    {
        $result = @ftp_chdir($this->connection, $directory);
        if ($result === false) {
            return $this->setError("Unable to change directory");
        }
        return $this;
    }

    /**
     * 将当前目录更改为父目录
     * @return FTPClient|boolean
     */
    public function parentDirectory()
    {
        $result = @ftp_cdup($this->connection);
        if ($result === false) {
            return $this->setError("Unable to get parent folder");
        }
        return $this;
    }

    /**
     * 返回当前目录名称
     * @return string
     */
    public function getDirectory()
    {
        $result = @ftp_pwd($this->connection);
        if ($result === false) {
            return $this->setError("Unable to get directory name");
        }
        return $result;
    }

    /**
     * 创建一个目录
     * @param string $directory
     * @return FTPClient|boolean
     */
    public function createDirectory($directory)
    {
        $result = @ftp_mkdir($this->connection, $directory);

        if ($result === false) {
            return $this->setError("Unable to create directory");
        }
        return $this;
    }

    /**
     * 删除一个目录
     * @param string $directory
     * @return FTPClient|boolean
     */
    public function removeDirectory($directory)
    {
        $result = @ftp_rmdir($this->connection, $directory);
        if ($result === false) {
            return $this->setError("Unable to remove directory");
        }
        return $this;
    }

    /**
     * 返回给定目录中的文件列表
     * @param string $directory
     * @return array|boolean
     */
    public function listDirectory($directory)
    {
        $result = @ftp_nlist($this->connection, $directory);
        asort($result);
        if ($result === false) {
            return $this->setError("Unable to list directory");
        }
        return $result;
    }

    /**
     * 删除FTP服务器上的文件
     * @param string $path
     * @return FTPClient|boolean
     */
    public function delete($path)
    {
        $result = @ftp_delete($this->connection, $path);
        if ($result === false) {
            return $this->setError("Unable to get parent folder");
        }
        return $this;
    }

    /**
     * 返回给定文件的大小
     * Return -1 on error
     * @param string $remoteFile
     * @return int
     */
    public function size($remoteFile)
    {
        $size = @ftp_size($this->connection, $remoteFile);
        if ($size === -1) {
            return $this->setError("Unable to get file size");
        }
        return $size;
    }

    /**
     * 返回给定文件的最后修改时间
     * Return -1 on error
     * @param string $remoteFile
     * @return int
     */
    public function modifiedTime($remoteFile, $format = null)
    {
        $time = ftp_mdtm($this->connection, $remoteFile);
        if ($time !== -1 && $format !== null) {
            return date($format, $time);
        } else {
            return $time;
        }
    }

    /**
     * 重命名FTP服务器上的文件或目录
     * @param string $currentName
     * @param string $newName
     * @return bool
     */
    public function rename($currentName, $newName)
    {
        $result = @ftp_rename($this->connection, $currentName, $newName);
        return $result;
    }

    /**
     * 从FTP服务器下载文件
     * @param string $localFile
     * @param string $remoteFile
     * @param int    $mode
     * @param int    $resumepos
     * @return FTPClient|boolean
     */
    public function get($localFile, $remoteFile, $mode = FTPClient::ASCII, $resumePosision = 0)
    {
        $result = @ftp_get($this->connection, $localFile, $remoteFile, $mode, $resumePosision);
        if ($result === false) {
            return $this->setError(sprintf('Unable to get or save file "%s" from %s', $localFile, $remoteFile));
        }
        return $this;
    }

    /**
     * 从打开的文件上传到FTP服务器
     * @param string $remoteFile
     * @param string $localFile
     * @param int    $mode
     * @param int    $startPosision
     * @return FTPClient|boolean
     */
    public function put($remoteFile, $localFile, $mode = FTPClient::ASCII, $startPosision = 0)
    {
        $result = @ftp_put($this->connection, $remoteFile, $localFile, $mode, $startPosision);
        if ($result === false) {
            return $this->setError("Unable to put file");
        }
        return $this;
    }

    /**
     * 从FTP服务器下载文件并保存到打开的文件
     * @param resource $handle
     * @param string   $remoteFile
     * @param int      $mode
     * @param int      $resumepos
     * @return FTPClient|boolean
     */
    public function fget(resource $handle, $remoteFile, $mode = FTPClient::ASCII, $resumePosision = 0)
    {
        $result = @ftp_fget($this->connection, $handle, $remoteFile, $mode, $resumePosision);
        if ($result === false) {
            return $this->setError("Unable to get file");
        }
        return $this;
    }

    /**
     * 从打开的文件上传到FTP服务器
     * @param string   $remoteFile
     * @param resource $handle
     * @param int      $mode
     * @param int      $startPosision
     * @return FTPClient|boolean
     */
    public function fput($remoteFile, resource $handle, $mode = FTPClient::ASCII, $startPosision = 0)
    {
        $result = @ftp_fput($this->connection, $remoteFile, $handle, $mode, $startPosision);
        if ($result === false) {
            return $this->setError("Unable to put file");
        }
        return $this;
    }

    /**
     * 检索当前FTP流的各种运行时行为
     * TIMEOUT_SEC | AUTOSEEK
     * @param mixed $option
     * @return mixed
     */
    public function getOption($option)
    {
        switch ($option) {
            case FTPClient::TIMEOUT_SEC:
            case FTPClient::AUTOSEEK:
                $result = @ftp_get_option($this->connection, $option);
                return $result;
                break;
            default:
                return $this->setError("Unsupported option");
                break;
        }
    }

    /**
     * 设置其他运行时FTP选项
     * TIMEOUT_SEC | AUTOSEEK
     * @param mixed $option
     * @param mixed $value
     * @return mixed
     */
    public function setOption($option, $value)
    {
        switch ($option) {
            case FTPClient::TIMEOUT_SEC:
                if ($value <= 0) {
                    return $this->setError("Timeout value must be greater than zero");
                }
                break;
            case FTPClient::AUTOSEEK:
                if (!is_bool($value)) {
                    return $this->setError("Autoseek value must be boolean");
                }
                break;
            default:
                return $this->setError("Unsupported option");
                break;
        }
        return @ftp_set_option($this->connection, $option, $value);
    }

    /**
     * 为要上传的文件分配空间
     * @param int $filesize
     * @return FTPClient|boolean
     */
    public function allocate($filesize)
    {
        $result = @ftp_alloc($this->connection, $filesize);
        if ($result === false) {
            return $this->setError("Unable to allocate");
        }
        return $this;
    }

    /**
     * 通过FTP设置文件的权限
     * @param int    $mode
     * @param string $filename
     * @return FTPClient|boolean
     */
    public function chmod($mode, $filename)
    {
        $result = @ftp_chmod($this->connection, $mode, $filename);
        if ($result === false) {
            return $this->setError("Unable to change permissions");
        }
        return $this;
    }

    /**
     * 请求在FTP服务器上执行命令
     * @param string $command
     * @return FTPClient|boolean
     */
    public function exec($command)
    {
        $result = @ftp_exec($this->connection, $command);
        if ($result === false) {
            return $this->setError("Unable to exec command");
        }
        return $this;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * 设置错误信息
     * @param $error
     * @return bool
     */
    public function setError($error)
    {
        $this->error = $error;
        return false;
    }

    /**
     * 获取错误信息
     * @return null|string
     */
    public function getError()
    {
        return $this->error;
    }
}