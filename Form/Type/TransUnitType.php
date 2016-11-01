<?php

namespace Mornin\Bundle\TranslationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * TransUnit form type.
 *
 * @author Cédric Girard <c.girard@Mornin.fr>
 */
class TransUnitType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('key', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
            'label' => 'translations.key',
        ));
        $builder->add('domain', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
            'label'   => 'translations.domain',
            'data'    => isset($options['domains'][1]) ? $options['domains'][1] : '',
            'choices' => array_combine($options['domains'][0], $options['domains'][0]),
        ));
         $builder->add('translations', 'Symfony\Component\Form\Extension\Core\Type\CollectionType', array(
            'entry_type'     => 'Mornin\Bundle\TranslationBundle\Form\Type\TranslationType',
            'label'    => 'translations.page_title',
            'required' => false,
            'entry_options'  => array(
                'data_class' => $options['translation_class'],
            ),
        ));
        $builder->add('save', 'Symfony\Component\Form\Extension\Core\Type\SubmitType', array(
            'label' => 'translations.save',
        ));
        $builder->add('save_add', 'Symfony\Component\Form\Extension\Core\Type\SubmitType', array(
            'label' => 'translations.save_add',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'         => null,
            'domains'            => array('messages'),
            'translation_class'  => null,
            'translation_domain' => 'MorninTranslationBundle',
            'always_empty' => true,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    public function getBlockPrefix()
    {
         return 'lxk_trans_unit';
    }

}
