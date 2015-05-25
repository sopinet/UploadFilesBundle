<?php
/**
 * Created by PhpStorm.
 * User:
 *
 * Date: 11/12/14
 * Time: 11:39 AM
 */

namespace Sopinet\UploadFilesBundle\Controller;



use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\Post;
use AppBundle\Entity\Oil;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class ApiFileController extends FOSRestController {

    /**
     * @ApiDoc(
     *   description="Borra un fichero del servidor",
     *   parameters={
     *      {"name"="file", "dataType"="url", "required"=true, "description"="url del fichero"},
     *   }
     * )
     *
     * Borra un fichero del directorio orphanmanager del usuario
     *
     * @param Request $request
     * @return mixed
     *
     * @POST("/deleteFile")
     */
    public function deleteFileAction(Request $request)
    {
        $files = $this->get('oneup_uploader.orphanage.gallery')->getFiles();
        $deleteName = $request->get('deleteName');
        /** @var File $file */
        foreach ($files as $file) {
            if ($file->getFileName() == $deleteName) {
                $fs = new Filesystem();
                $fs->remove($file);

                return $this->get('apihelper')->msgOk();
            }
        }

        return $this->get('apihelper')->msgDenied();
    }
} 