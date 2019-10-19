<?php

declare(strict_types=1);

namespace Benda95280\MyEntities\entities\vehicle;

use Benda95280\MyEntities\entities\entity\CustomEntityProperties;
use pocketmine\math\Vector3;
use pocketmine\utils\UUID;

class VehicleProperties extends CustomEntityProperties
{
    const TYPE = "vehicle";
	public $carLocked = false;
	public $maxSpeed = 5;//in blocks per second
	public $acceleration = 0.5;//in blocks per second
	/** @var null|UUID */
	public $ownerUUID = null;
	/** @var Vector3[] */
	public $seats = [];
	public $driverPosition = null;
	public $passengerPositions = [];
}