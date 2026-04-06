<x-user-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('user.terminal.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h2 class="text-lg font-semibold text-gray-800">Terminal: {{ $account->username }}@{{ $account->server->name }}</h2>
        </div>
    </x-slot>

    <div class="bg-gray-900 rounded-xl shadow-sm overflow-hidden" style="height: 500px;">
        <div id="terminal" style="height: 100%; padding: 8px;"></div>
    </div>

    <p class="mt-3 text-xs text-gray-400">Connected as <strong>{{ $account->username }}</strong> on {{ $account->server->ip_address }}</p>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/xterm@5.3.0/css/xterm.min.css">
    <script src="https://cdn.jsdelivr.net/npm/xterm@5.3.0/lib/xterm.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xterm-addon-fit@0.8.0/lib/xterm-addon-fit.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const term = new Terminal({
                cursorBlink: true,
                fontSize: 14,
                fontFamily: 'Menlo, Monaco, "Courier New", monospace',
                theme: {
                    background: '#111827',
                    foreground: '#e5e7eb',
                    cursor: '#10b981',
                }
            });

            const fitAddon = new FitAddon.FitAddon();
            term.loadAddon(fitAddon);
            term.open(document.getElementById('terminal'));
            fitAddon.fit();

            const proxyUrl = @json($proxyUrl);
            const accountId = @json((string)$account->id);
            const token = @json($token);
            const csrfToken = @json(csrf_token());

            let polling = true;
            let inputBuffer = '';

            function sendInput(data) {
                fetch(proxyUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/octet-stream',
                    },
                    body: JSON.stringify({
                        account_id: accountId,
                        token: token,
                        input: data,
                    }),
                }).then(response => {
                    if (response.ok) return response.text();
                    throw new Error('Request failed');
                }).then(output => {
                    if (output) term.write(output);
                }).catch(() => {});
            }

            function pollOutput() {
                if (!polling) return;
                sendInput(inputBuffer);
                inputBuffer = '';
                setTimeout(pollOutput, 100);
            }

            pollOutput();

            term.onData(data => { inputBuffer += data; });

            term.onResize(({ rows, cols }) => {
                fetch(proxyUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ account_id: accountId, token: token, input: '\x01' + JSON.stringify({ rows, cols }) }),
                });
            });

            window.addEventListener('resize', () => fitAddon.fit());

            term.writeln('\x1b[32mConnecting to {{ $account->username }}@{{ $account->server->ip_address }}...\x1b[0m\r\n');

            window.addEventListener('beforeunload', () => { polling = false; });
        });
    </script>
</x-user-layout>
