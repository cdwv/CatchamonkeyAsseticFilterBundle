<?php
/**
 * @Author: Grzegorz Daszuta
 * @Date:   2015-05-20 11:54:31
 * @Last Modified by:   Grzegorz Daszuta
 * @Last Modified time: 2015-05-20 12:15:41
 */

namespace Catchamonkey\Bundle\AsseticFilterBundle\Assetic\Filter;

use Assetic\Asset\AssetInterface;  
use Assetic\Filter\FilterInterface;
use Assetic\Filter\BaseNodeFilter;

class UglifyFilter extends BaseNodeFilter
{
    private $uglifyjsBin;
    private $nodeBin;
    private $compress;
    private $beautify;
    private $mangle;
    private $screwIe8;
    private $comments;
    private $wrap;
    private $defines;
    private $sourceMap;

    public function __construct($uglifyjsBin = '/usr/bin/uglifyjs', $nodeBin = null)
    {
        $this->uglifyjsBin = $uglifyjsBin;
        $this->nodeBin = $nodeBin;
    }

    public function setCompress($compress)
    {
        $this->compress = $compress;
    }

    public function setBeautify($beautify)
    {
        $this->beautify = $beautify;
    }

    public function setMangle($mangle)
    {
        $this->mangle = $mangle;
    }

    public function setScrewIe8($screwIe8)
    {
        $this->screwIe8 = $screwIe8;
    }

    public function setComments($comments)
    {
        $this->comments = $comments;
    }

    public function setWrap($wrap)
    {
        $this->wrap = $wrap;
    }

    public function setDefines(array $defines)
    {
        $this->defines = $defines;
    }

    public function setSourceMap($sourceMap)
    {
        $this->sourceMap = $sourceMap;
    }

    public function filterLoad(AssetInterface $asset)
    {
    }

    public function filterDump(AssetInterface $asset)
    {
        $pb = $this->createProcessBuilder(
            $this->nodeBin
            ? array($this->nodeBin, $this->uglifyjsBin)
            : array($this->uglifyjsBin)
        );

        if ($this->compress) {
            $pb->add('--compress');

            if (is_string($this->compress) && !empty($this->compress)) {
                $pb->add($this->compress);
            }
        }

        if ($this->beautify) {
            $pb->add('--beautify');
        }

        if ($this->mangle) {
            $pb->add('--mangle');
        }

        if ($this->screwIe8) {
            $pb->add('--screw-ie8');
        }

        if ($this->comments) {
            $pb->add('--comments')->add(true === $this->comments ? 'all' : $this->comments);
        }

        if ($this->wrap) {
            $pb->add('--wrap')->add($this->wrap);
        }

        if ($this->defines) {
            $pb->add('--define')->add(join(',', $this->defines));
        }

        if($this->sourceMap) {
            // echo " --- source map --- \n";
            // echo ">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>" . substr($asset->getContent(), 0, 100) ."<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\n";
        }

        // input and output files
        $input = tempnam(sys_get_temp_dir(), 'input');
        $output = tempnam(sys_get_temp_dir(), 'output');

        file_put_contents($input, $asset->getContent());
        $pb->add('-o')->add($output)->add($input);

        $proc = $pb->getProcess();
        $code = $proc->run();
        unlink($input);

        if (0 !== $code) {
            if (file_exists($output)) {
                unlink($output);
            }

            if (127 === $code) {
                throw new \RuntimeException('Path to node executable could not be resolved.');
            }

            throw FilterException::fromProcess($proc)->setInput($asset->getContent());
        }


        if (!file_exists($output)) {
            throw new \RuntimeException('Error creating output file.');
        }

        $asset->setContent(file_get_contents($output) . "//# sourceMappingURL=/path/to/file.js.map\n");

        unlink($output);
    }
}
