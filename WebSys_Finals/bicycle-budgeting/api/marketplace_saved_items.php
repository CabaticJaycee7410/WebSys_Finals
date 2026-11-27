
<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');

function marketplace_saved_response($data,$status){
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function marketplace_saved_url($value){
    $value=trim((string)$value);
    if($value==='')return null;
    if(!filter_var($value,FILTER_VALIDATE_URL))return null;
    $scheme=parse_url($value,PHP_URL_SCHEME);
    if(!in_array(strtolower((string)$scheme),array('http','https')))return null;
    return $value;
}

if(!logged_in()){
    marketplace_saved_response(array('success'=>false,'message'=>'Please log in again.'),401);
}

$pdo=require __DIR__ . '/../config/database.php';
$userId=current_user_id();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $stmt=$pdo->prepare(
        "SELECT p.project_id,COALESCE(NULLIF(bp.build_name,''),p.project_name) AS project_name,p.budget_amount
         FROM projects p
         LEFT JOIN (
            SELECT project_id,MAX(NULLIF(build_name,'')) AS build_name
            FROM builder_plans GROUP BY project_id
         ) bp ON bp.project_id=p.project_id
         WHERE p.user_id=?
         ORDER BY p.created_at DESC,p.project_id DESC"
    );
    $stmt->execute(array($userId));
    $projects=$stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt=$pdo->prepare(
        "SELECT s.saved_item_id,s.item_type,s.project_id,s.product_title AS title,s.brand,s.seller_name,s.price,s.image_url AS image,s.product_url AS url,s.source,s.created_at,COALESCE(NULLIF(bp.build_name,''),p.project_name) AS project_name
         FROM marketplace_saved_items s
         LEFT JOIN projects p ON p.project_id=s.project_id AND p.user_id=s.user_id
         LEFT JOIN (
            SELECT project_id,MAX(NULLIF(build_name,'')) AS build_name
            FROM builder_plans GROUP BY project_id
         ) bp ON bp.project_id=p.project_id
         WHERE s.user_id=?
         ORDER BY s.created_at DESC,s.saved_item_id DESC"
    );
    $stmt->execute(array($userId));
    $items=$stmt->fetchAll(PDO::FETCH_ASSOC);

    marketplace_saved_response(array('success'=>true,'projects'=>$projects,'items'=>$items),200);
}

if($_SERVER['REQUEST_METHOD']!=='POST'){
    marketplace_saved_response(array('success'=>false,'message'=>'Unsupported request.'),405);
}

$csrfToken=isset($_POST['csrf_token'])?$_POST['csrf_token']:null;
if(!verify_csrf_token($csrfToken)){
    marketplace_saved_response(array('success'=>false,'message'=>'Session expired. Refresh the page and try again.'),403);
}

$action=isset($_POST['action'])?$_POST['action']:'';

if($action==='remove'){
    $savedItemId=isset($_POST['saved_item_id'])?(int)$_POST['saved_item_id']:0;
    if($savedItemId<=0){
        marketplace_saved_response(array('success'=>false,'message'=>'Invalid saved item.'),400);
    }

    $stmt=$pdo->prepare('DELETE FROM marketplace_saved_items WHERE saved_item_id=? AND user_id=?');
    $stmt->execute(array($savedItemId,$userId));

    marketplace_saved_response(array('success'=>true,'message'=>'Saved item removed.'),200);
}

if($action!=='save'){
    marketplace_saved_response(array('success'=>false,'message'=>'Invalid action.'),400);
}

$itemType=isset($_POST['item_type'])?$_POST['item_type']:'';
if(!in_array($itemType,array('wishlist','favorite'))){
    marketplace_saved_response(array('success'=>false,'message'=>'Invalid saved item type.'),400);
}

$title=isset($_POST['title'])?trim($_POST['title']):'';
$brand=isset($_POST['brand'])?trim($_POST['brand']):'';
$seller=isset($_POST['seller_name'])?trim($_POST['seller_name']):'';
$source=isset($_POST['source'])?trim($_POST['source']):'lazada';
$projectId=isset($_POST['project_id'])?(int)$_POST['project_id']:0;

if($title===''){
    marketplace_saved_response(array('success'=>false,'message'=>'Product title is required.'),400);
}

if($projectId>0){
    $stmt=$pdo->prepare('SELECT project_id FROM projects WHERE project_id=? AND user_id=? LIMIT 1');
    $stmt->execute(array($projectId,$userId));
    if(!$stmt->fetch()){
        marketplace_saved_response(array('success'=>false,'message'=>'Selected build plan does not belong to your account.'),403);
    }
}else{
    $projectId=null;
}

$priceInput=isset($_POST['price'])?trim($_POST['price']):'';
$price=null;
if($priceInput!=='' && is_numeric($priceInput) && (float)$priceInput>=0){
    $price=(float)$priceInput;
}

$imageUrl=marketplace_saved_url(isset($_POST['image'])?$_POST['image']:'');
$productUrl=marketplace_saved_url(isset($_POST['url'])?$_POST['url']:'');
$brand=substr($brand,0,150);
$seller=substr($seller,0,255);
$source=substr($source,0,40);
$productKey=hash('sha256',$productUrl!==null?strtolower($productUrl):strtolower($title.'|'.$brand.'|'.$seller));

if($projectId===null){
    $stmt=$pdo->prepare('SELECT saved_item_id FROM marketplace_saved_items WHERE user_id=? AND project_id IS NULL AND item_type=? AND product_key=? LIMIT 1');
    $stmt->execute(array($userId,$itemType,$productKey));
}else{
    $stmt=$pdo->prepare('SELECT saved_item_id FROM marketplace_saved_items WHERE user_id=? AND project_id=? AND item_type=? AND product_key=? LIMIT 1');
    $stmt->execute(array($userId,$projectId,$itemType,$productKey));
}

$existingId=$stmt->fetchColumn();
if($existingId){
    marketplace_saved_response(array('success'=>true,'already_saved'=>true,'saved_item_id'=>(int)$existingId,'message'=>'This product is already saved here.'),200);
}

$stmt=$pdo->prepare(
    'INSERT INTO marketplace_saved_items (user_id,project_id,item_type,product_key,product_title,brand,seller_name,price,image_url,product_url,source)
     VALUES (?,?,?,?,?,?,?,?,?,?,?)'
);
$stmt->execute(array($userId,$projectId,$itemType,$productKey,$title,$brand!==''?$brand:null,$seller!==''?$seller:null,$price,$imageUrl,$productUrl,$source!==''?$source:'lazada'));

marketplace_saved_response(array(
    'success'=>true,
    'saved_item_id'=>(int)$pdo->lastInsertId(),
    'message'=>$itemType==='wishlist'?'Added to Wishlist.':'Added to Favorites.'
),200);
