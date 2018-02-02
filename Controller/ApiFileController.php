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
use FOS\RestBundle\Controller\Annotations\Get;
use AppBundle\Entity\Oil;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;

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

                return $this->get('sopinet_apihelperbundle_apihelper')->msgOk();
            }
        }

        return $this->get('sopinet_apihelperbundle_apihelper')->msgDenied();
    }

    /**
     * @ApiDoc(
     *   description="Devuelve un fichero de la session"
     * )
     *
     * Borra un fichero del directorio orphanmanager del usuario
     *
     * @param Request $request
     * @return mixed
     *
     * @GET("/obtainFile/{filename}")
     */
    public function obtainFileAction(Request $request, $filename)
    {
        $files = $this->get('oneup_uploader.orphanage.gallery')->getFiles();
        /** @var File $file */
        foreach ($files as $file) {
            if ($file->getFileName() == $filename) {
                $response = new BinaryFileResponse($file);
                return $response;
            }
        }
        return new Response("", 404);
    }
} 