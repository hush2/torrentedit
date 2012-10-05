<?php

// Do not run composer!

$vendorDir = dirname(__DIR__);
$baseDir = dirname($vendorDir);
$libDir = $vendorDir . '/../lib';

return array(
    'Symfony\\Component\\Routing' => $vendorDir . '/symfony/routing/',
    'Symfony\\Component\\HttpKernel' => $vendorDir . '/symfony/http-kernel/',
    'Symfony\\Component\\HttpFoundation' => $vendorDir . '/symfony/http-foundation/',
    'Symfony\\Component\\EventDispatcher' => $vendorDir . '/symfony/event-dispatcher/',    
    'Silex' => $vendorDir . '/silex/src/',
    'SessionHandlerInterface' => $vendorDir . '/symfony/http-foundation/Symfony/Component/HttpFoundation/Resources/stubs',
    'Pimple' => $vendorDir . '/pimple/lib/',
    'Guzzle' => $vendorDir . '/guzzle/src/',
    'Twig_' => $vendorDir . '/twig/lib/',
    'Symfony\\Bridge\\Twig' => $vendorDir . '/symfony/twig-bridge/', 
    'TorrentEdit' => $libDir,
    'BEncoder' => $vendorDir . '/bencoder',
);
