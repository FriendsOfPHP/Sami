Constructor options
===================

theme
-----

Theme name for render

======  ===========
type    default
======  ===========
string  ``default``
======  ===========

title
-----

Name for documentation

======  ===========
type    default
======  ===========
string  ``API``
======  ===========

template_dirs
------------

Path to directory where stored themes. 
For example you can set path to your theme.

.. code-block:: php

    return new Sami($iterator, array(
        'theme'                => 'custom_theme',
        'template_dirs'        => [__DIR__.'/themes/custom_theme'],
    ));

======  ===========
type    default
======  ===========
array   ``[]``
======  ===========
