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
    chat.innerHTML = '<div class="text-center text-muted mt-5">Loading...</div>';
    try{
      const res = await fetch(`<?= BASE_URL ?>/messaging/thread?type=${encodeURIComponent(type)}&id=${encodeURIComponent(id)}`);
      const data = await res.json();
      if (data && data.success){ renderMessages(data.messages || []); }
      else { chat.innerHTML = '<div class="text-center text-danger mt-5">Failed to load</div>'; }
    }catch(e){ chat.innerHTML = '<div class="text-center text-danger mt-5">Failed to load</div>'; }
  }

  list && list.addEventListener('click', function(e){
    const a = e.target.closest('.recipient-item');
    if (!a) return;
    e.preventDefault();
    const type = a.getAttribute('data-type');
    const id = a.getAttribute('data-id');
    const label = a.textContent.trim();
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
      if (data && data.success){ input.value=''; loadThread(type, id, title.textContent); }
    }catch(e){}
  });
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
