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
	/** @var bool */
	private $dropDeath = false;
	/** @var string */
    private static $instance;
	public static $skinsList;

	public const PREFIX = TextFormat::BLUE . 'PlayerHeadObj' . TextFormat::DARK_GRAY . '> ';
	
	public function onEnable() : void{
		$this->getLogger()->info("§aLoading ...");

        if (self::$instance === null) {
            self::$instance = $this;
        }
		
		$this->saveDefaultConfig();

		$data = $this->getConfig()->getAll();
		self::$skinsList = $data["skins"];
		
		Entity::registerEntity(HeadEntityObj::class, true, ['PlayerHeadObj']);

		$this->getServer()->getCommandMap()->register('PlayerHeadObj', new PHCommand($data["message"]));
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		//Count skins available
		$pathSkinsHead = PlayerHeadObj::getInstance()->getDataFolder()."skins\\";
		$countFileSkinsHeadSmall = 0;
		$countFileSkinsHeadNormal= 0;
		foreach(self::$skinsList as $skinName => $skinValue) {
			if (!file_exists($pathSkinsHead.$skinName.'.png')) {
				$this->getLogger()->info("§4'".$skinName."' Do not have any skin (png) file ! It has been removed from plugin.");
				unset(self::$skinsList[$skinName]);
				continue;
			}
			if ($skinValue["type"] == "head") {
				if ($skinValue["size"] === 0) $countFileSkinsHeadSmall++;
				else if ($skinValue["size"] === 1) $countFileSkinsHeadNormal++;
				else {
					$this->getLogger()->info("§4'".$skinName."' Size error ! It has been removed from plugin.");
					unset(self::$skinsList[$skinName]);
					continue;
				}
			}
			else {
				$this->getLogger()->info("§4'".$skinName."' Type do not exist ! It has been removed from plugin.");
				unset(self::$skinsList[$skinName]);
				continue;
			}
		}
		$this->getLogger()->info("§b§l$countFileSkinsHeadSmall §r§bHead skin small§r§f found");
		$this->getLogger()->info("§b§l$countFileSkinsHeadNormal §r§bHead skin normal§r§f found");
		$this->getLogger()->info("§aActivated");
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
		echo $yaw;
		if($yaw < 0){
			$yaw += 360.0;
		}

		foreach([45, 90, 135, 180, 225, 270, 315, 360] as $direction){
			if($yaw <= $direction){
				return $direction;
			}
		}

		return $yaw;
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