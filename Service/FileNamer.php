<?php

namespace Sopinet\UploadFilesBundle\Service;

use Oneup\UploaderBundle\Uploader\File\FileInterface;
use Oneup\UploaderBundle\Uploader\Naming\NamerInterface;
use Symfony\Component\Validator\Constraints\Date;

class FileNamer implements NamerInterface
{
    public function name(FileInterface $file)
    {
        $date = new \DateTime();

        return (floor($date->getTimestamp())).'.'.$file->getExtension();
    }
}