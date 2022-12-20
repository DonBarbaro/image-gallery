<?php

namespace App\Api;

class ApiError
{
    const TYPE_BAD_REQUEST = 'bad_request';
    const TYPE_PHOTO_NOT_FOUND = 'photo_not_found';
    const TYPE_GALLERY_NOT_FOUND = 'gallery_not_found';
    const TYPE_FILE_NOT_FOUND = 'file_not_found';
    const TYPE_GALLERY_WITH_SAME_NAME_ALREADY_EXISTS = 'gallery_with_this_name_already_exists';
    const TYPE_GALLERY_DOES_NOT_EXISTS = 'gallery_does_not_exists';
    const TYPE_PHOTO_DOES_NOT_EXISTS = 'photo_does_not_exists';
    const TYPE_PHOTO_PREVIEW_CANT_BE_GENERATED = 'photo_preview_cant_be_generated';
    const TYPE_GALLERY_NAME_CAN_NOT_CONTAIN = 'gallery_name_can_not_contain';
    const TYPE_DIRECT_GALLERY_DOES_NOT_EXIST = 'direct_gallery_does_not_exist';

    private static $titles = array(
        self::TYPE_BAD_REQUEST => 'Bad request',
        self::TYPE_PHOTO_NOT_FOUND => 'Photo not found',
        self::TYPE_GALLERY_WITH_SAME_NAME_ALREADY_EXISTS => 'Gallery with this name already exists',
        self::TYPE_GALLERY_DOES_NOT_EXISTS => 'Gallery does not exists',
        self::TYPE_PHOTO_PREVIEW_CANT_BE_GENERATED => "The photo preview can't be generated",
        self::TYPE_GALLERY_NAME_CAN_NOT_CONTAIN => 'Gallery name can not contain "/"',
        self::TYPE_GALLERY_NOT_FOUND => 'Gallery not found',
        self::TYPE_FILE_NOT_FOUND => 'File not found',
        self::TYPE_PHOTO_DOES_NOT_EXISTS => 'Photo does not exist',
        self::TYPE_DIRECT_GALLERY_DOES_NOT_EXIST => 'Direct gallery does not exists',

    );
    private int $statusCode;
    private string $message;
    public function __construct($statusCode, $type)
    {
        $this->statusCode = $statusCode;
        $this->message = self::$titles[$type];
    }
    public function getStatusCode()
    {
        return $this->statusCode;
    }
    public function getMessage()
    {
        return $this->message;
    }
}