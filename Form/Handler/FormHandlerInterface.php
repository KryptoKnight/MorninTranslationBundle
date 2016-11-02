<?php

namespace Mornin\Bundle\TranslationBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Cédric Girard <c.girard@Mornin.fr>
 */
interface FormHandlerInterface
{
    /**
     * Create an element to be used as form data.
     *
     * @return mixed
     */
    public function createFormData();

    /**
     * Returns an array with options to pass to the form.
     * @param $default Form defaults params Array
     * @return array
     */
    public function getFormOptions($default);

    /**
     * Process the form and returns true if the form is valid.
     *
     * @param FormInterface $form
     * @param Request $request
     * @return boolean
     */
    public function process(FormInterface $form, Request $request);
}
