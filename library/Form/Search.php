<?php
/**
 * EasyBib Copyright 2008-2011
 * Modifying, copying, of code contained herein that is not specifically
 * authorized by Imagine Easy Solutions LLC ("Company") is strictly prohibited.
 * Violators will be prosecuted.
 *
 * This restriction applies to proprietary code developed by EasyBib. Code from
 * third-parties or open source projects may be subject to other licensing
 * restrictions by their respective owners.
 *
 * Additional terms can be found at http://www.easybib.com/company/terms
 *
 * PHP Version 5
 *
 * @category Management
 * @package  Lagged\Zf\Crud
 * @author   Yvan Volochine <yvan.volochine@gmail.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  GIT: $Id$
 * @link     http://lagged.biz
 */

namespace Lagged\Zf\Crud\Form;

/**
 * A basic jump-to-page form for the {@link \Lagged\Zf\Crud\Controller}
 *
 * @category Management
 * @package  Lagged\Zf\Crud\Form
 * @author   Yvan Volochine <yvan.volochine@gmail.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: @package_release@
 * @link     http://lagged.biz
 */
class Search extends \Zend_Form
{

    /**
     * var Zend_Form_Element_Select $columns ''
     */
    public $columns;


    /**
     * init
     *
     * @return void
     * @TODO Validators
     */
    public function init()
    {
        $this->setMethod('post');

        $this->addElement(
            'text', 'search', array(
                'label'    => 'Search',
                'required' => true,
                /*
                'decorators' => array(
                    'ViewHelper',
                    'Errors',
                    'Description',
                    array('HtmlTag',array('tag' => 'span')),
                    array('Label',array('tag' => 'span','class' =>'label')),
                    array(
                        array('row' => 'HtmlTag'),
                        array('tag' => 'div', 'class', 'element')
                    )
                ),
                */
                /*
                'validators' => array(
                    array(
                        'validator' => 'Alnum',
                        'options'   => array(0, 20)
                    )
                )*/
            )
        );

        $this->addElement(
            'checkbox', 'exact', array(
                'label'  => 'exact'
            )
        );

        $this->columns = new \Zend_Form_Element_Select('columns');
        $this->columns->setLabel('into')->setRequired(true)
            ->setRegisterInArrayValidator(false);
        $this->addElement($this->columns);

        $this->addElement(
            'submit', 'submit', array(
                'ignore' => true,
                'label'  => 'Go',
            )
        );

        /**
         * @desc Apply Twitter Bootstrap to all elements.
         */
        \EasyBib_Form_Decorator::setFormDecorator(
            $this,
            \EasyBib_Form_Decorator::BOOTSTRAP,
            'submit'
        );
    }

}
