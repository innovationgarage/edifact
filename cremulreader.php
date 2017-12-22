<?php
require __DIR__.'/vendor/autoload.php';

use EDI\Parser;

$parser = new Parser();
$parsed = $parser->load($argv[1]);

function utf8_converter($array)
{
    array_walk_recursive($array, function(&$item, $key){
                $item = iconv("ISO-8859-1", "UTF-8", $item);
    });
 
    return $array;
}

// echo(json_encode(utf8_converter($parsed), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));


$mapping = new EDI\Mapping\MappingProvider('D95B');
$analyser = new EDI\Analyser();
$analyser->loadMessageXml($mapping->getMessage('CREMUL'));
$analyser->loadSegmentsXml($mapping->getSegments());
$analyser->process(utf8_converter($parsed));

echo(json_encode(json_decode($analyser->getJson()), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));