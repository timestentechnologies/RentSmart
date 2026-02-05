<!DOCTYPE html>
<html>
<head>
    <title>Tenant Messaging</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h5 mb-0"><i class="bi bi-chat-dots text-primary me-2"></i>Messages</h1>
            <a href="<?= BASE_URL ?>/tenant/dashboard" class="btn btn-sm btn-outline-secondary">Back to Dashboard</a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <strong>Conversation</strong>
        </div>
        <div class="card-body" id="tenantChatMessages" style="height: 420px; overflow-y: auto;">
            <div class="text-muted text-center">Loading conversation…</div>
        </div>
        <div class="card-footer">
            <form id="tenantChatForm" class="d-flex gap-2">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" id="tenant_msg_user_id" value="">
                <input type="text" name="body" id="tenant_msg_body" class="form-control" placeholder="Type a message…" autocomplete="off">
                <button class="btn btn-primary" type="submit">Send</button>
            </form>
        </div>
    </div>
</div>

<script>
(function(){
  const chat = document.getElementById('tenantChatMessages');
  const form = document.getElementById('tenantChatForm');
  const input = document.getElementById('tenant_msg_body');
  const uId = document.getElementById('tenant_msg_user_id');

  function render(items){
    chat.innerHTML = '';
    if (!items || !items.length){ chat.innerHTML = '<div class="text-center text-muted mt-5">No messages yet</div>'; return; }
    items.forEach(m => {
      const mine = (m.sender_type === 'tenant');
      const row = document.createElement('div');
      row.className = 'd-flex mb-2 ' + (mine ? 'justify-content-end' : 'justify-content-start');
      const bubble = document.createElement('div');
      bubble.className = 'p-2 rounded ' + (mine ? 'bg-primary text-white' : 'bg-light border');
      bubble.style.maxWidth = '75%';
      bubble.textContent = (m.body || '').toString();
      row.appendChild(bubble);
      chat.appendChild(row);
    });
    chat.scrollTop = chat.scrollHeight;
  }

  async function loadThread(){
    chat.innerHTML = '<div class="text-muted text-center mt-5">Loading…</div>';
    try{
      const res = await fetch('<?= BASE_URL ?>/tenant/messaging/thread');
      const data = await res.json();
      if (data && data.success){
        if (data.user_id) uId.value = String(data.user_id);
        render(data.messages || []);
      } else {
        chat.innerHTML = '<div class="text-danger text-center mt-5">Failed to load</div>';
      }
    } catch(e){ chat.innerHTML = '<div class="text-danger text-center mt-5">Failed to load</div>'; }
  }

  form && form.addEventListener('submit', async function(e){
    e.preventDefault();
    const body = (input.value || '').trim();
    if (!body) return;
    const fd = new FormData(form);
    try{
      const res = await fetch('<?= BASE_URL ?>/tenant/messaging/send', { method: 'POST', body: fd });
      const data = await res.json();
      if (data && data.success){ input.value=''; loadThread(); }
    }catch(e){}
  });

  loadThread();
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</body>
</html>
