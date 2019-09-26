<?php

declare(strict_types=1);

namespace Benda95280\MyEntities\commands;

use Benda95280\MyEntities\MyEntities;
use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class PHHeadCommand extends BaseSubCommand
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
        if (!$player instanceof Player) return;

        $param = ['size' => 'normal', 'health' => 1, 'unbreakable' => 0, 'data' => $player->getSkin()->getSkinData()];
        $player->getInventory()->addItem(MyEntities::getPlayerHeadItem($player->getName(), $player->getName(), $param));
        $player->sendMessage(TextFormat::colorize(sprintf(MyEntities::getInstance()->getConfig()->get("messages")['message-head-added'], $player->getName())));
    }
}
