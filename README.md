## Zend Framework CRUD 

WORK IN PROGRESS

### Requirements

 * [EasyBib_Form_Decorator][deco]

[deco]: https://github.com/easybib/EasyBib_Form_Decorator#readme

 * [Twitter Bootstrap][twitter bootstrap] (version 3)
 
 [twitter bootstrap]: https://github.com/twitter/bootstrap/tags

 Use your own bootstrap css files within your setup.
 See twitter bootstrap homepage (tags) for versions.

### Installation

Add `lagged/Zf_Crud` to your `composer.json`!

### Usage

...

    <?php

    class MyController extends \Lagged\Zf\Crud\Controller
    {
        protected $model      = 'My_Zend_Db_Table_Model';
        protected $title      = 'My Interface';
        // Optional
        protected $dbAdapter  = 'db';
        protected $count      = 15;
        protected $bulkDelete = true;
    }

