## Source of the code - Thanks To Enes5519
https://github.com/Enes5519/PlayerHead/

-----------------

# PlayerHeadObj
Features:
* You can place entity with custom head texture
* Custom parameters for entity:
  * Health
  * Unbreakable
  * 3 Sizes: Small / Normal / Block
  * Entity can be 'usable'
    * Max time usable
    * Actions: Message / Teleport / Heal / Effects
    * ChangeSkin when no more usable
    * AutoDestruction when no more usable
    * Show message when used
    * AutoDestruction message
    * Infinite actions (Array)
    * One random action from all actions possible

* Item to help :
  * Remover (To remove unbreakable entity)
  * Rotator (Change orientation of entity: 45° or 90°)
  
## Commands  
- **/pho entity [SkinName] : Give player headObj**
- **/pho item remover : Give item Remover**
- **/pho item rotator : Give item Rotator**

## Config extract :
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
        #When used, what it does ? (Heal 1 = Half Hearth)
        action: '{"heal": 1, "teleport": "1;2;3", "effect": "1/1/30;2/1/60;3/1/80", "msg": "May the force be with you ..."}'
        #choose one random action ?
        action_random: 1
```
  
## Screenshot 
<img height=200 src="https://i.ibb.co/9wq4s7R/playerheadobj-V1.png" />
<img height=200 src="https://i.ibb.co/wgQZ0m9/playerheadobj-V2.png" />

## Skins
Source / credits of the skin: https://minecraft-heads.com/

book: https://minecraft-heads.com/custom-heads/decoration/30771-old-book

bowl_pasta: https://minecraft-heads.com/custom-heads/food%20&%20drinks/30178-bowl-of-pasta-with-tomato-sauce

calice: https://minecraft-heads.com/custom-heads/decoration/883-golden-chalice

crate_locked: https://minecraft-heads.com/custom-heads/decoration/31223-locked-crate-gray

bible: https://minecraft-heads.com/custom-heads/decoration/603-bible

-----------------

## Thanks for help to:
- HimbeersaftLP
- Kenn Fatt
