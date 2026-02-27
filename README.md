<!-- PROJECT BADGES -->
<div align="center">

[![Poggit CI][poggit-ci-badge]][poggit-ci-url]
[![Stars][stars-badge]][stars-url]
[![License][license-badge]][license-url]

</div>


<!-- PROJECT LOGO -->
<br />
<div align="center">
  <img src="https://raw.githubusercontent.com/presentkim-pm/selection-visualize-utils/main/assets/icon.png" alt="Logo" width="80" height="80"/>
  <h3>selection-visualize-utils</h3>
  <p align="center">
    Provides utils for display block selection to player!

[View in Poggit][poggit-ci-url] · [Report a bug][issues-url] · [Request a feature][issues-url]

  </p>
</div>


<!-- ABOUT THE PROJECT -->

## About The Project

:heavy_check_mark: Provides `Selection` class for display block selection to player  
:heavy_check_mark: Provides `BlockPreview` class for temporary block previews (NORMAL + LIQUID layer)

- `kim\present\utils\selectionvisualize\Selection`  
- `kim\present\utils\selectionvisualize\BlockPreview`

-----

## Installation

See [Official Poggit Virion Documentation](https://github.com/poggit/support/blob/master/virion.md)

-----

## How to use?

### Basic example

```php
use kim\present\utils\selectionvisualize\Selection;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

/** @var Player $player */
$pos1 = new Vector3(0, 64, 0);
$pos2 = new Vector3(10, 70, 10);

$selection = new Selection($pos1, $pos2);
$selection->sendTo($player);

// Later, when you want to restore the original block:
$selection->restoreFrom($player);
// or restore for every viewer:
// $selection->restoreFromAll();
```

### Block preview example

```php
use kim\present\utils\selectionvisualize\BlockPreview;
use kim\present\utils\selectionvisualize\PreviewEntry;
use pocketmine\block\VanillaBlocks;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\world\Position;

/** @var PluginBase $plugin */
/** @var Player $player */

$preview = new BlockPreview($plugin);

$world = $player->getWorld();
$pos1 = new Position(0, 64, 0, $world);
$pos2 = new Position(1, 64, 0, $world);
$block = VanillaBlocks::STONE();
$tintBlock = VanillaBlocks::RED_STAINED_GLASS();

// Basic preview: stone on normal layer, tinted glass on liquid layer
$preview->show($player,
    new PreviewEntry($pos1, $block, $tintBlock),
    new PreviewEntry($pos2, $block, $tintBlock),
);

// Later, when you want to clear the preview:
$preview->clear($player);
```

-----

## License

Distributed under the **MIT**. See [LICENSE][license-url] for more information


[poggit-ci-badge]: https://poggit.pmmp.io/ci.shield/presentkim-pm/selection-visualize-utils/selection-visualize-utils?style=for-the-badge

[stars-badge]: https://img.shields.io/github/stars/presentkim-pm/selection-visualize-utils.svg?style=for-the-badge

[license-badge]: https://img.shields.io/github/license/presentkim-pm/selection-visualize-utils.svg?style=for-the-badge

[poggit-ci-url]: https://poggit.pmmp.io/ci/presentkim-pm/selection-visualize-utils/selection-visualize-utils

[stars-url]: https://github.com/presentkim-pm/selection-visualize-utils/stargazers

[issues-url]: https://github.com/presentkim-pm/selection-visualize-utils/issues

[license-url]: https://github.com/presentkim-pm/selection-visualize-utils/blob/main/LICENSE

[project-icon]: https://raw.githubusercontent.com/presentkim-pm/selection-visualize-utils/main/assets/icon.png
