<?php

namespace SixPixels\BinPacker\Tests;

use SixPixels\BinPacker\BinPacker;
use SixPixels\BinPacker\Model\Bin;
use SixPixels\BinPacker\Model\Block;
use PHPUnit\Framework\TestCase;

class BinPackerTest extends TestCase
{
    public function testSimple()
    {
        $bin = new Bin(1000, 1000);
        $blocks = [
            new Block(100, 100),
            new Block(300, 100),
            new Block(175, 125),
            new Block(200, 75),
            new Block(200, 75),
        ];

        $packer = new BinPacker();

        $blocks = $packer->pack($bin, $blocks);

        foreach ($blocks as $block) {
            $this->assertTrue($block->getNode() && $block->getNode()->isUsed());
        }
    }

    public function testRotation()
    {
        $bin = new Bin(2000, 100);

        $rotatable = new Block(100, 1000);
        $nonRotatable = new Block(100, 1000, false);

        $blocks = [$rotatable, $nonRotatable];

        $packer = new BinPacker();

        $blocks = $packer->pack($bin, $blocks);

        $this->assertTrue($rotatable->getNode() && $rotatable->getNode()->isUsed());
        $this->assertFalse($nonRotatable->getNode() && $nonRotatable->getNode()->isUsed());
    }

    public function testOverflow()
    {
        $bin = new Bin(1000, 1000);

        $blockTemplate = new Block(100, 100);

        $blocks = [];

        for ($i = 1; $i <= 200; $i++) {
            $blocks[] = clone $blockTemplate;
        }

        $packer = new BinPacker();

        $blocks = $packer->pack($bin, $blocks);

        $packed = array_filter($blocks, function (Block $block) {
            return $block->getNode() && $block->getNode()->isUsed();
        });

        $this->assertCount(200, $blocks);
        $this->assertCount(100, $packed);
    }

    public function testGrowthAllSides()
    {
        $bin = new Bin(10, 10, true, true);

        $blockTemplate = new Block(1, 1);

        $blocks = [];

        for ($i = 1; $i <= 200; $i++) {
            $blocks[] = clone $blockTemplate;
        }

        $packer = new BinPacker();

        $blocks = $packer->pack($bin, $blocks);

        $packed = array_filter($blocks, function (Block $block) {
            return $block->getNode() && $block->getNode()->isUsed();
        });

        $this->assertCount(200, $blocks);
        $this->assertCount(200, $packed);


        $this->assertEquals(15, $bin->getWidth());
        $this->assertEquals(14, $bin->getHeight());
    }

    public function testGrowthWidth()
    {
        $bin = new Bin(10, 10, true, false);

        $blockTemplate = new Block(1, 1);

        $blocks = [];

        for ($i = 1; $i <= 200; $i++) {
            $blocks[] = clone $blockTemplate;
        }

        $packer = new BinPacker();

        $blocks = $packer->pack($bin, $blocks);

        $packed = array_filter($blocks, function (Block $block) {
            return $block->getNode() && $block->getNode()->isUsed();
        });

        $this->assertCount(200, $blocks);
        $this->assertCount(200, $packed);


        $this->assertEquals(20, $bin->getWidth());
        $this->assertEquals(10, $bin->getHeight());
    }

    public function testGrowthHeight()
    {
        $bin = new Bin(10, 10, false, true);

        $blockTemplate = new Block(1, 1);

        $blocks = [];

        for ($i = 1; $i <= 200; $i++) {
            $blocks[] = clone $blockTemplate;
        }

        $packer = new BinPacker();

        $blocks = $packer->pack($bin, $blocks);

        $packed = array_filter($blocks, function (Block $block) {
            return $block->getNode() && $block->getNode()->isUsed();
        });

        $this->assertCount(200, $blocks);
        $this->assertCount(200, $packed);


        $this->assertEquals(10, $bin->getWidth());
        $this->assertEquals(20, $bin->getHeight());
    }
}
