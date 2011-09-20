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
 * @author   Till Klampaeckel <till@lagged.biz>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  GIT: $Id$
 * @link     http://lagged.biz
 */

namespace Lagged\Zf\Crud\Form;

/**
 * A basic form for the {@link \Lagged\Zf\Crud\Controller}
 *
 * @category Management
 * @package  Lagged\Zf\Crud\Form
 * @author   Till Klampaeckel <till@lagged.biz>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: @package_release@
 * @link     http://lagged.biz
 */
class Confirm extends \Zend_Form
{
    /**
     * init
     * Builds a confirm form for {@link \Lagged\Zf\Crud\Controller::deleteAction()}.
     *
     * @return void
     */
    public function init()
    {
        $this->setMethod('post');

        $confirm = new \Zend_Form_Element_Select('confirm');
        $confirm->setLabel('Please confirm')
            ->setRequired(true)
            ->setMultiOptions(array('no' => 'No', 'yes' => 'Yes'))
            ->addValidator('NotEmpty', true);

        $this->addElement($confirm);

        $this->addElement(
            'submit', 'submit', array(
                'ignore' => true,
                'label'  => 'I confirm!',
                'class'  => 'btn danger',
            ) // FIXME: class doesn't work yet
        );

        /**
         * @desc Apply Twitter Bootstrap to all elements.
         */
        \EasyBib_Form_Decorator::setFormDecorator(
            $this,
            \EasyBib_Form_Decorator::BOOTSTRAP,
            'submit',
            'cancel'
        );
    }

}
