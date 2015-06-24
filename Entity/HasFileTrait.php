<?php
namespace Sopinet\UploadFilesBundle\Entity;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

/**
 * Created by PhpStorm.
 * User: hud
 * Date: 5/05/15
 * Time: 12:54
 */
trait HasFileTrait
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
     * Funcion que añade la entidad de tipo file cuando se crea
     * @param LifecycleEventArgs $event
     *
     * @ORM\PostPersist
     */
    public function addNewFile(LifecycleEventArgs $event)
    {
        if ($this->file != null) {
            $em = $event->getEntityManager();
            $reflect = new \ReflectionClass($this);
            $class=$reflect->getShortName();
            eval("\$this->file->set".$class."(\$this);");
            $em->persist($this->file);
            $em->flush($this->file);
        }
    }
}