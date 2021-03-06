<?php
namespace App\Core\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\ImageValidator;

/**
 * Class LogoValidator
 * @package App\Core\Validator\Constraints
 */
class LogoValidator extends ImageValidator
{
    /**
     * validate
     *
     * @param $value
     * @param Constraint $constraint
     */
	public function validate($value, Constraint $constraint)
	{
		if (empty($value))
			return;

		parent::validate($value, $constraint);
	}
}