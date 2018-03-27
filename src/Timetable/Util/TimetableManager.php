<?php
namespace App\Timetable\Util;

use App\Calendar\Util\CalendarManager;
use App\Core\Manager\MessageManager;
use App\Core\Manager\TabManager;
use App\Core\Manager\TabManagerInterface;
use App\Entity\Timetable;
use App\Entity\TimetableDay;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Yaml\Yaml;

class TimetableManager extends TabManager
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var CalendarManager
     */
    private $calendarManager;

    /**
     * @var MessageManager
     */
    private $messageManager;

    /**
     * @var string
     */
    private $status = 'default';

    /**
     * @var RequestStack
     */
    private $stack;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * TimetableManager constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager,
                                CalendarManager $calendarManager,
                                MessageManager $messageManager,
                                RequestStack $stack,
                                RouterInterface $router
    )
    {
        $this->entityManager = $entityManager;
        $this->calendarManager = $calendarManager;
        $messageManager->setDomain('Timetable');
        $this->messageManager = $messageManager;
        $this->stack = $stack;
        $this->router = $router;
    }

    /**
     * @var null|Timetable
     */
    private $timetable;

    /**
     * @param $id
     */
    public function find($id): Timetable
    {
        $this->setTimetable($this->entityManager->getRepository(Timetable::class)->find($id));
        return $this->getTimetable(true);
    }

    /**
     * @return Timetable|null
     */
    public function getTimetable(bool $notNull = false): ?Timetable
    {
        if ($notNull && is_null($this->timetable))
        {
            $this->setTimetable(new Timetable());
            $this->timetable->setCalendar($this->calendarManager->getCurrentCalendar());
        }

        return $this->timetable;
    }

    /**
     * @param Timetable|null $timetable
     * @return TimetableManager
     */
    public function setTimetable(?Timetable $timetable): TimetableManager
    {
        $this->timetable = $timetable;

        return $this;
    }

    /**
     * @param Timetable $tt
     * @return string
     */
    public function displayGrades(Timetable $tt): string
    {
        $result = '';
        foreach($tt->getCalendarGrades()->getIterator() as $grade)
            $result .= $grade->getGrade() . ', ';

        return trim($result, ', ');
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * @return bool
     */
    public function isTimetable(): bool
    {
        if ($this->getTimetable()->getId() > 0)
            return true;
        return false;
    }

    /**
     * @return array
     */
    public function getTabs(): array
    {
        return Yaml::parse("
details:
    label: timetable.details.tab
    include: Timetable/details.html.twig
    translation: Timetable
days:
    label: timetable.days.tab
    include: Timetable/days.html.twig
    translation: Timetable
    display: isTimetable
");

    }

    /**
     * @return MessageManager
     */
    public function getMessageManager(): MessageManager
    {
        return $this->messageManager;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param $id
     */
    public function manageTTDay($id)
    {
        if (empty($id) || $id == 'ignore')
        {
            $this->setStatus('info');
            return;
        }

        $day = $this->getEntityManager()->getRepository(TimetableDay::class)->find($id);

        if (! $day instanceof TimetableDay) {
            $this->messageManager->add('danger', 'timetable.day.missing.message');
            $this->setStatus('danger');
            return;
        }

        if (! $day->canDelete()) {
            $this->messageManager->add('warning', 'timetable.day.remove.restricted', ['%{day}' => $day->getName()]);
            $this->setStatus('warning');
            return;
        }

        $this->getTimetable()->removeDay($day);
        $this->getEntityManager()->remove($day);
        $this->getEntityManager()->persist($this->getTimetable());
        $this->getEntityManager()->flush();

        $this->messageManager->add('success', 'timetable.day.removed.message', ['%{day}' => $day->getName()]);
        $this->setStatus('success');
        return;
    }

    /**
     * @param string $status
     * @return TimetableManager
     */
    public function setStatus(string $status): TimetableManager
    {
        $this->status = $status;
        return $this;
    }
    /**
     * @return string
     */
    public function getResetScripts(): string
    {
        $request = $this->stack->getCurrentRequest();

        $xx = "manageCollection('" . $this->router->generate("timetable_day_manage", ["id" => $request->get("id"), "cid" => "ignore"]) . "','daysCollection', '')\n";
//        $xx .= "manageCollection('" . $this->router->generate("external_activity_student_manage", ["id" => $request->get("id"), "cid" => "ignore"]) . "','studentCollection', '')\n";
//        $xx .= "manageCollection('" . $this->router->generate("external_activity_slot_manage", ["id" => $request->get("id"), "cid" => "ignore"]) . "','slotCollection', '')\n";

        return $xx;
    }
}
