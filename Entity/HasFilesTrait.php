<?php
namespace Sopinet\UploadFilesBundle\Entity;
use AppBundle\Entity\File;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

/**
 * Created by PhpStorm.
 * User: hud
 * Date: 5/05/15
 * Time: 12:54
 */
trait HasFilesTrait
{

    /**
     * Obtiene el nombre de la clase(teniendo en cuenta el namespace)
     * @return string
     */
    public function getClassName()
    {
        $reflect = new \ReflectionClass($this);

        return $reflect->getName();
    }

    /**
     * Obtiene el nombre de la clase(teniendo en cuenta el namespace)
     * @return string
     */
    public function getShortClassName()
    {
        $reflect = new \ReflectionClass($this);

        return $reflect->getShortName();
    }


    /**
     * Obtiene el nombre de la clase(teniendo en cuenta el namespace)
     * @return string
     */
    public function getNamespace()
    {
        $reflect = new \ReflectionClass($this);

        return $reflect->getNamespaceName();
    }


    /**
     * Obtiene el nombre de la clase(teniendo en cuenta el namespace)
     * @return string
     */
    public function getReflectionClass()
    {
        return new \ReflectionClass($this);
    }


    /**
     * Funcion que añade las entidades de tipo file cuando se crea
     * @param LifecycleEventArgs $event
     *
     * @ORM\PostPersist
     */
    public function addNewFiles(LifecycleEventArgs $event)
    {

        $em = $event->getEntityManager();
        $reflect = new \ReflectionClass($this);
        $class=$reflect->getShortName();
        if ($this->getFiles() != null ){
            foreach ($this->getFiles() as $file) {
                eval("\$file->set".$class."(\$this);");
                $em->persist($file);
                $em->flush($file);
            }
        }

    }
}