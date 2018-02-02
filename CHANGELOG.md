# uploadfiles-bundle

## 1.4
- Added cropper option for from type use 
```
//For circle cropper style
            $formMapper
            ->add('homeFile', 'dropzone_file_gallery', array(
                'maxFiles'=> 1,//default 8
                'required' => true,
                'type'=> 'crop',
                'style_type'=>'style_rounded_cropper',
                'mappedBy' => 'homeAppImage',
                'uploaderText' => 'Arrastra aquí la imagen o click para subirla'
            ))
//For normal cropper style
            $formMapper
            ->add('homeFile', 'dropzone_file_gallery', array(
                'maxFiles'=> 1,//default 8
                'required' => true,
                'type'=> 'crop',
                'style_type'=>'style_cropper',
                'mappedBy' => 'homeAppImage',
                'uploaderText' => 'Arrastra aquí la imagen o click para subirla'
            ))
            
```