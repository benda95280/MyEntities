<?php

declare(strict_types=1);

namespace Benda95280\MyEntities\entities;

use Benda95280\MyEntities\entities\entity\CloneEntityProperties;
use Benda95280\MyEntities\entities\entity\CustomEntityProperties;
use Benda95280\MyEntities\entities\head\HeadProperties;
use Benda95280\MyEntities\MyEntities;
use InvalidArgumentException;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\entity\Skin;
use pocketmine\nbt\NBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use xenialdan\customui\elements\Dropdown;
use xenialdan\customui\elements\Input;
use xenialdan\customui\elements\Label;
use xenialdan\customui\elements\StepSlider;
use xenialdan\customui\elements\Toggle;
use xenialdan\customui\windows\CustomForm;

abstract class Properties
{
	const PROPERTY_TAG = "MyEntities";
	const PROPERTY_TEXTURE = "texture";
	const PROPERTY_TYPE = "type";
	const PROPERTY_SIZE = "size";
	const PROPERTY_SIZE_NORMAL = "normal";
	const PROPERTY_SIZE_SMALL = "small";
	const PROPERTY_SIZE_BLOCK = "block";
	const SIZE_NORMAL = 0.5;
	const SIZE_SMALL = 0.25;
	const SIZE_BLOCK = 1;
	const TYPES = [
		HeadProperties::TYPE,
		CustomEntityProperties::TYPE,
		CloneEntityProperties::TYPE,
	];
	public $width;
	public $height;
	/** @var Skin */
	public $skin;
	public $skinEmpty;
	protected static $type;
	public $rotationPerTick = 0;
	public $size = self::SIZE_BLOCK;
	public $name = "";
	public $health = 1;
	public $unbreakable = false;
	public $usable = [
		"time" => 0,
		"skinchange" => 0,
		"reload" => 0,
		"destruction" => 0,
		"use_msg" => 1,
		"destruction_msg" => "",
		"action" => "{}",
		"action_random" => 0,
	];
	#public $usable = false;
	public $lastUsed = -1;

	public function __construct(?CompoundTag $nbt = null)
	{
		if ($nbt) {
			foreach (NBTStream::toArray($nbt) as $property => $value) {
				if ($property === "size") {
					switch ($value) {
						case self::PROPERTY_SIZE_BLOCK:
						{
							$this->size = self::SIZE_BLOCK;
							break;
						}
						case self::PROPERTY_SIZE_SMALL:
						{
							$this->size = self::SIZE_SMALL;
							break;
						}
						case self::PROPERTY_SIZE_NORMAL:
						default:
							break;
					}
					continue;
				}
				$this->$property = $value;
			}
		}
	}

	public function toTag(): CompoundTag
	{
		return MyEntities::arrayToCompTag((array)$this, self::PROPERTY_TAG);
	}

	/**
	 * @return int
	 * @throws InvalidArgumentException
	 */
	public static function getType(): int
	{
		$search = array_search(self::$type, self::TYPES);
		if (!is_int($search)) throw new InvalidArgumentException("Incorrect type given");
		return $search;
	}

	/**
	 * @return Block
	 * @throws InvalidArgumentException
	 */
	public static function getDestroyParticlesBlock(): Block
	{
		return BlockFactory::get(BlockIds::AIR);
	}

	public function getForm(): CustomForm
	{
		$form = new CustomForm("Entity properties");
		$form->setCallable(function (Player $player, $data) use ($form) {
			$player->sendMessage(print_r($data, true));
			$player->sendMessage(print_r($form->getContent(), true));
		});
		$form->addElement(new Label("Skin"));
		if ($this->skin instanceof Skin)
			$form->addElement(new Label($this->skin->getSkinId()));
		else {
			$form->addElement(new Dropdown("Select skin", array_keys(MyEntities::$skinsList)));
		}
		$form->addElement(new StepSlider("Rotation per tick", [0, 1, 2, 3, 4, 5, 10, 15, 20, 22.5, 30, 45, 90, 120, 180]));
		$form->addElement(new Toggle("Rotate counterclockwise", (bool)$this->rotationPerTick < 0));
		$form->addElement(new Input("Name", "Entity nametag", $this->name));
		$form->addElement(new Input("Health", "Integer", (string)$this->health));
		$form->addElement(new Toggle("Unbreakable", (bool)$this->unbreakable));
		return $form;
	}
}