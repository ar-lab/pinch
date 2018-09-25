<?php

class Pinch
{

    private $_config = array();

    private $_rootPath;
    private $_themesPath;
    private $_contentPath;

    protected $route;
    protected $file;

    protected $menu;
    protected $content;

    public function __construct()
    {
        $this->_rootPath = __DIR__ . '/..';
        $this->_themesPath = $this->_rootPath . '/themes';
        $this->_contentPath = $this->_rootPath . '/content';
    }

    public function setConfig(array $params)
    {
        $this->_config = array(
            'title' => isset($params['title']) ? $params['title'] : 'Pinch',
            'description' => isset($params['description']) ? $params['description'] : 'Documentation viewer',
            'theme' => isset($params['theme']) ? $params['theme'] : '',
            'template' => isset($params['template']) ? $params['template'] : 'main',
        );
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

        $this->file = $this->prepareFilePath();

        if (file_exists($this->file)) {
            $contentData = $this->loadFile();
        } else {
            header("HTTP/1.1 404 Not Found");
            $contentData = '# 404 Not Found';
        }

        $this->content = $this->prepareContent($contentData);

        $this->buildMenu();
        $this->renderPage();
    }

    protected function buildMenu()
    {
        $tree = $this->getTree($this->_contentPath);
        $this->menu = $this->getMenuItems($tree);
    }

    protected function getTree($dir)
    {
        $tree = array();

        $files = scandir($dir);
        if ($files !== false) {
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    $path = $dir . '/' . $file;
                    if (is_dir($path)) {
                        $tree[$file] = $this->getTree($path);
                    } else {
                        $tree[$file] = $path;
                    }
                }
            }
        }

        return $tree;
    }

    protected function getMenuItems($tree, $depth = 1)
    {
        $menuItems = array();

        foreach ($tree as $path) {
            if (!is_array($path)) {

                if (basename($path, '.md') === 'index') {
                    $currentDepth = $depth - 1;
                    $isSection = true;
                } else {
                    $currentDepth = $depth;
                    $isSection = false;
                }

                $item = array(
                    'depth' => $currentDepth,
                    'uri' => $this->parsePageUri($path),
                    'name' => $this->parsePageName($path),
                );

                // skup root page
                if ($item['depth'] === 0) continue;

                // skip hidden pages
                if (substr($item['name'], 0 , 1) === '.') continue;

                // add page to menu
                if ($isSection) {
                    array_unshift($menuItems, $item);
                } else {
                    array_push($menuItems, $item);
                }

            } else {
                $nextDepth = $depth + 1;
                $menuItems = array_merge($menuItems, $this->getMenuItems($path, $nextDepth));
            }
        }

        return $menuItems;
    }

    protected function parsePageUri($path)
    {
        $itemUri = str_replace($this->_contentPath, '', $path);
        $itemUri = str_replace('/index.md', '', $itemUri);
        $itemUri = rtrim($itemUri, '.md');

        return $itemUri;
    }

    protected function parsePageName($path)
    {
        $itemUri = $this->parsePageUri($path);
        $itemUriParts = explode('/', $itemUri);
        $itemName = array_pop($itemUriParts);
        $itemName = str_replace('_', ' ', $itemName);

        return $itemName;
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

        return $requestedFile;
    }

    protected function loadFile()
    {
        $data = file_get_contents($this->file);

        return $data;
    }

    protected function prepareContent($data)
    {
        $parsedown = new Parsedown;
        $content = $parsedown->text($data);

        return $content;
    }

    protected function renderPage()
    {
        $template = $this->getTemplatePath();
        $params = $this->getTemplateParams();

        $view = new PinchRender($template, $params);
        $output = $view->render();

        echo $output;
    }

    protected function getThemeUri()
    {
        $themeUri = str_replace($this->_rootPath, '', $this->getThemePath());

        return $themeUri;
    }

    protected function getThemePath()
    {
        $themeName = $this->_config['theme'];

        return $this->_themesPath . '/' . $themeName;
    }

    protected function getTemplatePath()
    {
        $themePath = $this->getThemePath();

        $templateName = $this->_config['template'];
        $templatePath = $themePath . '/' . $templateName . '.phtml';

        return $templatePath;
    }

    protected function getTemplateParams()
    {
        return array(
            'themeUri' => $this->getThemeUri(),
            'siteTitle' => $this->_config['title'],
            'siteDescription' => $this->_config['description'],
            'page' => $this->parsePageName($this->file),
            'menu' => $this->menu,
            'content' => $this->content,
        );
    }

}