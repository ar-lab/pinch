<?php

class Pinch
{

    private $_config = array();
    private $_rootPath;
    private $_themesPath;
    private $_contentPath;

    protected $route;
    protected $file;

    protected $meta;
    protected $content;

    public function __construct()
    {
        $this->_rootPath = __DIR__ . '/..';
        $this->_themesPath = $this->_rootPath . '/themes';
        $this->_contentPath = $this->_rootPath . '/content';
    }

    public function setConfig(array $params)
    {
        $this->_config = $params;
    }

    public function setThemesPath($path)
    {
        $this->_themesPath = $path;
    }

    public function setContentPath($path)
    {
        $this->_contentPath = $path;
    }

    public function showContent()
    {
        $this->parseRoure();

        $this->prepareFilePath();

        if (file_exists($this->file)) {
            $this->meta = $this->_config['meta'];
            $this->content = $this->loadFile();
        } else {
            header("HTTP/1.1 404 Not Found");
            $this->content = '# 404 Not Found';
        }

        $this->renderPage();
    }

    protected function parseRoure()
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

        $this->route = rawurldecode($uri);
    }

    protected function prepareFilePath()
    {
        $fileExtension = '.md';
        $requestedFile = $this->_contentPath . $this->route;

        if (is_dir($requestedFile)) {
            $requestedFile .= '/index' . $fileExtension;
        } else {
            $requestedFile .= $fileExtension;
        }

        $this->file = $requestedFile;
    }

    protected function loadFile()
    {
        $data = file_get_contents($this->file);

        return $data;
    }

    protected function renderPage()
    {
        $parsedown = new Parsedown;
        $content = $parsedown->text($this->content);

        $template = $this->getTemplatePath();
        $params = $this->getTemplateParams();

        $params['content'] = $content;

        $view = new PinchRender($template, $params);
        $output = $view->render();

        echo $output;
    }

    protected function getTemplatePath()
    {
        $themeName = $this->_config['theme'];
        $templateName = $this->_config['template'];

        return $this->_themesPath . '/' . $themeName . '/' . $templateName . '.phtml';
    }

    protected function getTemplateParams()
    {
        return array(
            'meta' => $this->meta,
            'content' => $this->content,
        );
    }

}