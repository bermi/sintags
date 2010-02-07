Copyright 2005-2010 Bermi Ferrer

You can read Sintags documentation at http://www.bermi.org/projects/sintags 

# Sintags the Akelos PHP Framework template language.

Sintags is a template language extracted from the [Akelos PHP Framework](http://www.akelos.org/). The initial goal when designing the Sintags was to allow WYSIWYG HTML editor compatibility using a simplistic approach to looping collections and printing variables.

Sintags will not prevent users from running PHP code on your views by default. The goal with Sintags is not building another full-fledged language. It’s to make coding views less frustrating and verbose. In fact Sintags code will be compiled into PHP for performance reasons.

The name Sintags comes uses the Spanish word “Sin” which means without. Ironically, the only tag allowed in Sintags is the <hidden></hidden> tag, which will skip the content within the tags.

## Using Sintags

Using Sintags in your project is pretty straight forward.

    // include the autoloader
    include 'src/autoload.php';
    
    // Create a Sintags instance
    $Sintags = new AkSintagsParser();
    
    $sintags_text = '{var}';
    
    //Parse the sintags string
    $php_code = $Sintags->parse($sintags_text);
    
    // $php_code will contain => <?php echo $var; ?>


You should cache generated php code to avoid hitting the Sintags engine for the same content.

### Sintags helpers

You can register helpers in sintags by defining the AK_SINTAGS_AVAILABLE_HELPERS constant with a serialized list of helpers.

    define('AK_SINTAGS_AVAILABLE_HELPERS', serialize(array (
      'url_for' => 'url_helper',
      'render' => 'controller',
      'h' => 'text_helper',
    )));


Using the above declaration will add the methods url_for, render and h to Ruby-esque sintags, which can be written like:

    <%= url_for Person %>

which will be converted to 

    <?php echo $url_helper->url_for($Person); ?>


The usefulness comes when things get more complex like with

    <%= url_for h(Person) %>

which will be converted to 

    <?php echo $url_helper->url_for($text_helper->h($Person)); ?>


## Running the tests

Just call 

    php test/run_tests.php
