<?php

namespace SixPixels\BinPacker;

use SixPixels\BinPacker\Model\Bin;
use SixPixels\BinPacker\Model\Block;
use SixPixels\BinPacker\Model\Node;

class BinPacker
{
    /**
     * @param Bin $bin
     * @param Block[] $blocks
     * @param callable|null $stepCallback
     *
     * @return Block[]
     */
    public function pack(Bin $bin, array $blocks, ?callable $stepCallback = null): array
    {
        $blocks = $this->sort($blocks);

        $root = new Node(0, 0, $bin->getWidth(), $bin->getHeight());

        $bin->setNode($root);

        /** @var Block $block */
        foreach ($blocks as $block) {
            $node = $this->findNodeWithRotation($root, $block);

            if ($node === null && ($bin->isWidthGrowthAllowed() || $bin->isHeightGrowthAllowed())) {
                $this->grow($bin, $block->getWidth(), $block->getHeight());
                $node = $this->findNodeWithRotation($root, $block);
                if ($node === null) {
                    $this->shrink($bin);
                    $this->grow($bin, $block->getWidth(), $block->getHeight());
                    $node = $this->findNodeWithRotation($root, $block);
                }
            }

            if ($node !== null) {
                $block->setNode($this->splitNode($node, $block->getWidth(), $block->getHeight()));
            }

            if ($stepCallback) {
                $stepCallback($bin, $blocks, $block);
            }
        }

        $this->shrink($bin);

        return $blocks;
    }

    private function findNodeWithRotation(Node $root, Block $block)
    {
        if (null === $node = $this->findNode($root, $block->getWidth(), $block->getHeight())) {
            if ($block->isRotatable()) {
                $block->rotate();
                $node = $this->findNode($root, $block->getWidth(), $block->getHeight());
            }
        }

        return $node;
    }

    private function findNode(Node $node, $w, $h): ?Node
    {
        if ($node->isUsed()) {
            return $this->findNode($node->getRight(), $w, $h) ?: $this->findNode($node->getDown(), $w, $h);
        } elseif ($w <= $node->getWidth() && $h <= $node->getHeight()) {
            return $node;
        }

        return null;
    }

    private function splitNode(Node $node, $w, $h)
    {
        $node->setUsed(true);
        $node->setDown(new Node($node->getX(), $node->getY() + $h, $node->getWidth(), $node->getHeight() - $h));
        $node->setRight(new Node($node->getX() + $w, $node->getY(), $node->getWidth() - $w, $h));
        $node->setWidth($w);
        $node->setHeight($h);

        return $node;
    }

    private function shrink(Bin $bin)
    {
        $this->shrinkDownAndRight($bin, $bin->getNode());
    }

    private function shrinkDownAndRight(Bin $bin, Node $node) {
        if ($node->getRight() && !$node->getRight()->isUsed() && $node->getRight()->getHeight() >= $bin->getHeight()) {
            $bin->setWidth($bin->getWidth() - $node->getRight()->getWidth());
            $node->setRight(null);
        } elseif ($node->getRight()) {
            $this->shrinkDownAndRight($bin, $node->getRight());
        }

        if ($node->getDown() && !$node->getDown()->isUsed() && $node->getDown()->getWidth() >= $bin->getWidth()) {
            $bin->setHeight($bin->getHeight() - $node->getDown()->getHeight());
            $node->setDown(null);
        } else if ($node->getDown()) {
            $this->shrinkDownAndRight($bin, $node->getDown());
        }
    }

    private function grow(Bin $bin, $w, $h)
    {
        $canGrowRight = false;
        $this->canGrowRight($bin->getNode(), $w, $h, $canGrowRight);

        $shouldGrowRight = !($bin->getWidth() >= $bin->getHeight() + $h);

        if ($canGrowRight && $shouldGrowRight && $bin->isWidthGrowthAllowed() || !$bin->isHeightGrowthAllowed()) {
            $bin->setWidth($bin->getWidth() + $w);

            $this->growRight($bin->getNode(), $w, $h);
        } elseif($bin->isHeightGrowthAllowed()) {
            $bin->setHeight($bin->getHeight() + $h);

            $this->growDown($bin->getNode(), $w, $h);
        }
    }

    public function canGrowRight(Node $node, $w, $h, &$can)
    {
        if (!$node->isUsed() && $node->getRight() === null) {
            if ($node->getHeight() >= $h) {
                $can = true;
            }
        }

        if ($node->getRight()) {
            $this->canGrowRight($node->getRight(), $w, $h, $can);
        }

        if ($node->getDown()) {
            $this->canGrowRight($node->getDown(), $w, $h, $can);
        }
    }

    public function growRight(Node $node, $w, $h)
    {
        if (!$node->isUsed() && $node->getRight() === null) {
            $node->setWidth($node->getWidth() + $w);
        }

        if ($node->getRight()) {
            $this->growRight($node->getRight(), $w, $h);
        }

        if ($node->getDown()) {
            $this->growRight($node->getDown(), $w, $h);
        }
    }

    public function growDown(Node $node, $w, $h)
    {
        if ($node->getDown()) {
            $this->growDown($node->getDown(), $w, $h);
        } else {
            $node->setHeight($node->getHeight() + $h);
        }
    }

    private function sort($blocks)
    {
        usort($blocks, function (Block $a, Block $b) {
            return $a->getHeight() < $b->getHeight() ;
        });

        return $blocks;
    }
}
