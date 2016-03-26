<?php

namespace FlyPHP\Rendering;

use FlyPHP\Http\Response;

/**
 * A view that can be rendered and output.
 * Used for generating dynamic error pages and pages for management and statistics.
 */
class View
{
    /**
     * The filename of the view.
     *
     * @var string
     */
    private $fileName;

    /**
     * The view context data used, passed when rendering.
     * An associative array containing keys and values.
     *
     * @var array
     */
    private $data;

    /**
     * Constructs a new view with default settings.
     *
     * @param string $fileName
     */
    public function __construct(string $fileName)
    {
        $this->fileName = $fileName;
        $this->data = [];
    }

    /**
     * Magic function to set context data.
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * Magic function to get context data.
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->data[$name];
    }

    /**
     * Magic function to check if context data is set.
     *
     * @param string $name
     * @return bool
     */
    public function __isset(string $name)
    {
        return isset($this->data[$name]);
    }

    /**
     * Magic function to unset context data.
     *
     * @param string $name
     */
    public function __unset(string $name)
    {
        unset($this->data[$name]);
    }

    /**
     * Renders the view.
     *
     * @return string
     */
    public function render()
    {
        return ViewRenderer::instance()->render($this->fileName, $this->data);
    }

    /**
     * Given a $response, renders this view to the response body.
     *
     * @param Response $response
     */
    public function output(Response $response)
    {
        $response->appendBody($this->render());
    }

    /**
     * Renders this view, and returns it as a string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }
}