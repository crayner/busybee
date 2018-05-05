<?php
namespace App\Timetable\Entity;

use App\Entity\Space;
use App\Entity\TimetablePeriod;
use Doctrine\Common\Collections\Collection;
use Hillrange\Security\Util\UserTrackInterface;
use Hillrange\Security\Util\UserTrackTrait;

abstract class TimetablePeriodActivityExtension implements UserTrackInterface
{
    use UserTrackTrait;

    /**
     * @return string
     */
    public function getFullName(): string
    {
        return $this->getActivity()->getFullName();
    }

    /**
     * @return mixed
     */
    public function getNameShort()
    {
        return $this->getActivity()->getNameShort();
    }

    /**
     * Get Grades
     *
     * @return null|Collection
     */
    public function getGrades()
    {
        if (!empty($this->getActivity()))
            return $this->getActivity()->getGrades();
        return null;
    }

    /**
     * @return string
     */
    public function getColumnName(): string
    {
        $period = $this->getPeriod();
        if ($period instanceof TimetablePeriod)
            return $period->getColumnName();
        return '';
    }

    /**
     * @return Collection
     */
    public function loadTutors(): Collection
    {
        $tutors = $this->getTutors();

        if ($tutors->count() === 0)
            $tutors = $this->getActivity()->getTutors();

        return $tutors;
    }

    /**
     * @return Space|null
     */
    public function loadSpace(): ?Space
    {
        if ($this->getSpace() instanceof Space)
            return $this->getSpace();

        return $this->getActivity()->getSpace();

    }

    /**
     * @return string
     */
    public function loadTutorNames(): string
    {
        $names = '';
        foreach($this->loadTutors()->getIterator() as $tutor)
            $names .= $tutor->getTutor()->getFullName(['preferredOnly' => true]) . ', ';

        return trim($names, ', ');
    }
}