<?php

declare(strict_types=1);

namespace Benda95280\MyEntities\commands;

use Benda95280\MyEntities\MyEntities;
use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class EditEntityCommand extends BaseSubCommand
{
	/**
	 * This is where all the arguments, permissions, sub-commands, etc would be registered
     */
	protected function prepare(): void
	{
		/*
		 * /mye edit
		 */
		$this->setPermission("MyEntities.edit");
	}

	/**
	 * @param CommandSender $sender
	 * @param string $aliasUsed
	 * @param array $args
	 */
	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
	{
		$player = $args["player"] ?? $sender;
		if (!$player instanceof Player) return;
		MyEntities::$editing[$player->getRawUniqueId()] = true;
		$sender->sendMessage(MyEntities::PREFIX . "Hit entity to edit");
	}
}
