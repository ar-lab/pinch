<?php

class PinchRender
{

    protected $templatePath;
    protected $templateParams;

    public function __construct($templatePath, $templateParams = array())
    {
        $this->templatePath = $templatePath;
        $this->templateParams = $templateParams;
    }

    public function render()
    {
        ob_start();
        $this->wrapTemplate($this->templatePath, $this->templateParams);
        return ob_get_clean();
    }

    protected function wrapTemplate($path, array $params) {
        extract($params);
        include $path;
    }

}