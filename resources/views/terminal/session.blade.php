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

            const agentUrl = '{{ $agentUrl }}';
            const username = '{{ $account->username }}';
            const token = '{{ $token }}';

            // Connect via fetch streaming
            const controller = new AbortController();
            const encoder = new TextEncoder();
            const decoder = new TextDecoder();

            // Create a writable stream for input
            let inputWriter = null;

            const connectUrl = agentUrl + '/terminal/connect?username=' + username + '&token=' + token;

            fetch(connectUrl, {
                method: 'POST',
                signal: controller.signal,
                headers: { 'Content-Type': 'application/octet-stream' },
                body: new ReadableStream({
                    start(ctrl) {
                        inputWriter = ctrl;
                    }
                }),
                duplex: 'half',
            }).then(response => {
                const reader = response.body.getReader();
                function read() {
                    reader.read().then(({ done, value }) => {
                        if (done) {
                            term.writeln('\r\n\x1b[33mSession ended.\x1b[0m');
                            return;
                        }
                        term.write(value);
                        read();
                    });
                }
                read();
            }).catch(err => {
                if (err.name !== 'AbortError') {
                    term.writeln('\r\n\x1b[31mConnection failed: ' + err.message + '\x1b[0m');
                }
            });

            // Send keystrokes
            term.onData(data => {
                if (inputWriter) {
                    inputWriter.enqueue(encoder.encode(data));
                }
            });

            // Handle resize
            term.onResize(({ rows, cols }) => {
                if (inputWriter) {
                    const msg = '\x01' + JSON.stringify({ rows, cols });
                    inputWriter.enqueue(encoder.encode(msg));
                }
            });

            window.addEventListener('resize', () => fitAddon.fit());

            // Cleanup on page leave
            window.addEventListener('beforeunload', () => {
                controller.abort();
            });
        });
    </script>
</x-user-layout>
