# uploadfiles-bundle

## Instalation
1. Add this bundle to your composer.json file
  
  ```
  //composer.json
  require:{
    ....
    "sopinet/uploadfiles-bundle": "dev-master"
    ....
   }
  ```
2. Add to your AppKernel.php file

  ```
  //app/AppKernel.php
  $bundles = array(
    ....
    new Sopinet\UploadFilesBundle\SopinetUploadFilesBundle(),
    new Oneup\UploaderBundle\OneupUploaderBundle(),
    ....
   )
  ```
3. Configure oneuploader-bundle:
  1. with basic configuration: https://github.com/1up-lab/OneupUploaderBundle/blob/master/Resources/doc/index.md#step-3-configure-the-bundle
  2. And Using the Orphanage configuration(for now configure the mappings as gallery) :
https://github.com/1up-lab/OneupUploaderBundle/blob/master/Resources/doc/orphanage.md
4. Add the form type to your config, and this bundle to assetics config:

    ```
  //app/config/config.yml
  ...
  twig:
    form:
        resources:
            - 'SopinetUploadFilesBundle:Form:file.html.twig'
  assetic:
      bundles: [ SopinetUploadFilesBundle ]
  ...
  ```
5. Add the routing to your routing.yml:

    ```
  //app/config/routing.yml
  ...
  sopinet_uploadfiles:
      resource: @SopinetUploadFilesBundle/Resources/config/routing.yml
  ...
  ```
6. Create your File entity, something like this:
```
<?php
/**
 * Created by PhpStorm.
 * User: hud
 * Date: 20/05/15
 * Time: 9:56
 */
namespace AppBundle\Entity;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bridge\Doctrine\Validator\Constraints as DoctrineAssert;


/**
 * @ORM\Entity
 * @ORM\Table(name="file")
 * @DoctrineAssert\UniqueEntity("id")
 * @ORM\HasLifecycleCallbacks
 */
class File
{
    use ORMBehaviors\Timestampable\Timestampable;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $path;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }


    public function getAbsolutePath()
    {
        return null === $this->path
            ? null
            : $this->getUploadRootDir().'/'.$this->path;
    }

    public function getWebPath()
    {
        return null === $this->path
            ? null
            : $this->getUploadDir().'/'.$this->path;
    }
re
    public function getFrontPath()
    {
        return null === $this->path
            ? null
            : $this->getUploadDir().'/'.$this->path;
    }

    protected function getUploadRootDir()
    {
        // the absolute directory path where uploaded
        // documents should be saved
        return __DIR__.'/../../../web'.$this->getUploadDir();
    }

    protected function getUploadDir()
    {
        // get rid of the __DIR__ so it doesn't screw up
        // when displaying uploaded doc/image in the view.
        return '';
    }

    public function getName()
    {
        return explode('/', $this->getPath())[2];
    }

    /**
     * @ORM\PostRemove
     */
    public function deleteFile(LifecycleEventArgs $event)
    {
        $fs = new Filesystem();
        //$fs->remove($this->getAbsolutePath());
    }
}
```
## Usage
* Create your relationships between your project entities and your new entity field(respect the owning side for lifecicle callbacks and cascade):
```
/**
 * @ORM\Entity
 * @ORM\Table(name="file")
 * @DoctrineAssert\UniqueEntity("id")
 * @ORM\HasLifecycleCallbacks
 */
class File
{
    ...
    /**
     * @ORM\OneToOne(targetEntity="Application\Sopinet\UserBundle\Entity\User", mappedBy="file")
     */
    protected $user;
    
        /**
     * @ORM\ManyToOne(targetEntity="Oil", inversedBy="files")
     * @ORM\JoinColumn(name="oil_id", referencedColumnName="id")
     */
    protected $oil;

    ...
}

/**
 * Entity User
 *
 * @ORM\Table("fos_user_user")
 * @ORM\Entity(repositoryClass="Application\Sopinet\UserBundle\Entity\UserRepository")
 * @ORM\HasLifecycleCallbacks
 */
class User extends BaseUser
{
 ...
     /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\File", inversedBy="user")
     */
    protected $file;
  
 ...
}

//src/AppBundle/Entity/Oil.php
use Sopinet\UploadFilesBundle\Entity\HasFilesTrait;
/**
 * Oil
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Entity\OilRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Oil
{
    use HasFilesTrait;
    ...
        /**
     * @ORM\OneToMany(targetEntity="File", mappedBy="oil", cascade={"persist"})
     */
    protected $files;
    ...
}
```

