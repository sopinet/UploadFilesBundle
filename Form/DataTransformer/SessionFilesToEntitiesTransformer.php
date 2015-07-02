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

    private $fieldName;
    private $mappedBy;

    /**
     * @param ObjectManager $om
     * @param OrphanageManager $orphanageManager
     */
    public function __construct(ObjectManager $om, OrphanageManager $orphanageManager, $fieldName, $mappedBy)
    {
        $this->om = $om;
        $this->orphanageManager = $orphanageManager;
        $this->fieldName = $fieldName;
        $this->mappedBy = $mappedBy;
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
    public function reverseTransform($fields)
    {
        // Pasar los ficheros almacenados en la sesión a la carpeta web y devolver el array de ficheros
        $entityNamespace=str_replace('Entity', '', $_REQUEST['entityNamespace']);
        $entity= $this->om->getRepository($entityNamespace.':'.$_REQUEST['entityClass'])->find($_REQUEST['entity']);
        if ($entity == null) {
            $className=$this->om->getRepository($entityNamespace.':'.$_REQUEST['entityClass'])->getClassName();
            $entity = new $className();
        }
        $multiple=$this->isCollection($entity);
        $images= $this->getImages();
        //si no hay ficheros subidos
        if (count($images)==0) {
            if ($entity != null) {
                //Se crea la entidad dependiendo del tipo de relacion que tenga con el fichero
                if (!$multiple && $this->invokeMethod($entity, 'get')!=null) {
                    $this->om->remove($this->invokeMethod($entity, 'get'));
                    $this->invokeMethod($entity, 'set', false, null);
                    $this->om->persist($entity);
                    $this->om->flush();
                } elseif ($multiple) {
                    foreach ($this->invokeMethod($entity, 'get', $multiple) as $file) {
                        $this->om->remove($file);
                        $this->invokeMethod($entity, 'remove', $multiple, $file);
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
                if (!$multiple) {
                    $file = $this->createSingleFile($entity, $images[0]);
                } elseif ($multiple) {
                    $file = $this->createMultipleFiles($entity, $images);
                }
                $data = $file;
            } else {
                if ($multiple) {
                    $data = array($file);
                } else {
                    $data = $file;
                }

            }
        } else {
            $data = array();
            //Obtenemos todos las entidades de tipo file
            foreach ($images as $image) {
                $file=$this->om->getRepository('AppBundle:File')->findOneByPath('uploads/gallery/'.$image->getFilename());
                if ( $file == null ) {
                    // Se obtiene la entidad relacionada con los ficheros
                    $entity= $this->om->getRepository($entityNamespace.':'.$_REQUEST['entityClass'])->find($_REQUEST['entity']);
                    if ($entity == null) {
                        $className=$this->om->getRepository($entityNamespace.':'.$_REQUEST['entityClass'])->getClassName();
                        $entity =new $className();
                    }
                    //Se crea la entidad dependiendo del tipo de relacion que tenga con el fichero
                    if (!$multiple) {
                        $data = $this->createSingleFile($entity, $images[0]);
                    } elseif ($multiple) {
                        $data = $this->createMultipleFiles($entity, $images);
                    }
                    break;
                }
                //borramos los ficheros que ya no sirven
                if ($multiple) {
                    $files=$this->invokeMethod($entity, 'get', true);
                    foreach ($files as $file) {
                        if (!in_array($file, $data)) {
                            $this->invokeMethod($entity, 'remove', true, $file);
                            $this->om->remove($file);
                        }
                    }
                    $this->om->flush();
                }
                $data=$file;
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
        if ($this->invokeMethod($entity, 'get')!=null) {
            $this->om->remove($this->invokeMethod($entity, 'get'));
            $this->invokeMethod($entity, 'set', false, null);
            $this->om->flush();
        }
        $file->setPath('uploads/gallery/'.$image->getFilename());
        eval("\$file->set".$this->getInversedBy()."(\$entity);");
        $this->invokeMethod($entity, 'set', false, $file);
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

        $files=$this->invokeMethod($entity, 'get', true);

        //Asociamos los nuevos ficheros con la entidad
        foreach ($data as $new) {
            if (!in_array($new, $files->toArray())) {
                $this->invokeMethod($entity, 'add', true, $new);
                eval("\$new->set".$this->getInversedBy()."(\$entity);");
                $this->om->persist($new);
            }
        }

        $this->om->flush();

        return $data;
    }

    /**
     * Check if the fields is a collection
     * @param $entity
     * @return mixed
     */
    private function isCollection($entity)
    {
        $reflectionClass=$entity->getReflectionClass();

        return $reflectionClass->hasMethod('add'.$this->prepareString($this->fieldName));
    }

    /**
     * @param $fieldname
     * @return string
     */
    private function prepareString($fieldname)
    {
        $field=ucfirst($fieldname);

        return substr($field, 0, strlen($field) - 1);
    }

    /**
     * Invoke a method for the entity field with some parameters
     * @param $entity
     * @param $string
     * @param bool $collection
     * @param null $parameters
     * @return mixed
     */
    private function invokeMethod($entity, $string, $collection=false ,$parameters=null)
    {
        $reflectionClass = $entity->getReflectionClass();
        //Se prepara el nombre del campo
        $string= ($collection && $string!='get') ? $string.$this->prepareString($this->fieldName) : $string.ucfirst($this->fieldName);
        /** @var \ReflectionClass $reflectionClass */
        $method = $reflectionClass->getMethod($string);
        /** @var \ReflectionMethod $method */
        $data=$method->invoke($entity, $parameters);

        return $data;
    }

    /**
     * Obtiene las imagenes de la cache asociadas al campo correspondiente
     * @return array
     */
    private function getImages()
    {
        $manager = $this->orphanageManager->get('gallery');
        $images = $manager->getFiles();
        $clone = clone $images;
        $data=array();
        foreach ($images as $image) {
            if (explode('_', $image->getFilename())[0]==$this->fieldName) {
                $data[]=$image;
                $clone->files()->name($image);
                $manager->uploadFiles(array($image));
            }
        }

        return $data;
    }

    private function getInversedBy()
    {
        return $this->mappedBy== 'default'? $_REQUEST['entityClass']: ucfirst($this->mappedBy);
    }
}
