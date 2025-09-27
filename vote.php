<?php
header('Content-Type: application/json');

$dataFile = __DIR__ . '/data.json';
if (!file_exists($dataFile)) {
    echo json_encode(['success'=>false,'message'=>'Data file missing']);
    exit;
}

$moduleId = $_POST['moduleId'] ?? null;
$optionId = $_POST['optionId'] ?? null;
if (!$moduleId || !$optionId) {
    echo json_encode(['success'=>false,'message'=>'Missing parameters']);
    exit;
}

// Prevent double vote with cookie
$cookieName = 'azure_poll_votes';
$voted = isset($_COOKIE[$cookieName]) ? explode(',', $_COOKIE[$cookieName]) : [];
if (in_array($moduleId, $voted)) {
    echo json_encode(['success'=>false,'message'=>'You already voted for this module.']);
    exit;
}

// Lock and update JSON
$fp = fopen($dataFile,'c+');
if(!$fp){echo json_encode(['success'=>false,'message'=>'Cannot open data file']);exit;}
flock($fp, LOCK_EX);
$json = stream_get_contents($fp);
$data = $json ? json_decode($json,true) : null;
if(!$data) $data=['modules'=>[]];

$found=false;
foreach($data['modules'] as &$m){
    if($m['id']==$moduleId){
        foreach($m['options'] as &$o){
            if($o['id']==$optionId){
                $o['votes'] = ($o['votes']??0)+1;
                $found=true;
                break;
            }
        }
        break;
    }
}
unset($m,$o);

if($found){
    ftruncate($fp,0); rewind($fp);
    fwrite($fp,json_encode($data,JSON_PRETTY_PRINT));
}
flock($fp, LOCK_UN);
fclose($fp);

if(!$found){
    echo json_encode(['success'=>false,'message'=>'Module/option not found']);
    exit;
}

// Set cookie (30 days)
$voted[] = $moduleId;
setcookie($cookieName, implode(',',array_unique($voted)), time()+60*60*24*30, '/');

echo json_encode(['success'=>true,'data'=>$data]);
