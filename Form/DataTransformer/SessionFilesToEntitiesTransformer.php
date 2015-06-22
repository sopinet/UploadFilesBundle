<?php
/**
 * Created by PhpStorm.
 * User: hud
 * Date: 20/05/15
 * Time: 12:02
 */
namespace Sopinet\UploadFilesBundle\Form\DataTransformer;

use AppBundle\Entity\File;
use Doctrine\Common\Collections\ArrayCollection;
use Oneup\UploaderBundle\Uploader\Orphanage\OrphanageManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Doctrine\Common\Persistence\ObjectManager;

class SessionFilesToEntitiesTransformer implements DataTransformerInterface
{
    /**
     * @var ObjectManager
     */
    private $om;

    /**
     * @var OrphanageManager
     */
    private $orphanageManager;

    /**
     * @param ObjectManager $om
     * @param OrphanageManager $orphanageManager
     */
    public function __construct(ObjectManager $om, OrphanageManager $orphanageManager)
    {
        $this->om = $om;
        $this->orphanageManager = $orphanageManager;
    }

    /**
     * Transforms an object (file)
     *
     * @param  File $file
     *
     * @return File
     */
    public function transform($file)
    {
        if ($file instanceof File) {
            return array($file->getWebPath());
        } else if ($file instanceof ArrayCollection) {
            $data=array();
            foreach ($file as $f) {
                $data[]=$f->getWebPath();
            }

            return $data;
        }

    }

    /**
     * Transforms a collection os files on session on entities
     *
     * @throws TransformationFailedException if object (issue) is not found.
     */
    public function reverseTransform($id)
    {
        // Pasar los ficheros almacenados en la sesión a la carpeta web y devolver el array de ficheros
        $manager = $this->orphanageManager->get('gallery');
        $images = $manager->uploadFiles();

        if (count($images)==0) {
            return null;
        } else if (count($images)==1) {
            $file=$this->om->getRepository('AppBundle:File')->findByPath('uploads/gallery/'.$images[0]->getFilename());
            if( $file == null){
                $file = new File();

                $file->setPath('uploads/gallery/'.$images[0]->getFilename());
                $this->om->persist($file);

                $this->om->flush();
            }
            $data=$file;

        } else {
            $data = array();
            foreach ($images as $image) {
                $file=$this->om->getRepository('AppBundle:File')->findOneByPath('uploads/gallery/'.$image->getFilename());
                if( $file == null){
                    $file = new File();

                    $file->setPath('uploads/gallery/'.$image->getFilename());
                    $this->om->persist($file);

                    $this->om->flush();
                }
                $data[]=$file;
            }
            $this->om->flush();
        }

        return $data;
    }
}