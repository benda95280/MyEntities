<?php

declare(strict_types=1);

namespace Benda95280\MyEntities\commands;

use Benda95280\MyEntities\commands\arguments\PlayerNameTargetArgument;
use Benda95280\MyEntities\entities\entity\CloneEntityProperties;
use Benda95280\MyEntities\MyEntities;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use InvalidArgumentException;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use RuntimeException;

class CloneCommand extends BaseSubCommand
{
	/**
	 * This is where all the arguments, permissions, sub-commands, etc would be registered
	 * @throws ArgumentOrderException
	 */
	protected function prepare(): void
	{
		/*
		 * /mye head {PlayerName} : Give player headObj
		 */
		$this->registerArgument(0, new PlayerNameTargetArgument("player", true));
		$this->setPermission("MyEntities.clone");
	}

	/**
	 * @param CommandSender $sender
	 * @param string $aliasUsed
	 * @param array $args
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
	{
		$player = $args["player"] ?? $sender;
		if (!$player instanceof Player || !$sender instanceof Player) return;
		$properties = new CloneEntityProperties();
		$properties->skin = $player->getSkin();
		$properties->name = $player->getDisplayName();
		$properties->width = $player->width;
		$properties->height = $player->height;
		$properties->scale = $player->getScale();
		$sender->getInventory()->addItem(MyEntities::getPlayerCloneItem($properties));
		$sender->sendMessage(TextFormat::colorize(sprintf(MyEntities::getInstance()->getConfig()->get("messages")['message-clone-added'], $player->getName())));//TODO
	}
}
