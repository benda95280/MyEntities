# About it
If you use this plugin, please consider to share (By opening an issue) your entities, skins or model.
It will encourage me to continue and try to put new functionalities.
* *Main use for the plugin was for my child* , i continue only for **you**
If you wanna help me, you will find on what i'm stuck at the bottom of the page


**Main idea and code - Thanks to Enes5519**

https://github.com/Enes5519/PlayerHead/

-----------------

# MyEntities
[![HitCount](http://hits.dwyl.io/benda95280/MyEntities.svg)](http://hits.dwyl.io/benda95280/MyEntities)
Download: https://poggit.pmmp.io/ci/benda95280/MyEntities

Do you need to add entity and manage it easily , and add more blocks/items to your server ?
Want a life dispenser ? A small flower pot ? Or just some popcorn ?

## Features:
* !! Some examples included !!
* Can be run from console
* Automatic creation of entity in config file when Skin in folder and not exist (I'm so lazy ...)
* Items/entities can be given to players
* You can place entities with custom head texture EASILY
* You can place custom entities, with geometry
* Powerful automatic verification of your config file (See Screenshot) -> Change 'log-level' in config file for more informations
* Custom parameters for entity:
  * Health
  * Unbreakable
  * 3 Sizes (Head Entity): Small / Normal / Block
  * Entity can be 'usable'
    * Max time usable
    * Actions (Infinite - Array): Message / Teleport / Heal / Effects / Give items
    * One random action from all actions possible
      * Support set of action (Set 1: Message + item / Set2: Message + item + effect ...) when random action enabled
    * Change skin when no more usable
    * Auto destruction when no more usable
    * Auto destruction message
    * Show message when used
    * Show message when empty

* Item to help :
  * Remover (To remove unbreakable entity)
  * Rotator (Change orientation of entity: 45° or 90°)
  
# Commands
- **/mye entity [SkinName] {PlayerName} : Give player headObj**
- **/mye item remover {PlayerName} : Give item Remover**
- **/mye item rotator {PlayerName} : Give item Rotator**

(PlayerName is optional)

# Config extract :
```
skins:
  book_1:
    type: 'head'
    name: 'Nice Book'
    param:
      size: 'small'
      health: 1
      unbreakable: 1
      usable:
        #Number usable time
        time: 3
        #New Skin when empty ? (Skin name must be '*_empty.png')
        skinchange: 1
        #Automatic Reload in second ? 0 = Never / Max 300 (5min)
        reload: 0
        #Destruction when empty ? 0 = No
        destruction: 0
        #Message when using (Empty/remaining ...)?
        use_msg: 1
        #Custom message when descruction happen
        destruction_msg: "Oups, sorry ..."
        #When used, what it does ?
		# Heal 1 = Half Hearth
		# Effects: EFFECT/Amplifier/Duration   |   Items: ID/meta/count
        # action: '{"heal": 1, "teleport": "1;2;3", "effect": "1/1/30;2/1/60;3/1/80", "item": "1/1/30;2/1/60;3/1/20", "msg": "May the force be with you ..."}'
        # action: '{"Set1":{"heal": 4, "item": "1/1/30;2/1/60;3/1/80"}, "Set2":{"heal":8,"msg":"Big Heal;Dude;You like that ?" }}'
		action: '{"heal": 1, "effect": "1/1/30;2/1/60;3/1/80", "msg": "May the force be with you ..."}'
        #choose one random action ?
        action_random: 1
```
  
# Screenshot 
<img height=200 src="https://i.ibb.co/9wq4s7R/playerheadobj-V1.png" />
<img height=200 src="https://i.ibb.co/wgQZ0m9/playerheadobj-V2.png" /><img height=200 src="https://i.ibb.co/dtpjQ8h/playerheadobj-V2-usable.png" />
<img height=200 src="https://i.ibb.co/kmLfxqn/playerheadobj-Log-Example.png" />
(Inspired from ... HalfLife, please send me a new model/skin if you can ...)

<img height=200 src="https://i.ibb.co/jyVrWBy/Life-Dispenser-4.gif" />



# Skins Credits
Source / credits of the skin: https://minecraft-heads.com/

book: https://minecraft-heads.com/custom-heads/decoration/30771-old-book

bowl_pasta: https://minecraft-heads.com/custom-heads/food%20&%20drinks/30178-bowl-of-pasta-with-tomato-sauce

calice: https://minecraft-heads.com/custom-heads/decoration/883-golden-chalice

crate_locked: https://minecraft-heads.com/custom-heads/decoration/31223-locked-crate-gray

bible: https://minecraft-heads.com/custom-heads/decoration/603-bible

-----------------

# Thanks to

A big thanks to :

- HimbeersaftLP
- Kenn Fatt
- XenialDan (Major ReWrite)

-----------------

# Working On

## I'm working on:
- [ ] Control what's dropped when entity is killed
- [ ] New actions : item/block dropping - Money (Later)
- [ ] Prevent spam of the entity (per player) - (Thinking to use Tick number + Array NBT)
- [ ] Prevent spam of the entity (per Entity) - (Thinking to use Tick number)
- [ ] Looking how to add custom sound on use and empty (I like HalfLife sound :) )


## I'm stuck on (Help needed):
- [x] Looking for information about: Entity act as a Block (Collision, breaking ...)
- [ ] Collision seems very strange, it's like knocback when you touch it
- [ ] Prevent placing entity over existing entity (Like flower)
- [ ] Is it possible to added floating effect to the entity ? (Like a cloud)
- [ ] Looking for help for the reload function in the entity. I've to use a Task, but i want a task that check ALL of my entity that need to be updated, and not a task per entity
- [x] Looking to the right way to have the file CheckIn.php loaded


## Known BUG
- Wait issue
