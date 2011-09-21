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
class JumpTo extends \Zend_Form
{
    /**
     * init
     *
     * @return void
     */
    public function init()
    {
        $this->setMethod('post');

        $this->addElement(
            'text', 'p', array(
                'label'    => 'Jump to page',
                'required' => true
            )
        );

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
