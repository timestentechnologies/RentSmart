<?php

namespace App\Controllers;

use App\Models\ESignRequest;
use App\Models\Tenant;
use App\Models\User;

class ESignController
{
    private $userId;

    public function __construct()
    {
        $this->userId = $_SESSION['user_id'] ?? null;
    }

    public function index()
    {
        if (!$this->userId) { $_SESSION['flash_message'] = 'Please login'; redirect('/'); return; }
        $model = new ESignRequest();
        $requests = $model->listForUser((int)$this->userId);
        require 'views/esign/index.php';
    }

    public function create()
    {
        if (!$this->userId) { $_SESSION['flash_message'] = 'Please login'; redirect('/'); return; }
        $tenantModel = new Tenant();
        $tenants = $tenantModel->getAll($this->userId);
        // Users by selected roles
        $userModel = new User();
        $db = $userModel->getDb();
        $userModel->find($this->userId);
        if ($userModel->isAdmin()) {
            $stmt = $db->prepare("SELECT id, name, role, email FROM users WHERE role IN ('admin','administrator','manager','agent','landlord','caretaker') ORDER BY name ASC");
            $stmt->execute();
            $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $propIds = $userModel->getAccessiblePropertyIds();
            $users = [];
            if (!empty($propIds)) {
                $in = implode(',', array_fill(0, count($propIds), '?'));
                // Caretakers assigned to accessible properties
                $sqlCaretakers = "SELECT DISTINCT u.id, u.name, u.role, u.email
                                   FROM users u
                                   JOIN properties p ON p.caretaker_user_id = u.id
                                   WHERE u.role = 'caretaker' AND p.id IN ($in)
                                   ORDER BY u.name ASC";
                $stmtC = $db->prepare($sqlCaretakers);
                $stmtC->execute($propIds);
                $users = $stmtC->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            }
            // Always include admins
            $stmtA = $db->prepare("SELECT id, name, role, email FROM users WHERE role IN ('admin','administrator') ORDER BY name ASC");
            $stmtA->execute();
            $admins = $stmtA->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            $byId = [];
            foreach ($users as $u) { $byId[(int)$u['id']] = $u; }
            foreach ($admins as $a) { $byId[(int)$a['id']] = $a; }
            $users = array_values($byId);
        }
        $entity_type = $_GET['entity_type'] ?? null;
        $entity_id = isset($_GET['entity_id']) ? (int)$_GET['entity_id'] : null;
        require 'views/esign/create.php';
    }

