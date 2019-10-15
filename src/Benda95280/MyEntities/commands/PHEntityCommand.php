<?php

declare(strict_types=1);

namespace Benda95280\MyEntities\commands;

use Benda95280\MyEntities\entities\entity\CustomEntityProperties;
use Benda95280\MyEntities\entities\head\HeadProperties;
use Benda95280\MyEntities\entities\Properties;
use Benda95280\MyEntities\entities\vehicle\VehicleProperties;
use Benda95280\MyEntities\MyEntities;
use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\entity\Skin;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class PHEntityCommand extends BaseSubCommand
{
    /**
     * This is where all the arguments, permissions, sub-commands, etc would be registered
     * @throws \CortexPE\Commando\exception\ArgumentOrderException
     */
    protected function prepare(): void
    {
        /*
         * /mye entity [SkinName] {PlayerName} : Give player headObj
         */
        $this->registerArgument(0, new SkinsEnumArgument("skin", false));
        $this->registerArgument(1, new PlayerNameTargetArgument("player", true));
        $this->setPermission("MyEntities.give");
    }

    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param array $args
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $player = $args["player"] ?? $sender;
        if (!$player instanceof Player) return;

        if (!empty($skinName = ($args["skin"] ?? ""))) {
            if (isset(MyEntities::$skinsList[$skinName])) {
                $pathSkinsHead = MyEntities::getInstance()->getDataFolder() . "skins" . DIRECTORY_SEPARATOR;
                $nameFinal = ucfirst(MyEntities::$skinsList[$skinName]['name']);
                $param = MyEntities::$skinsList[$skinName]['param'];
                if (MyEntities::$skinsList[$skinName]['type'] === "custom") {
                    $properties = new CustomEntityProperties(MyEntities::arrayToCompTag($param, Properties::PROPERTY_TAG));
                    $properties->name = $nameFinal;
                    $properties->skin = new Skin(
                        $skinName,
                        MyEntities::createSkin($skinName),
                        "",
                        $properties->geometryName,
                        file_get_contents($pathSkinsHead . $skinName . '.json')
                    );
                    $player->getInventory()->addItem(MyEntities::getPlayerCustomItem($properties));
                    #$player->getInventory()->addItem(MyEntities::getPlayerCustomItem($skinName, $nameFinal, $param));
                }
                if (MyEntities::$skinsList[$skinName]['type'] === "vehicle") {
                    $properties = new VehicleProperties(MyEntities::arrayToCompTag($param, Properties::PROPERTY_TAG));
                    $properties->name = $nameFinal;
                    $properties->skin = new Skin(
                        $skinName,
                        MyEntities::createSkin($skinName),
                        "",
                        $properties->geometryName,
                        file_get_contents($pathSkinsHead . $skinName . '.json')
                    );
                    $player->getInventory()->addItem(MyEntities::getPlayerCustomItemVehicle($properties));
                    #$player->getInventory()->addItem(MyEntities::getPlayerCustomItem($skinName, $nameFinal, $param));
                } else {
                    $properties = new HeadProperties(MyEntities::arrayToCompTag($param, Properties::PROPERTY_TAG));
                    $properties->name = $nameFinal;
                    $properties->skin = new Skin(
                        $skinName,
                        MyEntities::createSkin($skinName),
                        "",
                        HeadProperties::GEOMETRY_NAME,
                        HeadProperties::GEOMETRY
                    );
                    $player->getInventory()->addItem(MyEntities::getPlayerHeadItem($properties));
                    #$player->getInventory()->addItem(MyEntities::getPlayerHeadItem($skinName, $nameFinal, $param));
                }
                $player->sendMessage(TextFormat::colorize(sprintf(MyEntities::getInstance()->getConfig()->get("messages")['message-head-added'], $nameFinal)));

            } else {
                $sender->sendMessage(MyEntities::PREFIX . "Error: Entity do not exist !");
            }
        }
    }
}
