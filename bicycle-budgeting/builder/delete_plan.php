<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
header('Content-Type: application/json; charset=utf-8');
function delete_reply(array $data,int $status=200): void { http_response_code($status); echo json_encode($data); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') delete_reply(['success'=>false,'message'=>'Invalid request method.'],405);
$data = json_decode(file_get_contents('php://input'),true);
if (!is_array($data) || !verify_csrf_token($data['csrfToken'] ?? null)) delete_reply(['success'=>false,'message'=>'Session expired or invalid request.'],403);
$projectId=(int)($data['projectId'] ?? 0); $userId=current_user_id();
if ($projectId<1) delete_reply(['success'=>false,'message'=>'Invalid build plan.'],422);
$pdo=require __DIR__.'/../config/database.php';
try {
    $pdo->beginTransaction();
    $stmt=$pdo->prepare('SELECT bicycle_id FROM projects WHERE project_id=? AND user_id=? FOR UPDATE');
    $stmt->execute([$projectId,$userId]); $project=$stmt->fetch();
    if (!$project) { $pdo->rollBack(); delete_reply(['success'=>false,'message'=>'Build plan not found.'],404); }
    $bicycleId=(int)($project['bicycle_id'] ?? 0);
    $stmt=$pdo->prepare('DELETE FROM projects WHERE project_id=? AND user_id=?'); $stmt->execute([$projectId,$userId]);
    if ($bicycleId>0) {
        $stmt=$pdo->prepare('SELECT COUNT(*) FROM projects WHERE bicycle_id=?'); $stmt->execute([$bicycleId]);
        if ((int)$stmt->fetchColumn()===0) { $stmt=$pdo->prepare('DELETE FROM bicycles WHERE bicycle_id=? AND user_id=?'); $stmt->execute([$bicycleId,$userId]); }
    }
    $pdo->commit(); delete_reply(['success'=>true,'message'=>'Build plan deleted.']);
} catch(Throwable $error) {
    if($pdo->inTransaction()) $pdo->rollBack();
    error_log('Builder delete: '.$error->getMessage());
    delete_reply(['success'=>false,'message'=>'Unable to delete this plan.'],500);
}
