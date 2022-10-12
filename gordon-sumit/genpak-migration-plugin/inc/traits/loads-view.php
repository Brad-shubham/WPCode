<?php

namespace Genpak\Plugins\Migration;

trait LoadsView
{
    protected function loadView($file, $data = [])
    {
        extract($data);
        require plugin_dir_path(GPM_BASE_FILE) . 'views/' . $file . '.php';
    }
}
