<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */

namespace XTS_BUILD\Telegram\Bot\Objects;

/**
 * Class Document.
 *
 *
 * @property string       $fileId     Unique file identifier.
 * @property PhotoSize    $thumb      (Optional). Document thumbnail as defined by sender.
 * @property string       $fileName   (Optional). Original filename as defined by sender.
 * @property string       $mimeType   (Optional). MIME type of the file as defined by sender.
 * @property int          $fileSize   (Optional). File size.
 */
class Document extends BaseObject
{
    /**
     * {@inheritdoc}
     */
    public function relations()
    {
        return [
            'thumb' => PhotoSize::class,
        ];
    }
}
