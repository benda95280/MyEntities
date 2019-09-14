<?php

/*	
 *  Original Source: https://github.com/Enes5519/PlayerHead 
 *  MyEntities - a PocketMine-MP plugin to add player custom entities and support for custom Player Head on server
 *  Copyright (C) 2019 Benda95280
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Benda95280\MyEntities;

use Benda95280\MyEntities\commands\PHCommand;
use Benda95280\MyEntities\entities\MyCustomEntity;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;



class MyEntities extends PluginBase implements Listener{
	/** @var MyEntities */
    private static $instance;
	/** @var array $skinsList */
	public static $skinsList;
	/** @var array $miscList */
	public static $miscList;
	/** @var array $configData */
	public static $configData;

	public const PREFIX = TextFormat::BLUE . 'MyEntities' . TextFormat::DARK_GRAY . '> '.TextFormat::WHITE;
	
	public function onEnable() : void{

        if (self::$instance === null) {
            self::$instance = $this;
        }
		
		//Load configuration file
		$this->saveDefaultConfig();
		$data = $this->getConfig()->getAll();
		//Define Public Var Data-Config File
		self::$configData = $data;
		self::$skinsList = $data["skins"];
		self::$miscList = $data["misc"];
		
		self::logMessage("§aLoading ...",1);
		
		Entity::registerEntity(MyCustomEntity::class, true, ['MyEntities']);

		$this->getServer()->getCommandMap()->register('MyEntities', new PHCommand($data["message"]));
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		//Count skins available
		$pathSkinsHead = $this->getDataFolder() . "skins" . DIRECTORY_SEPARATOR;
		$countFileSkinsHeadSmall = 0;
		$countFileSkinsHeadNormal = 0;
		$countFileSkinsHeadBlock = 0;
		$countFileSkinsCustom = 0;
		
		//All checks are here
		include("CheckIn.php");
	}
	
    public static function getInstance() : MyEntities {
        return self::$instance;
    }

	public function onPlace(BlockPlaceEvent $event) : void{
		$player = $event->getPlayer();
		if($player->hasPermission('MyEntities.spawn') and (($item = $player->getInventory()->getItemInHand())->getId() === Item::MOB_HEAD || ($item = $player->getInventory()->getItemInHand())->getId() === Item::COMMAND_BLOCK) and ($blockData = $item->getCustomBlockData()) !== null){
			$nbt = Entity::createBaseNBT($event->getBlock()->add(0.5, 0, 0.5), null, self::getYaw($event->getBlock()->add(0.5, 0, 0.5), $player)); // Add 0.5 because block center is at half coordinate
			if ($blockData->hasTag("skin_empty")) 
				$nbt->setByteArray("Skin_empty", $blockData->getByteArray("skin_empty"));
			if ($blockData->hasTag("Geometry")) {
				$nbt->setString("Geometry", $blockData->getString("Geometry"));
			}
			$blockDataSkin = $blockData->getCompoundTag("skin");
			$blockDataParam = $blockData->getCompoundTag("param");
			$blockDataSkin->setName('Skin');
			$blockDataParam->setName('Param');
			$nbt->setTag($blockDataSkin);
			$nbt->setTag($blockDataParam);
            (new MyCustomEntity($player->level, $nbt))->spawnToAll();
			if(!$player->isCreative()){
				$player->getInventory()->setItemInHand($item->setCount($item->getCount() - 1));
			}
			$event->setCancelled();
		}
	}

	private static function getYaw(Vector3 $pos, Vector3 $target) : float{
		//Entity must rotate 90° cause of block
		//TODO: Handle 45° for other block
		$yaw = atan2($target->z - $pos->z, $target->x - $pos->x) / M_PI * 180 - 90;
		if($yaw < 0){
			$yaw += 360.0;
		}
		// Round to nearest multiple of 45
		return round($yaw / 90) * 90;
	}

	/**
	 * @param string $name
	 * @param string $nameFinal
	 * @param array $param
	 * @return Item
	 */
	public static function getPlayerHeadItem(string $name,string $nameFinal,array $param) : Item{
		if (isset($param["usable"]["skinchange"]) && $param["usable"]["skinchange"] == 1) 
			$item = (ItemFactory::get(Item::MOB_HEAD, 3))
			->setCustomBlockData(new CompoundTag("", [
				new CompoundTag('skin', [
					new StringTag('Name', $name),
					new ByteArrayTag('Data', MyEntities::createSkin($name)),
				]),
				new ByteArrayTag('skin_empty', MyEntities::createSkin($name."_empty")),
				MyEntities::arrayToCompTag($param,"param")
				]))
			->setCustomName(TextFormat::colorize('&r'.$nameFinal, '&'));
		else
			$item = (ItemFactory::get(Item::MOB_HEAD, 3))
			->setCustomBlockData(new CompoundTag("", [
				new CompoundTag('skin', [
					new StringTag('Name', $name),
					new ByteArrayTag('Data', MyEntities::createSkin($name)),
				]),
				MyEntities::arrayToCompTag($param,"param")
				]))
			->setCustomName(TextFormat::colorize('&r'.$nameFinal, '&'));
		return $item;
	}
	
	public static function getPlayerCustomItem(string $name,string $nameFinal,array $param) : Item{
		$pathSkinsHead = self::$instance->getDataFolder() . "skins" . DIRECTORY_SEPARATOR;
		if (isset($param["usable"]["skinchange"]) && $param["usable"]["skinchange"] == 1)
			$item = (ItemFactory::get(Item::COMMAND_BLOCK, 3))
			->setCustomBlockData(new CompoundTag("", [
				new CompoundTag('skin', [
					new StringTag('Name', $name),
					new ByteArrayTag('Data', MyEntities::createSkin($name)),
				]),
				new ByteArrayTag('skin_empty', MyEntities::createSkin($name."_empty")),
				MyEntities::arrayToCompTag($param,"param"),
				new StringTag('Geometry', file_get_contents($pathSkinsHead.$name.'.json'))
				]))
			->setCustomName(TextFormat::colorize('&r'.$nameFinal, '&'));
		else
			$item = (ItemFactory::get(Item::COMMAND_BLOCK, 3))
			->setCustomBlockData(new CompoundTag("", [
				new CompoundTag('skin', [
					new StringTag('Name', $name),
					new ByteArrayTag('Data', MyEntities::createSkin($name)),
				]),
				MyEntities::arrayToCompTag($param,"param"),
				new StringTag('Geometry', file_get_contents($pathSkinsHead.$name.'.json'))
				
				]))
			->setCustomName(TextFormat::colorize('&r'.$nameFinal, '&'));
		return $item;
	}

    public static function createSkin($skinName){
			$path = MyEntities::getInstance()->getDataFolder()."skins\\{$skinName}.png";
			$img = @imagecreatefrompng($path);
			$bytes = '';
			$l = (int) @getimagesize($path)[1];
			for ($y = 0; $y < $l; $y++) {
				for ($x = 0; $x < 64; $x++) {
					$rgba = @imagecolorat($img, $x, $y);
					$a = ((~((int)($rgba >> 24))) << 1) & 0xff;
					$r = ($rgba >> 16) & 0xff;
					$g = ($rgba >> 8) & 0xff;
					$b = $rgba & 0xff;
					$bytes .= chr($r) . chr($g) . chr($b) . chr($a);
				}
			}
			@imagedestroy($img);
			return $bytes;
    }
    public static function arrayToCompTag($array,String $arrayname){
		$tag = new CompoundTag($arrayname, []);
		foreach($array as $key => $value){
			if (is_int($value)) $tag->setTag(new IntTag($key, $value));
			elseif (is_string($value)) $tag->setTag(new StringTag($key, $value));
			elseif (is_array($value)) $tag->setTag(MyEntities::arrayToCompTag($value,$key));
		}
		return $tag;
    }
	
	public static function logMessage(String $message, $level){
		//Level 0 Only Error
		if(self::$miscList["log-level"] >= $level)		self::$instance->getLogger()->info("§4".$message);
		//Level 1 Minimal thing
		else if(self::$miscList["log-level"] >= $level)	self::$instance->getLogger()->info($message);
		//Level 2 Usless Thing
		else if(self::$miscList["log-level"] >= $level)	self::$instance->getLogger()->info($message);
    }
	
}