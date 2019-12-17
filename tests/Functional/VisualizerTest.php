<?php

namespace SixPixels\BinPacker\Tests;

use SixPixels\BinPacker\BinPacker;
use SixPixels\BinPacker\Model\Bin;
use SixPixels\BinPacker\Model\Block;
use SixPixels\BinPacker\Visualizer;
use PHPUnit\Framework\TestCase;

class VisualizerTest extends TestCase
{
    public function testSimple()
    {
        $bin = new Bin(1000, 1000);

        $blocks = [];

        for ($i = 1; $i <= 3; $i++) {
            $w = rand(50, 250);
            $h = rand(50, 250);

            for ($j = 1; $j <= 3; $j++) {
                $blocks[] = new Block($w, $h, false, $i);
            }
        }

        $packer = new BinPacker();

        $blocks = $packer->pack($bin, $blocks);

        $visualizer = new Visualizer();
        $image = $visualizer->visualize($bin, $blocks);

        //$image->setFormat('jpg');
        //$image->writeImage('bin.jpg');

        $this->assertInstanceOf(\Imagick::class, $image);
    }
}
