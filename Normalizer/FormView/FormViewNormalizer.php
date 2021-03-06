<?php

namespace Videni\Bundle\RapidGraphQLBundle\Normalizer\FormView;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Limenius\Liform\FormUtil;
use JMS\Serializer\Context;

class FormViewNormalizer
{
    private $formViewNormalizerResolver;

    public function __construct(FormViewNormalizerResolver $formViewNormalizerResolver)
    {
        $this->formViewNormalizerResolver = $formViewNormalizerResolver;
    }

    public function normalize(FormInterface $form, FormView $formView, Context $context)
    {
        $formConfig = $form->getConfig();
        if($formConfig->hasOption('view_normalizer')) {
            //this form has custom view normalizer
            $viewNormalizer = $formConfig->getOption('view_normalizer');
            if(\is_callable($viewNormalizer)) {
                return \call_user_func($viewNormalizer, $form, $formView, $context);
            }
        }

        $ancestries = FormUtil::typeAncestry($form);
        if(empty($formView->children)) {
            return $this->getTypedValue($formView, $ancestries, $form);
        }

        $normalizer = $this->formViewNormalizerResolver->resolve($form, $formView, $ancestries);
        if ($normalizer) {
            return $normalizer->normalize($form, $formView, $context);
        }

        // Force serialization as {} instead of []
        $data = array();
        foreach ($formView->children as $name => $child) {
            $childValues = $this->normalize($form[$name], $child, $context);
            if (!$this->isEmpty($childValues)) {
                $data[$name] = $childValues;
            }
        }

        return $data;
    }

    /**
     * Skip empty values(null, '', [], empty ArratCollection) because
     * https://github.com/erikras/redux-form/issues/2149
     *
     * @param  mix  $value
     * @return boolean
     */
    private function isEmpty($value): bool
    {
        return $value === null || $value === '' || $value === [] || ($value instanceof ArrayCollection && $value->isEmpty());
    }

     /**
     * Symfony form transforms scalar to string, but we need to keep it is,
     * check normToView method of Form class for more details.
     *
     * @param FormView $formView
     * @return mix
     */
    private function getTypedValue(FormView $formView, array $ancestries, $form)
    {
        // handle separatedly the case with checkboxes, so the result is
        // true/false instead of 1/0
        if (isset($formView->vars['checked'])) {
            return $formView->vars['checked'];
        }

        if (in_array('choices', $ancestries)) {
            return $formView->vars['value'];
        }

        // don't convert string to numeric for autocomplete and select
        if (isset($formView->vars['widget_options']) &&
            isset($formView->vars['widget_options']['autocomplete_alias'])) {
            return $formView->vars['value'];
        }

        $value = $formView->vars['value'];
        if (in_array('text', $ancestries)) {
            return $value;
        }

        // A simple way to convert string to numeric
        return is_numeric($value)? $value + 0: $value;
    }
}
