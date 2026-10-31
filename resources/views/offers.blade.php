<x-app-layout>
    <div class="space-y-6" x-data="{ 
        showModal: false, 
        isEditing: false, 
        modalTitle: 'Create Special Offer',
        formAction: '{{ route('offers.store') }}',
        formMethod: 'POST',
        offerData: {
            title: '',
            description: '',
            badge_text: 'SPECIAL OFFER',
            discount_percentage: 20,
            food_id: '',
            banner_image_url: '',
            is_active: true
        },
        openCreateModal() {
            this.isEditing = false;
            this.modalTitle = 'Create Special Offer';
            this.formAction = '{{ route('offers.store') }}';
            this.formMethod = 'POST';
            this.offerData = {
                title: '',
                description: '',
                badge_text: 'SPECIAL OFFER',
                discount_percentage: 20,
                food_id: '',
                banner_image_url: '',
                is_active: true
            };
            this.showModal = true;
        },
        openEditModal(offer) {
            this.isEditing = true;
            this.modalTitle = 'Edit Offer: ' + offer.title;
            this.formAction = '/offers/' + offer.id;
            this.formMethod = 'PUT';
            this.offerData = {
                title: offer.title || '',
                description: offer.description || '',
                badge_text: offer.badge_text || 'SPECIAL OFFER',
                discount_percentage: offer.discount_percentage || 0,
                food_id: offer.food_id || '',
                banner_image_url: offer.banner_image_url || '',
                is_active: !!offer.is_active
            };
            this.showModal = true;
        }
    }">

        @if (session('success'))
            <div class="flex items-center justify-between bg-green-50 border border-green-200 text-green-700 px-5 py-3 rounded-2xl text-sm font-medium">
                <span>{{ session('success') }}</span>
                <button @click="window.location.reload()" class="text-green-500 hover:text-green-700">&times;</button>
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-5 py-3 rounded-2xl text-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <nav class="flex items-center text-xs font-bold text-gray-500 mb-2 uppercase tracking-wider">
                    <a href="{{ route('dashboard') }}" class="hover:text-[#C9A050] transition-colors">Dashboard</a>
                    <svg class="w-3.5 h-3.5 mx-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    <span class="text-[#0A2E2A] font-black">Promotions</span>
                </nav>
                <h1 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight">Special Offers & Banners</h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-1 font-medium">Manage promotional marketing cards synced directly with the user app home banner</p>
            </div>
            <button @click="openCreateModal()" class="px-6 py-3.5 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] hover:from-[#d8b05f] hover:to-[#C9A050] text-[#0A2E2A] rounded-2xl text-xs sm:text-sm font-black flex items-center space-x-2.5 shadow-lg shadow-[#C9A050]/25 transition-all duration-300 hover:scale-[1.02] shrink-0 self-start sm:self-auto cursor-pointer">
                <svg class="w-4 h-4 text-[#0A2E2A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                <span>Add New Offer</span>
            </button>
        </div>

        <!-- Offers Table Card -->
        <div class="bg-white/95 backdrop-blur-md rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 overflow-hidden">
            <div class="p-6 sm:p-8 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white/40">
                <div>
                    <h2 class="text-xl font-black text-gray-900 tracking-tight">Active Campaign Banners</h2>
                    <p class="text-xs text-gray-500 mt-0.5">These promotional cards are displayed on the user mobile app home carousel</p>
                </div>
                <span class="text-xs font-bold text-[#0A2E2A] bg-[#C9A050]/15 border border-[#C9A050]/30 px-3 py-1.5 rounded-xl self-start sm:self-auto">
                    {{ count($offers) }} Active Campaigns
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[900px]">
                    <thead>
                        <tr class="bg-gray-50/80 border-b border-gray-100">
                            <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[220px]">Offer</th>
                            <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[140px]">Badge</th>
                            <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[120px]">Discount</th>
                            <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[160px]">Linked Dish</th>
                            <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[120px]">Status</th>
                            <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest text-right min-w-[120px]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($offers as $offer)
                            <tr class="hover:bg-[#C9A050]/[0.03] transition-all duration-300">
                                <td class="px-6 py-5 align-middle">
                                    <div class="flex items-center space-x-3.5">
                                        @if ($offer->banner_image_url || ($offer->food && $offer->food->image_url))
                                            <img src="{{ $offer->banner_image_url ?: $offer->food->image_url }}" class="w-12 h-12 rounded-2xl object-cover shadow-sm ring-1 ring-gray-200 shrink-0" alt="">
                                        @else
                                            <div class="w-12 h-12 rounded-2xl bg-[#C9A050]/15 border border-[#C9A050]/30 flex items-center justify-center text-[#0A2E2A] font-black text-base shrink-0">
                                                %
                                            </div>
                                        @endif
                                        <div class="flex flex-col gap-1 min-w-0">
                                            <h4 class="font-bold text-gray-900 text-sm truncate">{{ $offer->title }}</h4>
                                            <p class="text-xs text-gray-500 truncate max-w-xs">{{ $offer->description ?: 'No description provided' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5 align-middle">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider bg-[#0A2E2A] text-[#C9A050] border border-[#C9A050]/30">
                                        {{ $offer->badge_text ?: 'SPECIAL OFFER' }}
                                    </span>
                                </td>
                                <td class="px-6 py-5 align-middle">
                                    <span class="text-sm font-black text-[#C9A050]">
                                        {{ $offer->discount_percentage }}% OFF
                                    </span>
                                </td>
                                <td class="px-6 py-5 align-middle">
                                    @if ($offer->food)
                                        <span class="text-sm font-semibold text-gray-900">{{ $offer->food->name }}</span>
                                        <p class="text-[10px] text-gray-400">Rs. {{ number_format($offer->food->price, 2) }}</p>
                                    @else
                                        <span class="text-xs text-gray-400 italic">General Promo</span>
                                    @endif
                                </td>
                                <td class="px-6 py-5 align-middle">
                                    <form action="{{ route('offers.toggle', $offer) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="px-3 py-1.5 rounded-full text-[11px] font-bold border transition-all cursor-pointer {{ $offer->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 shadow-2xs' : 'bg-gray-100 text-gray-500 border-gray-200 hover:bg-gray-200' }}">
                                            {{ $offer->is_active ? 'Active' : 'Paused' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-6 py-5 text-right align-middle">
                                    <div class="flex items-center justify-end space-x-2">
                                        <button @click='openEditModal(@json($offer))' class="p-2.5 text-gray-400 hover:text-[#0A2E2A] hover:bg-[#C9A050]/20 bg-gray-50 rounded-xl transition-all border border-gray-200/80 cursor-pointer" title="Edit offer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        </button>
                                        <form action="{{ route('offers.destroy', $offer) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this offer?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2.5 text-gray-400 hover:text-red-600 hover:bg-red-50 bg-gray-50 rounded-xl transition-all border border-gray-200/80 cursor-pointer" title="Delete offer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-20 text-center">
                                    <div class="w-14 h-14 rounded-2xl bg-[#C9A050]/15 text-[#0A2E2A] flex items-center justify-center mx-auto mb-3 shadow-xs">
                                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path></svg>
                                    </div>
                                    <h3 class="text-base font-black text-gray-900">No promotional offers yet</h3>
                                    <p class="text-xs text-gray-500 mt-1 font-medium">Create an offer card to sync with the mobile app banner</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($offers->hasPages())
                <div class="p-6 border-t border-gray-100">
                    {{ $offers->links() }}
                </div>
            @endif
        </div>

        <!-- Add / Edit Offer Modal -->
        <template x-teleport="body">
            <div x-show="showModal"
                 class="fixed inset-0 z-[100] flex items-center justify-center p-4 overflow-y-auto"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 style="display: none;">

                <div class="fixed inset-0 bg-gray-950/70 backdrop-blur-md" @click="showModal = false"></div>

                <div class="relative bg-white rounded-[2.5rem] shadow-2xl shadow-[#0A2E2A]/25 max-w-lg w-full overflow-hidden transform transition-all my-8 border border-gray-100"
                     x-show="showModal"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 translate-y-4">

                    <form :action="formAction" method="POST">
                        @csrf
                        <template x-if="isEditing">
                            <input type="hidden" name="_method" value="PUT">
                        </template>

                        <div class="bg-gradient-to-r from-[#0A2E2A] via-[#0D3833] to-[#0A2E2A] px-8 py-6 flex items-center justify-between border-b border-[#C9A050]/20">
                            <div class="flex items-center space-x-3.5">
                                <div class="w-12 h-12 rounded-2xl bg-[#C9A050] border border-[#b88f3f] flex items-center justify-center text-[#0A2E2A] font-black text-xl shadow-md shrink-0">
                                    %
                                </div>
                                <div>
                                    <h3 class="text-xl font-black text-white tracking-tight" x-text="modalTitle"></h3>
                                    <p class="text-xs text-emerald-100 font-medium">Synchronized live with customer mobile app banner</p>
                                </div>
                            </div>
                            <button type="button" @click="showModal = false" class="p-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-colors cursor-pointer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <div class="p-6 sm:p-8 space-y-4">
                            <!-- Title -->
                            <div>
                                <label class="block text-xs font-black text-gray-700 uppercase tracking-wider mb-2">Campaign Title</label>
                                <input type="text" name="title" x-model="offerData.title" required placeholder="e.g. Chicken Teriyaki Promo" class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl text-sm font-semibold text-gray-900 focus:ring-2 focus:ring-[#C9A050] focus:border-[#C9A050] transition-all">
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-xs font-black text-gray-700 uppercase tracking-wider mb-2">Tagline / Subtext</label>
                                <textarea name="description" x-model="offerData.description" rows="2" placeholder="e.g. Authentic Teriyaki glaze with Japanese spices" class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl text-xs sm:text-sm font-semibold text-gray-900 focus:ring-2 focus:ring-[#C9A050] focus:border-[#C9A050] transition-all"></textarea>
                            </div>

                            <!-- Badge & Discount -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-black text-gray-700 uppercase tracking-wider mb-2">Badge Text</label>
                                    <input type="text" name="badge_text" x-model="offerData.badge_text" placeholder="SPECIAL OFFER" class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl text-sm font-semibold text-gray-900 focus:ring-2 focus:ring-[#C9A050] focus:border-[#C9A050] transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-black text-gray-700 uppercase tracking-wider mb-2">Discount %</label>
                                    <input type="number" name="discount_percentage" x-model="offerData.discount_percentage" min="0" max="100" placeholder="20" class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl text-sm font-semibold text-gray-900 focus:ring-2 focus:ring-[#C9A050] focus:border-[#C9A050] transition-all">
                                </div>
                            </div>

                            <!-- Linked Food Item -->
                            <div>
                                <label class="block text-xs font-black text-gray-700 uppercase tracking-wider mb-2">Linked Culinary Dish (Optional)</label>
                                <select name="food_id" x-model="offerData.food_id" class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl text-sm font-semibold text-gray-900 focus:ring-2 focus:ring-[#C9A050] focus:border-[#C9A050] transition-all cursor-pointer">
                                    <option value="">-- No specific dish (General Promotion) --</option>
                                    @foreach ($foods as $food)
                                        <option value="{{ $food->id }}">{{ $food->name }} (Rs. {{ number_format($food->price, 2) }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Banner Image URL -->
                            <div>
                                <label class="block text-xs font-black text-gray-700 uppercase tracking-wider mb-2">Banner Image URL (Optional)</label>
                                <input type="url" name="banner_image_url" x-model="offerData.banner_image_url" placeholder="https://example.com/banner.jpg" class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl text-sm font-semibold text-gray-900 focus:ring-2 focus:ring-[#C9A050] focus:border-[#C9A050] transition-all">
                            </div>

                            <!-- Active status toggle -->
                            <div class="flex items-center space-x-3 pt-2">
                                <input type="checkbox" id="is_active" name="is_active" value="1" x-model="offerData.is_active" class="w-5 h-5 text-[#C9A050] rounded-lg focus:ring-[#C9A050] border-gray-300 cursor-pointer">
                                <label for="is_active" class="text-xs font-bold text-gray-800 cursor-pointer">Display this promotional banner as active</label>
                            </div>

                            <div class="pt-4 flex space-x-3">
                                <button type="button" @click="showModal = false" class="flex-1 py-3.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-2xl font-bold text-xs sm:text-sm transition-colors cursor-pointer">Cancel</button>
                                <button type="submit" class="flex-1 py-3.5 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] hover:from-[#d8b05f] hover:to-[#C9A050] text-[#0A2E2A] font-black rounded-2xl text-xs sm:text-sm shadow-lg shadow-[#C9A050]/25 hover:shadow-xl transition-all duration-300 cursor-pointer">Save Campaign</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>
</x-app-layout>
