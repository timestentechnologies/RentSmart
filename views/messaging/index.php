<?php
ob_start();
?>
<div class="container-fluid pt-4">
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0"><i class="bi bi-chat-dots text-primary me-2"></i>Messaging</h1>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-header">
                    <strong>Recipients</strong>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" id="recipientList">
                        <div class="list-group-item bg-light small text-muted">Broadcast</div>
                        <a href="#" class="list-group-item list-group-item-action recipient-item" data-type="broadcast" data-id="all">
                            <i class="bi bi-megaphone me-2"></i>
                            All Tenants
                            <div class="small text-muted">Send one message to every accessible tenant</div>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action recipient-item" data-type="broadcast" data-id="due_current_month">
                            <i class="bi bi-exclamation-circle me-2"></i>
                            Tenants with Balance (Current Month)
                            <div class="small text-muted">Only tenants owing rent for this month</div>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action recipient-item" data-type="broadcast" data-id="due_previous_months">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Tenants with Balance (Previous Months)
                            <div class="small text-muted">Tenants owing rent including previous months</div>
                        </a>

                        <?php if (!empty($recipients['tenants'])): ?>
                            <div class="list-group-item bg-light small text-muted">Tenants</div>
                            <?php foreach ($recipients['tenants'] as $t): ?>
                                <a href="#" class="list-group-item list-group-item-action recipient-item" data-type="tenant" data-id="<?= (int)$t['id'] ?>">
                                    <i class="bi bi-person me-2"></i>
                                    <?= htmlspecialchars($t['name']) ?>
                                    <div class="small text-muted"><?= htmlspecialchars(($t['property'] ?? '-') . ' • ' . ($t['unit'] ?? '-')) ?></div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if (!empty($recipients['caretakers'])): ?>
                            <div class="list-group-item bg-light small text-muted">Caretakers</div>
                            <?php foreach ($recipients['caretakers'] as $c): ?>
                                <a href="#" class="list-group-item list-group-item-action recipient-item" data-type="user" data-id="<?= (int)$c['id'] ?>">
                                    <i class="bi bi-person-badge me-2"></i>
                                    <?= htmlspecialchars($c['name']) ?>
                                    <div class="small text-muted"><?= htmlspecialchars($c['role'] ?? 'caretaker') ?></div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if (!empty($recipients['admins'])): ?>
                            <div class="list-group-item bg-light small text-muted">Admins</div>
                            <?php foreach ($recipients['admins'] as $a): ?>
                                <a href="#" class="list-group-item list-group-item-action recipient-item" data-type="user" data-id="<?= (int)$a['id'] ?>">
                                    <i class="bi bi-shield-lock me-2"></i>
                                    <?= htmlspecialchars($a['name']) ?>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if (!empty($recipients['users'])): ?>
                            <div class="list-group-item bg-light small text-muted">Users</div>
                            <?php foreach ($recipients['users'] as $u): ?>
                                <a href="#" class="list-group-item list-group-item-action recipient-item" data-type="user" data-id="<?= (int)$u['id'] ?>">
                                    <i class="bi bi-person-circle me-2"></i>
                                    <?= htmlspecialchars($u['name']) ?>
                                    <?php if (!empty($u['role'])): ?>
                                        <div class="small text-muted"><?= htmlspecialchars($u['role']) ?></div>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong id="chatTitle">Select a recipient</strong>
                </div>
                <div class="card-body" style="height: 450px; overflow-y:auto;" id="chatMessages">
                    <div class="text-center text-muted mt-5">No conversation selected</div>
                </div>
                <div class="card-footer">
                    <form id="chatForm" class="d-flex gap-2">
                        <input type="hidden" name="receiver_type" id="receiver_type">
                        <input type="hidden" name="receiver_id" id="receiver_id">
                        <input type="text" name="body" id="chatInput" class="form-control" placeholder="Type a message..." autocomplete="off">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-send"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
  const list = document.getElementById('recipientList');
  const chat = document.getElementById('chatMessages');
  const form = document.getElementById('chatForm');
  const input = document.getElementById('chatInput');
  const rType = document.getElementById('receiver_type');
  const rId = document.getElementById('receiver_id');
  const title = document.getElementById('chatTitle');
  const sendBtn = form ? form.querySelector('button[type="submit"]') : null;

  function scrollBottom(){ chat.scrollTop = chat.scrollHeight; }

  function renderMessages(items){
    chat.innerHTML = '';
    if (!items || !items.length){ chat.innerHTML = '<div class="text-center text-muted mt-5">No messages yet</div>'; return; }
    const uid = <?= (int)($_SESSION['user_id'] ?? 0) ?>;
    items.forEach(m => {
      const mine = (m.sender_type === 'user' && parseInt(m.sender_id) === uid);
      const row = document.createElement('div');
      row.className = 'd-flex mb-2 ' + (mine ? 'justify-content-end' : 'justify-content-start');
      const bubble = document.createElement('div');
      bubble.className = 'p-2 rounded ' + (mine ? 'bg-primary text-white' : 'bg-light');
      bubble.style.maxWidth = '75%';
      bubble.innerText = m.body;
      row.appendChild(bubble);
      chat.appendChild(row);
    });
    scrollBottom();
  }

  async function loadThread(type, id, label){
    title.textContent = label || 'Conversation';
    rType.value = type; rId.value = id;
    if (sendBtn) sendBtn.disabled = false;
    chat.innerHTML = '<div class="text-center text-muted mt-5">Loading...</div>';
    try{
      const res = await fetch(`<?= BASE_URL ?>/messaging/thread?type=${encodeURIComponent(type)}&id=${encodeURIComponent(id)}`);
      const data = await res.json();
      if (data && data.success){ renderMessages(data.messages || []); }
      else { chat.innerHTML = '<div class="text-center text-danger mt-5">Failed to load</div>'; }
    }catch(e){ chat.innerHTML = '<div class="text-center text-danger mt-5">Failed to load</div>'; }
  }

  async function selectBroadcast(id, label){
    rType.value = 'broadcast';
    rId.value = id;
    title.textContent = (label || 'Broadcast') + ' (loading...)';
    chat.innerHTML = '<div class="text-center text-muted mt-5">Loading recipients...</div>';
    if (sendBtn) sendBtn.disabled = true;

    try {
      const res = await fetch(`<?= BASE_URL ?>/messaging/broadcast-meta?key=${encodeURIComponent(id)}`);
      const data = await res.json();
      if (!data || !data.success) {
        title.textContent = (label || 'Broadcast');
        chat.innerHTML = '<div class="text-center text-danger mt-5">Failed to load recipients.</div>';
        if (sendBtn) sendBtn.disabled = false;
        return;
      }

      const count = parseInt(data.count || 0);
      const recipients = Array.isArray(data.recipients) ? data.recipients : [];

      if (count <= 0) {
        title.textContent = (label || 'Broadcast') + ' - No tenants';
        chat.innerHTML = '<div class="text-center text-warning mt-5">No tenants in this broadcast group.</div>';
        if (sendBtn) sendBtn.disabled = true;
        return;
      }

      const previewCount = recipients.length;
      title.textContent = (label || 'Broadcast') + ' - ' + count + ' tenants';

      let html = '';
      html += '<div class="mb-3">';
      html += '<div class="small text-muted">This message will be sent to ' + count + ' tenants.</div>';
      html += '</div>';

      html += '<div class="border rounded p-2" style="max-height:320px; overflow:auto;">';
      html += '<div class="small text-muted mb-2">Recipients preview' + (previewCount < count ? (' (showing ' + previewCount + ' of ' + count + ')') : '') + ':</div>';
      html += '<div class="list-group list-group-flush">';
      recipients.forEach(function(r){
        const name = (r && r.name) ? String(r.name) : 'Tenant';
        const property = (r && r.property) ? String(r.property) : '-';
        const unit = (r && r.unit) ? String(r.unit) : '-';
        html += '<div class="list-group-item px-0">';
        html += '<div class="fw-semibold">' + name.replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</div>';
        html += '<div class="small text-muted">' + property.replace(/</g,'&lt;').replace(/>/g,'&gt;') + ' • ' + unit.replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</div>';
        html += '</div>';
      });
      html += '</div>';
      html += '</div>';

      html += '<div class="text-center text-muted mt-3">Broadcast selected. Type your message below and click send.</div>';
      chat.innerHTML = html;
      if (sendBtn) sendBtn.disabled = false;
    } catch (e) {
      title.textContent = (label || 'Broadcast');
      chat.innerHTML = '<div class="text-center text-danger mt-5">Failed to load recipients.</div>';
      if (sendBtn) sendBtn.disabled = false;
    }
  }

  list && list.addEventListener('click', function(e){
    const a = e.target.closest('.recipient-item');
    if (!a) return;
    e.preventDefault();
    const type = a.getAttribute('data-type');
    const id = a.getAttribute('data-id');
    const label = a.textContent.trim();
    if (type === 'broadcast') {
      selectBroadcast(id, label);
      return;
    }
    loadThread(type, id, label);
  });

  form && form.addEventListener('submit', async function(e){
    e.preventDefault();
    const type = rType.value, id = rId.value, body = input.value.trim();
    if (!type || !id || !body) return;
    const payload = new FormData(form);
    try{
      const res = await fetch('<?= BASE_URL ?>/messaging/send', { method:'POST', body: payload, headers: { 'X-CSRF-TOKEN': '<?= csrf_token() ?>' } });
      const data = await res.json();
      if (data && data.success){
        input.value='';
        if (type === 'broadcast') {
          const sent = (data && typeof data.sent !== 'undefined') ? data.sent : null;
          chat.innerHTML = '<div class="text-center text-success mt-5">Message sent' + (sent !== null ? (' to ' + sent + ' tenants') : '') + '.</div>';
        } else {
          loadThread(type, id, title.textContent);
        }
      }
    }catch(e){}
  });
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
