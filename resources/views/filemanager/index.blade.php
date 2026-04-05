<x-user-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">File Manager</h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- Account Selector -->
    @if(!$selectedAccount)
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">Select Account</h3>
                <p class="text-sm text-gray-500 mt-1">Browse files for a hosting account.</p>
            </div>
            <div class="px-6 py-5">
                @if($accounts->isEmpty())
                    <p class="text-sm text-gray-500">No accounts available.</p>
                @else
                    <form method="GET" action="{{ route('user.filemanager.index') }}" class="flex items-end gap-4">
                        <div class="flex-1">
                            <label for="account_id" class="block text-sm font-medium text-gray-700 mb-1.5">Account</label>
                            <select name="account_id" id="account_id"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}">
                                        {{ $account->username }} ({{ $account->server->name }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                            Browse Files
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @else
        <!-- File Browser -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden" x-data="{ newFolder: false, folderName: '', renaming: null, renameName: '' }">
            <!-- Toolbar -->
            <div class="px-6 py-3 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                <div class="flex items-center space-x-2 text-sm">
                    <!-- Breadcrumb -->
                    @php
                        $parts = explode('/', trim($currentPath, '/'));
                        $breadcrumb = '';
                    @endphp
                    @foreach($parts as $i => $part)
                        @php $breadcrumb .= '/' . $part; @endphp
                        @if($i < count($parts) - 1)
                            <a href="{{ route('user.filemanager.index', ['account_id' => $selectedAccount->id, 'path' => $breadcrumb]) }}"
                               class="text-indigo-600 hover:text-indigo-800 transition">{{ $part }}</a>
                            <span class="text-gray-400">/</span>
                        @else
                            <span class="text-gray-800 font-medium">{{ $part }}</span>
                        @endif
                    @endforeach
                </div>

                <div class="flex items-center space-x-2">
                    <!-- New Folder -->
                    <button @click="newFolder = !newFolder" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                        New Folder
                    </button>

                    <!-- Upload -->
                    <form action="{{ route('user.filemanager.upload') }}" method="POST" enctype="multipart/form-data" class="inline-flex">
                        @csrf
                        <input type="hidden" name="account_id" value="{{ $selectedAccount->id }}">
                        <input type="hidden" name="path" value="{{ $currentPath }}">
                        <label class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition cursor-pointer">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                            Upload
                            <input type="file" name="file" class="hidden" onchange="this.form.submit()">
                        </label>
                    </form>
                </div>
            </div>

            <!-- New Folder Input -->
            <div x-show="newFolder" x-collapse class="px-6 py-3 border-b border-gray-100 bg-indigo-50">
                <form action="{{ route('user.filemanager.mkdir') }}" method="POST" class="flex items-center gap-3">
                    @csrf
                    <input type="hidden" name="account_id" value="{{ $selectedAccount->id }}">
                    <input type="text" name="path" x-model="folderName" placeholder="Folder name"
                        class="flex-1 rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        x-ref="folderInput"
                        @keydown.enter.prevent="$el.closest('form').querySelector('[name=path]').value = '{{ $currentPath }}/' + folderName; $el.closest('form').submit()">
                    <button type="submit" @click="$el.closest('form').querySelector('[name=path]').value = '{{ $currentPath }}/' + folderName"
                        class="px-3 py-2 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                        Create
                    </button>
                    <button type="button" @click="newFolder = false" class="px-3 py-2 text-xs text-gray-600 hover:text-gray-800 transition">Cancel</button>
                </form>
            </div>

            <!-- Parent directory link -->
            @php
                $parentPath = dirname($currentPath);
                $homeDir = '/home/' . $selectedAccount->username;
            @endphp
            @if($currentPath !== $homeDir && strlen($currentPath) > strlen($homeDir))
                <a href="{{ route('user.filemanager.index', ['account_id' => $selectedAccount->id, 'path' => $parentPath]) }}"
                   class="flex items-center px-6 py-3 text-sm text-gray-500 hover:bg-gray-50 border-b border-gray-100 transition">
                    <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" /></svg>
                    ..
                </a>
            @endif

            <!-- File List -->
            @if(empty($entries))
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                    <p class="mt-3 text-sm text-gray-500">This folder is empty.</p>
                </div>
            @else
                <!-- Table header -->
                <div class="hidden sm:grid grid-cols-12 px-6 py-2 text-xs font-medium text-gray-400 uppercase tracking-wide border-b border-gray-100">
                    <div class="col-span-6">Name</div>
                    <div class="col-span-2">Size</div>
                    <div class="col-span-2">Permissions</div>
                    <div class="col-span-2 text-right">Actions</div>
                </div>

                <div class="divide-y divide-gray-50">
                    @foreach($entries as $entry)
                        <div class="grid grid-cols-12 items-center px-6 py-2.5 hover:bg-gray-50 transition text-sm">
                            <!-- Name -->
                            <div class="col-span-6 flex items-center space-x-3 min-w-0">
                                @if($entry['is_dir'])
                                    <svg class="w-5 h-5 text-yellow-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" /></svg>
                                    <a href="{{ route('user.filemanager.index', ['account_id' => $selectedAccount->id, 'path' => $entry['path']]) }}"
                                       class="text-indigo-600 hover:text-indigo-800 font-medium truncate transition">
                                        {{ $entry['name'] }}
                                    </a>
                                @else
                                    <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    <span class="text-gray-800 truncate">{{ $entry['name'] }}</span>
                                @endif
                            </div>

                            <!-- Size -->
                            <div class="col-span-2 text-xs text-gray-500">
                                @if(!$entry['is_dir'])
                                    @if($entry['size'] >= 1048576)
                                        {{ number_format($entry['size'] / 1048576, 1) }} MB
                                    @elseif($entry['size'] >= 1024)
                                        {{ number_format($entry['size'] / 1024, 1) }} KB
                                    @else
                                        {{ $entry['size'] }} B
                                    @endif
                                @else
                                    —
                                @endif
                            </div>

                            <!-- Permissions -->
                            <div class="col-span-2 text-xs font-mono text-gray-500">
                                {{ $entry['permissions'] }}
                            </div>

                            <!-- Actions -->
                            <div class="col-span-2 flex items-center justify-end space-x-2">
                                @if(!$entry['is_dir'] && $entry['size'] < 2097152)
                                    <a href="{{ route('user.filemanager.edit', ['account_id' => $selectedAccount->id, 'path' => $entry['path']]) }}"
                                       class="text-indigo-600 hover:text-indigo-800 text-xs font-medium transition">Edit</a>
                                @endif

                                <form action="{{ route('user.filemanager.archive') }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="account_id" value="{{ $selectedAccount->id }}">
                                    <input type="hidden" name="path" value="{{ $entry['path'] }}">
                                    <button type="submit" class="text-gray-400 hover:text-gray-600 transition" title="{{ str_ends_with($entry['name'], '.zip') || str_ends_with($entry['name'], '.tar.gz') ? 'Extract' : 'Compress' }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>
                                    </button>
                                </form>

                                <form action="{{ route('user.filemanager.delete') }}" method="POST" class="inline"
                                      onsubmit="return confirm('Delete {{ $entry['name'] }}?')">
                                    @csrf
                                    <input type="hidden" name="account_id" value="{{ $selectedAccount->id }}">
                                    <input type="hidden" name="path" value="{{ $entry['path'] }}">
                                    <button type="submit" class="text-gray-400 hover:text-red-600 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</x-user-layout>
