<?php

use Sami\Version\GitVersionCollection;

$dir = '/Users/fabien/Code/github/fabpot/Twig/lib';

$versions = GitVersionCollection::create($dir)
    ->addFromTags('v1.*')
    ->add('master', 'master branch')
;

return new Sami\Sami($dir, array(
    'theme'                => 'enhanced',
    'title'                => 'Twig 1.6 API',
    'build_dir'            => __DIR__.'/../build/twig/%version%',
    'cache_dir'            => __DIR__.'/../cache/twig/%version%',
    'simulate_namespaces'  => true,
    'default_opened_level' => 1,
    'versions' => $versions,
));
