<?php

declare(strict_types=1);

namespace Benda95280\MyEntities\entities\entity;

use Benda95280\MyEntities\entities\Properties;
use pocketmine\nbt\tag\CompoundTag;
use xenialdan\customui\elements\Dropdown;
use xenialdan\customui\elements\Input;
use xenialdan\customui\elements\StepSlider;
use xenialdan\customui\windows\CustomForm;

class CloneEntityProperties extends Properties
{
	const TYPE = "clone";
	protected static $type = self::TYPE;
    public $width = 0.6;
    public $height = 1.8;
	public $scale = 1.0;
	public $lookAtRange = 0;

	public function __construct(?CompoundTag $nbt = null)
	{
		$this->size = self::SIZE_BLOCK;
		parent::__construct($nbt);
	}

	public function getForm(): CustomForm
	{
		$form = parent::getForm();
		$form->addElement(new Dropdown("Size", ["Normal", "Baby", "Giant", "Custom"]));
		$form->addElement(new Input("Custom size", "(float) Custom size if selected"));
		$form->addElement(new StepSlider("Look at player range, 0 = off", [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]));
		return $form;
	}
}