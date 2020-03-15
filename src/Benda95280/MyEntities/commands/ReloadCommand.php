<?php

declare(strict_types=1);

namespace Benda95280\MyEntities\commands;

use Benda95280\MyEntities\CheckIn;
use Benda95280\MyEntities\MyEntities;
use CortexPE\Commando\BaseSubCommand;
use InvalidStateException;
use pocketmine\command\CommandSender;

class ReloadCommand extends BaseSubCommand
{
    /**
     * This is where all the arguments, permissions, sub-commands, etc would be registered
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
        } catch (InvalidStateException $e) {
        }
    }
}
