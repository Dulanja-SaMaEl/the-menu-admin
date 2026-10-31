<x-app-layout>
    <div class="space-y-6" x-data="{ 
        showModal: false,
        isEditing: false,
        userData: {},
        formAction: '{{ route('users.store') }}',
        formMethod: 'POST',
        openCreateModal() {
            this.isEditing = false;
            this.userData = { name: '', email: '', role: 'System Admin', password: '', password_confirmation: '' };
            this.formAction = '{{ route('users.store') }}';
            this.formMethod = 'POST';
            this.showModal = true;
        },
        openEditModal(user) {
            this.isEditing = true;
            this.userData = { ...user, role: user.role || 'System Admin', password: '', password_confirmation: '' };
            this.formAction = '/users/' + user.id;
            this.formMethod = 'PUT';
            this.showModal = true;
        }
    }">

        @if (session('success'))
            <div class="flex items-center justify-between bg-green-50 border border-green-200 text-green-700 px-5 py-3 rounded-2xl text-sm font-medium shadow-sm">
                <span>{{ session('success') }}</span>
                <button @click="window.location.reload()" class="text-green-500 hover:text-green-700 font-bold text-lg leading-none">&times;</button>
            </div>
        @endif

        @if ($errors->any())
            <div class="flex flex-col bg-red-50 border border-red-200 text-red-700 px-5 py-3 rounded-2xl text-sm font-medium shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-bold">Please fix the following errors:</span>
                    <button @click="window.location.reload()" class="text-red-500 hover:text-red-700 font-bold text-lg leading-none">&times;</button>
                </div>
                <ul class="list-disc list-inside text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-[#0A2E2A]/5 border border-[#0A2E2A]/10 mb-2">
                    <span class="w-2 h-2 rounded-full bg-[#C9A050]"></span>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#0A2E2A]">Access Control</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0A2E2A] tracking-tight">Staff & User Management</h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Manage administrative credentials, restaurant roles, and system access</p>
            </div>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">
                <!-- Search Form -->
                <form method="GET" action="{{ url()->current() }}" class="relative w-full sm:w-72">
                    <input type="text"
                           name="search"
                           value="{{ $search ?? '' }}"
                           placeholder="Search staff, email, role..."
                           @input.debounce.1500ms="$el.form.submit()"
                           class="w-full pl-11 pr-4 py-3 text-xs sm:text-sm font-bold border border-gray-200 rounded-2xl focus:ring-2 focus:ring-[#C9A050] focus:border-[#C9A050] bg-gray-50/50 focus:bg-white text-gray-900 transition-all duration-200 shadow-inner">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </form>
                <button @click="openCreateModal()" class="px-5 py-3 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] hover:from-[#d8b05f] hover:to-[#C9A050] text-[#0A2E2A] font-black rounded-2xl shadow-lg shadow-[#C9A050]/25 transition-all duration-300 hover:scale-[1.02] flex items-center justify-center space-x-2 shrink-0 min-h-[44px] text-sm cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>Add New User</span>
                </button>
            </div>
        </div>

        <div class="bg-white/95 backdrop-blur-md rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[850px]">
                    <thead class="bg-gray-50/80 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[240px]">Name</th>
                            <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[200px]">Email</th>
                            <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[150px]">Role</th>
                            <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[140px]">Created At</th>
                            <th class="px-6 py-4.5 text-right text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[120px]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100/70">
                        @forelse($users as $user)
                            <tr class="hover:bg-[#C9A050]/[0.03] transition-all duration-300">
                                <td class="px-6 py-5 align-middle">
                                    <div class="flex items-center space-x-3.5">
                                        <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=0A2E2A&color=C9A050"
                                            class="w-10 h-10 rounded-xl shadow-sm border border-gray-100" alt="{{ $user->name }}">
                                        <div class="flex flex-col gap-0.5">
                                            <span class="font-bold text-gray-900 block text-sm">{{ $user->name }}</span>
                                            <span class="text-[11px] text-gray-400">ID: #USR-{{ str_pad($user->id, 4, '0', STR_PAD_LEFT) }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5 align-middle text-sm font-medium text-gray-600">{{ $user->email }}</td>
                                <td class="px-6 py-5 align-middle">
                                    @php
                                        $roleStyles = match($user->role) {
                                            'Super Admin' => 'bg-purple-50 text-purple-700 border-purple-200',
                                            'System Admin' => 'bg-[#0A2E2A]/5 text-[#0A2E2A] border-[#0A2E2A]/15 font-black',
                                            'HR' => 'bg-teal-50 text-teal-700 border-teal-200',
                                            'Chef' => 'bg-amber-50 text-amber-700 border-amber-200',
                                            default => 'bg-gray-50 text-gray-700 border-gray-200',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold border {{ $roleStyles }}">
                                        {{ $user->role ?? 'System Admin' }}
                                    </span>
                                </td>
                                <td class="px-6 py-5 align-middle text-gray-500 text-xs font-semibold">{{ $user->created_at->format('M d, Y') }}</td>
                                <td class="px-6 py-5 text-right align-middle">
                                    <div class="flex items-center justify-end space-x-2">
                                        <button @click="openEditModal({{ json_encode($user) }})" class="p-2.5 text-gray-400 hover:text-[#C9A050] hover:bg-[#C9A050]/10 transition-colors rounded-xl cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                                </path>
                                            </svg>
                                        </button>
                                        <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2.5 text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors rounded-xl cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                    </path>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 bg-gray-50 rounded-3xl flex items-center justify-center mx-auto mb-4 border border-gray-100">
                                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                            </svg>
                                        </div>
                                        <h3 class="text-[#0A2E2A] font-extrabold text-base">
                                            @if(!empty($search))
                                                No users matching "{{ $search }}"
                                            @else
                                                No users found
                                            @endif
                                        </h3>
                                        <p class="text-xs text-gray-400 mt-1">
                                            @if(!empty($search))
                                                Try adjusting your search criteria or clear the search.
                                            @else
                                                Get started by inviting or creating a new team member
                                            @endif
                                        </p>
                                        @if(!empty($search))
                                            <a href="{{ route('users.index') }}" class="mt-4 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-all">
                                                Clear Search Filter
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <template x-teleport="body">
            <div x-show="showModal"
                 class="fixed inset-0 z-[100] flex items-center justify-center p-4 overflow-x-hidden overflow-y-auto"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 style="display: none;">

                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showModal = false"></div>

                <div class="relative bg-white rounded-[2.5rem] shadow-2xl max-w-lg w-full overflow-hidden transform transition-all border border-gray-100"
                     x-show="showModal"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 translate-y-4">

                    <div class="bg-gradient-to-r from-[#0A2E2A] via-[#0D3833] to-[#0A2E2A] border-b border-[#C9A050]/20 px-8 py-6 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#C9A050]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-white" x-text="isEditing ? 'Edit User' : 'Add New User'"></h3>
                                <p class="text-xs text-white/70 mt-0.5">Staff credentials and permissions</p>
                            </div>
                        </div>
                        <button @click="showModal = false" class="p-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <form method="POST" :action="formAction" class="p-8 space-y-5">
                        @csrf
                        <template x-if="isEditing">
                            <input type="hidden" name="_method" value="PUT">
                        </template>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Full Name</label>
                            <input type="text" name="name" x-model="userData.name" required
                                class="w-full px-5 py-3.5 rounded-2xl border border-gray-200 focus:border-[#C9A050] focus:ring-4 focus:ring-[#C9A050]/15 outline-none transition-all duration-200 text-sm @error('name') border-red-300 ring-1 ring-red-200 @enderror"
                                placeholder="Enter full name">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Email Address</label>
                            <input type="email" name="email" x-model="userData.email" required
                                class="w-full px-5 py-3.5 rounded-2xl border border-gray-200 focus:border-[#C9A050] focus:ring-4 focus:ring-[#C9A050]/15 outline-none transition-all duration-200 text-sm @error('email') border-red-300 ring-1 ring-red-200 @enderror"
                                placeholder="Enter email address">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Role</label>
                            <select name="role" x-model="userData.role" required
                                class="w-full px-5 py-3.5 rounded-2xl border border-gray-200 focus:border-[#C9A050] focus:ring-4 focus:ring-[#C9A050]/15 outline-none transition-all duration-200 text-sm @error('role') border-red-300 ring-1 ring-red-200 @enderror">
                                <option value="Super Admin">Super Admin</option>
                                <option value="System Admin">System Admin</option>
                                <option value="HR">HR</option>
                                <option value="Chef">Chef</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Password</label>
                            <input type="password" name="password" x-model="userData.password" :required="!isEditing"
                                class="w-full px-5 py-3.5 rounded-2xl border border-gray-200 focus:border-[#C9A050] focus:ring-4 focus:ring-[#C9A050]/15 outline-none transition-all duration-200 text-sm @error('password') border-red-300 ring-1 ring-red-200 @enderror"
                                placeholder="Enter password (min 8 characters)">
                            <template x-if="isEditing">
                                <p class="mt-1.5 text-xs text-gray-400">Leave blank to keep the current password.</p>
                            </template>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Confirm Password</label>
                            <input type="password" name="password_confirmation" x-model="userData.password_confirmation" :required="!isEditing"
                                class="w-full px-5 py-3.5 rounded-2xl border border-gray-200 focus:border-[#C9A050] focus:ring-4 focus:ring-[#C9A050]/15 outline-none transition-all duration-200 text-sm"
                                placeholder="Confirm password">
                        </div>

                        <div class="flex space-x-4 pt-3">
                            <button type="button" @click="showModal = false" class="flex-1 px-6 py-3.5 rounded-2xl font-bold text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">Cancel</button>
                            <button type="submit" class="flex-1 px-6 py-3.5 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] hover:from-[#d8b05f] hover:to-[#C9A050] text-[#0A2E2A] font-black rounded-2xl shadow-lg shadow-[#C9A050]/25 transition-all duration-300 hover:scale-[1.02] text-sm" x-text="isEditing ? 'Save Changes' : 'Create User'"></button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>
</x-app-layout>
