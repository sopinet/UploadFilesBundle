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
        $entityNamespace=str_replace('Entity', '', $_REQUEST['entityNamespace']);
        $entity= $this->om->getRepository($entityNamespace.':'.$_REQUEST['entityClass'])->find($_REQUEST['entity']);
        if ($entity == null) {
            $className=$this->om->getRepository($entityNamespace.':'.$_REQUEST['entityClass'])->getClassName();
            $entity = new $className();
        }
        //si no hay ficheros subidos
        if (count($images)==0) {
            if ($entity != null) {
                //Se crea la entidad dependiendo del tipo de relacion que tenga con el fichero
                if ($entity->getReflectionClass()->hasMethod('getFile')) {
                    $this->om->remove($entity->getFile());
                    $entity->setFile(null);
                    $this->om->persist($entity);
                    $this->om->flush();
                } elseif ($entity->getReflectionClass()->hasMethod('getFiles')) {
                    foreach ($entity->getFiles() as $file) {
                        $this->om->remove($file);
                        $entity->removeFile($file);
                        $this->om->persist($entity);
                        $this->om->flush();
                    }
                }
            }

            return null;
            //En caso de que la relacion sea 1 a 1
        } else if (count($images)==1) {
            $file=$this->om->getRepository('AppBundle:File')->findOneByPath('uploads/gallery/'.$images[0]->getFilename());
            //Obtenemos la entidad de tipo file si existe o creamos una nueva
            if ( $file == null) {
                //Se crea la entidad dependiendo del tipo de relacion que tenga con el fichero
                if ($entity->getReflectionClass()->hasMethod('getFile')) {
                    $file = $this->createSingleFile($entity, $images[0]);
                } elseif ($entity->getReflectionClass()->hasMethod('getFiles')) {
                    $file = $this->createMultipleFiles($entity, $images);
                }
            }
            if ($entity->getReflectionClass()->hasMethod('getFile')) {
                $data = $file;
            } elseif ($entity->getReflectionClass()->hasMethod('getFiles')) {
                $data = array($file);
            }
        } else {
            $data = array();
            //Obtenemos todos las entidades de tipo file
            foreach ($images as $image) {
                $file=$this->om->getRepository('AppBundle:File')->findOneByPath('uploads/gallery/'.$image->getFilename());
                if ( $file == null ) {
                    // Se obtiene la entidad relacionada con los ficheros
                    $entity= $this->om->getRepository($entityNamespace.':'.$_REQUEST['entityClass'])->find($_REQUEST['entity']);
                    if ($entity ==null) {
                        $className=$this->om->getRepository($entityNamespace.':'.$_REQUEST['entityClass'])->getClassName();
                        $entity =new $className();
                    }
                    //Se crea la entidad dependiendo del tipo de relacion que tenga con el fichero
                    if ($entity->getReflectionClass()->hasMethod('getFile')) {
                        $data = $this->createSingleFile($entity, $images[0]);
                    } elseif ($entity->getReflectionClass()->hasMethod('getFiles')) {
                        $data = $this->createMultipleFiles($entity, $images);
                    }
                    break;
                }
                //borramos los ficheros que ya no sirven
                if ($entity->getReflectionClass()->hasMethod('getFiles')) {
                    $files=$entity->getFiles();
                    foreach ($files as $file) {
                        if (!in_array($file, $data)) {
                            $entity->removeFile($file);
                            $this->om->remove($file);
                        }
                    }
                    $this->om->flush();
                }
                $data[]=$file;
            }

        }

        return $data;
    }

    /**
     * Crea una entidad file con una relacionada (1 a 1) con la entidad
     * @param $entity
     * @param $file
     * @return File
     */
    private function createSingleFile($entity, $image)
    {
        if ($entity->getId() == null) {
            $file = new File();
            $file->setPath('uploads/gallery/'.$image->getFilename());
            $this->om->persist($file);
            $this->om->flush();

            return $file;
        }
        $file = new File();
        $entity= $this->om->getRepository($entityNamespace.':'.$_REQUEST['entityClass'])->find($_REQUEST['entity']);
        if ($entity->getFile()!=null) {
            $this->om->remove($entity->getFile());
            $entity->setFile(null);
            $this->om->flush();
        }
        $file->setPath('uploads/gallery/'.$image->getFilename());
        eval("\$file->set".$_REQUEST['entityClass']."(\$entity);");
        $entity->setFile($file);
        $this->om->persist($file);
        $this->om->persist($entity);
        $this->om->flush();

        return $file;
    }

    private function createMultipleFiles($entity, $images)
    {
        $data=array();
        if ($entity->getId() == null) {
            foreach ($images as $image) {
                $file = new File();
                $file->setPath('uploads/gallery/'.$image->getFilename());
                $this->om->persist($file);
                $this->om->flush();
                $data[]=$file;
            }

            return $data;
        }
        //Para cada imagen se obtiene un registro o un nuevo fichero
        foreach ($images as $image) {
            $file=$this->om->getRepository('AppBundle:File')->findOneByPath('uploads/gallery/'.$image->getFilename());
            if ( $file == null ) {
                $file = new File();

                $file->setPath('uploads/gallery/'.$image->getFilename());
                $this->om->persist($file);

                $this->om->flush();
            }
            $data[]=$file;
        }
        $this->om->flush();

        $files=$entity->getFiles();

        //Asociamos los nuevos ficheros con la entidad
        foreach ($data as $new) {
            if (!in_array($new, $files->toArray())) {
                $entity->addFile($new);
                eval("\$new->set".$_REQUEST['entityClass']."(\$entity);");
                $this->om->persist($new);
            }
        }

        $this->om->flush();

        return $data;
    }
}
