<?php

declare(strict_types=1);

namespace Benda95280\MyEntities\commands;

use Benda95280\MyEntities\commands\arguments\PlayerNameTargetArgument;
use Benda95280\MyEntities\entities\head\HeadProperties;
use Benda95280\MyEntities\MyEntities;
use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class HeadCommand extends BaseSubCommand
{
    /**
     * This is where all the arguments, permissions, sub-commands, etc would be registered
     * @throws \CortexPE\Commando\exception\ArgumentOrderException
     */
    protected function prepare(): void
    {
        /*
         * /mye head {PlayerName} : Give player headObj
         */
        $this->registerArgument(0, new PlayerNameTargetArgument("player", true));
        $this->setPermission("MyEntities.head");
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
        if (!$player instanceof Player || !$sender instanceof Player) return;
        $properties = new HeadProperties();
        $properties->skin = $player->getSkin();
        $properties->userName = $player->getName();
        $properties->name = TextFormat::colorize(sprintf('&r&6%s\'s Head', $player->getName()), '&');
        $sender->getInventory()->addItem(MyEntities::getPlayerHeadItem($properties));
        $sender->sendMessage(TextFormat::colorize(sprintf(MyEntities::getInstance()->getConfig()->get("messages")['message-head-added'], $player->getName())));
    }
}
