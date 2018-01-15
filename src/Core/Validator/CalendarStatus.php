<?php
namespace App\Core\Validator;

use App\Core\Validator\Constraints\CalendarStatusValidator;
use Symfony\Component\Validator\Constraint;

class CalendarStatus extends Constraint
{
	public $message = 'calendar.error.status';

	public $id;

	public function validatedBy()
	{
		return CalendarStatusValidator::class;
	}
}
