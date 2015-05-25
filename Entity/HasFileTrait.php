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
     * Track preUpdate File
     * @param PreUpdateEventArgs $event
     *
     * @ORM\PreUpdate
     */
    public function trackPreUpdateFile(PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField('file') ) {
            $this->previousFile = $this->file;
        }

    }
    /**
     * Funcion que elimina las entidades de tipo file que se eliminan
     * @param LifecycleEventArgs $event
     *
     * @ORM\PostUpdate
     */
    public function removeUnusedFile(LifecycleEventArgs $event)
    {
        if (!empty($this->previousFile)) {
            $em = $event->getEntityManager();
            $em->remove($this->previousFile);
            $this->previousFile = false;
            $reflect = new \ReflectionClass($this);
            $class=$reflect->getShortName();
            eval("\$this->file->set".$class."(\$this);");
            $em->persist($this->file);
            $em->flush();
        }
    }
}