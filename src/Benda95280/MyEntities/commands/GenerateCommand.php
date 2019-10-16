<?php

declare(strict_types=1);

namespace Benda95280\MyEntities\commands;

use Benda95280\MyEntities\commands\arguments\EntityTypeArgument;
use Benda95280\MyEntities\entities\entity\CustomEntityProperties;
use Benda95280\MyEntities\entities\head\HeadProperties;
use Benda95280\MyEntities\entities\vehicle\VehicleProperties;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class GenerateCommand extends BaseSubCommand
{
    /**
     * This is where all the arguments, permissions, sub-commands, etc would be registered
     * @throws \CortexPE\Commando\exception\ArgumentOrderException
     */
    protected function prepare(): void
    {
        /*
         * /mye generate <type> <name> : Generate an entity configuration
         */
        $this->registerArgument(0, new EntityTypeArgument("type", false));
        $this->registerArgument(1, new RawStringArgument("name", false));
        $this->setPermission("MyEntities.generate");
    }

    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param array $args
     * @throws \InvalidArgumentException
     */
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        /** @var Player $sender */
        $type = (string)$args["type"];
        $name = (string)$args["name"];
        if (empty(trim($name))) {
            $sender->sendMessage(TextFormat::RED . "Name cannot be empty");//todo translation
            return;
        }
        switch ($type) {
            case EntityTypeArgument::ENTITY:
                {
                    $properties = new CustomEntityProperties();
                    $sender->sendForm($properties->getForm());
                    break;
                }
            case EntityTypeArgument::HEAD:
                {
                    $properties = new HeadProperties();
                    $sender->sendForm($properties->getForm());
                    break;
                }
            case EntityTypeArgument::VEHICLE:
                {
                    $properties = new VehicleProperties();
                    $sender->sendForm($properties->getForm());
                    break;
                }
            default:
                {
                    $sender->sendMessage(TextFormat::RED . "Incorrect type given");
                    return;
                }
        }
    }
}
