<?php

declare(strict_types=1);

namespace Benda95280\MyEntities\commands;

use Benda95280\MyEntities\MyEntities;
use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
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
                $nameFinal = ucfirst(MyEntities::$skinsList[$skinName]['name']);
                $param = MyEntities::$skinsList[$skinName]['param'];
                //Checker si l'entity est custom ...
                if (MyEntities::$skinsList[$skinName]['type'] == "custom") {
                    $player->getInventory()->addItem(MyEntities::getPlayerCustomItem($skinName, $nameFinal, $param));
                } else {
                    $player->getInventory()->addItem(MyEntities::getPlayerHeadItem($skinName, $nameFinal, $param));
                }
                $player->sendMessage(TextFormat::colorize(sprintf(MyEntities::getInstance()->getConfig()->get("messages")['message-head-added'], $nameFinal)));

            } else {
                $sender->sendMessage(MyEntities::PREFIX . "Error: Entity do not exist !");
            }
        }
    }
}
