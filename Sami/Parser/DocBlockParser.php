<?php

/*
 * This file is part of the Sami utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sami\Parser;

use Sami\Parser\Node\DocBlockNode;

class DocBlockParser
{

    public function parse($comment){

        $description = '';
        $tags        = array();
        $tag         = null;
        $pointer     = '';
        $padding     = 0;
        $comment     = '* ' . trim($comment, "/* \t\n\r\0\x0B");
        $comment     = preg_split('/\r\n|\r|\n/', $comment);
        $doc         = new DocBlockNode();

        // analyze each line
        foreach($comment as $line){

          // drop any wrapping spaces
          $line = trim($line);

          // drop "* "
          if($line !== '')
            $line = substr($line, 2);      

          if(strpos($line, '@') !== 0){

            // preserve formatting of tag descriptions,
            // because they may span across multiple lines
            if($tag !== null){
              $trimmed = trim($line);

              if($padding !== 0)
                $trimmed = str_pad($trimmed, strlen($line) - $padding, ' ', STR_PAD_LEFT);
              else
                $padding = strlen($line) - strlen($trimmed);

              $pointer .= "\n{$trimmed}";
              continue;
            }
            
            // tag definitions have not started yet; assume this is part of the description text
            $description .= "\n{$line}";        
            continue;
          }  

          $padding = 0;
          $parts = explode(' ', $line, 2);

          // invalid tag? (should we include it as an empty array?)
          if(!isset($parts[1]))
            continue;

          $tag = substr($parts[0], 1);
          $line = ltrim($parts[1]);

          // tags that have a single component (eg. link, license, author, throws...);
          // note that @throws may have 2 components, however most people use it like "@throws ExceptionClass if whatever...",
          // which, if broken into two values, leads to an inconsistent description sentence
          if(!in_array($tag, array('global', 'param', 'return', 'var', 'throws'))){
            $tags[$tag][] = $line;
            end($tags[$tag]);
            $pointer = &$tags[$tag][key($tags[$tag])];
            continue;
          }

          // tags with 2 or 3 components (var, param, return);
          $parts = explode(' ', $line, 2);
          $parts[1] = isset($parts[1]) ? ltrim($parts[1]) : null;
          $lastIdx = 1;

          // expecting 3 components on the 'param' tag: type varName varDescription
          if($tag === 'param'){
            $lastIdx = 2;
            $parts[0] = $this->parseHint($parts[0]);
            if(in_array($parts[1][0], array('&', '$'), true)){
              $line     = ltrim(array_pop($parts));
              $parts    = array_merge($parts, explode(' ', $line, 2));
              $parts[1] = ltrim($parts[1], '&$');
              $parts[2] = isset($parts[2]) ? ltrim($parts[2]) : null;
            }else{
              $parts[2] = $parts[1];
              $parts[1] = null;
            }
          
          }elseif(($tag === 'var') || ($tag === 'return')){
            $parts[0] = $this->parseHint($parts[0]);
          }

          $tags[$tag][] = $parts;
          end($tags[$tag]);
          $pointer = &$tags[$tag][key($tags[$tag])][$lastIdx];
        }

        // split title from the description texts at the nearest 2x new-line combination
        // (note: loose check because 0 isn't valid as well)
        if(strpos($description, "\n\n")){
          list($title, $description) = explode("\n\n", $description, 2);

        // if we don't have 2 new lines, try to extract first sentence
        }else{  
          // in order for a sentence to be considered valid,
          // the next one must start with an uppercase letter    
          $sentences = preg_split('/(?<=[.?!])\s+(?=[A-Z])/', $description, 2, PREG_SPLIT_NO_EMPTY);

          // failed to detect a second sentences? then assume there's only title and no description text
          $title = isset($sentences[0]) ? $sentences[0] : $description;
          $description = isset($sentences[1]) ? $sentences[1] : '';
        }

        $doc->setShortDesc(ltrim($title));
        $doc->setLongDesc(ltrim($description));

        foreach($tags as $name => $values)
          foreach($values as $value)
            $doc->addTag($name, $value);

        return $doc;
    }

    protected function parseHint($hint)
    {
        $hints = array();
        foreach (explode('|', $hint) as $hint) {
            if ('[]' == substr($hint, -2)) {
                $hints[] = array(substr($hint, 0, -2), true);
            } else {
                $hints[] = array($hint, false);
            }
        }

        return $hints;
    }

}
