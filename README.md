## Zend Framework CRUD 

WORK IN PROGRESS

### Requirements

 * [EasyBib_Form_Decorator][deco]

[deco]: https://github.com/easybib/EasyBib_Form_Decorator#readme

 * [Twitter Bootstrap][twitter bootstrap] (used version: 2.2.1)
 
 [twitter bootstrap]: https://github.com/twitter/bootstrap/tags

 Use your own bootstrap css files or enable $bootstrapIntegration = true within setup.
 It will include http://twitter.github.com/bootstrap/assets/css/bootstrap.css then.
 But we cannot guarantee, that this version will match with the version we use (2.2.1).

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
        protected $bootstrapIntegration = true;
    }

