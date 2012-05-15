<?php

return new Sami\Sami('/path/to/Twig/lib', array(
    'theme'                => 'enhanced',
    'title'                => 'Twig 1.6 API',
    'build_dir'            => __DIR__.'/../build/twig',
    'cache_dir'            => __DIR__.'/../cache/twig',
    'simulate_namespaces'  => true,
    'default_opened_level' => 1,
));
