<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/1
 * Time: 下午9:43
 */

namespace inhere\shm;

/**
 * Class ShmOp
 *  - require enable --enable-shmop
 *  - support *nix and windows
 *
 * @package inhere\shm
 */
class ShmOp extends BaseShm
{
    /**
     * {@inheritDoc}
     */
    protected function init()
    {
        if (!self::isSupported()) {
            throw new \RuntimeException(
                'To use shmop you will need to compile PHP with the --enable-shmop parameter in your configure line.',
                -500
            );
        }

        parent::init();
    }

    /**
     * open
     */
    public function open()
    {
        /**
         * resource shmop_open ( int $key , string $flags , int $mode , int $size )
         * $flags:
         *      a 访问只读内存段
         *      c 创建一个新内存段，或者如果该内存段已存在，尝试打开它进行读写
         *      w 可读写的内存段
         *      n 创建一个新内存段，如果该内存段已存在，则会失败
         * $mode: 八进制格式  0655
         * $size: 开辟的数据大小 字节
         */
        $this->shmId = shmop_open($this->key, 'c', 0644, $this->config['size']);

        if (!$this->shmId) {
            throw new \RuntimeException('Create shared memory block failed', -200);
        }
    }

    /**
     * write data to SHM
     * @param string $data
     * @return bool
     */
    public function doWrite($data)
    {
        return shmop_write($this->shmId, $data, 0) === strlen($data);
    }

    /**
     * read data form SHM
     * @param int $size
     * @return string
     */
    public function doRead($size = 0)
    {
        return shmop_read($this->shmId, 0, (int)$size ?: $this->size());
    }

    /**
     * @return int
     */
    public function size()
    {
        return shmop_size($this->shmId);
    }

    /**
     * close
     */
    public function close()
    {
        // Now lets delete the block and close the shared memory segment
        if (!shmop_delete($this->shmId)) {
            throw new \RuntimeException("Couldn't mark shared memory block for deletion.", __LINE__);
        }

        shmop_close($this->shmId);
    }

    /**
     * clear
     */
    protected function clear()
    {
        $this->write('');
    }

    /**
     * @return bool
     */
    public static function isSupported()
    {
        return function_exists('shmop_open');
    }
}
