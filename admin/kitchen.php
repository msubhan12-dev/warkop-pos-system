<?php
require_once '../config/config.php';
requireRole(['owner', 'admin', 'pelayan']);

$pageTitle = 'Dapur';
include '../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-slate-50 to-slate-100">
    <div class="max-w-2xl w-full">
        <!-- Coming Soon Container -->
        <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden">
            <!-- Header Section with Icon -->
            <div class="bg-gradient-to-r from-orange-500 to-red-500 px-6 sm:px-8 py-12 sm:py-16 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 sm:w-24 sm:h-24 bg-white rounded-2xl mb-6 shadow-lg">
                    <i class="fas fa-fire text-4xl sm:text-5xl text-orange-500"></i>
                </div>
                <h1 class="text-3xl sm:text-4xl font-black text-white font-outfit mb-3 tracking-tight">Menu Dapur</h1>
                <p class="text-white/90 text-sm sm:text-base font-medium">Kitchen Management System</p>
            </div>

            <!-- Coming Soon Content -->
            <div class="px-6 sm:px-8 py-12 sm:py-16 text-center">
                <div class="mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-orange-100 rounded-full mb-6">
                        <i class="fas fa-hourglass-end text-2xl text-orange-600"></i>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 font-outfit mb-3">Coming Soon</h2>
                    <p class="text-slate-600 text-sm sm:text-base leading-relaxed max-w-md mx-auto">
                        Kami sedang menyiapkan fitur manajemen dapur yang canggih untuk mengelola pesanan dan alur produksi dengan lebih efisien.
                    </p>
                </div>

                <!-- Features Preview -->
                <div class="bg-gradient-to-r from-orange-50 to-red-50 rounded-2xl p-6 sm:p-8 mb-8 border border-orange-200">
                    <h3 class="font-bold text-slate-800 mb-4 text-sm uppercase tracking-wide">Fitur yang Akan Hadir</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-left">
                        <div class="flex items-start space-x-3">
                            <div class="w-6 h-6 rounded-full bg-orange-500 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <i class="fas fa-check text-white text-xs"></i>
                            </div>
                            <span class="text-slate-700 text-sm font-medium">Antrian Pesanan Real-time</span>
                        </div>
                        <div class="flex items-start space-x-3">
                            <div class="w-6 h-6 rounded-full bg-orange-500 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <i class="fas fa-check text-white text-xs"></i>
                            </div>
                            <span class="text-slate-700 text-sm font-medium">Status Update Otomatis</span>
                        </div>
                        <div class="flex items-start space-x-3">
                            <div class="w-6 h-6 rounded-full bg-orange-500 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <i class="fas fa-check text-white text-xs"></i>
                            </div>
                            <span class="text-slate-700 text-sm font-medium">Notifikasi Pesanan Baru</span>
                        </div>
                        <div class="flex items-start space-x-3">
                            <div class="w-6 h-6 rounded-full bg-orange-500 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <i class="fas fa-check text-white text-xs"></i>
                            </div>
                            <span class="text-slate-700 text-sm font-medium">Manajemen Prioritas</span>
                        </div>
                    </div>
                </div>

                <!-- Back Button -->
                <a href="index.php" class="inline-flex items-center justify-center bg-gradient-to-r from-slate-800 to-slate-900 hover:from-slate-900 hover:to-black text-white font-bold px-8 py-3.5 rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Kembali ke Dashboard
                </a>
            </div>

            <!-- Footer Note -->
            <div class="bg-slate-50 border-t border-slate-200 px-6 sm:px-8 py-4 text-center">
                <p class="text-xs text-slate-500 font-medium">
                    <i class="fas fa-info-circle mr-2 text-slate-400"></i>
                    Fitur ini akan segera tersedia dalam update mendatang
                </p>
            </div>
        </div>

        <!-- Decorative Elements -->
        <div class="mt-12 text-center">
            <div class="inline-flex items-center space-x-2 text-slate-400">
                <i class="fas fa-utensils text-lg"></i>
                <span class="text-sm font-medium">Warkop OS - Kitchen Management</span>
                <i class="fas fa-utensils text-lg"></i>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
