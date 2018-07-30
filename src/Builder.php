<?php
/**
 * Composer plugin for config assembling
 *
 * @link      https://github.com/hiqdev/composer-config-plugin
 * @package   composer-config-plugin
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2016-2018, HiQDev (http://hiqdev.com/)
 */

namespace hiqdev\composer\config;

use hiqdev\composer\config\configs\ConfigFactory;

/**
 * Builder assembles config files.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 */
class Builder
{
    /**
     * @var string path to output assembled configs
     */
    protected $outputDir;

    /**
     * @var array collected variables
     */
    protected $vars = [];

    /**
     * @var array configurations
     */
    protected $configs = [];

    const OUTPUT_DIR_SUFFIX = '-output';

    public function __construct($outputDir = null)
    {
        $this->setOutputDir($outputDir);
    }

    public function setOutputDir($outputDir)
    {
        $this->outputDir = isset($outputDir) ? $outputDir : static::findOutputDir();
    }

    public function getOutputDir(): string
    {
        return $this->outputDir;
    }

    public static function rebuild($outputDir = null)
    {
        $builder = new self([], $outputDir);
        $builder->loadFiles();
        $builder->buildConfigs();
    }

    /**
     * Returns default output dir.
     * @param string $vendor path to vendor dir
     * @return string
     */
    public static function findOutputDir($vendor = null)
    {
        if ($vendor) {
            $dir = $vendor . '/hiqdev/' . basename(dirname(__DIR__));
        } else {
            $dir = dirname(__DIR__);
        }

        return $dir . static::OUTPUT_DIR_SUFFIX;
    }

    /**
     * Returns full path to assembled config file.
     * @param string $filename name of config
     * @param string $vendor path to vendor dir
     * @return string absolute path
     */
    public static function path($filename, $vendor = null)
    {
        return static::findOutputDir($vendor) . DIRECTORY_SEPARATOR . $filename . '.php';
    }

    /**
     * Builds configs by given files list.
     * @param null|array $files files to process: config name => list of files
     */
    public function buildConfigs($files = null)
    {
        if (is_null($files)) {
            $files = $this->files;
        }
        $resolver = new Resolver($files);
        $files = $resolver->get();
        foreach ($files as $name => $paths) {
            $this->createConfig($name)->load($paths)->build()->write();
        }
        $this->writeConfig('__rebuild');
    }

    public function getOutputPath($name)
    {
        return $this->outputDir . DIRECTORY_SEPARATOR . $name . '.php';
    }

    public function writeConfig(string $name, array $values = null)
    {
        $this->createConfig($name, $values)->write();
    }

    protected function createConfig($name, array $values = null) {
        $config = ConfigFactory::create($this, $name);
        $this->configs[$name] = $config;
        if ($values !== null) {
            $config->setValues($values);
        }

        return $config;
    }

    public function getConfig(string $name)
    {
        if (!isset($this->configs[$name])) {
            throw new \Exception('INTERNAL get wrong config');
        }

        return $this->configs[$name];
    }

    public function loadConfig($name)
    {
        return $this->loadFile($this->getOutputPath($name));
    }

    public function getVars()
    {
        $vars = [];
        foreach ($this->configs as $name => $config) {
            $vars[$name] = $config->getValues();
        }

        return $vars;
    }
}
