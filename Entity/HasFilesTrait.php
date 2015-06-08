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
     * Track preUpdate File
     * @param PreUpdateEventArgs $event
     *
     * @ORM\PreUpdate
     */
    public function trackPreUpdateFiles(PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField('files') ) {
            $this->previousFiles = $this->files;
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
        $reflect = new \ReflectionClass($this);
        $class=$reflect->getShortName();
        $em = $event->getEntityManager();
        $files=null;
        if ($this->files) {
            foreach ($this->files as $file) {
                eval("\$file->set".$class."(\$this);");
                $em->persist($file);
                $em->flush($file);
            }
        }
        eval("\$files = \$em->getRepository('AppBundle:File')->findBy".$class."(\$this);");
        if ($files!=null) {
            foreach ($files as $file) {
                if ($this->files==null) {
                    $em->remove($file);
                } elseif(!in_array($file, $this->files->toArray())) {
                    $em->remove($file);
                }
            }
        }
        $this->previousFiles = false;
    }

    public function setFiles($files)
    {
        if ($files instanceof File) {
            $this->files = array($files);
        } else {
            $this->files = $files;

        }

        return $this;
    }
}