<?php

namespace App\Controllers;

use Core\BaseController;
use Twig\Environment;

class ProfileController extends BaseController
{
    public function __construct(Environment $twig)
    {
        parent::__construct($twig);
    }

    /**
     * Example method.
     */
    public function index()
    {
        // Your logic here...
    }
}