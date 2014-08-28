Sami: an API documentation generator
====================================

Curious about what Sami generates? Have a look at the `Symfony API`_.

Installation
------------

Get Sami from as a `phar file`_:

.. code-block:: bash

    $ curl -O http://get.sensiolabs.org/sami.phar

Check that everything worked as expected by executing the ``sami.phar`` file
without any arguments:

.. code-block:: bash

    $ php sami.phar

Configuration
-------------

Before generating documentation, you must create a configuration file. Here is
the simplest possible one:

.. code-block:: php

    <?php

    return new Sami\Sami('/path/to/symfony/src');

The configuration file must return an instance of ``Sami\Sami`` and the first
argument of the constructor is the path to the code you want to generate
documentation for.

Actually, instead of a directory, you can use any valid PHP iterator (and for
that matter any instance of the Symfony `Finder`_ class):

.. code-block:: php

    <?php

    use Sami\Sami;
    use Symfony\Component\Finder\Finder;

    $iterator = Finder::create()
        ->files()
        ->name('*.php')
        ->exclude('Resources')
        ->exclude('Tests')
        ->in('/path/to/symfony/src')
    ;

    return new Sami($iterator);

The ``Sami`` constructor optionally takes an array of options as a second
argument:

.. code-block:: php

    return new Sami($iterator, array(
        'theme'                => 'symfony',
        'title'                => 'Symfony2 API',
        'build_dir'            => __DIR__.'/build',
        'cache_dir'            => __DIR__.'/cache',
        'default_opened_level' => 2,
    ));

And here is how you can configure different versions:

.. code-block:: php

    <?php

    use Sami\Sami;
    use Sami\Version\GitVersionCollection;
    use Symfony\Component\Finder\Finder;

    $iterator = Finder::create()
        ->files()
        ->name('*.php')
        ->exclude('Resources')
        ->exclude('Tests')
        ->in($dir = '/path/to/symfony/src')
    ;

    // generate documentation for all v2.0.* tags, the 2.0 branch, and the master one
    $versions = GitVersionCollection::create($dir)
        ->addFromTags('v2.0.*')
        ->add('2.0', '2.0 branch')
        ->add('master', 'master branch')
    ;

    return new Sami($iterator, array(
        'theme'                => 'symfony',
        'versions'             => $versions,
        'title'                => 'Symfony2 API',
        'build_dir'            => __DIR__.'/../build/sf2/%version%',
        'cache_dir'            => __DIR__.'/../cache/sf2/%version%',
        'default_opened_level' => 2,
    ));

To generate documentation for a PHP 5.2 project, simply set the
``simulate_namespaces`` option to ``true``.

You can find more configuration examples under the ``examples/`` directory of
the source code.

Sami only documents the public API (public properties and methods); override
the default configured ``filter`` to change this behavior:

.. code-block:: php

    <?php

    use Sami\Parser\Filter\TrueFilter;

    $sami = new Sami(...);
    // document all methods and properties
    $sami['filter'] = function () {
        return new TrueFilter();
    };

Rendering
---------

Now that we have a configuration file, let's generate the API documentation:

.. code-block:: bash

    $ php sami.php update /path/to/config.php

The generated documentation can be found under the configured ``build/``
directory (note that the client side search engine does not work on Chrome due
to JavaScript execution restriction, unless Chrome is started with the
"--allow-file-access-from-files" option -- it works fine in Firefox).

By default, Sami is configured to run in "incremental" mode. It means that when
running the ``update`` command, Sami only re-generates the files that needs to
be updated based on what has changed in your code since the last execution.

Sami also detects problems in your phpdoc and can tell you what you need to fix
if you add the ``-v`` option:

.. code-block:: bash

    $ php sami.php update /path/to/config.php -v

Creating a Theme
----------------

If the default themes do not suit your needs, you can very easily create a new
one, or just override an existing one.

A theme is just a directory with a ``manifest.yml`` file that describes the
theme (this is a YAML file):

.. code-block:: yaml

    name:   symfony
    parent: default

The above configuration creates a new ``symfony`` theme based on the
``default`` built-in theme. To override a template, just create a file with
the same name as the original one. For instance, here is how you can extend the
default class template to prefix the class name with "Class " in the class page
title:

.. code-block:: jinja

    {# pages/class.twig #}

    {% extends 'default/pages/class.twig' %}

    {% block title %}Class {{ parent() }}{% endblock %}

If you are familiar with Twig, you will be able to very easily tweak every
aspect of the templates as everything has been well isolated in named Twig
blocks.

A theme can also add more templates and static files. Here is the manifest for
the default theme:

.. code-block:: yaml

    name: default

    static:
        'css/sami.css': 'css/sami.css'
        'css/bootstrap.min.css': 'css/bootstrap.min.css'
        'css/bootstrap-theme.min.css': 'css/bootstrap-theme.min.css'
        'css/bootstrap.css.map': 'css/bootstrap.css.map'
        'css/bootstrap-theme.css.map': 'css/bootstrap-theme.css.map'
        'fonts/glyphicons-halflings-regular.eot': 'fonts/glyphicons-halflings-regular.eot'
        'fonts/glyphicons-halflings-regular.svg': 'fonts/glyphicons-halflings-regular.svg'
        'fonts/glyphicons-halflings-regular.ttf': 'fonts/glyphicons-halflings-regular.ttf'
        'fonts/glyphicons-halflings-regular.woff': 'fonts/glyphicons-halflings-regular.woff'
        'js/bootstrap.min.js': 'js/bootstrap.min.js'
        'js/jquery-1.11.1.min.js': 'js/jquery-1.11.1.min.js'

    global:
        'index.twig':       'index.html'
        'doc-index.twig':   'doc-index.html'
        'namespaces.twig':  'namespaces.html'
        'classes.twig':     'classes.html'
        'interfaces.twig':  'interfaces.html'
        'traits.twig':      'traits.html'
        'opensearch.twig':  'opensearch.xml'
        'search.twig':      'search.html'

    namespace:
        'namespace.twig':   '%s.html'

    class:
        'class.twig':       '%s.html'


Files are contained into sections, depending on how Sami needs to treat them:

* ``static``: Files are copied as is (for assets like images, stylesheets, or
  JavaScript files);

* ``global``: Templates that do not depend on the current class context;

* ``namespace``: Templates that should be generated for every namespace;

* ``class``: Templates that should be generated for every class.

.. _Symfony API: http://api.symfony.com/
.. _phar file:   http://get.sensiolabs.org/sami.phar
.. _Finder:      http://symfony.com/doc/current/components/finder.html
