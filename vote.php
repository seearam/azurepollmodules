<?php
header('Content-Type: application/json');

$dataFile = __DIR__ . "/data.json";
$data = json_decode(file_get_contents($dataFile), true);
$input = json_decode(file_get_contents("php://input"), true);

$moduleId = $input['moduleId'] ?? null;
$optionId = $input['optionId'] ?? null;

if(!$moduleId || !$optionId){ echo json_encode(["error"=>"Invalid data"]); exit; }

foreach($data['modules'] as &$module){
    if($module['id'] == $moduleId){
        foreach($module['options'] as &$opt){
            if($opt['id'] == $optionId){
                $opt['votes'] += 1;
                file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
                echo json_encode($module);
                exit;
            }
        }
    }
}
echo json_encode(["error"=>"Not found"]);
