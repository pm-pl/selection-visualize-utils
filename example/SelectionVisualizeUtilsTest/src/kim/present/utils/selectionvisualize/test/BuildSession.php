<?php

/**
 *
 *  ____                           _   _  ___
 * |  _ \ _ __ ___  ___  ___ _ __ | |_| |/ (_)_ __ ___
 * | |_) | '__/ _ \/ __|/ _ \ '_ \| __| ' /| | '_ ` _ \
 * |  __/| | |  __/\__ \  __/ | | | |_| . \| | | | | | |
 * |_|   |_|  \___||___/\___|_| |_|\__|_|\_\_|_| |_| |_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the MIT License. see <https://opensource.org/licenses/MIT>.
 *
 * @author       PresentKim (debe3721@gmail.com)
 * @link         https://github.com/PresentKim
 * @license      https://opensource.org/licenses/MIT MIT License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 *
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace kim\present\utils\selectionvisualize\test;

use kim\present\utils\selectionvisualize\BlockPreview;
use kim\present\utils\selectionvisualize\PreviewEntry;
use kim\present\utils\selectionvisualize\Selection;
use pocketmine\block\Block;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\Position;

final class BuildSession{

    public function __construct(
        private readonly Player $player,
        private readonly BlockPreview $preview,
        private ?Selection $selection = null,
        private ?Block $previewBlock = null
    ){}

    public function getPlayer() : Player{
        return $this->player;
    }

    public function getSelection() : ?Selection{
        return $this->selection;
    }

    public function setPos1(Vector3 $pos) : void{
        if($this->selection === null){
            $this->selection = new Selection($pos, $pos);
        }else{
            $this->selection->pos1 = $pos;
        }

        $this->sendSelectionToPlayer();
    }

    public function setPos2(Vector3 $pos) : void{
        if($this->selection === null){
            $this->selection = new Selection($pos, $pos);
        }else{
            $this->selection->pos2 = $pos;
        }

        $this->sendSelectionToPlayer();
    }

    public function setSelection(?Selection $selection) : void{
        $this->selection?->restoreFrom($this->player);
        $this->selection = $selection;
        $this->sendSelectionToPlayer();
    }

    public function getPreviewBlock() : ?Block{
        return $this->previewBlock;
    }

    public function setPreviewBlock(?Block $previewBlock) : void{
        $this->previewBlock = $previewBlock;

        $this->sendSelectionToPlayer();
    }

    private function sendSelectionToPlayer() : void{
        $this->preview->clear($this->player);

        if($this->selection === null){
            return;
        }

        $this->selection->sendTo($this->player);
        if($this->previewBlock === null || !$this->previewBlock->canBePlaced()){
            return;
        }

        $previewEntries = [];
        $min = $this->selection->getMin();
        $max = $this->selection->getMax();
        $world = $this->player->getWorld();
        for($x = $min->x; $x <= $max->x; ++$x){
            for($y = $min->y; $y <= $max->y; ++$y){
                for($z = $min->z; $z <= $max->z; ++$z){
                    $previewEntries[] = new PreviewEntry(
                        new Position($x, $y, $z, $world),
                        $this->previewBlock,
                        self::getPreviewLiquidBlock()
                    );
                }
            }
        }
        $this->preview->show($this->player, ...$previewEntries);
    }

    public function close() : void{
        $this->selection?->restoreFrom($this->player);
        $this->preview->clear($this->player);
    }

    private static function getPreviewLiquidBlock() : Block{
        /** @var Block|null $liquidBlock */
        static $liquidBlock = null;
        return $liquidBlock ??= VanillaBlocks::STAINED_HARDENED_GLASS()->setColor(DyeColor::LIGHT_BLUE());
    }
}

