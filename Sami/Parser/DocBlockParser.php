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

    /**
     * Parses a DocBlock comment into a data structure.
     *
     * @link    https://github.com/digitalnature/php-ref/blob/master/ref.php#L1212
     * @link    http://pear.php.net/manual/en/standards.sample.php
     * @param   string $comment
     * @return  DocBlockNode
     */
    public static function parse($comment)
    {

        $title       = '';
        $description = '';
        $tags        = array();
        $tag         = null;
        $pointer     = null;
        $padding     = false;
        $doc         = new DocBlockNode();

        foreach(array_slice(preg_split('/\r\n|\r|\n/', $comment), 1, -1) as $line){

            // drop any leading spaces
            $line = ltrim($line);

            // drop "* "
            if($line !== '')
                $line = substr($line, 2);      

            if(strpos($line, '@') === 0){
                $padding = false;        
                $pos     = strpos($line, ' ');
                $tag     = substr($line, 1, $pos - 1);
                $line    = trim(substr($line, $pos));

                // tags that have two or more values;
                // note that 'throws' may also have two values, however most people use it like "@throws ExceptioClass if whatever...",
                // which, if broken into two values, leads to an inconsistent description sentence...
                if(in_array($tag, array('global', 'param', 'return', 'var'))){
                    $parts = array();

                    if(($pos = strpos($line, ' ')) !== false){
                        $parts[] = substr($line, 0, $pos);
                        $line = ltrim(substr($line, $pos));

                        if(($pos = strpos($line, ' ')) !== false){

                          // we expect up to 3 elements in 'param' tags
                          if(($tag === 'param') && in_array($line[0], array('&', '$'), true)){
                               $parts[] = substr($line, 0, $pos);
                               $parts[] = ltrim(substr($line, $pos));
                            
                          }else{
                               if($tag === 'param')
                                   $parts[] = '';

                              $parts[] = ltrim($line);
                          }

                        }else{
                            $parts[] = $line;
                        }

                    }else{
                        $parts[] = $line;            
                    }

                    $parts += array_fill(0, ($tag !== 'param') ? 2 : 3, '');

                    // following exists just to make it compatabile with the old parser;            
                    $parts[0] = explode('|', $parts[0]);
                    foreach($parts[0] as &$p)
                        $p = array($p, false); // old parser has a weird false value here...           

                    // maybe we should leave out empty (invalid) entries?
                    if(array_filter($parts)){
                        $tags[$tag][] = $parts;
                        $pointer = &$tags[$tag][count($tags[$tag]) - 1][count($parts) - 1];
                    }  

                // tags that have only one value (eg. 'link', 'license', 'author' ...)
                }else{
                    $tags[$tag][] = trim($line);
                    $pointer = &$tags[$tag][count($tags[$tag]) - 1];
                }

                continue;
            }

            // preserve formatting of tag descriptions, because
            // in some frameworks (like Lithium) they span across multiple lines
            if($tag !== null){

                $trimmed = trim($line);

                if($padding !== false){
                    $trimmed = str_pad($trimmed, strlen($line) - $padding, ' ', STR_PAD_LEFT);
                  
                }else{
                    $padding = strlen($line) - strlen($trimmed);
                }  

                $pointer .=  "\n{$trimmed}";
                continue;
            }

            // tag definitions have not started yet;
            // assume this is title / description text
            $description .= "\n{$line}";
          }

          $description = trim($description);

          // determine the real title and description by splitting the text
          // at the nearest encountered [dot + space] or [2x new line]
          if($description !== ''){
              $stop = min(array_filter(array(strlen($description), strpos($description, '. '), strpos($description, "\n\n"))));
              $title = substr($description, 0, $stop + 1);
              $description = ltrim(substr($description, $stop + 1));
          }

          $doc->setShortDesc($title);
          $doc->setLongDesc($description);

          foreach($tags as $tag => $values)
              foreach($values as $value)
                   $doc->addTag($tag, $value);

          return $doc;
    }

}
