        </main>
    </div>

    <script>
        function toggleMobileMenu() {
            const sidebar = document.getElementById('mobileSidebar');
            const panel = document.getElementById('mobileSidebarPanel');
            
            if (sidebar.classList.contains('opacity-0')) {
                // Open
                sidebar.classList.remove('opacity-0', 'pointer-events-none');
                panel.classList.remove('translate-x-full');
            } else {
                // Close
                sidebar.classList.add('opacity-0', 'pointer-events-none');
                panel.classList.add('translate-x-full');
            }
        }
        
        function toggleDesktopSidebar() {
            const sidebar = document.getElementById('desktopSidebar');
            const icon = document.getElementById('sidebarToggleIcon');
            
            if (sidebar.classList.contains('w-64')) {
                // Collapse
                sidebar.classList.remove('w-64');
                sidebar.classList.add('w-20', 'sidebar-collapsed');
                icon.classList.replace('fa-chevron-left', 'fa-chevron-right');
            } else {
                // Expand
                sidebar.classList.remove('w-20', 'sidebar-collapsed');
                sidebar.classList.add('w-64');
                icon.classList.replace('fa-chevron-right', 'fa-chevron-left');
            }
        }

        // Close mobile sidebar when clicking outside panel
        document.getElementById('mobileSidebar')?.addEventListener('click', function(e) {
            if (e.target === this) {
                toggleMobileMenu();
            }
        });
        
        <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['owner', 'admin', 'kasir'])): ?>
        // Audio element for notification
        const notifSound = new Audio('https://actions.google.com/sounds/v1/alarms/beep_short.ogg');
        
        let lastCheckTime = '<?= date('Y-m-d H:i:s') ?>';
        const checkEndpoint = '<?= APP_URL ?>/admin/check_new_orders';
        
        function checkForNewOrders() {
            fetch(`${checkEndpoint}?since=${encodeURIComponent(lastCheckTime)}`, { credentials: 'same-origin' })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (data.max_order_time) lastCheckTime = data.max_order_time;
                        if (data.new_orders > 0) {
                            // Play sound
                            notifSound.play().catch(e => console.log('Audio autoplay blocked', e));
                            
                            // Show modal with all new orders
                            showOrderModal(data.orders);
                            
                            data.orders.forEach(order => {
                                // Text to Speech Voice Notification
                                if ('speechSynthesis' in window) {
                                    let paymentName = (order.payment_method || 'cash').toLowerCase() === 'qris' ? 'kris' : 'tunai';
                                    let orderType = order.table_number ? `makan di meja ${order.table_number}` : 'dibungkus atau online';
                                    
                                    let text = `Ada pesanan ${orderType} dari kak ${order.customer_name}, bayar pakai ${paymentName}.`;
                                    if (order.proof_of_payment) {
                                        text = `Kak ${order.customer_name} telah mengirim bukti pembayaran kris. Mohon dicek.`;
                                    }
                                    
                                    const utterance = new SpeechSynthesisUtterance(text);
                                    utterance.lang = 'id-ID';
                                    utterance.rate = 0.95;
                                    utterance.pitch = 1.1;
                                    window.speechSynthesis.speak(utterance);
                                }
                            });
                        }
                    }
                })
                .catch(err => console.error('Error checking orders:', err));
        }
        
        function showOrderModal(orders) {
            // Check if modal already exists
            let modal = document.getElementById('globalOrderNotifModal');
            let listContainer = document.getElementById('globalOrderNotifList');
            
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'globalOrderNotifModal';
                modal.className = 'fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-md opacity-0 pointer-events-none transition-opacity duration-300';
                
                modal.innerHTML = `
                    <div class="bg-white rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden transform scale-95 transition-transform duration-300 flex flex-col" id="globalOrderNotifContent">
                        <div class="bg-gradient-to-r from-emerald-500 to-teal-600 p-6 text-center relative">
                            <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-3 shadow-lg border-4 border-emerald-100">
                                <i class="fas fa-bell text-4xl text-emerald-500 animate-bounce"></i>
                            </div>
                            <h2 class="text-2xl font-extrabold text-white font-outfit drop-shadow-md">Pesanan Baru Masuk!</h2>
                            <p class="text-emerald-100 font-medium mt-1">Segera proses pesanan di bawah ini</p>
                        </div>
                        
                        <div class="p-6 bg-slate-50 max-h-[50vh] overflow-y-auto" id="globalOrderNotifList">
                            <!-- Order items will be injected here -->
                        </div>
                        
                        <div class="p-6 bg-white border-t border-slate-100 flex flex-col gap-3">
                            <button onclick="window.location.href='<?= APP_URL ?>/admin/orders'" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3.5 px-4 rounded-xl shadow-md transition-colors flex items-center justify-center gap-2">
                                Buka Halaman Orders <i class="fas fa-arrow-right"></i>
                            </button>
                            <button onclick="closeGlobalOrderModal()" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-3.5 px-4 rounded-xl transition-colors">
                                Tutup & Lanjutkan Nanti
                            </button>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
                listContainer = document.getElementById('globalOrderNotifList');
            }
            
            // Add orders to list
            orders.forEach(order => {
                const onum = order.order_number;
                const formattedNum = onum.substring(0,3) + '-' + onum.substring(3,11) + '-' + onum.substring(11);
                
                const item = document.createElement('div');
                item.className = 'bg-white border border-slate-200 rounded-2xl p-4 mb-3 shadow-sm border-l-4 border-l-emerald-500';
                item.innerHTML = `
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="font-extrabold text-slate-800 text-lg font-outfit mb-1 tracking-wide">#${formattedNum}</h4>
                            <p class="text-sm text-slate-600 mb-1"><i class="fas fa-user text-slate-400 mr-2"></i>${order.customer_name}</p>
                            <span class="inline-block bg-slate-100 text-slate-600 text-xs font-bold px-2 py-1 rounded-md">
                                <i class="fas fa-money-bill-wave text-emerald-500 mr-1"></i> ${(order.payment_method || 'CASH').toUpperCase()}
                            </span>
                        </div>
                        ${order.table_number ? `<div class="bg-emerald-50 text-emerald-700 font-bold px-3 py-1.5 rounded-lg text-sm border border-emerald-100"><i class="fas fa-chair mr-1"></i>Meja ${order.table_number}</div>` : `<div class="bg-orange-50 text-orange-700 font-bold px-3 py-1.5 rounded-lg text-sm border border-orange-100"><i class="fas fa-shopping-bag mr-1"></i>Take Away</div>`}
                    </div>
                `;
                listContainer.prepend(item); // Add to top
            });
            
            // Show modal
            modal.classList.remove('opacity-0', 'pointer-events-none');
            document.getElementById('globalOrderNotifContent').classList.remove('scale-95');
        }
        
        window.closeGlobalOrderModal = function() {
            const modal = document.getElementById('globalOrderNotifModal');
            if (modal) {
                modal.classList.add('opacity-0', 'pointer-events-none');
                document.getElementById('globalOrderNotifContent').classList.add('scale-95');
                // Clear the list after closing
                setTimeout(() => {
                    document.getElementById('globalOrderNotifList').innerHTML = '';
                }, 300);
            }
        };
        
        // Poll every 1 second for a realtime feel
        setInterval(checkForNewOrders, 1000);
        <?php endif; ?>
    </script>
</body>
</html>
