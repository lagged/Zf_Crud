## Zend Framework CRUD 

WORK IN PROGRESS

### Usage

...

    <?php
    require_once '/path/to/vendor/Zf_Crud/library/Autoload.php';
    \Lagged\Zf\Crud\Autoload::register();

    class MyController extends \Lagged\Zf\Crud\Controller
    {
        protected $model = 'My_Zend_Db_Table_Model';
        protected $title = 'My Interface';
    }

