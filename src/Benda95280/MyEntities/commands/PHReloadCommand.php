<?php

declare(strict_types=1);

namespace Benda95280\MyEntities\commands;

use Benda95280\MyEntities\MyEntities;
use Benda95280\MyEntities\CheckIn;
use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class PHReloadCommand extends BaseSubCommand
{
    /**
     * This is where all the arguments, permissions, sub-commands, etc would be registered
     * @throws \CortexPE\Commando\exception\ArgumentOrderException
     */
    protected function prepare(): void
    {
        /*
         * /mye reload : Reload Configuration
         */
        $this->setPermission("MyEntities.reload");
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
        $countFileSkinsHeadSmall = 0;
        $countFileSkinsHeadNormal = 0;
        $countFileSkinsHeadBlock = 0;
        $countFileSkinsCustom = 0;
		MyEntities::loadConfig();

        try {
            CheckIn::check($countFileSkinsHeadSmall, $countFileSkinsHeadNormal, $countFileSkinsHeadBlock, $countFileSkinsCustom);
        } catch (\InvalidStateException $e) {
        }
    }
}
