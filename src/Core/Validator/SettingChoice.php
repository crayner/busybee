<?php
namespace App\Core\Validator;

use App\Core\Validator\Constraints\SettingChoiceValidator;
use Symfony\Component\Validator\Constraint;

class SettingChoice extends Constraint
{
	public $settingName;
	public $strict = false;
	public $extra_choices = [];  // Add additional choices not found in the setting.
	public $message = 'setting.validator.choice.invalid';
	public $settingDataName = null;  //  use this key in a layered array
    public $transDomain = 'Setting';
    public $useLowerCase = false;
    public $translation = null;

	/**
	 * @return string
	 */
	public function validatedBy()
	{
		return SettingChoiceValidator::class;
	}

	/**
	 * @return array
	 */
	public function getRequiredOptions()
	{
		return [
			'settingName',
		];
	}
}