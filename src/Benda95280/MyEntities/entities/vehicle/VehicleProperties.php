<?php

declare(strict_types=1);

namespace Benda95280\MyEntities\entities\vehicle;

use Benda95280\MyEntities\entities\entity\CustomEntityProperties;
use pocketmine\math\Vector3;
use pocketmine\utils\UUID;
use xenialdan\customui\elements\StepSlider;
use xenialdan\customui\windows\CustomForm;

class VehicleProperties extends CustomEntityProperties
{
	const TYPE = "vehicle";
	public $carLocked = false;
	//Default values result in max speed after 2.5 seconds
	//Georgiev G.Z., "Acceleration Calculator", [online] Available at: https://www.gigacalculator.com/calculators/acceleration-calculator.php URL [Accessed Date: 19 Oct, 2019].
	public $maxSpeed = 3;//in blocks per second m / s //was 5
	public $acceleration = 0.5;// m / s^2
	/** @var null|UUID */
	public $ownerUUID = null;
	/** @var Vector3[] */
	public $seats = [];
	public $driverPosition = null;
	public $passengerPositions = [];

	public function getForm(): CustomForm
	{
		$form = parent::getForm();
		$form->addElement(new StepSlider("Speed in m/s", [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]));
		$form->addElement(new StepSlider("Acceleration in m/s", [0.5, 0.75, 1, 1.25, 1.5, 1.75, 2]));
		$form->addElement(new StepSlider("Amount of seats", [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]));
		return $form;
	}
}