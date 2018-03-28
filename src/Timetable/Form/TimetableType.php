<?php
namespace App\Timetable\Form;

use App\Entity\Calendar;
use App\Entity\CalendarGrade;
use App\Entity\Timetable;
use App\Timetable\Form\Subscriber\TimetableSubscriber;
use App\Timetable\Validator\TimetableGrades;
use Doctrine\ORM\EntityRepository;
use Hillrange\Form\Type\CollectionType;
use Hillrange\Form\Type\EntityType;
use Hillrange\Form\Type\TextType;
use Hillrange\Form\Type\ToggleType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TimetableType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $calId = ! empty($options['data']) ? $options['data'] : null;
        $calId = $calId ? $calId->getCalendar() : null;
        $calId = $calId ? $calId->getId() : null;

        $builder
            ->add('name', TextType::class,
                [
                    'label' => 'timetable.name.label',
                ]
            )
            ->add('code', TextType::class,
                [
                    'label' => 'timetable.code.label',
                ]
            )
            ->add('active', ToggleType::class,
                [
                    'label' => 'timetable.active.label',
                ]
            )
            ->add('calendar', EntityType::class,
                [
                    'label' => 'timetable.calendar.label',
                    'class' => Calendar::class,
                    'choice_label' => 'name',
               ]
            )
            ->add('calendarGrades', EntityType::class,
                [
                    'label' => 'timetable.grades.label',
                    'help' => 'timetable.grades.help',
                    'class' => CalendarGrade::class,
                    'choice_label' => 'grade',
                    'required' => false,
                    'multiple' => true,
                    'expanded' => true,
                    'attr' => [
                        'class' => 'form-control-sm',
                    ],
                    'query_builder' => function (EntityRepository $er) use ($calId) {
                        return $er->createQueryBuilder('g')
                            ->leftJoin('g.calendar', 'c')
                            ->where('c.id = :calendar_id')
                            ->setParameter('calendar_id', $calId)
                            ->orderBy('g.sequence', 'ASC')
                        ;
                    },
                    'constraints' => [
                        new TimetableGrades(),
                    ],
                ]
            )
            ->add('days', CollectionType::class,
                [
                    'entry_type' => TimetableDayType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'sort_manage' => true,
                    'allow_down' => true,
                    'allow_up' => true,
                    'entry_options' => [
                        'timetable_id' => $options['data']->getId(),
                    ],
                ]
            )
            ->add('columns', CollectionType::class,
                [
                    'entry_type' => TimetableColumnType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'sort_manage' => true,
                    'allow_down' => true,
                    'allow_up' => true,
                ]
            )
            ->addEventSubscriber($this->timetableSubscriber)
        ;

    }

    /**
     * @return null|string
     */
    public function getBlockPrefix()
    {
        return 'tt';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'translation_domain' => 'Timetable',
                'data_class' => Timetable::class,
                'attr' => [
                    'novalidate' => '',
                ],
            ]
        );
    }

    /**
     * @var TimetableSubscriber
     */
    private $timetableSubscriber;

    /**
     * TimetableType constructor.
     * @param TimetableSubscriber $timetableSubscriber
     */
    public function __construct(TimetableSubscriber $timetableSubscriber)
    {
        $this->timetableSubscriber = $timetableSubscriber;
    }
}