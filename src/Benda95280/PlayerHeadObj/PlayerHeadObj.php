<?php

/*	
 *  Original Source: https://github.com/Enes5519/PlayerHead 
 *  PlayerHeadObj - a Altay and PocketMine-MP plugin to add player head on server
 *  Copyright (C) 2018 Enes Yıldırım
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

namespace Benda95280\PlayerHeadObj;

use Benda95280\PlayerHeadObj\commands\PHCommand;
use Benda95280\PlayerHeadObj\entities\HeadEntityObj;
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



class PlayerHeadObj extends PluginBase implements Listener{
	/** @var PlayerHeadObj  */
    private static $instance;
	/** @var array $skinsList */
	public static $skinsList;
	/** @var array $miscList */
	public static $miscList;

	public const PREFIX = TextFormat::BLUE . 'PlayerHeadObj' . TextFormat::DARK_GRAY . '> '.TextFormat::WHITE;
	
	public function onEnable() : void{

        if (self::$instance === null) {
            self::$instance = $this;
        }
		
		//Load configuration file
		$this->saveDefaultConfig();
		$data = $this->getConfig()->getAll();
		self::$skinsList = $data["skins"];
		self::$miscList = $data["misc"];
		
		if (self::$miscList["log-level"] > 0) $this->getLogger()->info("§aLoading ...");
		
		Entity::registerEntity(HeadEntityObj::class, true, ['PlayerHeadObj']);

		$this->getServer()->getCommandMap()->register('PlayerHeadObj', new PHCommand($data["message"]));
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		//Count skins available
		$pathSkinsHead = $this->getDataFolder() . "skins" . DIRECTORY_SEPARATOR;
		$countFileSkinsHeadSmall = 0;
		$countFileSkinsHeadNormal = 0;
		$countFileSkinsHeadBlock = 0;
		foreach(self::$skinsList as $skinName => $skinValue) {
			
			// ** BASIC CHECK ** //
			
			//Entity must have a skin file
			if (!file_exists($pathSkinsHead.$skinName.'.png')) {
				$this->getLogger()->info("§4'".$skinName."' Do not have any skin (png) file ! It has been removed from plugin.");
				unset(self::$skinsList[$skinName]);
				continue;
			}
			//Entity declaration cannot have white space and correct lenght
			if (preg_match('/\s/',$skinName) || strlen($skinName) <= 4 || strlen($skinName) >= 16) {
				$this->getLogger()->info("§4'".$skinName."' Entity declaration cannot contain space and have 4-16 Char ! It has been removed from plugin.");
				unset(self::$skinsList[$skinName]);
				continue;
			}
			//Entity must have a correct lenght	name
			if (!isset($skinValue["name"]) || strlen($skinValue["name"]) <= 4 || strlen($skinValue["name"]) >= 16) {
				$this->getLogger()->info("§4'".$skinName."' Name must have have 4-16 Char ! It has been removed from plugin.");
				unset(self::$skinsList[$skinName]);
				continue;
			}
			
			// ** BASIC PARAM CHECK ** //
			
			//Entity must have Param child
			if (!isset($skinValue["param"])) {
				$this->getLogger()->info("§4'".$skinName."' must have a param child ! It has been removed from plugin.");
				unset(self::$skinsList[$skinName]);
				continue;
			}
			//Entity must have a parameter Health in Param
			if (!isset($skinValue["param"]["health"]) || !is_int($skinValue["param"]["health"]) || $skinValue["param"]["health"] < 1 || $skinValue["param"]["health"] > 75) {
				$this->getLogger()->info("§4'".$skinName."' must have  1-75 (Int) Health-Param ! It has been removed from plugin.");
				unset(self::$skinsList[$skinName]);
				continue;
			}
			//Entity must have a parameter Unbreakable in Param
			if (!isset($skinValue["param"]["unbreakable"]) || !is_int($skinValue["param"]["unbreakable"]) || !($skinValue["param"]["unbreakable"] == 1 || $skinValue["param"]["unbreakable"] == 0)) {
				$this->getLogger()->info("§4'".$skinName."' must have 0 or 1 int Unbreakable-Param ! It has been removed from plugin.");
				unset(self::$skinsList[$skinName]);
				continue;
			}
			
			// ** USABLE PARAM CHECK ** //
			
			if (isset($skinValue["param"]["usable"]) ) {
				//These variable must be set and correct
				
				
				//Must be unbreakable to be usable !
				if ($skinValue["param"]["unbreakable"] == 0) {
					$this->getLogger()->info("§4'".$skinName."' must be unbreakable, because you set is usable ! It has been removed from plugin.");
					unset(self::$skinsList[$skinName]);
					continue;
				}					
				//Check usable time (1-20)
				if (!isset($skinValue["param"]["usable"]["time"]) || !is_int($skinValue["param"]["usable"]["time"]) || $skinValue["param"]["usable"]["time"] < 1 || $skinValue["param"]["usable"]["time"] > 20) {
					$this->getLogger()->info("§4'".$skinName."' must have correct value for Time-Usable-Param (1-20) ! It has been removed from plugin.");
					unset(self::$skinsList[$skinName]);
					continue;
				}
				//Check Reload Time (0-20)
				if (!isset($skinValue["param"]["usable"]["reload"]) || !is_int($skinValue["param"]["usable"]["reload"]) || $skinValue["param"]["usable"]["reload"] < 0 || $skinValue["param"]["usable"]["reload"] > 300) {
					$this->getLogger()->info("§4'".$skinName."' must have correct value for Reload-Usable-Param (0-300) ! It has been removed from plugin.");
					unset(self::$skinsList[$skinName]);
					continue;
				}
				//Check Skin change 
				if (!isset($skinValue["param"]["usable"]["skinchange"]) || !is_int($skinValue["param"]["usable"]["skinchange"]) || !($skinValue["param"]["usable"]["skinchange"] == 1 || $skinValue["param"]["usable"]["skinchange"] == 0)) {
					$this->getLogger()->info("§4'".$skinName."' must have 0 or 1 int for SkinChange-Usable-Param ! It has been removed from plugin.");
					unset(self::$skinsList[$skinName]);
					continue;
				}
				//Check Skin change, the skin has to exist
				if (isset($skinValue["param"]["usable"]["skinchange"]) && $skinValue["param"]["usable"]["skinchange"] == 1 && !file_exists($pathSkinsHead.$skinName.'_empty.png') ) {
					$this->getLogger()->info("§4'".$skinName."' have skinChange Set, but no skin available '".$skinName."_empty.png'! It has been removed from plugin.");
					unset(self::$skinsList[$skinName]);
					continue;
				}
				//Check Descruction 
				if (!isset($skinValue["param"]["usable"]["destruction"]) || !is_int($skinValue["param"]["usable"]["destruction"]) || !($skinValue["param"]["usable"]["destruction"] == 1 || $skinValue["param"]["usable"]["destruction"] == 0)) {
					$this->getLogger()->info("§4'".$skinName."' must have 0 or 1 int for Destruction-Usable-Param ! It has been removed from plugin.");
					unset(self::$skinsList[$skinName]);
					continue;
				}
				//Check Destruction_MSG
				if (!isset($skinValue["param"]["usable"]["destruction_msg"]) || !is_string($skinValue["param"]["usable"]["destruction_msg"])) {
					$this->getLogger()->info("§4'".$skinName."' must have correct value for Destruction_msg-Usable-Param (String or empty) ! It has been removed from plugin.");
					unset(self::$skinsList[$skinName]);
					continue;
				}
				//Check if message show up when used
				if (!isset($skinValue["param"]["usable"]["use_msg"]) || !is_int($skinValue["param"]["usable"]["use_msg"]) || !($skinValue["param"]["usable"]["use_msg"] == 1 || $skinValue["param"]["usable"]["use_msg"] == 0)) {
					$this->getLogger()->info("§4'".$skinName."' must have 0 or 1 int for use_msg-Usable-Param ! It has been removed from plugin.");
					unset(self::$skinsList[$skinName]);
					continue;
				}
				//Check Action is set
				if (!isset($skinValue["param"]["usable"]["action"]) || !is_string($skinValue["param"]["usable"]["action"])) {
					$this->getLogger()->info("§4'".$skinName."' must be set or empty for action_random-Usable-Param ! It has been removed from plugin.");
					unset(self::$skinsList[$skinName]);
					continue;
				}
				//Check RandomAction change when empty
				if (!isset($skinValue["param"]["usable"]["action_random"]) || !is_int($skinValue["param"]["usable"]["action_random"]) || !($skinValue["param"]["usable"]["action_random"] == 1 || $skinValue["param"]["usable"]["action_random"] == 0)) {
					$this->getLogger()->info("§4'".$skinName."' must have 0 or 1 int for action_random-Usable-Param ! It has been removed from plugin.");
					unset(self::$skinsList[$skinName]);
					continue;
				}
			}			
			
			
			//** Entity verification **//
			
			// HEAD ENTITY //
			//Type of entity is a must to have
			
			//TODO: Create missing parameter and save it
			//TODO: Log error if unknown parameter
			
			if (isset($skinValue["type"]) || $skinValue["type"] == "head") {
				//Head must have a size
				if (isset($skinValue["param"]["size"]) && $skinValue["param"]["size"] === "small") $countFileSkinsHeadSmall++;
				else if (isset($skinValue["param"]["size"]) && $skinValue["param"]["size"] === "normal") $countFileSkinsHeadNormal++;
				else if (isset($skinValue["param"]["size"]) && $skinValue["param"]["size"] === "block") $countFileSkinsHeadBlock++;
				else {
					$this->getLogger()->info("§4'".$skinName."' Size error ! It has been removed from plugin.");
					unset(self::$skinsList[$skinName]);
					continue;
				}
				if (self::$miscList["log-level"] > 1)	$this->getLogger()->info("§b§lLoaded: §r§6Head Skin§r§f $skinName / Size: ".$skinValue["param"]["size"]." / name: '".$skinValue["name"]."'");

			}
			else {
				$this->getLogger()->info("§4'".$skinName."' Type do not exist ! It has been removed from plugin.");
				unset(self::$skinsList[$skinName]);
				continue;
			}
		}
		if (self::$miscList["log-level"] > 0) {
			$this->getLogger()->info("§b§l$countFileSkinsHeadSmall §r§bHead skin small§r§f found");
			$this->getLogger()->info("§b§l$countFileSkinsHeadNormal §r§bHead skin normal§r§f found");
			$this->getLogger()->info("§b§l$countFileSkinsHeadBlock §r§bHead skin block§r§f found");
			$this->getLogger()->info("§aActivated");
		}
	}
	
    public static function getInstance() : PlayerHeadObj {
        return self::$instance;
    }

	public function onPlace(BlockPlaceEvent $event) : void{
		$player = $event->getPlayer();
		if($player->hasPermission('PlayerHeadObj.spawn') and ($item = $player->getInventory()->getItemInHand())->getId() === Item::MOB_HEAD and ($blockData = $item->getCustomBlockData()) !== null){
			$nbt = Entity::createBaseNBT($event->getBlock()->add(0.5, 0, 0.5), null, self::getYaw($event->getBlock()->add(0.5, 0, 0.5), $player)); // Add 0.5 because block center is at half coordinate
			if ($blockData->hasTag("skin_empty")) 
				$nbt->setByteArray("Skin_empty", $blockData->getByteArray("skin_empty"));
			$blockDataSkin = $blockData->getCompoundTag("skin");
			$blockDataParam = $blockData->getCompoundTag("param");
			$blockDataSkin->setName('Skin');
			$blockDataParam->setName('Param');
			$nbt->setTag($blockDataSkin);
			$nbt->setTag($blockDataParam);
            (new HeadEntityObj($player->level, $nbt))->spawnToAll();
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
					new ByteArrayTag('Data', PlayerHeadObj::createSkin($name)),
				]),
				new ByteArrayTag('skin_empty', PlayerHeadObj::createSkin($name."_empty")),
				PlayerHeadObj::arrayToCompTag($param,"param")
				]))
			->setCustomName(TextFormat::colorize('&r'.$nameFinal, '&'));
		else
			$item = (ItemFactory::get(Item::MOB_HEAD, 3))
			->setCustomBlockData(new CompoundTag("", [
				new CompoundTag('skin', [
					new StringTag('Name', $name),
					new ByteArrayTag('Data', PlayerHeadObj::createSkin($name)),
				]),
				PlayerHeadObj::arrayToCompTag($param,"param")
				]))
			->setCustomName(TextFormat::colorize('&r'.$nameFinal, '&'));
		return $item;
	}

    public static function createSkin($skinName){
			$path = PlayerHeadObj::getInstance()->getDataFolder()."skins\\{$skinName}.png";
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
			elseif (is_array($value)) $tag->setTag(PlayerHeadObj::arrayToCompTag($value,$key));
		}
		return $tag;
    }
	
}