<?php /* Public sign page: variables $req array or $invalid bool from controller */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $invalid ? 'Invalid Link' : 'Sign Request' ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; }
    .card { border: none; border-radius: 1rem; box-shadow: 0 8px 30px rgba(0,0,0,.06); }
    #pad { border:1px dashed #bbb; border-radius: 8px; background:#fff; touch-action: none; }
  </style>
</head>
<body>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-lg-7">
        <div class="card">
          <div class="card-body p-4">
            <?php if (!empty($invalid) && $invalid): ?>
              <div class="text-center py-5">
                <h3 class="mb-3">Link Not Available</h3>
                <p class="text-muted">This signature request is invalid, expired, or already completed.</p>
              </div>
            <?php else: ?>
              <h4 class="mb-1">Sign Request</h4>
              <div class="text-muted mb-3">Title: <?= htmlspecialchars($req['title'] ?? '-') ?></div>
              <?php if (!empty($req['message'])): ?>
                <div class="alert alert-info"><?= nl2br(htmlspecialchars($req['message'])) ?></div>
              <?php endif; ?>
              <form id="signForm" method="post" action="<?= BASE_URL ?>/esign/submit/<?= htmlspecialchars($req['token']) ?>">
                <div class="mb-3">
                  <label class="form-label">Your Full Name</label>
                  <input type="text" name="signer_name" class="form-control" required>
                </div>
                <div class="mb-2">
                  <label class="form-label">Draw Signature</label>
                  <canvas id="pad" width="700" height="200"></canvas>
                  <input type="hidden" name="signature_data" id="signature_data">
                </div>
                <div class="d-flex gap-2">
                  <button class="btn btn-primary" type="submit">Submit Signature</button>
                  <button type="button" id="clearBtn" class="btn btn-outline-secondary">Clear</button>
                  <a href="<?= BASE_URL ?>/esign/decline/<?= htmlspecialchars($req['token']) ?>" class="btn btn-outline-danger" onclick="return confirm('Decline to sign?')">Decline</a>
                </div>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script>
    (function(){
      const pad = document.getElementById('pad');
      const ctx = pad.getContext('2d');
      const form = document.getElementById('signForm');
      const out = document.getElementById('signature_data');
      const clearBtn = document.getElementById('clearBtn');
      let drawing = false, last = null;
      ctx.lineWidth = 2;
      ctx.lineCap = 'round';

      function pos(e){
        const rect = pad.getBoundingClientRect();
        if (e.touches && e.touches[0]) e = e.touches[0];
        return { x: (e.clientX - rect.left), y: (e.clientY - rect.top) };
      }
      function start(e){ drawing = true; last = pos(e); }
      function move(e){
        if (!drawing) return;
        const p = pos(e);
        ctx.beginPath(); ctx.moveTo(last.x, last.y); ctx.lineTo(p.x, p.y); ctx.stroke();
        last = p; e.preventDefault();
      }
      function end(){ drawing = false; }
      pad.addEventListener('mousedown', start); pad.addEventListener('mousemove', move); window.addEventListener('mouseup', end);
      pad.addEventListener('touchstart', start, {passive:false}); pad.addEventListener('touchmove', move, {passive:false}); pad.addEventListener('touchend', end);

      clearBtn && clearBtn.addEventListener('click', function(e){ e.preventDefault(); ctx.clearRect(0,0,pad.width,pad.height); });

      form && form.addEventListener('submit', function(e){
        // Require some ink on canvas
        const blank = document.createElement('canvas'); blank.width = pad.width; blank.height = pad.height;
        if (pad.toDataURL() === blank.toDataURL()) { e.preventDefault(); alert('Please draw your signature.'); return false; }
        out.value = pad.toDataURL('image/png');
      });
    })();
  </script>
</body>
</html>
