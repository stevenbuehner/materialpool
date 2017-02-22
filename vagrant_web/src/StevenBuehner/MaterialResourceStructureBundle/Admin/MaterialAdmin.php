<?php

namespace StevenBuehner\MaterialResourceStructureBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class MaterialAdmin extends AbstractAdmin
{
    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
            ->add('title')
            ->add('createdByBot')
            ->add('relevance')
            ->add('deleted')
            ->add('author')
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
			->addIdentifier('title', 'text')
            ->add('createdByBot', 'boolean')
            ->add('relevance')
            ->add('deleted', 'boolean')
            ->add('author')
            ->add('resourcenBegrenzung')
            ->add('_action', null, array(
                'actions' => array(
                    'show' => array(),
                    'edit' => array(),
                    'delete' => array(),
                )
            ))
        ;
    }

    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('title')
            ->add('createdByBot')
            ->add('relevance')
            ->add('deleted')
            ->add('author')
            ->add('resourcenBegrenzung')
        ;
    }

    /**
     * @param ShowMapper $showMapper
     */
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
            ->add('title')
            ->add('createdByBot')
            ->add('relevance')
            ->add('deleted')
            ->add('author')
            ->add('resourcenBegrenzung')
        ;
    }
}
