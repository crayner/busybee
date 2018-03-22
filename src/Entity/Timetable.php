<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use Hillrange\Security\Util\UserTrackInterface;
use Hillrange\Security\Util\UserTrackTrait;

class Timetable implements UserTrackInterface
{
    use UserTrackTrait;

    /**
     * @var null|int
     */
    private $id;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     * @return Timetable
     */
    public function setId(?int $id): Timetable
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @var null|string
     */
    private $name;

    /**
     * @return null|string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param null|string $name
     * @return Timetable
     */
    public function setName(?string $name): Timetable
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @var null|string
     */
    private $code;

    /**
     * @return null|string
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @param null|string $code
     * @return Timetable
     */
    public function setCode(?string $code): Timetable
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @var null|Calendar
     */
    private $calendar;

    /**
     * @return Calendar|null
     */
    public function getCalendar(): ?Calendar
    {
        return $this->calendar;
    }

    /**
     * @param Calendar|null $calendar
     * @return Timetable
     */
    public function setCalendar(?Calendar $calendar): Timetable
    {
        $this->calendar = $calendar;
        return $this;
    }

    /**
     * @var null|Collection
     */
    private $calendarGrades;

    /**
     * @return Collection|null
     */
    public function getCalendarGrades(): ?Collection
    {
        if(empty($this->calendarGrades))
            $this->calendarGrades = new ArrayCollection();

        if ($this->calendarGrades instanceof PersistentCollection && ! $this->calendarGrades->isInitialized())
            $this->calendarGrades->initialize();

        $iterator =  $this->calendarGrades->getIterator();
        $iterator->uasort(
            function ($a, $b) {
                return ($a->getSequence() < $b->getSequence()) ? -1 : 1;
            }
        );

        $this->calendarGrades = new ArrayCollection(iterator_to_array($iterator, false));


        return $this->calendarGrades;
    }

    /**
     * @param Collection|null $calendarGrades
     * @return Timetable
     */
    public function setCalendarGrades(?Collection $calendarGrades): Timetable
    {
        $this->calendarGrades = $calendarGrades;
        return $this;
    }

    /**
     * @param CalendarGrade|null $calendarGrade
     * @return $this
     */
    public function addCalendarGrade(?CalendarGrade $calendarGrade): Timetable
    {
        if (empty($calendarGrade))
            return $this;

        if ($this->getCalendarGrades()->contains($calendarGrade))
            return $this;

        $this->calendarGrades->add($calendarGrade);

        return $this;
    }

    /**
     * @param CalendarGrade|null $calendarGrade
     * @return Timetable
     */
    public function removeCalendarGrade(?CalendarGrade $calendarGrade): Timetable
    {
        if (empty($calendarGrade))
            return $this;

        $this->getCalendarGrades()->removeElement($calendarGrade);

        return $this;
    }

    /**
     * @var boolean
     */
    private $active;

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active ? true : false;
    }

    /**
     * @param bool $active
     * @return Timetable
     */
    public function setActive(bool $active): Timetable
    {
        $this->active = $active ? true : false;

        return $this;
    }
}