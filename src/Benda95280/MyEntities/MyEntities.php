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
use CortexPE\Commando\PacketHooker;
use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class MyEntities extends PluginBase implements Listener
{
    /** @var MyEntities */
    private static $instance;
    /** @var array $skinsList */
    public static $skinsList;
    /** @var array $miscList */
    public static $miscList;
    /** @var array $configData */
    public static $configData;
    /** @var String $pathSkins */
    public static $pathSkins;
    /** @var array $pathSkins */
    public static $language;

    public const PREFIX = TextFormat::BLUE . 'MyEntities' . TextFormat::DARK_GRAY . '> ' . TextFormat::WHITE;

    /**
     * @throws \pocketmine\plugin\PluginException
     * @throws \CortexPE\Commando\exception\HookAlreadyRegistered
     */
    public function onEnable(): void
    {
        if (self::$instance === null) {
            self::$instance = $this;
        }
        if (!PacketHooker::isRegistered()) {
            PacketHooker::register($this);
        }

        self::$instance->saveDefaultConfig();
        self::loadConfig();
		self::initializeLanguage();
		
        self::logMessage("§a".self::$language['init_loading']." ...", 1);

        //Set Folder Skins
        self::$pathSkins = $this->getDataFolder() . "skins";
        //Save default skins on first load
        if (!is_dir(self::$pathSkins)) {
            foreach ($this->getResources() as $resource) {
                $this->saveResource("skins" . DIRECTORY_SEPARATOR . $resource->getFilename());
            }
        }
        self::$pathSkins .= DIRECTORY_SEPARATOR;

        Entity::registerEntity(MyCustomEntity::class, true, ['MyEntities']);
        $this->getServer()->getCommandMap()->registerAll("MyEntities", [
            new PHCommand("myentities", self::$language['cmd_myentities'], ["mye"]),
        ]);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        //Count skins available
        $countFileSkinsHeadSmall = 0;
        $countFileSkinsHeadNormal = 0;
        $countFileSkinsHeadBlock = 0;
        $countFileSkinsCustom = 0;

        try {
            CheckIn::check($countFileSkinsHeadSmall, $countFileSkinsHeadNormal, $countFileSkinsHeadBlock, $countFileSkinsCustom);
        } catch (\InvalidStateException $e) {
        }
    }

    public static function loadConfig()
    {
        self::getInstance()->reloadConfig();
        //Load configuration file
        $data = self::getInstance()->getConfig()->getAll();
        //Define Public Var Data-Config File
        self::$configData = $data;
        self::$skinsList = $data["skins"];
        self::$miscList = $data["misc"];
    }

    public static function getInstance(): MyEntities
    {
        return self::$instance;
    }

    /**
     * @param string $name
     * @param string $nameFinal
     * @param array $param
     * @return Item
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public static function getPlayerHeadItem(string $name, string $nameFinal, array $param): Item
    {
        if (isset($param["usable"]["skinchange"]) && $param["usable"]["skinchange"] == 1)
            $item = (ItemFactory::get(Item::MOB_HEAD, 3))
                ->setCustomBlockData(new CompoundTag("", [
                    new CompoundTag('skin', [
                        new StringTag('Name', $name),
                        new ByteArrayTag('Data', MyEntities::createSkin($name)),
                    ]),
                    new ByteArrayTag('skin_empty', MyEntities::createSkin($name . "_empty")),
                    MyEntities::arrayToCompTag($param, "param")
                ]))
                ->setCustomName(TextFormat::colorize('&r' . $nameFinal, '&'));
        else if (isset($param["data"]))
            $item = (ItemFactory::get(Item::MOB_HEAD, 3))
                ->setCustomBlockData(new CompoundTag("", [
                    new CompoundTag('skin', [
                        new StringTag('Name', $name),
                        new ByteArrayTag('Data', $param["data"]),
                    ]),
                    MyEntities::arrayToCompTag($param, "param")
                ]))
                ->setCustomName(TextFormat::colorize(sprintf('&r&6%s\'s Head', $nameFinal), '&'));
        else
            $item = (ItemFactory::get(Item::MOB_HEAD, 3))
                ->setCustomBlockData(new CompoundTag("", [
                    new CompoundTag('skin', [
                        new StringTag('Name', $name),
                        new ByteArrayTag('Data', MyEntities::createSkin($name)),
                    ]),
                    MyEntities::arrayToCompTag($param, "param")
                ]))
                ->setCustomName(TextFormat::colorize('&r' . $nameFinal, '&'));
        return $item;
    }

    /**
     * @param string $name
     * @param string $nameFinal
     * @param array $param
     * @return Item
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public static function getPlayerCustomItem(string $name, string $nameFinal, array $param): Item
    {
        $pathSkinsHead = self::$instance->getDataFolder() . "skins" . DIRECTORY_SEPARATOR;
        if (isset($param["usable"]["skinchange"]) && $param["usable"]["skinchange"] == 1)
            $item = (ItemFactory::get(Item::END_PORTAL_FRAME))
                ->setCustomBlockData(new CompoundTag("", [
                    new CompoundTag('skin', [
                        new StringTag('Name', $name),
                        new ByteArrayTag('Data', MyEntities::createSkin($name)),
                    ]),
                    new ByteArrayTag('skin_empty', MyEntities::createSkin($name . "_empty")),
                    MyEntities::arrayToCompTag($param, "param"),
                    new StringTag('Geometry', file_get_contents($pathSkinsHead . $name . '.json'))
                ]))
                ->setCustomName(TextFormat::colorize('&r' . $nameFinal, '&'));
        else
            $item = (ItemFactory::get(Item::END_PORTAL_FRAME))
                ->setCustomBlockData(new CompoundTag("", [
                    new CompoundTag('skin', [
                        new StringTag('Name', $name),
                        new ByteArrayTag('Data', MyEntities::createSkin($name)),
                    ]),
                    MyEntities::arrayToCompTag($param, "param"),
                    new StringTag('Geometry', file_get_contents($pathSkinsHead . $name . '.json'))

                ]))
                ->setCustomName(TextFormat::colorize('&r' . $nameFinal, '&'));
        return $item;
    }
	
	private function initializeLanguage(){
		if (!isset(self::$miscList['language'])) {
			self::logMessage("Language not set, English applied", 0);
            self::$miscList['language'] = 'en';
			//TODO: Error here if language is missing:
			//[Server thread/CRITICAL]: ErrorException: "Undefined index: language" (EXCEPTION) in "plugins/MyEntities/src/Benda95280/MyEntities/MyEntities" at line 205
		}
		$languageSet = self::$miscList['language'];
		//Get all language file
		$language = [];
		foreach($this->getResources() as $resource){
			if($resource->isFile() and substr(($filename = $resource->getFilename()), 0, 5) === "lang_"){
				$language[substr($filename, 5, -4)] = yaml_parse(file_get_contents($resource->getPathname()));
			}
		}
		//Check if language exist, has set in config
		if (isset($language[$languageSet])) {
			self::$language = $language[$languageSet];
		}
		else {
			self::$language = $language["en"];
			self::logMessage(sprintf(self::$language['init_lang_notexist'], $languageSet), 0);
		}
	}

    public static function createSkin($skinName)
    {
        $path = MyEntities::getInstance()->getDataFolder() . "skins" . DIRECTORY_SEPARATOR . "{$skinName}.png";
        $img = @imagecreatefrompng($path);
        $bytes = '';
        for ($y = 0; $y < imagesy($img); $y++) {
            for ($x = 0; $x < imagesx($img); $x++) {
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

    /**
     * @param $array
     * @param String $arrayname
     * @return CompoundTag
     */
    public static function arrayToCompTag($array, String $arrayname)
    {
        $nbt = new BigEndianNBTStream();
        $tag = $nbt::fromArray($array);
        $tag->setName($arrayname);
        return $tag;
    }

    public static function logMessage(String $message, $level)
    {
        //Level 0 Only Error
        if (self::$miscList["log-level"] >= $level) self::$instance->getLogger()->info("§4" . $message);
        //Level 1 Minimal thing
        else if (self::$miscList["log-level"] >= $level) self::$instance->getLogger()->info($message);
        //Level 2 Useless Thing
        else if (self::$miscList["log-level"] >= $level) self::$instance->getLogger()->info($message);
    }

}