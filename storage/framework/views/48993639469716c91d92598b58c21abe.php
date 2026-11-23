<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Board - Installation Wizard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .installer-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 700px;
            overflow: hidden;
        }
        .installer-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 40px;
            text-align: center;
        }
        .installer-header h1 { font-size: 24px; margin-bottom: 8px; }
        .installer-header p { opacity: 0.9; font-size: 14px; }
        .step-indicators {
            display: flex;
            justify-content: center;
            padding: 20px 40px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            gap: 8px;
        }
        .step-dot {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #dee2e6;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
            cursor: pointer;
        }
        .step-dot.active {
            background: #667eea;
            color: white;
            transform: scale(1.1);
            box-shadow: 0 2px 8px rgba(102,126,234,0.4);
        }
        .step-dot.completed {
            background: #28a745;
            color: white;
        }
        .step-dot.completed::after { content: '✓'; }
        .step-dot.completed span { display: none; }
        .installer-body { padding: 40px; min-height: 400px; }
        .step-content { display: none; }
        .step-content.active { display: block; }
        .step-content h2 { font-size: 20px; color: #333; margin-bottom: 8px; }
        .step-content .subtitle { color: #6c757d; font-size: 14px; margin-bottom: 24px; line-height: 1.5; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; color: #495057; margin-bottom: 6px; font-size: 14px; }
        .form-group .hint { font-size: 12px; color: #6c757d; margin-bottom: 8px; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #667eea;
            padding: 16px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 24px;
            font-size: 13px;
            color: #004085;
            line-height: 1.6;
        }
        .checklist { list-style: none; padding: 0; }
        .checklist li {
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
        }
        .checklist li:last-child { border-bottom: none; }
        .checklist .status {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            flex-shrink: 0;
        }
        .checklist .status.pending { background: #e9ecef; color: #6c757d; }
        .checklist .status.success { background: #d4edda; color: #28a745; }
        .checklist .status.error { background: #f8d7da; color: #dc3545; }
        .installer-footer { display: flex; justify-content: space-between; padding: 20px 40px 30px; }
        .btn {
            padding: 12px 28px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-secondary { background: #e9ecef; color: #495057; }
        .btn-secondary:hover { background: #dee2e6; }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(102,126,234,0.4); }
        .btn-primary:disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        .spinner {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .progress-bar { height: 4px; background: #e9ecef; border-radius: 2px; margin-top: 12px; overflow: hidden; }
        .progress-bar .fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: width 0.5s ease;
            border-radius: 2px;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-top: 16px;
            font-size: 13px;
            display: none;
        }
        .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .final-credentials {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        .final-credentials h3 { font-size: 14px; color: #333; margin-bottom: 12px; }
        .cred-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 13px;
            border-bottom: 1px solid #e9ecef;
        }
        .cred-row:last-child { border-bottom: none; }
        .cred-row .label { color: #6c757d; }
        .cred-row .value { color: #333; font-weight: 600; font-family: monospace; }
        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; }
            .installer-body { padding: 24px; }
            .installer-header { padding: 24px; }
        }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="installer-header">
            <h1>🚀 Support Board Installation</h1>
            <p>Step-by-step setup wizard for your support platform</p>
        </div>

        <div class="step-indicators" id="stepIndicators"></div>

        <form id="installForm" onsubmit="return false;">
            <div class="installer-body">
                <div class="step-content active" id="step0">
                    <h2>Welcome!</h2>
                    <p class="subtitle">This wizard will guide you through setting up Support Board PHP.</p>
                    <div class="info-box">
                        <strong>What this installer does:</strong><br>
                        1. Configures your MySQL database connection<br>
                        2. Creates the database schema (migrations)<br>
                        3. Seeds demo data (users, departments, articles)<br>
                        4. Sets up vLLM / LLM integration for the chatbot<br>
                        5. Configures the web crawler
                    </div>
                    <h3 style="margin-bottom:12px; font-size:16px;">Requirements</h3>
                    <ul class="checklist" id="requirementsCheck">
                        <li><span class="status pending">?</span> PHP 8.1+ installed</li>
                        <li><span class="status pending">?</span> MySQL / MariaDB running</li>
                        <li><span class="status pending">?</span> Composer installed</li>
                        <li><span class="status pending">?</span> Laravel dependencies present</li>
                    </ul>
                </div>
                <div class="step-content" id="step1">
                    <h2>Database Configuration</h2>
                    <p class="subtitle">Enter your MySQL connection details.</p>
                    <div class="info-box">
                        You'll need your MySQL host, port, database name, username, and password.
                        If the database doesn't exist, the installer will try to create it.
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Host</label>
                            <input type="text" id="dbHost" value="127.0.0.1">
                            <div class="hint">Usually 127.0.0.1 or localhost</div>
                        </div>
                        <div class="form-group">
                            <label>Port</label>
                            <input type="number" id="dbPort" value="3306">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Database Name</label>
                            <input type="text" id="dbName" value="supportboard">
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" id="dbUser" value="root">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" id="dbPass">
                    </div>
                    <div id="dbTestAlert" class="alert"></div>
                </div>
                <div class="step-content" id="step2">
                    <h2>Install & Configure</h2>
                    <p class="subtitle">Run migrations, seed data, and configure integrations.</p>
                    <div class="info-box">
                        This step will:<br>
                        • Generate application keys<br>
                        • Run all database migrations<br>
                        • Seed demo data (admin, agent, user accounts)<br>
                        • Configure vLLM for the chatbot AI<br>
                        • (Optional) Set up WhatsApp support for live chats
                    </div>
                    <h3 style="font-size:16px; margin: 16px 0 12px;">🤖 AI / LLM Configuration</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>LLM Endpoint</label>
                            <input type="url" id="llmEndpoint" value="http://localhost:8000/v1">
                            <div class="hint">vLLM, Ollama, or OpenAI compatible endpoint</div>
                        </div>
                        <div class="form-group">
                            <label>Model</label>
                            <input type="text" id="llmModel" value="gpt-3.5-turbo">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>API Key <span style="font-weight:normal; color:#6c757d;">(optional for local vLLM/Ollama)</span></label>
                        <input type="password" id="llmApiKey" placeholder="sk-... or leave blank for local">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Temperature (0.0–2.0)</label>
                            <input type="number" id="llmTemp" value="0.7" min="0" max="2" step="0.1">
                        </div>
                        <div class="form-group">
                            <label>Max Tokens</label>
                            <input type="number" id="llmMaxTokens" value="1024" min="1">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>System Prompt</label>
                        <textarea id="llmPrompt" rows="3">You are a helpful customer support assistant. Provide concise, accurate, and friendly responses. If you don't know something, suggest contacting a human agent.</textarea>
                    </div>

                    <h3 style="font-size:16px; margin: 24px 0 12px;">📱 WhatsApp Support <span style="font-weight:normal; color:#6c757d; font-size:13px;">(optional)</span></h3>
                    <div class="info-box" style="background:#f0fdf4; border-left-color:#22c55e; color:#166534;">
                        <strong>WhatsApp Business API</strong> allows customers to reach your support team via WhatsApp.
                        Get your API key from <a href="https://developers.facebook.com" target="_blank">Facebook Developers</a>.
                        Leave blank to skip WhatsApp setup.
                    </div>
                    <div class="form-group">
                        <label>WhatsApp API Key</label>
                        <input type="password" id="whatsappApiKey" placeholder="EAA placed here... or leave blank to skip">
                        <div class="hint">Facebook access token with WhatsApp permissions</div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone Number ID</label>
                            <input type="text" id="whatsappPhoneId" placeholder="e.g., 103847562891234">
                            <div class="hint">Your WhatsApp Business phone number ID from Facebook</div>
                        </div>
                        <div class="form-group">
                            <label>Message Template ID</label>
                            <input type="text" id="whatsappTemplateId" placeholder="e.g., hello_world_123456789">
                            <div class="hint">Pre-approved template for outbound messages</div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Auto-Reply Message</label>
                            <input type="text" id="whatsappAutoReply" placeholder="Thanks for contacting us! An agent will reply soon.">
                            <div class="hint">Sent automatically when a customer messages</div>
                        </div>
                        <div class="form-group">
                            <label>Webhook Verify Token</label>
                            <input type="text" id="whatsappVerifyToken" value="supportboard_verify_token">
                            <div class="hint">Custom token to verify webhook requests from Facebook</div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top:20px;">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="checkbox" id="seedData" checked>
                            Seed demo data (users, departments, articles, chatbot Q&A)
                        </label>
                    </div>
                    <div id="installProgress" class="alert info" style="display:none;">
                        <div id="installStatus"></div>
                        <div class="progress-bar"><div class="fill" id="progressFill" style="width:0%"></div></div>
                    </div>
                </div>
                <div class="step-content" id="step3">
                    <h2 id="finalTitle">Installation Status</h2>
                    <p class="subtitle" id="finalSubtitle">Checking installation results...</p>
                    <ul class="checklist" id="resultList"></ul>
                    <div id="finalCredentials" class="final-credentials" style="display:none;">
                        <h3>🔑 Demo Account Credentials</h3>
                        <div class="cred-row"><span class="label">Admin</span><span class="value">admin@supportboard.local / admin123</span></div>
                        <div class="cred-row"><span class="label">Agent</span><span class="value">agent@supportboard.local / agent123</span></div>
                        <div class="cred-row"><span class="label">User</span><span class="value">user@example.com / user123</span></div>
                    </div>
                </div>
            </div>

            <div class="installer-footer">
                <button type="button" class="btn btn-secondary" id="btnPrev" onclick="prevStep()" style="visibility:hidden;">← Back</button>
                <button type="button" class="btn btn-primary" id="btnNext" onclick="nextStep()">Next →</button>
            </div>
        </form>
    </div>

<script>
const STEPS = [{name:'Welcome'},{name:'Database'},{name:'Install'},{name:'Done'}];
let currentStep = 0;

function init() {
    const indicators = document.getElementById('stepIndicators');
    STEPS.forEach((s, i) => {
        const dot = document.createElement('div');
        dot.className = 'step-dot' + (i === 0 ? ' active' : '');
        dot.innerHTML = '<span>' + (i + 1) + '</span>';
        dot.onclick = () => goToStep(i);
        indicators.appendChild(dot);
    });
    checkRequirements();
}

function checkRequirements() {
    const checks = document.querySelectorAll('#requirementsCheck li');
    fetch('/installer/check').then(r => r.json()).then(data => {
        checks[0].querySelector('.status').className = 'status ' + (data.php ? 'success' : 'error');
        checks[0].querySelector('.status').innerHTML = data.php ? '✓' : '✗';
        checks[1].querySelector('.status').className = 'status ' + (data.mysql === true ? 'success' : 'pending');
        checks[1].querySelector('.status').innerHTML = data.mysql === true ? '✓' : '?';
        checks[2].querySelector('.status').className = 'status ' + (data.composer ? 'success' : 'error');
        checks[2].querySelector('.status').innerHTML = data.composer ? '✓' : '✗';
        checks[3].querySelector('.status').className = 'status ' + (data.laravel ? 'success' : 'error');
        checks[3].querySelector('.status').innerHTML = data.laravel ? '✓' : '✗';
    }).catch(() => {
        checks.forEach(c => { c.querySelector('.status').className = 'status pending'; c.querySelector('.status').innerHTML = '?'; });
    });
}

function goToStep(step) { if (step <= currentStep + 1) { currentStep = step; updateUI(); } }
function updateUI() {
    document.querySelectorAll('.step-content').forEach((el, i) => el.classList.toggle('active', i === currentStep));
    document.querySelectorAll('.step-dot').forEach((dot, i) => {
        dot.classList.remove('active', 'completed');
        if (i === currentStep) dot.classList.add('active');
        else if (i < currentStep) dot.classList.add('completed');
    });
    document.getElementById('btnPrev').style.visibility = currentStep === 0 ? 'hidden' : 'visible';
    const btn = document.getElementById('btnNext');
    if (currentStep === STEPS.length - 2) { btn.innerHTML = '🚀 Install'; }
    else if (currentStep === STEPS.length - 1) { btn.innerHTML = '🎉 Finish'; btn.className = 'btn btn-success'; }
    else { btn.innerHTML = 'Next →'; btn.className = 'btn btn-primary'; }
}

function nextStep() {
    if (currentStep === 0) { currentStep = 1; updateUI(); }
    else if (currentStep === 1) testDatabase();
    else if (currentStep === 2) runInstallation();
    else if (currentStep === 3) window.location.href = '/';
}

function prevStep() { if (currentStep > 0) { currentStep--; updateUI(); } }

function showAlert(id, type, msg) {
    const el = document.getElementById(id);
    el.className = 'alert ' + type; el.innerHTML = msg; el.style.display = 'block';
}

function testDatabase() {
    const data = { db_host: document.getElementById('dbHost').value, db_port: document.getElementById('dbPort').value, db_name: document.getElementById('dbName').value, db_user: document.getElementById('dbUser').value, db_password: document.getElementById('dbPass').value };
    const btn = document.getElementById('btnNext'); btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Testing...';
    fetch('/installer/test-db', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) }).then(r => r.json()).then(result => {
        if (result.success) { showAlert('dbTestAlert', 'success', '✓ Database connection successful! ' + (result.created ? 'Database created.' : 'Database exists.')); currentStep = 2; updateUI(); }
        else { showAlert('dbTestAlert', 'error', '✗ ' + (result.message || 'Connection failed.')); }
        btn.disabled = false; btn.innerHTML = 'Next →';
    }).catch(err => { showAlert('dbTestAlert', 'error', '✗ Request failed: ' + err.message); btn.disabled = false; btn.innerHTML = 'Next →'; });
}

function runInstallation() {
    const data = {
        llm_endpoint: document.getElementById('llmEndpoint').value,
        llm_model: document.getElementById('llmModel').value,
        llm_api_key: document.getElementById('llmApiKey').value,
        llm_temperature: document.getElementById('llmTemp').value,
        llm_max_tokens: document.getElementById('llmMaxTokens').value,
        llm_system_prompt: document.getElementById('llmPrompt').value,
        seed: document.getElementById('seedData').checked,
        whatsapp_api_key: document.getElementById('whatsappApiKey').value,
        whatsapp_phone_number_id: document.getElementById('whatsappPhoneId').value,
        whatsapp_message_template_id: document.getElementById('whatsappTemplateId').value,
        whatsapp_auto_reply: document.getElementById('whatsappAutoReply').value,
        whatsapp_verify_token: document.getElementById('whatsappVerifyToken').value
    };
    const alert = document.getElementById('installProgress'); alert.style.display = 'block';
    const status = document.getElementById('installStatus'); const fill = document.getElementById('progressFill');
    const btn = document.getElementById('btnNext'); btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Installing...';
    const steps = [{name:'Generating keys...',p:10},{name:'Running migrations...',p:30},{name:'Seeding data...',p:50},{name:'Configuring AI...',p:70},{name:'Configuring WhatsApp...',p:85},{name:'Finalizing...',p:100}];
    let si = 0; const iv = setInterval(() => { if (si < steps.length) { status.textContent = steps[si].name; fill.style.width = steps[si].p+'%'; si++; } }, 1500);
    fetch('/installer/run', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) }).then(r => r.json()).then(result => {
        clearInterval(iv); fill.style.width = '100%'; status.textContent = 'Complete!';
        setTimeout(() => { currentStep = 3; showResults(result); updateUI(); }, 500);
    }).catch(err => { clearInterval(iv); status.textContent = 'Error: ' + err.message; fill.style.width = '0%'; btn.disabled = false; btn.innerHTML = 'Retry'; });
}

function showResults(result) {
    const list = document.getElementById('resultList'); list.innerHTML = '';
    [{label:'Database migrations',s:result.migrations},{label:'Demo data seeding',s:result.seed},{label:'vLLM configuration',s:result.llm},{label:'Application keys',s:result.keys},{label:'Crawler config',s:result.crawler},{label:'WhatsApp setup',s:result.whatsapp}].forEach(item => {
        const li = document.createElement('li');
        const st = document.createElement('span'); st.className = 'status ' + (item.s !== false ? 'success' : 'error'); st.innerHTML = item.s !== false ? '✓' : (item.s === false ? '✗' : '?');
        li.appendChild(st); li.appendChild(document.createTextNode(' ' + item.label)); list.appendChild(li);
    });
    document.getElementById('finalTitle').textContent = result.success ? '✅ Installation Complete!' : '⚠️ Partial Installation';
    document.getElementById('finalSubtitle').textContent = result.success ? 'Your Support Board is ready to use.' : 'Some steps failed. Check the errors above.';
    document.getElementById('finalCredentials').style.display = result.success ? 'block' : 'none';
}

init();
</script>
</body>
</html>
<?php /**PATH /var/www/html/resources/views/installer/welcome.blade.php ENDPATH**/ ?>