<?php

namespace Sopinet\UploadFilesBundle\Twig\Extension;


use AppBundle\Entity\File;
use Oneup\UploaderBundle\Templating\Helper\UploaderHelper;
use Oneup\UploaderBundle\Uploader\Orphanage\OrphanageManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class UploaderExtension extends \Twig_Extension
{
    protected $orphanManager;
    protected $config;

    public function __construct(OrphanageManager $orphanManager, SessionInterface $session, array $config)
    {
        $this->orphanManager = $orphanManager;
        $this->session = $session;
        $this->config = $config;
    }

    public function getName()
    {
        return 'sopinet_file_upload';
    }

    public function getFunctions()
    {
        return array(
            'sopinet_file_upload_cache_clear'   => new \Twig_Function_Method($this, 'clear'),
            'sopinet_file_upload_load_on_cache'   => new \Twig_Function_Method($this, 'load'),
        );
    }

    public function clear()
    {
        #ToDO prueba de concepto sacar a un servicio
        $manager = $this->orphanManager->get('gallery');
        $fs = new Filesystem();
        $finder = new Finder();
        if ($fs->exists($this->config['directory'].'/'.$this->session->getId())) {
            $files = $finder->ignoreUnreadableDirs()->in($this->config['directory'].'/'.$this->session->getId());
            $fs->remove($files);
        }
    }

    public function load(File $file)
    {
        #ToDO prueba de concepto sacar a un servicio
        $manager = $this->orphanManager->get('gallery');
        $fs = new Filesystem();
        $fs->copy($file->getAbsolutePath(), $this->config['directory'].'/'.$this->session->getId().'/gallery/'.$file->getName());
    }
}
