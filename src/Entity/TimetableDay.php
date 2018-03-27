<?php
namespace App\Entity;

use App\Timetable\Entity\TimetableDayExtension;
use Hillrange\Form\Util\ColourManager;
use Hillrange\Security\Util\UserTrackInterface;
use Hillrange\Security\Util\UserTrackTrait;

class TimetableDay extends TimetableDayExtension implements UserTrackInterface
{
    use UserTrackTrait;

    /**
     * @var null|integer
     */
    private $id;

    /**
     * @return null|int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     * @return TimetableDay
     */
    public function setId(?int $id): TimetableDay
    {
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
     * @return TimetableDay
     */
    public function setName(?string $name): TimetableDay
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @var null|Timetable
     */
    private $timetable;

    /**
     * @return Timetable|null
     */
    public function getTimetable(): ?Timetable
    {
        return $this->timetable;
    }

    /**
     * @param Timetable|null $timetable
     * @return TimetableDay
     */
    public function setTimetable(?Timetable $timetable): TimetableDay
    {
        $this->timetable = $timetable;
        return $this;
    }

    /**
     * @var string|null
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
     * @return TimetableDay
     */
    public function setCode(?string $code): TimetableDay
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @var string|null
     */
    private $colour;

    /**
     * @return null|string
     */
    public function getColour(): ?string
    {
        return $this->colour;
    }

    /**
     * @param null|string $colour
     * @return TimetableDay
     */
    public function setColour(?string $colour): TimetableDay
    {
        $this->colour = ColourManager::formatColour($colour);

        return $this;
    }

    /**
     * @var string|null
     */
    private $fontColour;

    /**
     * @return null|string
     */
    public function getFontColour(): ?string
    {
        return $this->fontColour;
    }

    /**
     * @param null|string $fontColour
     * @return TimetableDay
     */
    public function setFontColour(?string $fontColour): TimetableDay
    {
        $this->fontColour = ColourManager::formatColour($fontColour);

        return $this;
    }
}