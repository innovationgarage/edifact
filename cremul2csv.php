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

$mapping = new EDI\Mapping\MappingProvider('D95B');
$analyser = new EDI\Analyser();
$analyser->loadMessageXml($mapping->getMessage('CREMUL'));
$analyser->loadSegmentsXml($mapping->getSegments());
$analyser->process(utf8_converter($parsed));

$data = json_decode($analyser->getJson());

$lines = array();
$line = array();
foreach ($data as $item) {
  $item = (array) $item;
  if (array_key_exists("lineItem", $item)) {
    array_push($lines, $line);
    $line = array();
  } else {
    $keys = array_keys($item);
    $key = $keys[0];
    $value = (array) $item[$key];
    if (!array_key_exists($key, $line)) {
      $line[$key] = array();
    }
    if (array_key_exists("partyQualifier", $value)) {
      $line[$key][$value["partyQualifier"]] = $value;
    } else {
      array_push($line[$key], $value);
    }
  }
}
if (count($line)) {
  array_push($lines, $line);
}
$header = array_shift($lines);

$out = fopen('php://output', 'w');
foreach($lines as $line) {
  $txt = $line["freeText"][0]["textLiteral"];

  if (is_object($txt)) {
    $txt = $txt->freeText;
  }

  fputcsv($out, array(
    $line["monetaryAmount"][0]["monetaryAmount"]->monetaryAmount,
    $line["monetaryAmount"][0]["monetaryAmount"]->currencyCoded,
    $line["nameAndAddress"]["PL"]["partyName"],
    $txt
  ));
}
fclose($out);