* Add the HasFileTrait or the HasFilesTrait to your entities:
```
use Sopinet\UploadFilesBundle\Entity\HasFileTrait;
/**
 * Entity User
 *
 * @ORM\Table("fos_user_user")
 * @ORM\Entity(repositoryClass="Application\Sopinet\UserBundle\Entity\UserRepository")
 * @ORM\HasLifecycleCallbacks
 */
class User extends BaseUser
{
 ...
     use HasFileTrait;
     /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\File", mappedBy="user")
     */
    protected $file;
 ...
}

//src/AppBundle/Entity/Oil.php
use Sopinet\UploadFilesBundle\Entity\HasFilesTrait;
/**
 * Oil
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Entity\OilRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Oil
{
    use HasFilesTrait;
    ...
        /**
     * @ORM\OneToMany(targetEntity="File", mappedBy="oil", cascade={"persist"})
     */
    protected $files;
    ...
}
```
* Now you can already use this field type in your forms like this:
```
//src/AppBundle/Form/OilType.php
...
class OilType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
      $builder
        ->add('files', 'dropzone_file_gallery', array(
          'maxFiles'=> 4,//default 8
          'required' => false
        ))
...
```

* If you want to use another style or template for dropbox you can overwrite the field style as follow(this example render dropbox with default aspect):
```
//src/AppBundle/Form/OilType.php
...
class OilType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
      $builder
        ->add('files', 'dropzone_file_gallery', array(
          'maxFiles'=> 4,//default 8
          'required' => false,
          'type' => my_custom,
          'uploaderText' => 'your_custom_text',
        ))
        
//src/AppBundle/Resources/path_to/_template
{% form_theme oil_form _self %}
{% block my_custom %}
    <div class="modal-body">
        <div {{ block('widget_container_attributes') }}>
            {%- if form.parent is empty -%}
                {{ form_errors(form) }}
            {%- endif -%}
            {{- block('form_rows') -}}
            {{- form_rest(form) -}}
        </div>
    </div>
{% endblock %}

```

## Using with sonata

1. Using for one-to-one relationship, add to your config:
```
//app/config/config.yml
...
sonata_doctrine_orm_admin:
    templates:
        types:
            list:
                ...
                dropzone_file_gallery:   SopinetUploadFilesBundle:Admin:file.html.twig
            show:
                ...
                dropzone_file_gallery:   SopinetUploadFilesBundle:Admin:file.html.twig
...
```
then use as in forms

2. Using for one-to-many relationship, on add set a custom template for field
```
//AppBundle/Admin/AcmeEntityAdmin.php
...
    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        //...
        $listMapper
        ->add('files', 'dropzone_file_gallery', array(
            'template' => 'SopinetUploadFilesBundle:Admin:file_list.html.twig'
            )
        )

    }
...
```
if you want to customize sonata render you can create your own block as follow

```
//AppBundle/Admin/AcmeEntityAdmin.php
...
    public $fileUploadType='myblockName';
    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        //...
        $listMapper
        ->add('file', 'dropzone_file_gallery', array(
            'template' => 'SopinetUploadFilesBundle:Admin:file.html.twig'
            )
        )

    }
...
//mytemplate.twig
...
{% block sonata_uploadfile_myblockName %}
    //your custom preview template here
{% endblock %
...
```
 
 

## Types and styles added already on bundle

1. Types:
    
    modal: replace dropbox box with button for display a modal with dropbox box inside
    pdf_preview: replace dropbox with a pdf preview(previews for multiples files), recommend use with style_none.

2. Styles:

    style_default: just the regular dropbox style
    style_iconic: replace the dropbox box with a + icon
    style_none: non style
    
3. Types sonata preview:

    pdf: same as pdf preview only work with one to one entity to file relationship
    to use add
    ```
    //AppBundle/Admin/AcmeEntityAdmin.php
    ...
        public $yourFieldNameUploadType='pdf';
    ...

    ```
    