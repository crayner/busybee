<?php
namespace App\Calendar\Util;

use App\Core\Manager\MessageManager;
use App\Core\Manager\TabManagerInterface;
use App\Entity\Calendar;
use App\Entity\CalendarGrade;
use App\Entity\SpecialDay;
use App\Entity\Student;
use App\Entity\Term;
use App\Repository\CalendarRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use Hillrange\Form\Util\CollectionInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Yaml\Yaml;

class CalendarManager implements TabManagerInterface
{
	/**
	 * @var EntityManagerInterface
	 */
	private $manager;

	/**
	 * @var Form
	 */
	private $form;

	/**
	 * @var array
	 */
	private $data;

	/**
	 * @var TokenStorageInterface
	 */
	private static $tokenStorage;

	/**
	 * @var UserInterface
	 */
	private static $currentUser;

    /**
     * @var Calendar
     */
    private static $currentCalendar;

    /**
     * @var Calendar
     */
    private static $nextCalendar;

	/**
	 * @var CalendarRepository
	 */
	private static $calendarRepository;

	/**
	 * @var Year
	 */
	private $year;

	/**
	 * @var  Calendar
	 */
	private $calendar;

    /**
     * @var MessageManager
     */
	private $messageManager;

    /**
     * @var string
     */
    private $status;

    /**
     * @var CalendarGradeManager
     */
    private $calendarGradeManager;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var RequestStack
     */
    private $stack;

	/**
	 * YearManager constructor.
	 *
	 * @param ObjectManager $manager
	 */
	public function __construct(EntityManagerInterface $manager, TokenStorageInterface $tokenStorage, Year $year, MessageManager $messageManager,
                                CalendarGradeManager $calendarGradeManager, RouterInterface $router, RequestStack $stack)
	{
		$this->manager = $manager;
		self::$tokenStorage = $tokenStorage;
		self::$calendarRepository = $manager->getRepository(Calendar::class);
		$this->year = $year;
        $this->messageManager = $messageManager;
        $this->messageManager->setDomain('Calendar');
        $this->calendarGradeManager = $calendarGradeManager;
        $this->calendarGradeManager->setCalendarManager($this);
        $this->router = $router;
        $this->stack = $stack;
	}

    /**
	 * @return EntityManagerInterface
	 */
	public function getEntityManager(): EntityManagerInterface
	{
		return $this->manager;
	}

	/**
	 * @param FormEvent $event
	 */
	public function preSubmit(FormEvent $event)
	{
		$this->data = $event->getData();
		$this->form = $event->getForm();

		$year = $this->form->getData();

		if (isset($this->data['terms']) && is_array($this->data['terms']))
		{
			foreach ($this->data['terms'] as $q => $w)
			{
				$w['year']               = $year->getId();
				$this->data['terms'][$q] = $w;
			}
		}

		if (isset($this->data['specialDays']) && is_array($this->data['specialDays']))
		{
			foreach ($this->data['specialDays'] as $q => $w)
			{
				$w['year']                     = $year->getId();
				$this->data['specialDays'][$q] = $w;
			}
		}

		$event->setData($this->data);

		return $event;

	}

    /**
     * @return Calendar
     */
    public function getCalendar(): ?Calendar
    {
        return $this->calendar;
    }

