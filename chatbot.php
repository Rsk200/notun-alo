<?php
// ============================================
// chatbot.php — AI Chat Interface (Functional Sidebar Redesign)
// Notun Alo Recycling Platform
// ============================================
require_once 'includes/config.php';
ob_start();
requireLogin();

$user     = getCurrentUser($pdo);
$userName = e($user['name'] ?? 'User');
$userPts  = getUserPoints($pdo, (int)($user['id'] ?? 0));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>AI Assistant — Notun Alo</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        :root {
            --brand-dark: #0A2E1E;
            --brand-primary: #1D9E75;
            --brand-light: #E6F5EE;
            --brand-border: #6EE7B7;
            --text-primary: #111827;
            --text-secondary: #4B5563;
            --text-muted: #9CA3AF;
            --border: #E5E7EB;
            --bg-page: #F5F7F2;
            --bg-sidebar: #FFFFFF;
            --bg-chat: #F9FAFB;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-font-smoothing: antialiased; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-page); height: 100vh; overflow: hidden; display: flex; flex-direction: column; }

        .app-shell { flex: 1; display: flex; overflow: hidden; background: white; border-top: 1px solid var(--border); }
        
        /* SIDEBAR */
        .sidebar { width: 320px; border-right: 1px solid var(--border); display: flex; flex-direction: column; background: var(--bg-sidebar); }
        .sidebar-header { padding: 24px; border-bottom: 1px solid var(--border); }
        .btn-new-chat { width: 100%; height: 44px; background: var(--brand-primary); color: white; border: none; border-radius: 12px; font-weight: 600; font-size: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: 0.2s; }
        .btn-new-chat:hover { background: #065F46; transform: translateY(-1px); }
        
        .chat-list { flex: 1; overflow-y: auto; padding: 12px; }
        .chat-item { padding: 12px 16px; border-radius: 12px; cursor: pointer; transition: 0.2s; margin-bottom: 4px; display: flex; align-items: center; gap: 12px; border: 1px solid transparent; }
        .chat-item:hover { background: var(--bg-subtle); }
        .chat-item.active { background: var(--brand-light); border-color: var(--brand-border); }
        .chat-item.active .chat-icon { background: var(--brand-primary); color: white; }
        .chat-icon { width: 36px; height: 36px; background: var(--bg-subtle); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--brand-primary); font-size: 18px; }
        .chat-info { flex: 1; min-width: 0; }
        .chat-title { font-size: 14px; font-weight: 600; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .chat-meta { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

        /* MAIN CHAT AREA */
        .main-chat { flex: 1; display: flex; flex-direction: column; background: var(--bg-chat); position: relative; }
        .chat-header { height: 72px; background: white; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; padding: 0 32px; }
        .ai-info { display: flex; align-items: center; gap: 12px; }
        .ai-avatar { width: 40px; height: 40px; background: var(--brand-light); border: 1.5px solid var(--brand-border); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--brand-primary); }
        .status-pill { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--brand-primary); font-weight: 600; background: var(--brand-light); padding: 4px 12px; border-radius: 99px; }
        .status-dot { width: 6px; height: 6px; background: var(--brand-primary); border-radius: 50%; }

        .message-viewport { flex: 1; overflow-y: auto; padding: 40px; display: flex; flex-direction: column; gap: 24px; max-width: 1000px; margin: 0 auto; width: 100%; }
        .message-viewport::-webkit-scrollbar { width: 6px; }
        .message-viewport::-webkit-scrollbar-thumb { background: #D1D5DB; border-radius: 10px; }

        /* BUBBLES */
        .ai-message { display: flex; gap: 16px; max-width: 80%; animation: slideUp 0.3s ease; }
        .msg-avatar { width: 32px; height: 32px; background: var(--brand-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--brand-primary); flex-shrink: 0; font-size: 14px; }
        .ai-bubble { background: white; border: 1px solid var(--border); border-radius: 4px 16px 16px 16px; padding: 16px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); position: relative; }
        
        .user-message { align-self: flex-end; max-width: 80%; animation: slideUp 0.3s ease; }
        .user-bubble { background: var(--brand-primary); color: white; border-radius: 16px 4px 16px 16px; padding: 16px 20px; box-shadow: 0 4px 12px rgba(29,158,117,0.15); }

        @keyframes slideUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .msg-text { font-size: 15px; line-height: 1.6; }
        .msg-time { font-size: 10px; color: var(--text-muted); margin-top: 8px; }
        .user-bubble .msg-time { color: rgba(255,255,255,0.7); text-align: right; }

        .quick-replies { margin-left: 48px; display: flex; flex-wrap: wrap; gap: 8px; margin-top: -12px; }
        .chip { background: white; border: 1px solid var(--border); color: var(--text-secondary); padding: 8px 16px; border-radius: 99px; font-size: 13px; font-weight: 500; cursor: pointer; transition: 0.2s; }
        .chip:hover { border-color: var(--brand-primary); color: var(--brand-primary); background: var(--brand-light); }

        .input-bar { height: 88px; background: white; border-top: 1px solid var(--border); display: flex; align-items: center; padding: 0 32px; }
        .input-container { flex: 1; max-width: 1000px; margin: 0 auto; display: flex; align-items: center; gap: 16px; }
        .text-input-wrap { flex: 1; height: 52px; background: var(--bg-chat); border: 1px solid var(--border); border-radius: 16px; display: flex; align-items: center; padding: 0 20px; }
        #chatInput { flex: 1; border: none; background: transparent; outline: none; font-size: 15px; color: var(--text-primary); }
        .btn-send { width: 44px; height: 44px; background: var(--brand-primary); border: none; border-radius: 12px; color: white; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .btn-send:hover { background: #065F46; transform: scale(1.05); }
        .btn-send:disabled { background: #E5E7EB; cursor: not-allowed; transform: none; }

        .empty-state { text-align: center; margin: auto; max-width: 600px; padding: 40px; animation: fadeIn 0.5s ease; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .suggest-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-top: 32px; }
        .suggest-card { background: white; border: 1px solid var(--border); border-radius: 16px; padding: 20px; text-align: left; cursor: pointer; transition: 0.2s; }
        .suggest-card:hover { border-color: var(--brand-primary); transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.05); }
        .suggest-icon { font-size: 24px; color: var(--brand-primary); margin-bottom: 12px; }
        .suggest-txt { font-size: 15px; font-weight: 700; color: var(--text-primary); }
        .suggest-sub { font-size: 13px; color: var(--text-muted); margin-top: 4px; }

        @media (max-width: 800px) { .sidebar { display: none; } .message-viewport { padding: 20px; } .chat-header { padding: 0 20px; } .input-bar { padding: 0 20px; } .suggest-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="app-shell">
        <aside class="sidebar">
            <div class="sidebar-header">
                <button class="btn-new-chat" id="btnNewChat">
                    <i class="ti ti-plus"></i> New Conversation
                </button>
            </div>
            <div class="chat-list" id="chatList">
                <div class="chat-item active" data-id="main">
                    <div class="chat-icon"><i class="ti ti-message-code"></i></div>
                    <div class="chat-info">
                        <div class="chat-title">General Assistant</div>
                        <div class="chat-meta">Online · Active now</div>
                    </div>
                </div>
                <div class="chat-item" data-id="points">
                    <div class="chat-icon"><i class="ti ti-history"></i></div>
                    <div class="chat-info">
                        <div class="chat-title">Point Calculation Help</div>
                        <div class="chat-meta">2 days ago</div>
                    </div>
                </div>
                <div class="chat-item" data-id="pickup">
                    <div class="chat-icon"><i class="ti ti-truck"></i></div>
                    <div class="chat-info">
                        <div class="chat-title">Pickup Schedule Update</div>
                        <div class="chat-meta">1 week ago</div>
                    </div>
                </div>
            </div>
        </aside>

        <main class="main-chat">
            <header class="chat-header">
                <div class="ai-info">
                    <div class="ai-avatar"><i class="ti ti-robot"></i></div>
                    <div>
                        <div style="font-weight:700; color:var(--text-primary);" id="chatTitle">Notun Alo AI</div>
                        <div style="font-size:11px; color:var(--text-muted);" id="chatSubtitle">Secure · Bilingual Assistant</div>
                    </div>
                </div>
                <div class="status-pill">
                    <div class="status-dot"></div>
                    <span>Online</span>
                </div>
            </header>

            <div class="message-viewport" id="messageArea">
                <!-- Content Injected -->
            </div>

            <footer class="input-bar">
                <div class="input-container">
                    <div class="text-input-wrap">
                        <input type="text" id="chatInput" placeholder="Message Notun Alo AI..." autocomplete="off">
                    </div>
                    <button class="btn-send" id="sendBtn" disabled><i class="ti ti-send"></i></button>
                </div>
            </footer>
        </main>
    </div>

    <script>
        const messageArea = document.getElementById('messageArea');
        const chatInput = document.getElementById('chatInput');
        const sendBtn = document.getElementById('sendBtn');
        const chatList = document.getElementById('chatList');
        const btnNewChat = document.getElementById('btnNewChat');
        const chatTitle = document.getElementById('chatTitle');

        const userName = "<?= $userName ?>";
        const userPts = "<?= number_format($userPts) ?>";

        // MOCK CONVERSATION DATA
        const conversations = {
            main: {
                title: "General Assistant",
                messages: [
                    { type: 'ai', text: `Hello ${userName}! How can I help you today?`, quickReplies: ['Schedule a Pickup', 'Check Points'] }
                ]
            },
            points: {
                title: "Point Calculation Help",
                messages: [
                    { type: 'user', text: "How are my points calculated?" },
                    { type: 'ai', text: "Points are calculated based on material weight: Paper is 5 pts/kg, Plastic is 8 pts/kg, and Metal is 12 pts/kg." }
                ]
            },
            pickup: {
                title: "Pickup Schedule Update",
                messages: [
                    { type: 'user', text: "Is my pickup confirmed for tomorrow?" },
                    { type: 'ai', text: "Yes! Your pickup (Request #842) is scheduled for tomorrow between 10 AM and 2 PM. Our agent will call you 30 minutes before arrival." }
                ]
            }
        };

        let currentConvId = 'main';

        function loadConversation(id) {
            currentConvId = id;
            messageArea.innerHTML = '';
            
            // UI Update
            document.querySelectorAll('.chat-item').forEach(i => i.classList.remove('active'));
            const activeItem = document.querySelector(`.chat-item[data-id="${id}"]`);
            if (activeItem) {
                activeItem.classList.add('active');
                chatTitle.innerText = activeItem.querySelector('.chat-title').innerText;
            }

            if (id === 'new') {
                renderEmptyState();
                return;
            }

            const conv = conversations[id];
            if (conv) {
                conv.messages.forEach(msg => {
                    if (msg.type === 'user') appendUserMessage(msg.text, false);
                    else appendAiMessage(msg, false);
                });
            }
            scrollToBottom();
        }

        function renderEmptyState() {
            messageArea.innerHTML = `
                <div class="empty-state">
                    <div style="width:64px; height:64px; background:var(--brand-light); border-radius:20px; display:flex; align-items:center; justify-content:center; color:var(--brand-primary); font-size:32px; margin:0 auto 24px;">
                        <i class="ti ti-leaf"></i>
                    </div>
                    <h2 style="font-size:24px; font-weight:800; color:var(--text-primary);">New Conversation</h2>
                    <p style="font-size:15px; color:var(--text-secondary); margin-top:8px;">Ask me anything about your recycling journey.</p>
                    
                    <div class="suggest-grid">
                        <div class="suggest-card" onclick="handleSuggestion('Schedule a Pickup')">
                            <i class="ti ti-truck suggest-icon"></i>
                            <div class="suggest-txt">Schedule a Pickup</div>
                        </div>
                        <div class="suggest-card" onclick="handleSuggestion('Check Points')">
                            <i class="ti ti-star suggest-icon"></i>
                            <div class="suggest-txt">Check Points</div>
                        </div>
                        <div class="suggest-card" onclick="handleSuggestion('Impact Stats')">
                            <i class="ti ti-chart-bar suggest-icon"></i>
                            <div class="suggest-txt">Impact Stats</div>
                        </div>
                        <div class="suggest-card" onclick="handleSuggestion('Recycling Guide')">
                            <i class="ti ti-book suggest-icon"></i>
                            <div class="suggest-txt">Recycling Guide</div>
                        </div>
                    </div>
                </div>
            `;
        }

        function appendUserMessage(text, save = true) {
            const html = `
                <div class="user-message">
                    <div class="user-bubble">
                        <div class="msg-text">${text}</div>
                        <div class="msg-time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                    </div>
                </div>
            `;
            messageArea.insertAdjacentHTML('beforeend', html);
            if (save && conversations[currentConvId]) {
                conversations[currentConvId].messages.push({ type: 'user', text });
            }
            scrollToBottom();
        }

        function appendAiMessage(msg, save = true) {
            let content = `<div class="msg-text">${msg.text}</div>`;
            if (msg.bullets) content += `<ul style="margin-top:12px; padding-left:18px;">${msg.bullets.map(b => `<li style="margin-bottom:6px;">${b}</li>`).join('')}</ul>`;
            
            const html = `
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <div class="ai-message">
                        <div class="msg-avatar"><i class="ti ti-robot"></i></div>
                        <div class="ai-bubble">${content} <div class="msg-time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div></div>
                    </div>
                    ${msg.quickReplies ? `
                        <div class="quick-replies">
                            ${msg.quickReplies.map(r => `<div class="chip" onclick="handleQuickReply('${r}')">${r}</div>`).join('')}
                        </div>
                    ` : ''}
                </div>
            `;
            messageArea.insertAdjacentHTML('beforeend', html);
            if (save && conversations[currentConvId]) {
                conversations[currentConvId].messages.push({ type: 'ai', text: msg.text, bullets: msg.bullets, quickReplies: msg.quickReplies });
            }
            scrollToBottom();
        }

        function sendMessage() {
            const text = chatInput.value.trim();
            if (!text) return;

            if (currentConvId === 'new') {
                // Create a new real conversation
                const id = 'conv_' + Date.now();
                conversations[id] = { title: text, messages: [] };
                
                // Add to sidebar
                const item = document.createElement('div');
                item.className = 'chat-item';
                item.dataset.id = id;
                item.innerHTML = `
                    <div class="chat-icon"><i class="ti ti-message"></i></div>
                    <div class="chat-info">
                        <div class="chat-title">${text}</div>
                        <div class="chat-meta">Just now</div>
                    </div>
                `;
                item.onclick = () => loadConversation(id);
                chatList.prepend(item);
                
                loadConversation(id);
            }

            appendUserMessage(text);
            chatInput.value = '';
            sendBtn.disabled = true;

            setTimeout(() => {
                let reply = "Processing your request...";
                if (text.toLowerCase().includes('pickup')) reply = "I've initiated a pickup request for you. Please confirm details in the dashboard.";
                else if (text.toLowerCase().includes('point')) reply = `You have **${userPts}** reward points available.`;
                
                appendAiMessage({ text: reply });
            }, 1000);
        }

        function handleQuickReply(text) { chatInput.value = text; sendMessage(); }
        function handleSuggestion(text) { messageArea.innerHTML = ''; handleQuickReply(text); }
        function scrollToBottom() { messageArea.scrollTop = messageArea.scrollHeight; }

        // Sidebar Actions
        document.querySelectorAll('.chat-item').forEach(item => {
            item.onclick = () => loadConversation(item.dataset.id);
        });

        btnNewChat.onclick = () => loadConversation('new');

        chatInput.oninput = () => { sendBtn.disabled = !chatInput.value.trim(); };
        chatInput.onkeydown = (e) => { if (e.key === 'Enter' && !sendBtn.disabled) sendMessage(); };
        sendBtn.onclick = sendMessage;

        // Initialize
        loadConversation('main');
    </script>
</body>
</html>
