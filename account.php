<?php

require __DIR__ . '/vendor/autoload.php'; // Adjust the path if necessary

use Inertia\Inertia;

// Assuming you have a Inertia setup with a root template like app.blade.php
// and a Vite setup for your React components.

echo Inertia::render('AccountSelection');

?>