    /**
     * @return CalendarGrade|null
     */
    public function getCalendarGrade(): ?CalendarGrade
    {
        return $this->calendarGrade;
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
     * @param string $status
     * @return CalendarManager
     */
    public function setStatus(string $status): CalendarManager
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return Term|null
     */
    public function getTerm(): ?Term
    {
        return $this->term;
    }

    /**
     * @return Term|null
     */
    public function getSpecialDay(): ?SpecialDay
    {
        return $this->specialDay;
    }

    /**
	 * @param $sql
	 * @param $rsm
	 */
	private function executeQuery($sql, $rsm)
	{

		$query = $this->manager->createNativeQuery($sql, $rsm);
		try
		{
			$query->execute();
		}
		catch (DriverException $e)
		{
			if (!in_array($e->getErrorCode(), ['1091']))
				throw new \Exception($e->getMessage());
		}
        catch (PDOException $e)
        {
            if (!in_array($e->getErrorCode(), ['1146']))
                throw new \Exception($e->getMessage());
        }
	}

	/**
	 * Can Delete
	 *
	 * @param Calendar $year
	 *
	 * @return bool
	 */
	public function canDelete(Calendar $calendar): bool
	{
		if (! $calendar->canDelete())
			return false;

		return true;
	}

    /**
     * getCurrentUser
     *
     */
    private static function getCurrentUser() 
	{
		if (! is_null(self::$currentUser))
			return ;
		$token = self::$tokenStorage->getToken();

		if (is_null($token))
			return ;

		$user = $token->getUser();
		if ($user instanceof UserInterface)
			self::$currentUser = $user;

		return;
	}

    /**
     * getCurrentCalendar
     *
     * @return Calendar|null
     */
    public static function getCurrentCalendar(): ?Calendar
	{
		if (! is_null(self::$currentCalendar))
			return self::$currentCalendar;

		self::getCurrentUser();
		if (self::$currentUser instanceof UserInterface)
		{
			$settings = self::$currentUser->getUserSettings();
			if (isset($settings['calendar']))
				self::$currentCalendar = self::$calendarRepository->findOneBy(['id' => $settings['calendar']]);
			else
				self::$currentCalendar = self::$calendarRepository->findOneBy(['status' => 'current']);
		}
		else
			self::$currentCalendar = self::$calendarRepository->findOneBy(['status' => 'current']);

		return self::$currentCalendar;
	}

	/**
	 * @return CalendarRepository
	 */
	public static function getCalendarRepository(): CalendarRepository
	{
		return self::$calendarRepository;
	}

	/**
	 * @return array
	 */
	public function getTabs(): array
	{
		return Yaml::parse("
calendar:
    label: calendar.calendar.tab
    include: Calendar/calendarTab.html.twig
    message: calendarMessage
    translation: Calendar
terms:
    label: calendar.terms.tab
    include: Calendar/terms.html.twig
    message: termMessage
    translation: Calendar
specialDays:
    label: calendar.specialDays.tab
    include: Calendar/specialDays.html.twig
    message: specialDayMessage
    translation: Calendar
calendarGrades:
    label: calendar.calendar_grades.tab
    include: Calendar/calendar_grades.html.twig
    message: calendarGradeMessage
    translation: Calendar
");
	}

	/**
	 * @param Calendar $calendar
	 *
	 * @return Year
	 */
	public function generate(Calendar $calendar)
	{
		$this->calendar = $calendar;
		return $this->year->generate($this->calendar);
	}

	/**
	 * Set Calendar Day Types
	 * 
	 * @param Year     $year
	 * @param Calendar $calendar
	 *
	 * @return Calendar
	 */
	public function setCalendarDays(Year $year, Calendar $calendar)
	{
		$this->year     = $year;
		$this->calendar = $calendar;
		$this->setNonSchoolDays();
		$this->setTermBreaks();
		$this->setClosedDays();
		$this->setSpecialDays();

		return $this->calendar;
	}

	/**
	 * Set Non School Day
	 */
	public function setNonSchoolDays()
	{
		$schoolDays = $this->year->getSettingManager()->get('schoolweek');

		foreach ($this->year->getMonths() as $monthKey => $month)
		{
			foreach ($month->getWeeks() as $weekKey => $week)
			{
				foreach ($week->getDays() as $dayKey => $day)
				{
					// School Day ?
					if (!in_array($day->getDate()->format('D'), $schoolDays))
						$day->setSchoolDay(false);
					else
						$day->setSchoolDay(true);
				}
				$month->getWeeks()[$weekKey] = $week;
			}
			$this->year->getMonths()[$monthKey] = $month;
		}
	}

	/**
	 * Set Term Breaks
	 */
	public function setTermBreaks()
	{
		foreach ($this->year->getMonths() as $monthKey => $month)
		{
			foreach ($month->getWeeks() as $weekKey => $week)
			{
				foreach ($week->getDays() as $dayKey => $day)
				{
					// School Day ?
					$break = $this->isTermBreak($day);
					$this->year->getDay($day->getDate()->format('d.m.Y'))->setTermBreak($break);
					$day->setTermBreak($break);
					$week->getDays()[$dayKey] = $day;
				}
				$month->getWeeks()[$weekKey] = $week;
			}
			$this->year->getMonths()[$monthKey] = $month;
		}
	}

	/**
	 * @param Day $currentDate
	 *
	 * @return bool
	 */
	public function isTermBreak(Day $currentDate)
	{
		// Check if the day is a possible school day. i.e. Ignore Weekends
		if ($currentDate->isTermBreak()) return true;

		foreach ($this->calendar->getTerms() as $term)
		{
			if ($currentDate->getDate() >= $term->getFirstDay() && $currentDate->getDate() <= $term->getLastDay())
				return false;
		}

		$currentDate->setTermBreak(true);

		return true;
	}

	/**
	 *
	 */
	public function setClosedDays()
	{
		if (!is_null($this->calendar->getSpecialDays()))
			foreach ($this->calendar->getSpecialDays() as $specialDay)
				if ($specialDay->getType() == 'closure')
					$this->year->getDay($specialDay->getDay()->format('d.m.Y'))->setClosed(true, $specialDay->getName());
	}

	/**
	 *
	 */
	public function setSpecialDays()
	{
		if (!is_null($this->calendar->getSpecialDays()))
			foreach ($this->calendar->getSpecialDays() as $specialDay)
				if ($specialDay->getType() != 'closure')
					$this->year->getDay($specialDay->getDay()->format('d.m.Y'))->setSpecial(true, $specialDay->getName());
	}

	/**
	 * @param Day  $day
	 * @param null $class
	 *
	 * @return string
	 */
	public function getDayClass(Day $day, $class = null)
	{

		$class    = empty($class) ? '' : $class;
		$weekDays = $this->year->getSettingManager()->get('schoolWeek');
		$weekEnd  = true;

		if (isset($weekDays[$day->getDate()->format('D')]))
			$weekEnd = false;
		if (!$weekEnd)
			$class .= ' dayBold';

		if ($this->isTermBreak($day))
			$class .= ' termBreak';
		if ($day->isClosed())
		{
			$class .= ' isClosed';
			$class = str_replace(' termBreak', '', $class);
		}
		if ($day->isSpecial())
		{
			$class .= ' isSpecial';
			$class = str_replace(' termBreak', '', $class);
		}

		if (!$day->isSchoolDay())
		{
			$class .= ' isNonSchoolDay';
			$class = str_replace(' termBreak', '', $class);
		}

		if (empty($class)) return '';

		return ' class="' . trim($class) . '"';
	}

	/**
	 * @return array
	 */
	public function getCalendarList()
	{
		$results = $this->getEntityManager()->getRepository(Calendar::class)->findBy([], ['firstDay' => 'DESC']);
		return empty($results) ? [] : $results ;
	}

    /**
     * @param Calendar|null $calendar
     * @return null|Calendar
     */
    public static function getNextCalendar(?Calendar $calendar): ?Calendar
    {
        if (self::$nextCalendar && is_null($calendar))
            return self::$nextCalendar;

        $calendar = $calendar ?: self::getCurrentCalendar();

        $result = self::getCalendarRepository()->createQueryBuilder('c')
            ->where('c.firstDay > :firstDay')
            ->setParameter('firstDay', $calendar->getFirstDay()->format('Y-m-d'))
            ->getQuery()
            ->getResult();

        self::$nextCalendar = $result ? $result[0] : null ;

        return self::$nextCalendar;
    }

    /**
     * @param Calendar $calendar
     * @return bool
     */
    public function isCurrentCalendar(Calendar $calendar)
    {
        $current = $this->getCurrentCalendar();

        if ($current->getId() === $calendar->getId() && $current->getName() === $calendar->getName())
            return true;
        return false;
    }

    /**
     * @return array
     */
    public function getCurrentYears(): array
    {
        $x = [];
        $x[] = $this->getCurrentCalendar()->getFirstDay()->format('Y');
        $x[] = $this->getCurrentCalendar()->getLastDay()->format('Y');
        return $x;
    }

    /**
     * @param $id
     * @return Calendar|null
     */
    public function find($id): ?Calendar
    {
        if ($id === 'Add')
            $this->calendar = new Calendar();
        if (empty($id))
            $this->calendar = null;
        if (intval($id) > 0)
            $this->calendar = $this->getEntityManager()->getRepository(Calendar::class)->find($id);

        return $this->getCalendar();
    }

    /**
     * @param $cid
     */
    public function removeCalendarGrade($cid)
    {
        $this->setStatus('default');
        if ($cid === 'ignore')
            return ;

        if (! $this->getCalendar())
            return ;

        $this->findCalendarGrade($cid);

        $this->setStatus('warning');

        if (empty($this->calendarGrade) || ! $this->calendarGrade instanceof CalendarGrade) {
            $this->messageManager->add('warning', 'calendar.grades.missing.warning', ['%{calendarGrade}' => $cid]);
            return;
        }

        if ($this->calendar->getCalendarGrades()->contains($this->calendarGrade) && $this->calendarGradeManager->canDelete($this->calendarGrade)) {
            // Staff is NOT Deleted, but the DepartmentMember link is deleted.
            $this->calendar->removeCalendarGrade($this->calendarGrade);
            $this->getEntityManager()->remove($this->calendarGrade);
            $this->getEntityManager()->persist($this->calendar);
            $this->getEntityManager()->flush();

            $this->setStatus('success');
            $this->messageManager->add('success', 'calendar.calendar_grade.removed.success', ['%{calendarGrade}' => $this->calendarGrade->getFullName()]);
        } else {
            $this->setStatus('info');
            $this->messageManager->add('info', 'calendar.calendar_grade.removed.info', ['%{calendarGrade}' => $this->calendarGrade->getFullName()]);
        }
    }

    /**
     * @var null|CalendarGrade
     */
    private $calendarGrade;

    /**
     * @param $id
     * @return null|CalendarGrade
     */
    public function findCalendarGrade($id): ?CalendarGrade
    {
        $this->calendarGrade = $this->getEntityManager()->getRepository(CalendarGrade::class)->find(intval($id));

        return $this->getCalendarGrade();
    }

    /**
     * @return Calendar|null
     */
    public function refreshCalendarGrades(): ?Calendar
    {
        if (empty($this->calendar))
            return $this->calendar;

        try {
            $this->getEntityManager()->refresh($this->calendar);
            return $this->calendar->refresh();
        } catch (\Exception $e) {
            return $this->calendar;
        }
    }

    /**
     * @param $cid
     */
    public function removeTerm($cid)
    {
        $this->setStatus('default');
        if ($cid === 'ignore')
            return ;

        if (! $this->getCalendar())
            return ;

        $this->findTerm($cid);

        $this->setStatus('warning');

        if (empty($this->term)) {
            $this->messageManager->add('warning', 'calendar.term.missing.warning', ['%{term}' => $cid]);
            return ;
        }

        if ($this->calendar->getTerms()->contains($this->term) && $this->term->canDelete()) {
            // Staff is NOT Deleted, but the DepartmentMember link is deleted.
            $this->calendar->removeTerm($this->term);
            $this->getEntityManager()->remove($this->term);
            $this->getEntityManager()->persist($this->calendar);
            $this->getEntityManager()->flush();

            $this->setStatus('success');
            $this->messageManager->add('success', 'calendar.term.removed.success', ['%{term}' => $this->term->getFullName()]);
        } else {
            $this->setStatus('info');
            $this->messageManager->add('info', 'calendar.term.removed.info', ['%{term}' => $this->term->getFullName()]);
        }
    }

    /**
     * @var null|Term
     */
    private $term;

    /**
     * @param $id
     * @return null|CalendarGrade
     */
    public function findTerm($id): ?Term
    {
        $this->term = $this->getEntityManager()->getRepository(Term::class)->find(intval($id));

        return $this->getTerm();
    }

    /**
     * @param $cid
     */
    public function removeSpecialDay($cid)
    {
        $this->setStatus('default');
        if ($cid === 'ignore')
            return ;

        if (! $this->getCalendar())
            return ;

        $this->findSpecialDay($cid);

        $this->setStatus('warning');

        if (empty($this->specialDay)) {
            $this->messageManager->add('warning', 'calendar.special_day.missing.warning', ['%{specialDay}' => $cid]);
            return ;
        }

        if ($this->calendar->getSpecialDays()->contains($this->specialDay) && $this->specialDay->canDelete()) {
            // Staff is NOT Deleted, but the DepartmentMember link is deleted.
            $this->calendar->removeTerm($this->specialDay);
            $this->getEntityManager()->remove($this->specialDay);
            $this->getEntityManager()->persist($this->calendar);
            $this->getEntityManager()->flush();

            $this->setStatus('success');
            $this->messageManager->add('success', 'calendar.special_day.removed.success', ['%{specialDay}' => $this->specialDay->getFullName()]);
        } else {
            $this->setStatus('info');
            $this->messageManager->add('info', 'calendar.special_day.removed.info', ['%{specialDay}' => $this->specialDay->getFullName()]);
        }
    }

    /**
     * @var null|SpecialDay
     */
    private $specialDay;

    /**
     * @param $id
     * @return null|CalendarGrade
     */
    public function findSpecialDay($id): ?SpecialDay
    {
        $this->specialDay = $this->getEntityManager()->getRepository(SpecialDay::class)->find(intval($id));

        return $this->getSpecialDay();
    }

    /**
     * @return string
     */
    public function getResetScripts(): string
    {
        $request = $this->stack->getCurrentRequest();
        $xx = "manageCollection('" . $this->router->generate("calendar_grade_manage", ["id" => $request->get("id"), "cid" => "ignore"]) . "','gradeCollection', '')\n";
        $xx .= "manageCollection('" . $this->router->generate("calendar_term_manage", ["id" => $request->get("id"), "cid" => "ignore"]) . "','termCollection', '')\n";
        $xx .= "manageCollection('" . $this->router->generate("calendar_special_day_manage", ["id" => $request->get("id"), "cid" => "ignore"]) . "','specialDayCollection', '')\n";

        return $xx;
    }

    /**
     * @param string $method
     * @return bool
     */
    public function isDisplay(string $method = ''): bool
    {
        return true;
    }

    /**
     * getGradeInCurrentCalendar
     *
     * @param Student $student
     * @return CalendarGrade|null
     */
    public static function getStudentGradeInCurrentCalendar(Student $student): ?CalendarGrade
    {
        $grades = $student->getCalendarGrades();

        foreach ($grades as $grade)
            if ($grade->getCalendar() === self::getCurrentCalendar())
                return $grade->getCalendarGrade();

        return null;
    }
}