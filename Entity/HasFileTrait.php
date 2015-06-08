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
            $reflect = new \ReflectionClass($this);
            $class=$reflect->getShortName();
            $files= null;
            eval("\$files = \$em->getRepository('AppBundle:File')->findBy".$class."(\$this);");
            if ($files!=null) {
                foreach ($files as $file) {
                    if ($this->file!=$file) {
                        $em->remove($file);
                        $em->flush($file);
                    }
                }
            }
            eval("\$this->file->set".$class."(\$this);");
            $em->persist($this->file);
            $em->flush($this->file);
        }
    }

    /**
     * Funcion que añade la entidad de tipo file cuando se crea
     * @param LifecycleEventArgs $event
     *
     * @ORM\PostPersist
     */
    public function addNewFile(LifecycleEventArgs $event)
    {
        if ($this->file != null){
            $em = $event->getEntityManager();
            $reflect = new \ReflectionClass($this);
            $class=$reflect->getShortName();
            eval("\$this->file->set".$class."(\$this);");
            $em->persist($this->file);
            $em->flush($this->file);

        }
    }
}