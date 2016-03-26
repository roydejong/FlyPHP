<?php

namespace FlyPHP\Rendering;

/**
 * A twig-based view renderer. Singleton.
 */
class ViewRenderer
{
    /**
     * @var ViewRenderer
     */
    private static $instance;

    /**
     * @return ViewRenderer
     */
    public static function instance()
    {
        if (empty(self::$instance)) {
            self::$instance = new ViewRenderer();
        }

        return self::$instance;
    }

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * Initializes a new view renderer.
     */
    public function __construct()
    {
        $loader = new \Twig_Loader_Filesystem([
            FLY_DIR . '/res/views'
        ]);

        $this->twig = new \Twig_Environment($loader, [
            'cache' => FLY_DIR . '/cache/twig',
            'debug' => true
        ]);
    }

    /**
     * @param string $name
     * @param array $data
     * @return string
     */
    public function render(string $name, array $data)
    {
        return $this->twig->render($name, $data);
    }
}