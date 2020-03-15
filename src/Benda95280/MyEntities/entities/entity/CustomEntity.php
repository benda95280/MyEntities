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

namespace Benda95280\MyEntities\entities\entity;

use Benda95280\MyEntities\entities\BaseEntity;
use Benda95280\MyEntities\MyEntities;
use InvalidArgumentException;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;

class CustomEntity extends BaseEntity
{
    public function __construct(Level $level, CompoundTag $nbt)
    {
        $properties = new CustomEntityProperties($nbt->getCompoundTag("MyEntities"));
        parent::__construct($properties, $level, $nbt);
        $properties->skin = $this->skin;
    }

    /**
     * @return Block
     * @throws InvalidArgumentException
     */
    public static function getDestroyParticlesBlock(): Block
    {
        return BlockFactory::get(BlockIds::REDSTONE_BLOCK);//Bloody
    }

    public function getDrops(): array
    {
        return [MyEntities::getPlayerCustomItem($this->properties)];
    }
}