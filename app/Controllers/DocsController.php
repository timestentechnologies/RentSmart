<?php

namespace App\Controllers;

class DocsController
{
    public function index()
    {
        // Public documentation page
        require 'views/docs/index.php';
    }
}
