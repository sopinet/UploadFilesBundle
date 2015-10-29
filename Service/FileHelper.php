<?php

namespace Sopinet\UploadFilesBundle\Service;

use AppBundle\Entity\File;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use FOS\RestBundle\View\ViewHandler;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;

class FileHelper
{
    /**
     * Constructor
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->em = $container->get('doctrine')->getManager();
    }

    /**
     * Guarda un fichero y devuelve su objeto File
     *
     * @param UploadedFile $file_request
     * @param String $fieldName / Nombre del campo para el fichero en base de datos
     */
    public function uploadFileByFile(UploadedFile $file_request, $fieldName, $entity) {
        $file_content = file_get_contents($file_request->getPathname());
        $file_extension = $file_request->getClientOriginalExtension();

        $file_app_object = $this->writeFileBytes($file_content, $file_extension, $fieldName, $entity);
    }

    /**
     * Funcion para crear un directorio
     * @param unknown $path - Directorio a crear
     * @return unknown
     */
    private function createDir($path) {
        $dir = $path;
        if (!is_dir($dir)) {
            $old = umask(0);
            mkdir($dir,0777,true);
            umask($old);
            chmod($dir, 0777);
        }
        return $dir;
    }

    /**
     * Pasa bytes a Fichero
     *
     * @param $bytes
     * @param $output_file
     */
    private function bytes_to_file($bytes, $output_file) {
        $ifp = fopen($output_file, "wb");
        fwrite($ifp, $bytes);
        fclose($ifp);
    }

    /**
     * Escribe en un fichero sus Bytes, crea el objeto File
     *
     * @param $data_bytes
     * @param $extension
     */
    private function writeFileBytes($data_bytes, $extension, $field, $entity) {
        $dir_rel = "uploads" . DIRECTORY_SEPARATOR . "gallery" . DIRECTORY_SEPARATOR;
        $dir = $this->createDir($this->getRoot(). DIRECTORY_SEPARATOR ."web" . DIRECTORY_SEPARATOR . $dir_rel);

        // Obtenemos un nombre único
        $name=uniqid("AppFileUniqSystem", true);
        $name = $field . "_" . md5($name) .'.' . $extension;

        // Calculamos el nombre de la ruta completo y relativo, metemos los bytes
        $file_abs_name = $dir . $name;
        $this->bytes_to_file($data_bytes, $file_abs_name);
    }

    private function getRoot() {
        return $this->container->get('kernel')->getRootDir()."/..";
    }


}