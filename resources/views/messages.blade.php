<x-app-layout>
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-[#0A2E2A]/5 border border-[#0A2E2A]/10 mb-2">
                <span class="w-2 h-2 rounded-full bg-[#C9A050]"></span>
                <span class="text-[11px] font-bold uppercase tracking-wider text-[#0A2E2A]">Internal Communications</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0A2E2A] tracking-tight">Team Messages</h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Real-time internal chat with kitchen staff, drivers, and front-of-house</p>
        </div>
        
        <div class="flex items-center space-x-2">
            <div class="px-4 py-2 bg-white/95 backdrop-blur-md rounded-2xl shadow-md shadow-[#0A2E2A]/5 border border-gray-200/70 flex items-center space-x-2 text-xs font-bold text-[#0A2E2A]">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Dispatch Live</span>
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6 lg:gap-8 min-h-[600px] lg:h-[calc(100vh-190px)]"
         x-data="{
            activeChannel: 'Front of House',
            activeAvatar: 'https://ui-avatars.com/api/?name=Front+of+House&background=C9A050&color=fff',
            activeStatus: '4 members online',
            newMessage: '',
            searchQuery: '',
            threads: {
                'Front of House': [
                    { sender: 'Maria Gomez', isMe: false, text: 'Table 12 is asking for the bill, and also confirm the order please.', time: '8:14 AM' },
                    { sender: 'You', isMe: true, text: 'Send it through POS now.', time: '8:15 AM' },
                    { sender: 'Sandra James', isMe: false, text: 'Split payment or single?', time: '8:24 AM' }
                ],
                'Kitchen Staff': [
                    { sender: 'Chef Daniel', isMe: false, text: 'Two Chicken Biriyani orders for Table 7 ready in 5 mins.', time: '8:30 AM' },
                    { sender: 'You', isMe: true, text: 'Driver assigned, packaging for pickup.', time: '8:31 AM' }
                ],
                'Maria Gomez': [
                    { sender: 'Maria Gomez', isMe: false, text: 'Can you approve the void on Order #284?', time: '8:10 AM' },
                    { sender: 'You', isMe: true, text: 'Approved.', time: '8:12 AM' }
                ],
                'Daniel Okafor': [
                    { sender: 'Daniel Okafor', isMe: false, text: 'I will handle the catering event delivery at 6 PM.', time: '8:05 AM' }
                ]
            },
            sendMessage() {
                const text = this.newMessage.trim();
                if (!text) return;
                const now = new Date();
                const hours = now.getHours();
                const minutes = now.getMinutes().toString().padStart(2, '0');
                const ampm = hours >= 12 ? 'PM' : 'AM';
                const formattedTime = (hours % 12 || 12) + ':' + minutes + ' ' + ampm;

                if (!this.threads[this.activeChannel]) {
                    this.threads[this.activeChannel] = [];
                }
                this.threads[this.activeChannel].push({
                    sender: 'You',
                    isMe: true,
                    text: text,
                    time: formattedTime
                });
                this.newMessage = '';
                this.$nextTick(() => {
                    const el = document.getElementById('chat-scroll-container');
                    if (el) el.scrollTop = el.scrollHeight;
                });
            }
         }">
        <!-- Teams/Personal List -->
        <div class="w-full lg:w-1/3 bg-white/95 backdrop-blur-md rounded-[2.5rem] p-5 sm:p-7 flex flex-col shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 overflow-hidden min-h-[380px] lg:min-h-0">
            <div class="relative mb-6">
                <input type="text" 
                       x-model="searchQuery"
                       placeholder="Search conversations..." 
                       class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl text-sm focus:border-[#C9A050] focus:ring-4 focus:ring-[#C9A050]/15 outline-none transition-all">
                <svg class="w-4 h-4 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>

            <div class="flex-grow overflow-y-auto space-y-6 pr-1 custom-scrollbar">
                <div>
                    <h3 class="text-[11px] font-black text-gray-400 uppercase tracking-widest mb-3">Team Channels</h3>
                    <div class="space-y-2">
                        <div x-show="!searchQuery || 'Front of House'.toLowerCase().includes(searchQuery.toLowerCase())"
                             @click="activeChannel = 'Front of House'; activeAvatar = 'https://ui-avatars.com/api/?name=Front+of+House&background=C9A050&color=0A2E2A'; activeStatus = '4 members online'"
                             :class="activeChannel === 'Front of House' ? 'bg-gradient-to-r from-[#0A2E2A] via-[#0D3833] to-[#0A2E2A] text-white shadow-lg shadow-[#0A2E2A]/15 border border-[#C9A050]/30' : 'hover:bg-gray-50 text-gray-800 border-transparent'"
                             class="flex items-center space-x-3.5 p-3.5 rounded-2xl transition-all cursor-pointer group border">
                            <img src="https://ui-avatars.com/api/?name=Front+of+House&background=C9A050&color=0A2E2A" class="w-11 h-11 rounded-2xl shadow-sm group-hover:scale-105 transition-transform shrink-0" alt="FOH">
                            <div class="flex-grow min-w-0">
                                <div class="flex items-center justify-between mb-0.5">
                                    <h4 class="font-bold truncate text-sm" :class="activeChannel === 'Front of House' ? 'text-white' : 'text-gray-900'">Front of House</h4>
                                    <span class="text-[10px] font-bold shrink-0 ml-2" :class="activeChannel === 'Front of House' ? 'text-[#C9A050]' : 'text-gray-400'">Active</span>
                                </div>
                                <p class="text-xs truncate font-medium" :class="activeChannel === 'Front of House' ? 'text-white/70' : 'text-gray-500'">Sandra: Table 12 checkout ready.</p>
                            </div>
                        </div>

                        <div x-show="!searchQuery || 'Kitchen Staff'.toLowerCase().includes(searchQuery.toLowerCase())"
                             @click="activeChannel = 'Kitchen Staff'; activeAvatar = 'https://ui-avatars.com/api/?name=Kitchen&background=0A2E2A&color=C9A050'; activeStatus = 'Chef Daniel & 3 cooks online'"
                             :class="activeChannel === 'Kitchen Staff' ? 'bg-gradient-to-r from-[#0A2E2A] via-[#0D3833] to-[#0A2E2A] text-white shadow-lg shadow-[#0A2E2A]/15 border border-[#C9A050]/30' : 'hover:bg-gray-50 text-gray-800 border-transparent'"
                             class="flex items-center space-x-3.5 p-3.5 rounded-2xl transition-all cursor-pointer group border">
                            <img src="https://ui-avatars.com/api/?name=Kitchen&background=0A2E2A&color=C9A050" class="w-11 h-11 rounded-2xl shadow-sm group-hover:scale-105 transition-transform shrink-0" alt="Kitchen">
                            <div class="flex-grow min-w-0">
                                <div class="flex items-center justify-between mb-0.5">
                                    <h4 class="font-bold truncate text-sm" :class="activeChannel === 'Kitchen Staff' ? 'text-white' : 'text-gray-900'">Kitchen Staff</h4>
                                    <span class="text-[10px] font-bold shrink-0 ml-2" :class="activeChannel === 'Kitchen Staff' ? 'text-[#C9A050]' : 'text-gray-400'">Just Now</span>
                                </div>
                                <p class="text-xs truncate font-medium" :class="activeChannel === 'Kitchen Staff' ? 'text-white/70' : 'text-gray-500'">Chef Daniel: Two Biriyani ready.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-[11px] font-black text-gray-400 uppercase tracking-widest mb-3">Direct Messages</h3>
                    <div class="space-y-2">
                        <div x-show="!searchQuery || 'Maria Gomez'.toLowerCase().includes(searchQuery.toLowerCase())"
                             @click="activeChannel = 'Maria Gomez'; activeAvatar = 'https://ui-avatars.com/api/?name=Maria+Gomez&background=0A2E2A&color=C9A050'; activeStatus = 'Online'"
                             :class="activeChannel === 'Maria Gomez' ? 'bg-gradient-to-r from-[#0A2E2A] via-[#0D3833] to-[#0A2E2A] text-white shadow-lg shadow-[#0A2E2A]/15 border border-[#C9A050]/30' : 'hover:bg-gray-50 text-gray-800 border-transparent'"
                             class="flex items-center space-x-3.5 p-3.5 rounded-2xl transition-all cursor-pointer group border">
                            <img src="https://ui-avatars.com/api/?name=Maria+Gomez&background=0A2E2A&color=C9A050" class="w-11 h-11 rounded-2xl shadow-sm group-hover:scale-105 transition-transform shrink-0" alt="Maria">
                            <div class="flex-grow min-w-0">
                                <div class="flex items-center justify-between mb-0.5">
                                    <h4 class="font-bold truncate text-sm" :class="activeChannel === 'Maria Gomez' ? 'text-white' : 'text-gray-900'">Maria Gomez</h4>
                                    <span class="text-[10px] font-bold shrink-0 ml-2" :class="activeChannel === 'Maria Gomez' ? 'text-[#C9A050]' : 'text-gray-400'">12m ago</span>
                                </div>
                                <p class="text-xs truncate font-medium" :class="activeChannel === 'Maria Gomez' ? 'text-white/70' : 'text-gray-500'">Can you approve void on #284?</p>
                            </div>
                        </div>

                        <div x-show="!searchQuery || 'Daniel Okafor'.toLowerCase().includes(searchQuery.toLowerCase())"
                             @click="activeChannel = 'Daniel Okafor'; activeAvatar = 'https://ui-avatars.com/api/?name=Daniel+Okafor&background=0A2E2A&color=C9A050'; activeStatus = 'On Delivery'"
                             :class="activeChannel === 'Daniel Okafor' ? 'bg-gradient-to-r from-[#0A2E2A] via-[#0D3833] to-[#0A2E2A] text-white shadow-lg shadow-[#0A2E2A]/15 border border-[#C9A050]/30' : 'hover:bg-gray-50 text-gray-800 border-transparent'"
                             class="flex items-center space-x-3.5 p-3.5 rounded-2xl transition-all cursor-pointer group border">
                            <img src="https://ui-avatars.com/api/?name=Daniel+Okafor&background=0A2E2A&color=C9A050" class="w-11 h-11 rounded-2xl shadow-sm group-hover:scale-105 transition-transform shrink-0" alt="Daniel">
                            <div class="flex-grow min-w-0">
                                <div class="flex items-center justify-between mb-0.5">
                                    <h4 class="font-bold truncate text-sm" :class="activeChannel === 'Daniel Okafor' ? 'text-white' : 'text-gray-900'">Daniel Okafor</h4>
                                    <span class="text-[10px] font-bold shrink-0 ml-2" :class="activeChannel === 'Daniel Okafor' ? 'text-[#C9A050]' : 'text-gray-400'">20m ago</span>
                                </div>
                                <p class="text-xs truncate font-medium" :class="activeChannel === 'Daniel Okafor' ? 'text-white/70' : 'text-gray-500'">I will handle catering delivery.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="w-full lg:w-2/3 bg-white/95 backdrop-blur-md rounded-[2.5rem] flex flex-col shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 overflow-hidden min-h-[480px] lg:min-h-0">
            <!-- Chat Header -->
            <div class="p-5 sm:p-6 border-b border-gray-100/80 bg-gradient-to-r from-gray-50/50 via-white to-gray-50/30 flex items-center justify-between">
                <div class="flex items-center space-x-3.5 min-w-0">
                    <img :src="activeAvatar" class="w-11 h-11 rounded-2xl shadow-md shrink-0 border border-gray-100" alt="">
                    <div class="min-w-0">
                        <h3 class="font-extrabold text-[#0A2E2A] text-base truncate" x-text="activeChannel"></h3>
                        <p class="text-xs text-emerald-600 font-bold flex items-center mt-0.5">
                            <span class="w-2 h-2 bg-emerald-500 rounded-full mr-1.5 animate-pulse"></span>
                            <span x-text="activeStatus"></span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Messages Area -->
            <div id="chat-scroll-container" class="flex-grow overflow-y-auto p-5 sm:p-6 space-y-4 custom-scrollbar bg-gray-50/40">
                <div class="flex justify-center mb-2">
                    <span class="px-4 py-1.5 bg-white/90 backdrop-blur-sm rounded-full text-[10px] font-black text-gray-400 uppercase tracking-widest shadow-sm border border-gray-200/70">Today</span>
                </div>

                <template x-for="(msg, idx) in (threads[activeChannel] || [])" :key="idx">
                    <div>
                        <!-- Sent by Me -->
                        <template x-if="msg.isMe">
                            <div class="flex items-start justify-end space-x-3">
                                <div class="space-y-1.5 flex flex-col items-end max-w-lg">
                                    <div class="bg-gradient-to-r from-[#0A2E2A] to-[#0D3833] p-4 rounded-[1.5rem] rounded-tr-xs shadow-md shadow-[#0A2E2A]/10 text-white border border-[#C9A050]/20">
                                        <p class="text-[11px] font-bold text-[#C9A050] mb-0.5">You</p>
                                        <p class="text-xs sm:text-sm font-medium leading-relaxed" x-text="msg.text"></p>
                                    </div>
                                    <div class="flex items-center space-x-1.5 mr-1">
                                        <span class="text-[10px] font-bold text-gray-400" x-text="msg.time"></span>
                                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Received from Team/User -->
                        <template x-if="!msg.isMe">
                            <div class="flex items-start space-x-3 max-w-lg">
                                <img :src="'https://ui-avatars.com/api/?name=' + encodeURIComponent(msg.sender) + '&background=0A2E2A&color=C9A050'" class="w-8 h-8 rounded-xl shrink-0 mt-1 shadow-sm border border-gray-100" alt="">
                                <div class="space-y-1.5 min-w-0">
                                    <div class="bg-white p-4 rounded-[1.5rem] rounded-tl-xs shadow-sm border border-gray-200/70">
                                        <p class="text-[11px] font-bold text-[#0A2E2A] mb-0.5" x-text="msg.sender"></p>
                                        <p class="text-xs sm:text-sm font-medium text-gray-700 leading-relaxed" x-text="msg.text"></p>
                                    </div>
                                    <span class="text-[10px] font-bold text-gray-400 block ml-1" x-text="msg.time"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <!-- Message Input -->
            <div class="p-4 sm:p-5 border-t border-gray-100 bg-white">
                <form @submit.prevent="sendMessage()" class="flex items-center space-x-2 sm:space-x-3 bg-gray-50 rounded-2xl p-1.5 pr-2 border border-gray-200 focus-within:border-[#C9A050] focus-within:ring-2 focus-within:ring-[#C9A050]/20 transition-all">
                    <input type="text" x-model="newMessage" placeholder="Write a message to team..." 
                           class="flex-grow bg-transparent border-none focus:ring-0 text-xs sm:text-sm font-medium py-2 px-3 outline-none text-gray-800">
                    <button type="submit" class="p-2.5 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] hover:from-[#d8b05f] hover:to-[#C9A050] text-[#0A2E2A] font-black rounded-xl shadow-md shadow-[#C9A050]/20 shrink-0 min-h-[40px] min-w-[40px] flex items-center justify-center transition-all hover:scale-105">
                        <svg class="w-4 h-4 transform rotate-90" fill="currentColor" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"></path></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #E5E7EB;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #C9A050;
        }
    </style>
</x-app-layout>