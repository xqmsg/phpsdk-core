<?php namespace com\xqmsg\sdk\v2\models;

/**
 * Class File
 * @package com\xqmsg\sdk\v2\models
 */
class File {

    public string $name;
    public string $type;
    public string $path;
    public int $status;
    public int $size;

    /**
     * @param array $file
     * @return File
     */
    public static function uploaded(array $file) : File {
        $instance = new self();
        $instance->name = $file['name'] ?? "";
        $instance->type = $file['type'] ?? "";
        $instance->path = $file['tmp_name'] ?? "";
        $instance->status = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        $instance->size = $file['size'] ?? 0;
        return $instance;
    }

    /**
     * @return bool
     */
    public function ok() : bool {
        return $this->status === UPLOAD_ERR_OK;
    }

}