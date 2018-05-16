<?php
/**
 * Created by PhpStorm.
 *
 * This file is part of the Busybee Project.
 *
 * (c) Craig Rayner <craig@craigrayner.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * User: craig
 * Date: 9/05/2018
 * Time: 17:33
 */

namespace App\Timetable\Util;

use App\Core\Manager\MessageManager;
use App\Entity\Space;
use App\Entity\TimetableLine;
use App\Entity\TimetablePeriod;
use App\Entity\TimetablePeriodActivity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;

class PeriodManager
{
    /**
     * @var TimetableManager
     */
    private $timetableManager;

    /**
     * PeriodManager constructor.
     * @param TimetableManager $timetableManager
     */
    public function __construct(TimetableManager $timetableManager)
    {
        $this->timetableManager = $timetableManager;

    }

    /**
     * hasSpace
     *
     * @param TimetablePeriodActivity $activity
     * @return bool
     */
    public function hasSpace(TimetablePeriodActivity $activity): bool
    {
        if ($activity->loadSpace() instanceof Space)
            return true;
        return false;
    }

    /**
     * @param TimetablePeriodActivity $activity
     * @return Space|null
     */
    public function getSpace(TimetablePeriodActivity $activity): ?Space
    {
        return $activity->loadSpace();
    }

    /**
     * @param TimetablePeriodActivity $activity
     * @return bool
     */
    public function hasTutors(TimetablePeriodActivity $activity): bool
    {
        if ($activity->loadTutors()->count() > 0)
            return true;
        return false;
    }

    /**
     * @return MessageManager
     */
    public function getMessageManager(): MessageManager
    {
        return $this->getTimetableManager()->getMessageManager();
    }

    /**
     * @return TimetableManager
     */
    public function getTimetableManager(): TimetableManager
    {
        return $this->timetableManager;
    }

    /**
     * @param $id
     * @return TimetablePeriod
     */
    public function find($id): TimetablePeriod
    {
        return $this->setPeriod($this->getEntityManager()->getRepository(TimetablePeriod::class)->find(intval($id)))->getPeriod();
    }

    /**
     * @var TimetablePeriod
     */
    private $period;

    /**
     * @return TimetablePeriod
     */
    public function getPeriod(): TimetablePeriod
    {
        return $this->period;
    }

    /**
     * @param TimetablePeriod $period
     * @return PeriodManager
     */
    public function setPeriod(TimetablePeriod $period): PeriodManager
    {
        $this->period = $period;
        return $this;
    }

    /**
     * getEntityManager
     *
     * @return EntityManagerInterface
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->getTimetableManager()->getEntityManager();
    }

    /**
     * @param int $line
     */
    public function addLine(int $line)
    {
        $line = $this->getEntityManager()->getRepository(TimetableLine::class)->find($line);

        $count = 0;

        $exists = new ArrayCollection();
        foreach ($this->getPeriod()->getActivities() as $act)
            $exists->add($act->getActivity());

        foreach($line->getActivities()->getIterator() as $activity)
            if (! $exists->contains($activity)) {
                $act = new TimetablePeriodActivity();
                $act->setPeriod($this->period);
                $act->setActivity($activity);
                $count++;
            }

        if ($count > 0) {
            $this->getEntityManager()->persist($this->period);
            $this->getEntityManager()->flush();
            $this->getMessageManager()->add('success', 'period.activities.line.added', ['transChoice' => $count], 'Timetable');
            $this->getTimetableManager()->getPeriodReport($this->getPeriod());
        } else
            $this->getMessageManager()->add('warning', 'period.activities.line.added', ['transChoice' => 0], 'Timetable');
    }

    /**
     * @param int $id
     * @return bool
     */
    public function removeActivity(int $id): bool
    {
        $this->status = new \stdClass();

        $this->findActivity($id);

        if (! $this->activity)
        {
            $this->getMessageManager()->add('warning', 'period.activities.activity.missing.warning', [], 'Timetable');
            return false;
        }

        $this->getPeriod()->removeActivity($this->getActivity());

        try {
            $this->getEntityManager()->persist($this->getPeriod());
            $this->getEntityManager()->remove($this->getActivity());
            $this->getEntityManager()->flush();
        } catch (\Exception $e) {
            $this->getMessageManager()->add('danger', 'period.activities.activity.remove.error', ['%name%' => $this->getActivity()->getFullName(), '%error%' => $e->getMessage()], 'Timetable');
            return false;
        }
        $this->getMessageManager()->add('success', 'period.activities.activity.remove.success', ['%name%' => $this->getActivity()->getFullName()], 'Timetable');
        $this->getTimetableManager()->getPeriodReport($this->getPeriod());

    return true;
    }

    /**
     * @var TimetablePeriodActivity|null
     */
    private $activity;

    /**
     * @param int $id
     * @return TimetablePeriodActivity|null
     */
    public function findActivity(int $id): ?TimetablePeriodActivity
    {
        return $this->setActivity($this->getEntityManager()->getRepository(TimetablePeriodActivity::class)->find(intval($id)))->getActivity();
    }

    /**
     * @return TimetablePeriodActivity|null
     */
    public function getActivity(): ?TimetablePeriodActivity
    {
        return $this->activity;
    }

    /**
     * @param TimetablePeriodActivity|null $activity
     * @return PeriodManager
     */
    public function setActivity(?TimetablePeriodActivity $activity): PeriodManager
    {
        $this->activity = $activity;
        return $this;
    }
}