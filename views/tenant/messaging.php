<!DOCTYPE html>
<html>
<head>
    <?php
      $siteName = isset($settings['site_name']) && $settings['site_name'] ? $settings['site_name'] : 'RentSmart';
      $pageTitle = 'Messages | ' . htmlspecialchars($siteName);
      $ownerName = !empty($owner['name']) ? (string)$owner['name'] : 'Management';
    ?>
    <title><?= $pageTitle ?></title>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($siteFavicon ?? '') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/style.css">
    <style>
      .tenant-chat-shell{max-width:980px;margin:0 auto;}
      .tenant-chat-card{border-radius:14px;overflow:hidden;}
      .tenant-chat-header{background:#fff;}
      .tenant-chat-messages{height:62vh;min-height:360px;max-height:680px;overflow-y:auto;background:#f4f6fb;padding:14px;}
      .tenant-chat-row{display:flex;margin-bottom:10px;}
      .tenant-chat-row.mine{justify-content:flex-end;}
      .tenant-chat-row.theirs{justify-content:flex-start;}
      .tenant-chat-bubble{max-width:78%;padding:10px 12px;border-radius:14px;line-height:1.25;white-space:pre-wrap;word-break:break-word;box-shadow:0 1px 1px rgba(0,0,0,.05);}
      .tenant-chat-bubble.mine{background:#0d6efd;color:#fff;border-bottom-right-radius:6px;}
      .tenant-chat-bubble.theirs{background:#fff;border:1px solid rgba(0,0,0,.08);border-bottom-left-radius:6px;}
      .tenant-chat-meta{font-size:.75rem;margin-top:4px;opacity:.8;}
      .tenant-chat-meta.mine{text-align:right;color:rgba(255,255,255,.85);}
      .tenant-chat-meta.theirs{color:#6c757d;}
      .tenant-chat-composer{background:#fff;}
      .tenant-chat-input{border-radius:12px;}
      .tenant-chat-send{border-radius:12px;}
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="tenant-chat-shell">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="d-flex align-items-center gap-3">
        <a href="<?= BASE_URL ?>/tenant/dashboard" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left"></i> Back
        </a>
        <div class="d-flex align-items-center gap-2">
          <?php if (!empty($siteLogo)): ?>
            <img src="<?= htmlspecialchars($siteLogo) ?>" alt="Site Logo" style="max-height:40px;max-width:160px;object-fit:contain;">
          <?php endif; ?>
          <span class="fw-semibold">Messages</span>
        </div>
      </div>
      <a href="<?= BASE_URL ?>/tenant/logout" class="btn btn-outline-secondary btn-sm">Logout</a>
    </div>

    <div class="card tenant-chat-card">
      <div class="card-header tenant-chat-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
          <span class="fw-semibold"><i class="bi bi-chat-dots text-primary me-2"></i><?= htmlspecialchars($ownerName) ?></span>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="tenantChatRefreshBtn">Refresh</button>
      </div>

      <div class="tenant-chat-messages" id="tenantChatMessages">
        <div class="text-muted text-center">Loading conversation…</div>
      </div>

      <div class="card-footer tenant-chat-composer">
        <form id="tenantChatForm" class="d-flex gap-2 align-items-end">
          <?= csrf_field() ?>
          <input type="hidden" name="user_id" id="tenant_msg_user_id" value="">
          <input type="text" name="body" id="tenant_msg_body" class="form-control tenant-chat-input" placeholder="Type a message…" autocomplete="off">
          <button class="btn btn-primary tenant-chat-send" type="submit" id="tenantChatSendBtn">Send</button>
        </form>
        <div class="small text-danger mt-2" id="tenantChatError" style="display:none;"></div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const chat = document.getElementById('tenantChatMessages');
  const form = document.getElementById('tenantChatForm');
  const input = document.getElementById('tenant_msg_body');
  const uId = document.getElementById('tenant_msg_user_id');
  const err = document.getElementById('tenantChatError');
  const refreshBtn = document.getElementById('tenantChatRefreshBtn');
  const sendBtn = document.getElementById('tenantChatSendBtn');

  function setError(msg){
    if (!err) return;
    if (!msg){ err.textContent = ''; err.style.display = 'none'; return; }
    err.textContent = msg;
    err.style.display = 'block';
  }

  function formatTime(ts){
    try{
      const d = new Date(ts.replace(' ', 'T'));
      if (isNaN(d.getTime())) return '';
      return d.toLocaleString(undefined, { month:'short', day:'numeric', hour:'numeric', minute:'2-digit' });
    }catch(e){ return ''; }
  }

  function render(items){
    chat.innerHTML = '';
    if (!items || !items.length){ chat.innerHTML = '<div class="text-center text-muted mt-5">No messages yet</div>'; return; }
    items.forEach(m => {
      const mine = (m.sender_type === 'tenant');
      const row = document.createElement('div');
      row.className = 'tenant-chat-row ' + (mine ? 'mine' : 'theirs');
      const wrap = document.createElement('div');
      wrap.style.maxWidth = '100%';
      const bubble = document.createElement('div');
      bubble.className = 'tenant-chat-bubble ' + (mine ? 'mine' : 'theirs');
      bubble.textContent = (m.body || '').toString();
      const meta = document.createElement('div');
      meta.className = 'tenant-chat-meta ' + (mine ? 'mine' : 'theirs');
      meta.textContent = formatTime(m.created_at || '');
      wrap.appendChild(bubble);
      wrap.appendChild(meta);
      row.appendChild(wrap);
      chat.appendChild(row);
    });
    chat.scrollTop = chat.scrollHeight;
  }

  async function loadThread(){
    setError('');
    if (chat && chat.children && chat.children.length === 0) {
      chat.innerHTML = '<div class="text-muted text-center mt-5">Loading…</div>';
    }
    try{
      const res = await fetch('<?= BASE_URL ?>/tenant/messaging/thread');
      const data = await res.json();
      if (data && data.success){
        if (data.user_id) uId.value = String(data.user_id);
        render(data.messages || []);
      } else {
        setError('Failed to load messages.');
      }
    } catch(e){ setError('Failed to load messages.'); }
  }

  form && form.addEventListener('submit', async function(e){
    e.preventDefault();
    setError('');
    const body = (input.value || '').trim();
    if (!body) return;
    const fd = new FormData(form);
    if (sendBtn) sendBtn.disabled = true;
    try{
      const res = await fetch('<?= BASE_URL ?>/tenant/messaging/send', { method: 'POST', body: fd });
      const data = await res.json();
      if (data && data.success){ input.value=''; loadThread(); }
      else { setError((data && data.message) ? data.message : 'Failed to send.'); }
    }catch(e){ setError('Failed to send.'); }
    finally { if (sendBtn) sendBtn.disabled = false; }
  });

  refreshBtn && refreshBtn.addEventListener('click', function(){ loadThread(); });

  // lightweight polling
  let pollTimer = null;
  function startPolling(){
    if (pollTimer) window.clearInterval(pollTimer);
    pollTimer = window.setInterval(function(){ loadThread(); }, 15000);
  }

  loadThread();
  startPolling();
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
