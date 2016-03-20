<?php

namespace FlyPHP\Config;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

/**
 * Configuration file handler.
 */
class Configuration
{
    /**
     * @var Configuration
     */
    private static $instance;

    /**
     * @return Configuration
     */
    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new Configuration();
        }

        return self::$instance;
    }

    /**
     * Flag indicating whether the config file was loaded (or a load was attempted).
     *
     * @var bool
     */
    private $loaded;

    /**
     * The YAML data loaded from the configuration file.
     *
     * @var mixed
     */
    private $data;

    /**
     * Flag that indicates whether data was loaded successfully or not.
     *
     * @var bool
     */
    private $isValid;

    /**
     * A string containing the last encountered error while reading or parsing the file.
     *
     * @var string
     */
    private $loadError;

    /**
     * The path the configuration file was loaded from.
     *
     * @var string
     */
    private $path;

    /**
     * @var ServerConfigSection
     */
    public $serverConfig;

    /**
     * Initializes a new, blank configuration file.
     */
    public function __construct()
    {
        $this->loaded = false;
        $this->isValid = false;
        $this->loadError = 'No file was loaded';
        $this->path = 'fly.yaml';

        $this->serverConfig = new ServerConfigSection();
    }

    /**
     * Gets whether data was loaded successfully or not.
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->loaded && $this->isValid;
    }

    /**
     * Gets a string containing the last encountered error while reading or parsing the file.
     *
     * @return string
     */
    public function getError()
    {
        return $this->loadError;
    }

    /**
     * Initializes a new server configuration.
     */
    public function loadFrom($path = null)
    {
        $this->loaded = true;
        $this->isValid = false;
        $this->loadError = null;

        if ($path != null) {
            $this->path = $path;
        }

        if (!file_exists($path)) {
            $this->loadError = "Configuration file does not exist: {$path}";
            return false;
        }

        $yamlRaw = file_get_contents($path);

        $yamlParser = new Parser();

        try {
            $this->data = $yamlParser->parse($yamlRaw);
        } catch (ParseException $ex) {
            $this->loadError = "YML parser error: " . $ex->getMessage();
            return false;
        }

        if (isset($this->data['server'])) {
            $this->serverConfig->fill($this->data['server']);
        }

        $this->isValid = true;
        return true;
    }

    /**
     * Gets the path to the configuration file.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
}