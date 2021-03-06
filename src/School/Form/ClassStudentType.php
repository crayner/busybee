<?php
namespace App\School\Form;

use App\Core\Type\SettingChoiceType;
use App\Entity\Activity;
use App\Entity\ActivityStudent;
use App\Entity\ActivityTutor;
use App\Entity\Person;
use App\Entity\Student;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Hillrange\Form\Type\EntityType;
use Hillrange\Form\Type\HiddenEntityType;
use Hillrange\Form\Type\ToggleType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClassStudentType extends AbstractType
{
    /**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
            ->add('classReportable', ToggleType::class,
                [
                    'label' => 'activity_student.class_reportable.label',
                    'help' => 'activity_student.class_reportable.help',
                    'button_merge_class' => 'btn-sm',
                ]
            )
            ->add('activity', HiddenEntityType::class,
                [
                    'class' => Activity::class,
                ]
            )
            ->add('student', EntityType::class,
                [
                    'class' => Student::class,
                    'choice_label' => 'fullName',
                    'choice_value' => 'id',
                    'label' => 'activity_student.student.label',
                    'help' => 'activity_student.student.help',
                    'placeholder' => 'activity_student.student.placeholder',
                    'choices' => $options['student_list'],
                    'attr' => [
                        'class' => 'form-control-sm',
                    ],
                ]
            )
            ->add('id', HiddenType::class,
                [
                    'attr' => [
                        'class' => 'removeElement',
                    ],
                ]
            )
        ;
	}

	/**
	 * {@inheritdoc}
	 */
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(
			[
				'data_class'         => ActivityStudent::class,
				'translation_domain' => 'School',
			]
		);
		$resolver->setRequired(
		    [
		        'student_list',
            ]
        );
	}

	/**
	 * {@inheritdoc}
	 */
	public function getBlockPrefix()
	{
		return 'class_student';
	}
}
