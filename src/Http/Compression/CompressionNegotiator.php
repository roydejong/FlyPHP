<?php

namespace FlyPHP\Http\Compression;

use FlyPHP\Config\ServerConfigSection;
use FlyPHP\Http\Request;
use FlyPHP\Http\Response;

/**
 * Compression handler for a request.
 */
class CompressionNegotiator
{
    /**
     * An array containing available compression methods, indexed by algorithm name, and in the order of preference.
     *
     * @var CompressionMethod[]
     */
    private $methods;

    /**
     * Initializes compression options based on the provided server configuration.
     *
     * @param ServerConfigSection $config
     */
    public function load(ServerConfigSection $config)
    {
        $this->methods = [];

        if ($config->gzipEnabled) {
            $this->methods['gzip'] = new Gzip($config->gzipLevel);
        }
    }

    /**
     * Given a request, attempts to negotiate and return a suitable compression method.
     *
     * @param Request $request
     * @return CompressionMethod|null
     */
    public function negotiate(Request $request)
    {
        $acceptableMethods = explode(',', $request->getHeader('Accept-Encoding'));

        foreach ($acceptableMethods as $algoName) {
            $algoName = trim(strtolower($algoName));

            if (isset($this->methods[$algoName])) {
                $algo = $this->methods[$algoName];
                // TODO Constraints (e.g. content type)
                return $algo;
            }
        }

        return null;
    }
}