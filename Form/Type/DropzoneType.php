<?php
/**
 * Created by PhpStorm.
 * User: hud
 * Date: 20/05/15
 * Time: 10:49
 */
namespace Sopinet\UploadFilesBundle\Form\Type;

use Doctrine\Common\Persistence\ObjectManager;
use Oneup\UploaderBundle\Templating\Helper\UploaderHelper;
use Oneup\UploaderBundle\Uploader\Orphanage\OrphanageManager;
use Sopinet\UploadFilesBundle\Form\DataTransformer\SessionFilesToEntitiesTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class DropzoneType extends AbstractType
{
    /**
     * @var UploaderHelper
     */
    private $uploaderHelper;
    /**
     * @var OrphanageManager
     */
    private $orphanageManager;
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function __construct(UploaderHelper $uploaderHelper, ObjectManager $objectManager, OrphanageManager $orphanageManager)
    {
        $this->uploaderHelper = $uploaderHelper;
        $this->orphanageManager = $orphanageManager;
        $this->objectManager = $objectManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new SessionFilesToEntitiesTransformer($this->objectManager, $this->orphanageManager);
        $builder->addModelTransformer($transformer);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'attr' => array(
                'action' => $this->uploaderHelper->endpoint('gallery'),
                'class' => 'dropzone'
            ),
            'maxFiles' => 8,
            'type' => 'form_widget',
            'btnClass' => 'btn btn-info btn-lg',
            'btnText' => 'Files',
            'uploaderText' => 'Drop files here to upload',
            'style_type' => 'style_default'
        ));
    }

    /**
     * Pass the image URL to the view
     *
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (array_key_exists('maxFiles', $options)) {
            $view->vars['maxFiles'] = $options['maxFiles'];
        }
        if (array_key_exists('type', $options)) {
            $view->vars['type'] = $options['type'];
        }
        if (array_key_exists('btnClass', $options)) {
            $view->vars['btnClass'] = $options['btnClass'];
        }
        if (array_key_exists('btnText', $options)) {
            $view->vars['btnText'] = $options['btnText'];
        }
        if (array_key_exists('uploaderText', $options)) {
            $view->vars['uploaderText'] = $options['uploaderText'];
        }
        if (array_key_exists('attr', $options)) {
            $view->vars['attr'] = $options['attr'];
            if (!isset($view->vars['attr']['action'])) {
                $view->vars['attr']['action'] = $this->uploaderHelper->endpoint('gallery');
            }
        }
        if (array_key_exists('style_type', $options)) {
            $view->vars['style_type'] = $options['style_type'];
        }

    }

    public function getName()
    {
        return 'dropzone_file_gallery';
    }
}