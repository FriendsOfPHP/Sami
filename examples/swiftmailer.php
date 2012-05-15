<?php

use Sami\Sami;
use Sami\Version\GitVersionCollection;
use Symfony\Component\Finder\Finder;

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->in($dir = '/path/to/swiftmailer/lib/classes')
;

$versions = GitVersionCollection::create($dir)
    ->addFromTags(function ($version) { return preg_match('/^v?4\.\d+\.\d+$/', $version); })
    ->add('master', 'master branch')
;

return new Sami($iterator, array(
    'theme'                => 'enhanced',
    'versions'             => $versions,
    'title'                => 'Swiftmailer API',
    'build_dir'            => __DIR__.'/../build/swiftmailer/%version%',
    'cache_dir'            => __DIR__.'/../cache/swiftmailer/%version%',
    'simulate_namespaces'  => true,
    'default_opened_level' => 1,
));
