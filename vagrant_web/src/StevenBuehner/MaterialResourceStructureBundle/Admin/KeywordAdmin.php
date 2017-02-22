<?php
namespace StevenBuehner\MaterialResourceStructureBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use StevenBuehner\MaterialResourceStructureBundle\Entity\Keyword;

class KeywordAdmin extends AbstractAdmin {
	protected function configureFormFields(FormMapper $formMapper) {
		$formMapper
			->add('title', 'text')
			->add('parent', 'entity', [
				'class'        => Keyword::class,
				'choice_label' => 'title',
			]);

	}

	protected function configureDatagridFilters(DatagridMapper $datagridMapper) {
		$datagridMapper
			->add('title');
			//->add('created', 'datetime');
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper->addIdentifier('title');
	}
}