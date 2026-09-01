<style>
.assistente-ia-shell{display:flex!important;flex-direction:row!important;align-items:stretch;height:calc(100vh - 150px);min-height:520px;overflow:hidden}
.assistente-ia-sidebar{flex:0 0 280px;width:280px;max-width:280px;border-right:1px solid #e5e7eb;background:#f8fafc;display:flex;flex-direction:column;min-height:0}
.assistente-ia-sidebar-head{padding:16px;border-bottom:1px solid #e5e7eb}
.assistente-ia-conversations{overflow-y:auto;overflow-x:hidden;padding:8px;flex:1;min-height:0}
.assistente-ia-conversation{display:flex;align-items:center;gap:6px;width:100%;border:0;background:transparent;text-align:left;border-radius:8px;padding:11px 8px 11px 12px;margin-bottom:4px;color:#334155;cursor:pointer}
.assistente-ia-conversation:hover,.assistente-ia-conversation.active{background:#e2e8f0}
.assistente-ia-conversation-title{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-weight:500;flex:1;min-width:0}
.assistente-ia-conversation-date{display:block;color:#94a3b8;font-size:11px;margin-top:4px}
.assistente-ia-conversation-delete{border:0;background:transparent;color:#94a3b8;padding:4px;line-height:1;opacity:.5;cursor:pointer}
.assistente-ia-conversation-delete:hover{color:#dc2626;opacity:1}
.assistente-ia-main{display:flex;flex:1;min-width:0;flex-direction:column;background:#fff}
.assistente-ia-main-head{padding:16px 22px;border-bottom:1px solid #e5e7eb}
.assistente-ia-messages{flex:1;overflow:auto;padding:24px}
.assistente-ia-message{max-width:820px;margin:0 auto 18px;line-height:1.55;white-space:pre-wrap}
.assistente-ia-message-user{background:#f1f5f9;border-radius:12px;padding:12px 15px}
.assistente-ia-message-assistant{padding:4px 15px}
.assistente-ia-message-label{font-size:12px;font-weight:600;color:#64748b;margin-bottom:4px}
.assistente-ia-form{max-width:850px;width:100%;margin:0 auto;padding:14px 24px 20px}
@media(max-width:768px){.assistente-ia-sidebar{flex-basis:220px;width:220px;max-width:220px}.assistente-ia-messages{padding:16px}.assistente-ia-form{padding:12px}}
</style>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card assistente-ia-shell">
        <aside class="assistente-ia-sidebar">
            <div class="assistente-ia-sidebar-head">
                <button type="button" id="assistente-ia-new" class="btn btn-primary w-100" onclick="return false;"><i data-feather="plus" class="icon-16"></i> Nova conversa</button>
            </div>
            <div id="assistente-ia-conversations" class="assistente-ia-conversations"></div>
        </aside>
        <main class="assistente-ia-main">
            <div class="assistente-ia-main-head"><h1 class="h4 mb-0"><?php echo app_lang('assistente_ia'); ?></h1></div>
            <div id="assistente-ia-messages" class="assistente-ia-messages">
                <div class="assistente-ia-message assistente-ia-message-assistant"><div class="assistente-ia-message-label">Assistente</div>Olá! Como posso ajudar você no RISE CRM?</div>
            </div>
            <form id="assistente-ia-form" class="assistente-ia-form">
                <textarea id="assistente-ia-input" class="form-control" rows="3" placeholder="Pergunte sobre o RISE CRM..." autocomplete="off"></textarea>
                <button class="btn btn-primary mt-2" type="submit"><i data-feather="send" class="icon-16"></i> Enviar</button>
            </form>
        </main>
    </div>
</div>

<script>
(function () {
    const list = document.getElementById('assistente-ia-conversations');
    const messages = document.getElementById('assistente-ia-messages');
    const input = document.getElementById('assistente-ia-input');
    const conversationsUrl = '<?php echo get_uri('assistente-ia/conversas'); ?>';
    const chatUrl = '<?php echo get_uri('assistente-ia/chat'); ?>';
    let conversationId = 0;

    function escapeHtml(value) { return $('<div>').text(value || '').html(); }
    function renderMessages(items) {
        messages.innerHTML = '';
        if (!items || !items.length) {
            messages.innerHTML = '<div class="assistente-ia-message assistente-ia-message-assistant"><div class="assistente-ia-message-label">Assistente</div>Olá! Como posso ajudar você no RISE CRM?</div>';
            return;
        }
        items.forEach(function (item) {
            const role = item.role === 'user' ? 'Você' : 'Assistente';
            const css = item.role === 'user' ? 'assistente-ia-message-user' : 'assistente-ia-message-assistant';
            messages.insertAdjacentHTML('beforeend', '<div class="assistente-ia-message ' + css + '"><div class="assistente-ia-message-label">' + role + '</div>' + escapeHtml(item.content) + '</div>');
        });
        messages.scrollTop = messages.scrollHeight;
    }
    function renderConversationList(items) {
        list.innerHTML = '';
        (items || []).forEach(function (item) {
            const active = Number(item.id) === Number(conversationId) ? ' active' : '';
            list.insertAdjacentHTML('beforeend', '<div class="assistente-ia-conversation' + active + '" data-id="' + Number(item.id) + '"><button type="button" class="border-0 bg-transparent text-start p-0 flex-grow-1" data-open-id="' + Number(item.id) + '"><span class="assistente-ia-conversation-title">' + escapeHtml(item.title || 'Nova conversa') + '</span><span class="assistente-ia-conversation-date">' + escapeHtml(item.updated_at || item.created_at || '') + '</span></button><button type="button" class="assistente-ia-conversation-delete" title="Excluir conversa" data-delete-id="' + Number(item.id) + '"><i data-feather="trash-2" class="icon-14"></i></button></div>');
        });
    }
    async function loadConversations() {
        const response = await fetch(conversationsUrl);
        if (response.ok) renderConversationList(await response.json());
    }
    async function openConversation(id) {
        const response = await fetch(conversationsUrl + '/' + id);
        if (!response.ok) return;
        const data = await response.json();
        conversationId = Number(data.id);
        renderMessages(data.messages);
        await loadConversations();
    }
    list.addEventListener('click', function (event) {
        const deleteButton = event.target.closest('[data-delete-id]');
        if (deleteButton) {
            event.preventDefault();
            event.stopPropagation();
            const id = Number(deleteButton.dataset.deleteId);
            if (!window.confirm('Excluir esta conversa?')) return;
            fetch(conversationsUrl + '/' + id + '/delete', {method: 'POST'}).then(function () {
                if (conversationId === id) { conversationId = 0; renderMessages([]); }
                loadConversations();
            });
            return;
        }
        const button = event.target.closest('[data-open-id]');
        if (button) openConversation(Number(button.dataset.openId));
    });
    document.getElementById('assistente-ia-new').addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        conversationId = 0;
        renderMessages([]);
        document.querySelectorAll('.assistente-ia-conversation').forEach(function (item) { item.classList.remove('active'); });
        input.focus();
        return false;
    }, true);
    document.getElementById('assistente-ia-form').addEventListener('submit', async function (event) {
        event.preventDefault();
        const message = input.value.trim();
        if (!message) return;
        input.value = '';
        messages.insertAdjacentHTML('beforeend', '<div class="assistente-ia-message assistente-ia-message-user"><div class="assistente-ia-message-label">Você</div>' + escapeHtml(message) + '</div>');
        messages.insertAdjacentHTML('beforeend', '<div id="assistente-ia-loading" class="assistente-ia-message assistente-ia-message-assistant"><div class="assistente-ia-message-label">Assistente</div>Consultando o RISE CRM...</div>');
        messages.scrollTop = messages.scrollHeight;
        const response = await fetch(chatUrl, {method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({message: message, conversation_id: conversationId})});
        const data = await response.json();
        const loading = document.getElementById('assistente-ia-loading');
        if (loading) loading.remove();
        if (data.conversation_id) conversationId = Number(data.conversation_id);
        messages.insertAdjacentHTML('beforeend', '<div class="assistente-ia-message assistente-ia-message-assistant"><div class="assistente-ia-message-label">Assistente</div>' + escapeHtml(data.answer || data.error || 'Não foi possível obter uma resposta.') + '</div>');
        messages.scrollTop = messages.scrollHeight;
        await loadConversations();
    });
    loadConversations();
})();
</script>
