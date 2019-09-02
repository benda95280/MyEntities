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
use pocketmine\nbt\tag\ByteArrayTag;
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
		$countFileSkinsHeadNormal= 0;
		foreach(self::$skinsList as $skinName => $skinValue) {
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
			
			//** Entity verification **//
			
			// HEAD ENTITY //
			//Type of entity is a must to have
			if (isset($skinValue["type"]) || $skinValue["type"] == "head") {
				//Head must have a size
				if (isset($skinValue["size"]) && $skinValue["size"] === 0) $countFileSkinsHeadSmall++;
				else if (isset($skinValue["size"]) && $skinValue["size"] === 1) $countFileSkinsHeadNormal++;
				else {
					$this->getLogger()->info("§4'".$skinName."' Size error ! It has been removed from plugin.");
					unset(self::$skinsList[$skinName]);
					continue;
				}
				if (self::$miscList["log-level"] > 1)	$this->getLogger()->info("§b§lLoaded: §r§6Head Skin§r§f $skinName / Size: ".$skinValue["size"]." / name: '".$skinValue["name"]."'");

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
            $blockData->setName('Skin');
			$nbt->setTag($blockData);
            (new HeadEntityObj($player->level, $nbt))->spawnToAll();
			if(!$player->isCreative()){
				$player->getInventory()->setItemInHand($item->setCount($item->getCount() - 1));
			}
			$event->setCancelled();
		}
	}

	private static function getYaw(Vector3 $pos, Vector3 $target) : float{
		$yaw = atan2($target->z - $pos->z, $target->x - $pos->x) / M_PI * 180 - 90;
		if($yaw < 0){
			$yaw += 360.0;
		}
		// Round to nearest multiple of 45
		return round($yaw / 45) * 45;
	}

	/**
	 * @param string $name
	 * @return Item
	 */
	public static function getPlayerHeadItem(string $name,string $nameFinal) : Item{
		return (ItemFactory::get(Item::MOB_HEAD, 3))
			->setCustomBlockData(new CompoundTag('Skin', [
				new StringTag('Name', $name),
				new ByteArrayTag('Data', PlayerHeadObj::createSkin($name))
			]))
			->setCustomName(TextFormat::colorize('&r'.$nameFinal, '&'));
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
	
}