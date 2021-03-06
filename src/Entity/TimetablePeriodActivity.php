<?php
namespace App\Entity;

use App\Core\Exception\MissingClassException;
use App\Timetable\Entity\TimetablePeriodActivityExtension;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;

class TimetablePeriodActivity extends TimetablePeriodActivityExtension
{
    /**
     * @var null|integer
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
     * @return TimetablePeriodActivity
     */
    public function setId(?int $id): TimetablePeriodActivity
    {
        return $this;
    }

    /**
     * @var null|Space
     */
    private $space;

    /**
     * @return Space|null
     */
    public function getSpace(): ?Space
    {
        return $this->space;
    }

    /**
     * @param Space|null $space
     * @return TimetablePeriodActivity
     */
    public function setSpace(?Space $space): TimetablePeriodActivity
    {
        $this->space = $space;
        return $this;
    }

    /**
     * @var null|FaceToFace
     */
    private $activity;

    /**
     * @return Activity|null
     * @throws MissingClassException
     */
    public function getActivity(): ?Activity
    {
        if (empty($this->activity) || $this->activity instanceof Roll || $this->activity instanceof FaceToFace)
            return $this->activity;
        throw new MissingClassException('000 The activity must be a Roll or FaceToFace type.', ['activity' => $this->activity]);
    }

    /**
     * setActivity
     *
     * @param Activity|null $activity
     * @param bool $add
     * @return TimetablePeriodActivity
     * @throws MissingClassException
     */
    public function setActivity(?Activity $activity, $add = true): TimetablePeriodActivity
    {
        if (empty($activity) || $activity instanceof Roll || $activity instanceof FaceToFace) {
            if ($add && $activity)
                $activity->addPeriod($this, false);

            $this->activity = $activity;
        }
        else
            throw new MissingClassException('000 The activity must be a Roll or FaceToFace type.', ['activity' => $activity]);

        return $this;
    }

    /**
     * @var null|TimetablePeriod
     */
    private $period;

    /**
     * @return TimetablePeriod|null
     */
    public function getPeriod(): ?TimetablePeriod
    {
        return $this->period;
    }

    /**
     * @param TimetablePeriod|null $period
     * @return TimetablePeriodActivity
     */
    public function setPeriod(?TimetablePeriod $period, $add = true): TimetablePeriodActivity
    {
        if ($add && $period)
            $period->addActivity($this, false);

        if ($add && !$period)
            $period->removeActivity($this);

        $this->period = $period;

        return $this;
    }

    /**
     * @var null|Collection
     */
    private $tutors;

    /**
     * @return Collection
     */
    public function getTutors(): Collection
    {
        if (empty($this->tutors))
            $this->tutors = new ArrayCollection();

        if ($this->tutors instanceof PersistentCollection && ! $this->tutors->isInitialized())
            $this->tutors->initialize();

        return $this->tutors;
    }

    /**
     * @param Collection|null $tutors
     * @return TimetablePeriodActivity
     */
    public function setTutors(?Collection $tutors): TimetablePeriodActivity
    {
        $this->tutors = $tutors;
        return $this;
    }

    /**
     * @param TimetablePeriodActivityTutor|null $tutor
     * @param bool $add
     * @return TimetablePeriodActivity
     */
    public function addTutor(?TimetablePeriodActivityTutor $tutor, $add = true): TimetablePeriodActivity
    {
        if (empty($tutor) || $this->getTutors()->contains($tutor))
            return $this;

        if ($add)
            $tutor->setActivity($this, false);

        $this->tutors->add($tutor);

        return $this;
    }

    /**
     * @param TimetablePeriodActivityTutor|null $tutor
     * @return TimetablePeriodActivity
     */
    public function removeTutor(?TimetablePeriodActivityTutor $tutor): TimetablePeriodActivity
    {
        $this->getTutors()->removeElement($tutor);
        return $this;
    }
}