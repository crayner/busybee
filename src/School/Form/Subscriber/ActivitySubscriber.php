<?php
namespace App\School\Form\Subscriber;

use App\Calendar\Util\CalendarManager;
use App\Entity\ExternalActivity;
use App\Entity\FaceToFace;
use App\Entity\Roll;
use Hillrange\Form\Type\EntityType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ActivitySubscriber implements EventSubscriberInterface
{
    /**
     * @var CalendarManager
     */
    private $calendarManager;

    /**
     * ActivitySubscriber constructor.
     * @param CalendarManager $calendarManager
     */
    public function __construct(CalendarManager $calendarManager)
    {
        $this->calendarManager = $calendarManager;
    }

	/**
	 * @return array
	 */
	public static function getSubscribedEvents()
	{
		// Tells the dispatcher that you want to listen on the form.pre_submit
		// event and that the preSubmit method should be called.
		return array(
			FormEvents::PRE_SUBMIT => 'preSubmit',
            FormEvents::PRE_SET_DATA => 'preSetData',
		);
	}

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        if ($event->getForm()->getData() instanceof ExternalActivity)
        {
            $data = $event->getData();
            if (!isset($data['terms']))
            {
                $terms = $this->calendarManager->getCurrentCalendar()->getTerms();
                foreach($terms->getIterator() as $term)
                    $data['terms'][] = $term->getId();
            }
            if (!isset($data['calendarGrades']))
            {
                $cgs = $this->calendarManager->getCurrentCalendar()->getCalendarGrades();
                foreach($cgs->getIterator() as $cg)
                    $data['calendarGrades'][] = $cg->getId();
            }
            $event->setData($data);
        }

        if ($event->getForm()->getData() instanceof FaceToFace)
        {
            if ($event->getForm()->getData()->isReportable())
            {
                $data = $event->getData();
                foreach($data['students'] as $q=>$w)
                    $data['students'][$q]['classReportable'] = '1';
                $event->setData($data);
            }
        }
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        if ($data instanceof Roll)
        {
            $form->add('nextRoll', EntityType::class,
                [
                    'class' => Roll::class,
                    'choice_label' => 'fullName',
                    'label' => 'activity.next_roll.label',
                ]
            );
        }
    }
}