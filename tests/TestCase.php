<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

// Fix Git Bash on Windows: DOCUMENT_ROOT contains spaces (C:/Program Files/Git/)
// This breaks Symfony Cookie validation. Override it before anything else.
if (isset($_SERVER['DOCUMENT_ROOT']) && str_contains($_SERVER['DOCUMENT_ROOT'], ' ')) {
    $_SERVER['DOCUMENT_ROOT'] = '/';
}

abstract class TestCase extends BaseTestCase
{
    //
}