    public function store()
    {
        if (!$this->userId) { $_SESSION['flash_message'] = 'Please login'; redirect('/'); return; }
        try {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') throw new \Exception('Invalid request');
            if (!function_exists('verify_csrf_token') || !verify_csrf_token()) throw new \Exception('Invalid CSRF token');
            $title = trim($_POST['title'] ?? '');
            $message = trim($_POST['message'] ?? '');
            $recipient_type = ($_POST['recipient_type'] ?? '') === 'tenant' ? 'tenant' : 'user';
            $recipient_id = (int)($_POST['recipient_id'] ?? 0);
            $entity_type = $_POST['entity_type'] ?? null;
            $entity_id = !empty($_POST['entity_id']) ? (int)$_POST['entity_id'] : null;
            $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] . ' 23:59:59' : null;
            if ($title === '' || $recipient_id <= 0) throw new \Exception('Title and recipient are required');

            // Handle optional document upload
            $document_path = null;
            if (!empty($_FILES['document']) && is_uploaded_file($_FILES['document']['tmp_name'])) {
                $err = $_FILES['document']['error'] ?? UPLOAD_ERR_OK;
                if ($err !== UPLOAD_ERR_OK) throw new \Exception('Upload failed');
                $allowed = ['application/pdf','image/png','image/jpeg'];
                $mime = mime_content_type($_FILES['document']['tmp_name']);
                if (!in_array($mime, $allowed)) throw new \Exception('Only PDF/PNG/JPG allowed');
                $ext = pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION) ?: ($mime === 'application/pdf' ? 'pdf' : ($mime === 'image/png' ? 'png' : 'jpg'));
                $dir = __DIR__ . '/../../public/uploads/esign';
                if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
                $fname = 'doc_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest = $dir . '/' . $fname;
                if (!move_uploaded_file($_FILES['document']['tmp_name'], $dest)) throw new \Exception('Failed to save upload');
                $document_path = 'uploads/esign/' . $fname;
            }
            $model = new ESignRequest();
            $result = $model->createRequest([
                'title' => $title,
                'message' => $message,
                'requester_user_id' => (int)$this->userId,
                'recipient_type' => $recipient_type,
                'recipient_id' => $recipient_id,
                'entity_type' => $entity_type,
                'entity_id' => $entity_id,
                'expires_at' => $expires_at,
                'document_path' => $document_path,
            ]);
            $_SESSION['flash_message'] = 'Signature request created';
            $_SESSION['flash_type'] = 'success';
            redirect('/esign/show/' . (int)$result['id']);
            return;
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            redirect('/esign/create');
            return;
        }
    }

    public function show($id)
    {
        if (!$this->userId) { $_SESSION['flash_message'] = 'Please login'; redirect('/'); return; }
        $model = new ESignRequest();
        $req = $model->getById((int)$id);
        if (!$req) { http_response_code(404); echo 'Request not found'; return; }
        $signUrl = BASE_URL . '/esign/sign/' . $req['token'];
        require 'views/esign/show.php';
    }

    // Public sign page
    public function sign($token)
    {
        $model = new ESignRequest();
        $req = $model->getByToken($token);
        $invalid = false;
        if (!$req) { $invalid = true; }
        if (!$invalid && $req['status'] !== 'pending') { $invalid = true; }
        if (!$invalid && !empty($req['expires_at']) && strtotime($req['expires_at']) < time()) { $invalid = true; }
        require 'views/esign/sign.php';
    }

    public function submit($token)
    {
        try {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') throw new \Exception('Invalid request');
            $model = new ESignRequest();
            $req = $model->getByToken($token);
            if (!$req) throw new \Exception('Request not found');
            $name = trim($_POST['signer_name'] ?? '');
            $method = $_POST['method'] ?? 'draw';
            if ($name === '') throw new \Exception('Name is required');
            $data = '';
            $sigType = null;
            $initials = null;
            // Placement options
            $pos_mode = $_POST['pos_mode'] ?? 'auto';
            $x_pct = isset($_POST['pos_x']) && is_numeric($_POST['pos_x']) ? max(0.0, min(1.0, (float)$_POST['pos_x'])) : null;
            $y_pct = isset($_POST['pos_y']) && is_numeric($_POST['pos_y']) ? max(0.0, min(1.0, (float)$_POST['pos_y'])) : null;
            $corner = $_POST['corner'] ?? 'br'; // br, bl, tr, tl
            $page_mode = $_POST['page_mode'] ?? 'all'; // all, first, last

            if ($method === 'draw') {
                $data = $_POST['signature_data'] ?? '';
                if ($data === '') throw new \Exception('Please draw your signature');
                if (strpos($data, 'base64,') !== false) { $data = substr($data, strpos($data, 'base64,') + 7); }
                $sigType = 'draw';
            } elseif ($method === 'upload') {
                if (empty($_FILES['signature_file']) || !is_uploaded_file($_FILES['signature_file']['tmp_name'])) throw new \Exception('Upload a signature image');
                $err = $_FILES['signature_file']['error'] ?? UPLOAD_ERR_OK;
                if ($err !== UPLOAD_ERR_OK) throw new \Exception('Signature upload failed');
                $allowed = ['image/png','image/jpeg'];
                $mime = mime_content_type($_FILES['signature_file']['tmp_name']);
                if (!in_array($mime, $allowed)) throw new \Exception('Only PNG/JPG signature allowed');
                $bin = file_get_contents($_FILES['signature_file']['tmp_name']);
                $data = base64_encode($bin);
                $sigType = 'upload';
            } elseif ($method === 'initials') {
                $initials = trim($_POST['signature_initials'] ?? '');
                if ($initials === '') throw new \Exception('Enter your initials');
                $data = '';
                $sigType = 'initials';
            } else {
                throw new \Exception('Invalid method');
            }
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $ok = $model->markSigned($token, $name, $data, $ip, $ua, $sigType, $initials);
            if (!$ok) throw new \Exception('Unable to save signature');
            if (!empty($req['document_path'])) {
                $signedRel = $this->generateSignedCopy($req, $name, $sigType, $data, $initials, [
                    'pos_mode' => $pos_mode,
                    'x_pct' => $x_pct,
                    'y_pct' => $y_pct,
                    'corner' => $corner,
                    'page_mode' => $page_mode,
                ]);
                if ($signedRel) { $model->setSignedDocumentPath($token, $signedRel); }
            }
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Signature Saved</title><style>body{font-family:Arial;padding:24px}</style></head><body><h2>Thank you!</h2><p>Your signature has been recorded.</p></body></html>';
            return;
        } catch (\Exception $e) {
            http_response_code(400);
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Error</title><style>body{font-family:Arial;padding:24px;color:#a00}</style></head><body><h3>Error</h3><p>' . htmlspecialchars($e->getMessage()) . '</p></body></html>';
            return;
        }
    }

    private function generateSignedCopy(array $req, string $name, ?string $sigType, ?string $base64Data, ?string $initials, array $options = [])
    {
        try {
            $publicBase = realpath(__DIR__ . '/../../public');
            if (!$publicBase) return null;
            $srcRel = $req['document_path'];
            $srcFull = $publicBase . DIRECTORY_SEPARATOR . str_replace(['..','\\'], ['','/'], $srcRel);
            if (!is_file($srcFull)) return null;
            $outDir = $publicBase . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'esign';
            if (!is_dir($outDir)) @mkdir($outDir, 0775, true);
            $ext = strtolower(pathinfo($srcFull, PATHINFO_EXTENSION));
            $token = $req['token'] ?? bin2hex(random_bytes(4));
            $ts = date('Ymd_His');
            $pos_mode = $options['pos_mode'] ?? 'auto';
            $x_pct = isset($options['x_pct']) ? (float)$options['x_pct'] : null;
            $y_pct = isset($options['y_pct']) ? (float)$options['y_pct'] : null;
            $corner = $options['corner'] ?? 'br';
            $page_mode = $options['page_mode'] ?? 'all';

            $sigImg = null;
            if ($sigType === 'initials') {
                if (function_exists('imagecreatetruecolor')) {
                    $sigImg = imagecreatetruecolor(800, 200);
                    imagesavealpha($sigImg, true);
                    $trans = imagecolorallocatealpha($sigImg, 0, 0, 0, 127);
                    imagefill($sigImg, 0, 0, $trans);
                    $col = imagecolorallocate($sigImg, 0, 0, 0);
                    imagestring($sigImg, 5, 30, 80, $initials ?? '', $col);
                }
            } elseif ($base64Data) {
                $bin = base64_decode($base64Data);
                if ($bin !== false && function_exists('imagecreatefromstring')) {
                    $sigImg = imagecreatefromstring($bin);
                }
            }

            if (in_array($ext, ['png','jpg','jpeg'])) {
                if (!function_exists('imagecreatefromstring') || !$sigImg) return $this->generateSignedPdfFallback($req, $name, $sigType, $base64Data, $initials, $outDir, $token, $ts);
                $srcBin = file_get_contents($srcFull);
                $baseIm = imagecreatefromstring($srcBin);
                if (!$baseIm) return null;
                $bw = imagesx($baseIm); $bh = imagesy($baseIm);
                $sw = imagesx($sigImg); $sh = imagesy($sigImg);
                $targetW = max(200, (int)round($bw * 0.35));
                $ratio = $sw > 0 ? ($targetW / $sw) : 1;
                $targetH = max(60, (int)round($sh * $ratio));
                $res = imagecreatetruecolor($targetW, $targetH);
                imagesavealpha($res, true);
                $trans2 = imagecolorallocatealpha($res, 0, 0, 0, 127);
                imagefill($res, 0, 0, $trans2);
                imagecopyresampled($res, $sigImg, 0, 0, 0, 0, $targetW, $targetH, $sw, $sh);
                $pad = 40;
                if ($pos_mode === 'click' && $x_pct !== null && $y_pct !== null) {
                    $dx = (int)round($bw * $x_pct - $targetW / 2);
                    $dy = (int)round($bh * $y_pct - $targetH / 2);
                    if ($dx < 0) $dx = 0; if ($dy < 0) $dy = 0;
                    if ($dx > $bw - $targetW) $dx = $bw - $targetW;
                    if ($dy > $bh - $targetH) $dy = $bh - $targetH;
                } else {
                    // Corner placement
                    switch (strtolower($corner)) {
                        case 'tl': $dx = $pad; $dy = $pad; break;
                        case 'tr': $dx = max(0, $bw - $targetW - $pad); $dy = $pad; break;
                        case 'bl': $dx = $pad; $dy = max(0, $bh - $targetH - $pad); break;
                        case 'br':
                        default:
                            $dx = max(0, $bw - $targetW - $pad);
                            $dy = max(0, $bh - $targetH - $pad);
                    }
                }
                imagecopy($baseIm, $res, $dx, $dy, 0, 0, $targetW, $targetH);
                $meta = 'Signed by ' . $name . ' on ' . date('Y-m-d H:i');
                $txtY = min($bh - 10, $dy - 15);
                if ($txtY > 0) { $c = imagecolorallocate($baseIm, 50, 50, 50); imagestring($baseIm, 3, max(10, $dx), $txtY, $meta, $c); }
                $destRel = 'uploads/esign/signed_' . $token . '_' . $ts . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
                $destFull = $publicBase . DIRECTORY_SEPARATOR . $destRel;
                if ($ext === 'png') imagepng($baseIm, $destFull, 6); else imagejpeg($baseIm, $destFull, 90);
                return $destRel;
            }

            if ($ext === 'pdf') {
                if (class_exists('Imagick')) {
                    try {
                        $pdf = new \Imagick();
                        $pdf->readImage($srcFull);
                        $pages = $pdf->getNumberImages();
                        $sigPath = null;
                        if ($sigImg) {
                            $sigPath = $outDir . DIRECTORY_SEPARATOR . 'sig_' . $token . '_' . $ts . '.png';
                            imagepng($sigImg, $sigPath, 6);
                        }
                        for ($i = 0; $i < $pages; $i++) {
                            if ($page_mode === 'first' && $i !== 0) { continue; }
                            if ($page_mode === 'last' && $i !== ($pages - 1)) { continue; }
                            $pdf->setIteratorIndex($i);
                            $w = $pdf->getImageWidth();
                            $h = $pdf->getImageHeight();
                            if ($sigPath) {
                                $overlay = new \Imagick($sigPath);
                                $overlay->thumbnailImage((int)max(200, $w * 0.35), 0);
                                $pad = 40;
                                switch (strtolower($corner)) {
                                    case 'tl': $ox = $pad; $oy = $pad; break;
                                    case 'tr': $ox = max(0, $w - $overlay->getImageWidth() - $pad); $oy = $pad; break;
                                    case 'bl': $ox = $pad; $oy = max(0, $h - $overlay->getImageHeight() - $pad); break;
                                    case 'br':
                                    default:
                                        $ox = max(0, $w - $overlay->getImageWidth() - $pad);
                                        $oy = max(0, $h - $overlay->getImageHeight() - $pad);
                                }
                                $pdf->compositeImage($overlay, \Imagick::COMPOSITE_OVER, $ox, $oy);
                            }
                        }
                        $destRel = 'uploads/esign/signed_' . $token . '_' . $ts . '.pdf';
                        $destFull = $publicBase . DIRECTORY_SEPARATOR . $destRel;
                        $pdf->setImageFormat('pdf');
                        $pdf->writeImages($destFull, true);
                        return $destRel;
                    } catch (\Throwable $t) {
                        return $this->generateSignedPdfFallback($req, $name, $sigType, $base64Data, $initials, $outDir, $token, $ts);
                    }
                }
                return $this->generateSignedPdfFallback($req, $name, $sigType, $base64Data, $initials, $outDir, $token, $ts);
            }
            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function generateSignedPdfFallback(array $req, string $name, ?string $sigType, ?string $base64Data, ?string $initials, string $outDir, string $token, string $ts)
    {
        try {
            $publicBase = realpath(__DIR__ . '/../../public');
            if (!$publicBase) return null;
            $origRel = $req['document_path'];
            $sigImgData = '';
            if ($sigType === 'initials') {
                $sigImgData = '';
            } elseif ($base64Data) {
                $sigImgData = 'data:image/png;base64,' . $base64Data;
            }
            ob_start();
            $html = '<html><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,Arial,sans-serif;font-size:12px} .box{border:1px solid #ddd;padding:12px;border-radius:8px}</style></head><body>' .
                '<h2>Signed Copy</h2>' .
                '<div class="box">' .
                '<div><strong>Original:</strong> ' . htmlspecialchars($origRel) . '</div>' .
                '<div><strong>Signed by:</strong> ' . htmlspecialchars($name) . ' on ' . date('Y-m-d H:i') . '</div>' .
                (($sigType === 'initials') ? '<div><strong>Initials:</strong> ' . htmlspecialchars((string)$initials) . '</div>' : '') .
                (($sigImgData && $sigType !== 'initials') ? '<div style="margin-top:10px"><img style="max-width:480px" src="' . $sigImgData . '" /></div>' : '') .
                '<div style="margin-top:10px"><a href="' . BASE_URL . '/public/' . htmlspecialchars($origRel) . '">Open original</a></div>' .
                '</div></body></html>';
            ob_end_clean();
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $destRel = 'uploads/esign/signed_' . $token . '_' . $ts . '.pdf';
            $destFull = $publicBase . DIRECTORY_SEPARATOR . $destRel;
            file_put_contents($destFull, $dompdf->output());
            return $destRel;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function decline($token)
    {
        $model = new ESignRequest();
        $model->markDeclined($token);
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Declined</title><style>body{font-family:Arial;padding:24px}</style></head><body><h2>Request Declined</h2><p>You have declined to sign this request.</p></body></html>';
    }
}
