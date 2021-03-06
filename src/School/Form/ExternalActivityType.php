<?php
namespace App\School\Form;

use App\Calendar\Util\CalendarManager;
use App\Core\Type\SettingChoiceType;
use App\Entity\Activity;
use App\Entity\CalendarGrade;
use App\Entity\Term;
use App\People\Util\StudentManager;
use App\School\Form\Subscriber\ActivitySubscriber;
use App\School\Util\ActivityManager;
use Doctrine\ORM\EntityRepository;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Hillrange\Form\Type\CollectionType;
use Hillrange\Form\Type\EntityType;
use Hillrange\Form\Type\TextType;
use Hillrange\Form\Type\ToggleType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExternalActivityType extends AbstractType
{
    /**
     * @var ActivityManager
     */
    private $activityManager;

    /**
     * @var ActivitySubscriber
     */
    private $activitySubscriber;
    /**
     * ExternalActivityType constructor.
     * @param StudentManager $studentManager
     */
    public function __construct(ActivityManager $activityManager, ActivitySubscriber $activitySubscriber)
    {
        $this->activityManager = $activityManager;
        $this->activitySubscriber = $activitySubscriber;
    }

    /**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
        $grades = $options['data']->getCalendarGrades()->toArray();
        foreach($grades as $q=>$w)
            $grades[$q] = $w->getId();

		$builder
            ->add('name', TextType::class,
                [
                    'label' => 'external_activity.name.label',
                    'help' => 'external_activity.name.help',
                ]
            )
            ->add('code', TextType::class,
                [
                    'label' => 'external_activity.code.label',
                    'help' => 'external_activity.code.help',
                ]
            )
            ->add('students', CollectionType::class,
                [
                    'label' => 'external_activity.students.label',
                    'allow_add' => true,
                    'allow_delete' => true,
                    'entry_type' => ExternalActivityStudentsType::class,
                    'entry_options' => [
                        'student_list' => $this->activityManager->getPossibleStudents($options['data']),
                    ],
                    'route' => 'external_activity_student_manage',
                    'button_merge_class' => 'btn-sm',
                ]
            )
            ->add('provider', SettingChoiceType::class,
                [
                    'label' => 'external_activity.provider.label',
                    'setting_name' => 'activity.provider.type',
                    'placeholder' => 'external_activity.provider.placeholder',
                ]
            )
            ->add('tutors', CollectionType::class,
                [
                    'entry_type' => ActivityTutorType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'allow_up' => true,
                    'allow_down' => true,
                    'sort_manage' => true,
                    'route' => 'external_activity_tutor_manage',
                    'button_merge_class' => 'btn-sm',
                ]
            )
            ->add('registration', ToggleType::class,
                [
                    'label' => 'external_activity.registration.label',
                    'help' => 'external_activity.registration.help',
                ]
            )
            ->add('type', SettingChoiceType::class,
                [
                    'label' => 'external_activity.type.label',
                    'placeholder' => 'external_activity.type.placeholder',
                    'setting_name' => 'external.activity.type.list',
                ]
            )
            ->add('active', ToggleType::class,
                [
                    'label' => 'external_activity.active.label',
                ]
            )
            ->add('terms', EntityType::class,
                [
                    'label' => 'external_activity.terms.label',
                    'help' => 'external_activity.terms.help',
                    'class' => Term::class,
                    'choice_label' => 'name',
                    'required' => false,
                    'multiple' => true,
                    'expanded' => true,
                    'attr' => [
                        'class' => 'form-control-sm',
                    ],
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('t')
                            ->leftJoin('t.calendar', 'c')
                            ->where('c = :calendar')
                            ->setParameter('calendar', CalendarManager::getCurrentCalendar())
                            ->orderBy('t.firstDay', 'ASC')
                        ;
                    },
                ]
            )
            ->add('calendarGrades', EntityType::class,
                [
                    'label' => 'external_activity.calendar_grade.label',
                    'help' => 'external_activity.calendar_grade.help',
                    'class' => CalendarGrade::class,
                    'choice_label' => 'grade',
                    'required' => false,
                    'multiple' => true,
                    'expanded' => true,
                    'attr' => [
                        'class' => 'form-control-sm',
                    ],
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('g')
                            ->leftJoin('g.calendar', 'c')
                            ->where('c = :calendar')
                            ->setParameter('calendar', CalendarManager::getCurrentCalendar())
                            ->orderBy('g.sequence', 'ASC')
                            ;
                    },
                ]
            )
            ->add('maxParticipants', NumberType::class,
                [
                    'label' => 'external_activity.max_participants.label',
                ]
            )
            ->add('description', CKEditorType::class,
                [
                    'label' => 'external_activity.description.label',
                    'attr'     => [
                        'rows' => 4,
                    ],
                    'required' => false,
                ]
            )
            ->add('payment', NumberType::class,
                [
                    'label' => 'external_activity.payment.label',
                    'scale' => 2,
                ]
            )
            ->add('paymentType', SettingChoiceType::class,
                [
                    'label' => 'external_activity.payment_type.label',
                    'setting_name' => 'activity.payment.type',
                ]
            )
            ->add('paymentFirmness', SettingChoiceType::class,
                [
                    'label' => 'external_activity.payment_firmness.label',
                    'setting_name' => 'activity.payment.firmness',
                ]
            )
            ->add('activitySlots', CollectionType::class,
                [
                    'label' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'entry_type' => ActivitySlotType::class,
                    'route' => 'external_activity_slot_manage',
                ]
            )
        ;
		$builder->addEventSubscriber($this->activitySubscriber);
	}

	/**
	 * {@inheritdoc}
	 */
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(
			[
				'data_class'         => Activity::class,
				'translation_domain' => 'School',
			]
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getBlockPrefix()
	{
		return 'activity';
	}
}
