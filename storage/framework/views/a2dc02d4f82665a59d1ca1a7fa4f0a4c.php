<?php
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\Store;
use App\Services\QueueWorkerManager;
?>

<div class="min-h-screen bg-[#f4f4f5] text-zinc-950">



    

    <div class="pointer-events-none fixed inset-0 overflow-hidden">

        <div class="absolute -left-32 -top-32 h-96 w-96 rounded-full bg-red-500/[0.045] blur-3xl"></div>

        <div class="absolute -bottom-48 right-0 h-[32rem] w-[32rem] rounded-full bg-zinc-900/[0.035] blur-3xl"></div>

    </div>



    

    <div id="mobileOverlay" class="dashboard-overlay"></div>



    <aside id="dashboardSidebar" class="dashboard-sidebar">



        

        <div class="sidebar-glow sidebar-glow-red"></div>

        <div class="sidebar-glow sidebar-glow-white"></div>



        

        <div class="sidebar-brand">

            <div class="brand-logo-wrap">

                <img

                    src="<?php echo e(asset('images/logo-rizky-moto-shop.png')); ?>"

                    alt="Rizky Moto Shop"

                    class="brand-logo"

                >

            </div>



            <button

                type="button"

                class="mobile-close"

                onclick="closeMobileSidebar()"

                aria-label="Close sidebar"

            >

                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">

                    <path d="M6 6l12 12M18 6 6 18"/>

                </svg>

            </button>

        </div>



        

        <div class="sidebar-scroll">



            <div class="nav-section">

                <div class="nav-heading">Workspace</div>



                <div class="nav-list">

                    <button type="button" class="nav-item <?php echo e($activeSection === 'dashboard' ? 'active' : ''); ?>" wire:click="openDashboard">

                        <span class="nav-icon">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">

                                <rect x="3" y="3" width="7" height="7" rx="1.5"/>

                                <rect x="14" y="3" width="7" height="7" rx="1.5"/>

                                <rect x="3" y="14" width="7" height="7" rx="1.5"/>

                                <rect x="14" y="14" width="7" height="7" rx="1.5"/>

                            </svg>

                        </span>

                        <span class="nav-label">Dashboard</span>

                    </button>



                    <button type="button" class="nav-item">

                        <span class="nav-icon">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">

                                <path d="m12 3 1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Z"/>

                                <path d="m19 16 .8 2.2L22 19l-2.2.8L19 22l-.8-2.2L16 19l2.2-.8L19 16Z"/>

                            </svg>

                        </span>

                        <span class="nav-label">Product Generator</span>

                        <span class="nav-pill">AI</span>

                    </button>

                </div>

            </div>



            <div class="nav-section">

                <div class="nav-heading">Management</div>



                <div class="nav-list">

                    <button type="button" class="nav-item <?php echo e($activeSection === 'stores' ? 'active' : ''); ?>" wire:click="openStore">

                        <span class="nav-icon">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">

                                <path d="M4 10h16"/>

                                <path d="M5 10v9h14v-9"/>

                                <path d="M3 10 5 4h14l2 6"/>

                                <path d="M8 14h8"/>

                            </svg>

                        </span>

                        <span class="nav-label">Stores</span>

                    </button>



                    <button

                        type="button"

                        class="nav-item <?php echo e($activeSection === 'templates' ? 'active' : ''); ?>"

                        wire:click="openTemplates"

                        wire:loading.attr="disabled"

                        wire:target="openTemplates"

                    >

                        <span class="nav-icon">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">

                                <rect x="3" y="3" width="7" height="7" rx="1.5"/>

                                <rect x="14" y="3" width="7" height="7" rx="1.5"/>

                                <rect x="3" y="14" width="18" height="7" rx="1.5"/>

                            </svg>

                        </span>

                        <span class="nav-label">Templates</span>

                    </button>



                    <button type="button" class="nav-item" :class="{ active: $wire.activeSection === 'generator' }" wire:click="openGenerator">

                        <span class="nav-icon">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">

                                <rect x="3" y="3" width="18" height="18" rx="3"/>

                                <path d="m7 16 4-4 3 3 3-5"/>

                            </svg>

                        </span>

                        <span class="nav-label">Generations</span>

                    </button>



                    <button type="button" class="nav-item">

                        <span class="nav-icon">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">

                                <path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9L12 3Z"/>

                            </svg>

                        </span>

                        <span class="nav-label">Favorites</span>

                    </button>

                </div>

            </div>



            

            <div class="nav-section rms-settings-nav">

                <div class="nav-heading">System</div>



                <div class="nav-list">



                    <button

                        type="button"

                        class="nav-item <?php echo e(str_starts_with($activeSection, 'settings-') ? 'active' : ''); ?>"

                        wire:click="openSettings"

                        wire:loading.attr="disabled"

                        wire:target="openSettings"

                    >

                        <span class="nav-icon">

                            <svg

                                viewBox="0 0 24 24"

                                fill="none"

                                stroke="currentColor"

                                stroke-width="1.7"

                            >

                                <path d="M12 3v2"/>

                                <path d="M12 19v2"/>

                                <path d="m4.2 4.2 1.4 1.4"/>

                                <path d="m18.4 18.4 1.4 1.4"/>

                                <path d="M3 12h2"/>

                                <path d="M19 12h2"/>

                                <path d="m4.2 19.8 1.4-1.4"/>

                                <path d="m18.4 5.6 1.4-1.4"/>

                                <circle cx="12" cy="12" r="4"/>

                            </svg>

                        </span>



                        <span class="nav-label">

                            Settings

                        </span>

                    </button>



                </div>

            </div>



        </div>



        
        <div class="sidebar-api">
            <div class="api-status-card worker-status-card">
                <div class="api-status-top">
                    <div class="api-brand">
                        <span class="api-mark">AI</span>
                        <div>
                            <div class="api-title">AI Engine</div>
                            <div class="api-subtitle">Creative generation</div>
                        </div>
                    </div>
                    <span class="api-live-dot <?php echo e($this->workerStatus['running'] ? 'worker-live' : 'worker-offline'); ?>" aria-hidden="true"></span>
                </div>

                <div class="worker-mini-divider"></div>

                <div class="worker-status-line">
                    <span class="api-status-dot <?php echo e($this->workerStatus['running'] ? 'is-connected' : 'is-error'); ?>"></span>
                    <div class="worker-status-copy">
                        <strong><?php echo e($this->workerStatus['running'] ? 'Worker Running' : 'Worker Stopped'); ?></strong>
                        <span><?php echo e($this->workerStatus['running'] ? 'Queue siap memproses generation' : 'Generation queue sedang berhenti'); ?></span>
                    </div>
                    <span class="api-status-badge"><?php echo e($this->workerStatus['running'] ? 'LIVE' : 'OFF'); ?></span>
                </div>

                <div class="worker-meta-grid">
                    <div>
                        <small>QUEUE</small>
                        <strong><?php echo e($this->workerStatus['queue']); ?></strong>
                    </div>
                    <div>
                        <small>PID</small>
                        <strong><?php echo e($this->workerStatus['pid'] ?: '—'); ?></strong>
                    </div>
                </div>

                <div class="worker-actions">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->workerStatus['running']): ?>
                        <button type="button" class="worker-action worker-action-restart"
                            wire:click="restartQueueWorker" wire:loading.attr="disabled" wire:target="restartQueueWorker">
                            <span wire:loading.remove wire:target="restartQueueWorker">↻</span>
                            <span wire:loading wire:target="restartQueueWorker" class="worker-spinner">◌</span>
                            Restart
                        </button>
                        <button type="button" class="worker-action worker-action-stop"
                            wire:click="stopQueueWorker" wire:loading.attr="disabled" wire:target="stopQueueWorker">
                            <span wire:loading.remove wire:target="stopQueueWorker">■</span>
                            <span wire:loading wire:target="stopQueueWorker" class="worker-spinner">◌</span>
                            Stop
                        </button>
                    <?php else: ?>
                        <button type="button" class="worker-action worker-action-start"
                            wire:click="startQueueWorker" wire:loading.attr="disabled" wire:target="startQueueWorker">
                            <span wire:loading.remove wire:target="startQueueWorker">▶</span>
                            <span wire:loading wire:target="startQueueWorker" class="worker-spinner">◌</span>
                            Start Worker
                        </button>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->workerStatus['running'] && $this->workerStatus['started_at']): ?>
                    <div class="worker-started-at">Started <?php echo e($this->workerStatus['started_at']); ?></div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <div class="worker-api-footer">
                    <button type="button" class="worker-refresh" wire:click="refreshWorkerStatus" wire:loading.attr="disabled" wire:target="refreshWorkerStatus" title="Refresh worker status" aria-label="Refresh worker status">
                        <span wire:loading.remove wire:target="refreshWorkerStatus">↻</span>
                        <span wire:loading wire:target="refreshWorkerStatus" class="worker-spinner">◌</span>
                    </button>
                    <span>
                        <i class="worker-footer-dot <?php echo e($this->openAiStatus['state'] === 'connected' ? 'is-online' : ''); ?>"></i>
                        API <?php echo e($this->openAiStatus['label']); ?>

                    </span>
                    <span class="worker-step"><?php echo e($this->workerStatus['running'] ? 'ENGINE READY' : 'ENGINE PAUSED'); ?></span>
                </div>
            </div>
        </div>

        </aside>





    

    <div class="dashboard-main">



        

        <header class="topbar">

            <div class="topbar-left">



                <button

                    type="button"

                    class="mobile-menu"

                    onclick="openMobileSidebar()"

                    aria-label="Open sidebar"

                >

                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">

                        <path d="M4 6h16M4 12h16M4 18h16"/>

                    </svg>

                </button>



                <div class="topbar-title">

                    <div class="topbar-eyebrow">

                        <span></span>

                        Workspace

                    </div>

                    <div class="topbar-heading">AI Creative Dashboard</div>

                </div>

            </div>



            <div class="topbar-right">



                <div class="top-search">

                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">

                        <circle cx="11" cy="11" r="7"/>

                        <path d="m20 20-4-4"/>

                    </svg>

                    <input type="text" placeholder="Search workspace...">

                    <span class="search-shortcut">⌘ K</span>

                </div>



                

                <div class="notification-menu" id="notificationMenu">

                    <button

                        type="button"

                        class="topbar-button notification-button"

                        id="notificationButton"

                        aria-label="Notifications"

                        aria-expanded="false"

                        aria-haspopup="true"

                    >

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">

                            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>

                            <path d="M10 21h4"/>

                        </svg>

                        <span class="notification-dot"></span>

                        <span class="notification-count">4</span>

                    </button>



                    <div

                        class="notification-dropdown"

                        id="notificationDropdown"

                        aria-hidden="true"

                    >

                        <div class="notification-dropdown-head">

                            <div>

                                <span class="notification-eyebrow">SYSTEM CENTER</span>

                                <h3>Notifikasi</h3>

                                <p>Update terbaru dari workspace kamu.</p>

                            </div>



                            <button type="button" class="notification-mark-read" id="notificationMarkRead">

                                Tandai dibaca

                            </button>

                        </div>



                        <div class="notification-list">

                            <button type="button" class="notification-item is-unread">

                                <span class="notification-item-icon notification-icon-red">

                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">

                                        <path d="m12 3 1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Z"/>

                                        <path d="m19 16 .8 2.2L22 19l-2.2.8L19 22l-.8-2.2L16 19l2.2-.8L19 16Z"/>

                                    </svg>

                                </span>

                                <span class="notification-item-content">

                                    <strong>AI Creative Engine siap digunakan</strong>

                                    <span>Workspace visual berhasil dimuat dan siap untuk proses berikutnya.</span>

                                    <small>Baru saja</small>

                                </span>

                                <span class="notification-unread-dot"></span>

                            </button>



                            <button type="button" class="notification-item is-unread">

                                <span class="notification-item-icon notification-icon-green">

                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">

                                        <path d="M20 6 9 17l-5-5"/>

                                    </svg>

                                </span>

                                <span class="notification-item-content">

                                    <strong>OpenAI API status: Ready</strong>

                                    <span>Koneksi engine kreatif tersedia untuk tahap konfigurasi.</span>

                                    <small>2 menit lalu</small>

                                </span>

                                <span class="notification-unread-dot"></span>

                            </button>



                            <button type="button" class="notification-item">

                                <span class="notification-item-icon notification-icon-dark">

                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">

                                        <rect x="3" y="3" width="18" height="18" rx="3"/>

                                        <path d="M7 8h10M7 12h7M7 16h5"/>

                                    </svg>

                                </span>

                                <span class="notification-item-content">

                                    <strong>Template workspace diperbarui</strong>

                                    <span>Template visual baru akan muncul di Template Library.</span>

                                    <small>18 menit lalu</small>

                                </span>

                            </button>



                            <button type="button" class="notification-item">

                                <span class="notification-item-icon notification-icon-violet">

                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">

                                        <path d="M12 3v18M3 12h18"/>

                                        <circle cx="12" cy="12" r="8"/>

                                    </svg>

                                </span>

                                <span class="notification-item-content">

                                    <strong>Dashboard berhasil diperbarui</strong>

                                    <span>UI workspace Rizky Moto Shop menggunakan tampilan terbaru.</span>

                                    <small>1 jam lalu</small>

                                </span>

                            </button>

                        </div>



                        <div class="notification-dropdown-footer">

                            <span><i></i> Internal workspace</span>

                            <button type="button" id="notificationClose">Tutup</button>

                        </div>

                    </div>

                </div>



                <div class="topbar-divider"></div>



                

                <div class="profile-menu" id="profileMenu">



                    <button

                        type="button"

                        class="profile-button"

                        id="profileButton"

                        aria-expanded="false"

                        aria-haspopup="true"

                    >

                        <span class="profile-avatar">

                            <?php echo e($this->userInitials); ?>


                        </span>



                        <span class="profile-info">

                            <span class="profile-name">

                                <?php echo e($this->user?->name ?? 'User'); ?>


                            </span>



                            <span class="profile-role">

                                Administrator

                            </span>

                        </span>



                        <svg

                            class="profile-chevron"

                            id="profileChevron"

                            viewBox="0 0 24 24"

                            fill="none"

                            stroke="currentColor"

                            stroke-width="1.8"

                        >

                            <path d="m6 9 6 6 6-6"/>

                        </svg>

                    </button>



                    <div

                        class="profile-dropdown"

                        id="profileDropdown"

                        aria-hidden="true"

                    >

                        <div class="profile-dropdown-header">

                            <div class="profile-dropdown-avatar">

                                <?php echo e($this->userInitials); ?>


                            </div>



                            <div class="profile-dropdown-identity">

                                <strong>

                                    <?php echo e($this->user?->name ?? 'User'); ?>


                                </strong>



                                <span>

                                    <?php echo e($this->user?->email ?? '-'); ?>


                                </span>

                            </div>

                        </div>



                        <div class="profile-dropdown-divider"></div>



                        <button

                            type="button"

                            class="profile-dropdown-item"

                            data-profile-settings

                        >

                            <span class="profile-dropdown-icon">

                                <svg

                                    viewBox="0 0 24 24"

                                    fill="none"

                                    stroke="currentColor"

                                    stroke-width="1.7"

                                >

                                    <circle cx="12" cy="12" r="3"/>

                                    <path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-1.8 1.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5v.2h-2.5v-.2a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1-1.8-1.8.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H6.5v-2.5h.2a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1 1.8-1.8.1.1a1.7 1.7 0 0 0 1.9.3 1.7 1.7 0 0 0 1-1.5V4h2.5v.2a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1 1.8 1.8-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0-1.5 1h.2v2.5h-.2a1.7 1.7 0 0 0-1.5 1.4Z"/>

                                </svg>

                            </span>



                            <span class="profile-dropdown-text">

                                <strong>Pengaturan Akun</strong>

                                <small>Kelola profil dan akun</small>

                            </span>

                        </button>



                        <div class="profile-dropdown-divider"></div>



                        <form

                            method="POST"

                            action="<?php echo e(route('logout')); ?>"

                            class="profile-logout-form"

                        >

                            <?php echo csrf_field(); ?>



                            <button

                                type="submit"

                                class="profile-dropdown-item profile-logout"

                            >

                                <span class="profile-dropdown-icon">

                                    <svg

                                        viewBox="0 0 24 24"

                                        fill="none"

                                        stroke="currentColor"

                                        stroke-width="1.7"

                                    >

                                        <path d="M10 17l5-5-5-5"/>

                                        <path d="M15 12H3"/>

                                        <path d="M21 4v16"/>

                                    </svg>

                                </span>



                                <span class="profile-dropdown-text">

                                    <strong>Keluar</strong>

                                    <small>Keluar dari akun internal</small>

                                </span>

                            </button>

                        </form>

                    </div>

                </div>



            </div>

        </header>





        <main class="dashboard-content">



            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activeSection === 'dashboard'): ?>

            <div class="rms-content-enter" <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'workspace-dashboard'; ?>wire:key="workspace-dashboard">







            

            <section class="workspace-intro reveal reveal-1">



                <div class="workspace-intro-copy">

                    <div class="eyebrow-label">

                        <span class="eyebrow-dot"></span>

                        INTERNAL CREATIVE WORKSPACE

                    </div>



                    <h1 class="page-title">

                        Good afternoon,

                        <span><?php echo e($this->user?->name ?? 'User'); ?>.</span>

                    </h1>



                    <p class="page-description">

                        Satu workspace terpusat untuk mengelola visual produk,

                        Store, Template, dan aktivitas AI Rizky Moto Shop.

                    </p>



                    <div class="workspace-meta">

                        <span class="workspace-meta-item">

                            <i></i>

                            Workspace active

                        </span>



                        <span class="workspace-meta-divider"></span>



                        <span class="workspace-meta-item muted">

                            Last sync just now

                        </span>

                    </div>

                </div>



                <div class="workspace-intro-status">

                    <span class="system-status-dot"></span>



                    <div>

                        <span class="system-status-title">All Systems</span>

                        <span class="system-status-subtitle">Operational</span>

                    </div>



                    <span class="system-status-arrow">↗</span>

                </div>



            </section>





            

            <section class="premium-hero reveal reveal-2">



                <div class="premium-hero-grid"></div>

                <div class="premium-hero-noise"></div>



                <div class="premium-hero-glow premium-hero-glow-red"></div>

                <div class="premium-hero-glow premium-hero-glow-white"></div>



                <div class="premium-hero-content">



                    <div class="hero-kicker">

                        <span class="hero-kicker-dot"></span>

                        AI CREATIVE ENGINE

                    </div>



                    <h2 class="premium-hero-title">

                        From product photo

                        <span>to marketplace-ready.</span>

                    </h2>



                    <p class="premium-hero-description">

                        Siapkan bahan visual, gunakan preset Template,

                        lalu biarkan creative engine membantu menghasilkan

                        visual produk yang konsisten.

                    </p>



                    <div class="premium-hero-actions">

                        <button type="button" class="hero-primary">

                            <span class="sparkle-icon">✦</span>

                            Product Generator

                            <span class="button-arrow">→</span>

                        </button>



                        <button type="button" class="hero-secondary">

                            Explore Workspace

                            <span>↗</span>

                        </button>

                    </div>



                    <div class="hero-trust-row">

                        <span class="hero-trust-item">

                            <i></i>

                            Internal workspace

                        </span>



                        <span class="hero-trust-item">

                            <i></i>

                            Template based

                        </span>



                        <span class="hero-trust-item">

                            <i></i>

                            AI ready

                        </span>

                    </div>



                </div>





                

                <div class="ai-engine-visual">



                    <div class="engine-orbit engine-orbit-a"></div>

                    <div class="engine-orbit engine-orbit-b"></div>

                    <div class="engine-orbit engine-orbit-c"></div>



                    <div class="engine-scan-line"></div>



                    <div class="engine-core">

                        <div class="engine-core-ring"></div>

                        <div class="engine-core-inner">

                            <span class="engine-core-label">AI</span>

                            <small>ENGINE</small>

                        </div>

                    </div>



                    <div class="engine-node engine-node-img">

                        <span class="engine-node-icon">IMG</span>

                        <small>Product</small>

                    </div>



                    <div class="engine-node engine-node-style">

                        <span class="engine-node-icon">✦</span>

                        <small>Style</small>

                    </div>



                    <div class="engine-node engine-node-shop">

                        <span class="engine-node-icon">▣</span>

                        <small>Store</small>

                    </div>



                    <div class="engine-connection engine-connection-1"></div>

                    <div class="engine-connection engine-connection-2"></div>

                    <div class="engine-connection engine-connection-3"></div>



                </div>



                <div class="premium-hero-footer">

                    <span>RIZKY MOTO SHOP</span>

                    <span class="premium-hero-footer-line"></span>

                    <span>CREATIVE SYSTEM / 01</span>

                </div>



            </section>





            

            <section class="dashboard-section-heading reveal reveal-3">

                <div>

                    <span class="section-kicker">WORKSPACE OVERVIEW</span>

                    <h2>Creative activity</h2>

                </div>



                <span class="section-live">

                    <i></i>

                    LIVE DATA

                </span>

            </section>





            <section class="premium-stats-grid reveal reveal-3">



                

                <article class="premium-stat-card premium-stat-red">



                    <div class="premium-stat-top">

                        <div class="premium-stat-icon">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">

                                <path d="M4 10h16"/>

                                <path d="M5 10v9h14v-9"/>

                                <path d="M3 10 5 4h14l2 6"/>

                            </svg>

                        </div>



                        <span class="premium-stat-status live">

                            <i></i>

                            LIVE

                        </span>

                    </div>



                    <div class="premium-stat-label">Active Stores</div>

                    <div class="premium-stat-value"><?php echo e(str_pad((string) $this->activeStores, 2, '0', STR_PAD_LEFT)); ?></div>



                    <div class="premium-stat-bottom">

                        <span>Connected marketplace stores</span>

                        <strong><?php echo e($this->totalStores > 0 ? round(($this->activeStores / $this->totalStores) * 100) : 0); ?>%</strong>

                    </div>



                    <div class="premium-stat-progress">

                        <span style="width: <?php echo e($this->totalStores > 0 ? round(($this->activeStores / $this->totalStores) * 100) : 0); ?>%"></span>

                    </div>



                </article>





                

                <article class="premium-stat-card">



                    <div class="premium-stat-top">

                        <div class="premium-stat-icon violet">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">

                                <rect x="3" y="3" width="7" height="7" rx="1.5"/>

                                <rect x="14" y="3" width="7" height="7" rx="1.5"/>

                                <rect x="3" y="14" width="18" height="7" rx="1.5"/>

                            </svg>

                        </div>



                        <span class="premium-stat-status">

                            READY

                        </span>

                    </div>



                    <div class="premium-stat-label">Templates</div>

                    <div class="premium-stat-value"><?php echo e($this->totalTemplates); ?></div>



                    <div class="premium-stat-bottom">

                        <span>Configured visual templates</span>

                        <strong><?php echo e($this->totalTemplates); ?> total</strong>

                    </div>



                    <div class="premium-stat-progress violet">

                        <span style="width: <?php echo e($this->totalTemplates > 0 ? 100 : 0); ?>%"></span>

                    </div>



                </article>





                

                <article class="premium-stat-card">



                    <div class="premium-stat-top">

                        <div class="premium-stat-icon orange">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">

                                <rect x="3" y="3" width="18" height="18" rx="3"/>

                                <path d="m7 16 4-4 3 3 3-5"/>

                            </svg>

                        </div>



                        <span class="premium-stat-status">

                            TOTAL

                        </span>

                    </div>



                    <div class="premium-stat-label">Generations</div>

                    <div class="premium-stat-value"><?php echo e($this->totalGenerations); ?></div>



                    <div class="premium-stat-bottom">

                        <span>Visuals generated</span>

                        <strong>LIVE</strong>

                    </div>



                    <div class="premium-stat-progress orange">

                        <span style="width: <?php echo e($this->totalGenerations > 0 ? 100 : 0); ?>%"></span>

                    </div>



                </article>





                

                <article class="premium-stat-card">



                    <div class="premium-stat-top">

                        <div class="premium-stat-icon green">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">

                                <path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9L12 3Z"/>

                            </svg>

                        </div>



                        <span class="premium-stat-status">

                            SAVED

                        </span>

                    </div>



                    <div class="premium-stat-label">Favorites</div>

                    <div class="premium-stat-value"><?php echo e($this->favoriteCount); ?></div>



                    <div class="premium-stat-bottom">

                        <span>Saved visual results</span>

                        <strong><?php echo e($this->favoriteCount); ?> saved</strong>

                    </div>



                    <div class="premium-stat-progress green">

                        <span style="width: <?php echo e($this->favoriteCount > 0 ? 100 : 0); ?>%"></span>

                    </div>



                </article>



            </section>





            

            <section class="premium-lower-grid reveal reveal-4">



                

                <div class="premium-content-card quick-card">



                    <div class="premium-card-heading">

                        <div>

                            <span class="section-kicker">WORKSPACE</span>

                            <h3>Quick access</h3>

                            <p>Shortcut untuk aktivitas yang paling sering digunakan.</p>

                        </div>



                        <span class="card-index">01</span>

                    </div>





                    <div class="premium-quick-list">



                        <div class="premium-quick-item">

                            <div class="premium-quick-icon red">

                                <span>✦</span>

                            </div>



                            <div class="premium-quick-copy">

                                <strong>Product Generator</strong>

                                <span>Create a new product visual</span>

                            </div>



                            <span class="premium-quick-arrow">↗</span>

                        </div>





                        <div class="premium-quick-item">

                            <div class="premium-quick-icon dark">

                                <span>▣</span>

                            </div>



                            <div class="premium-quick-copy">

                                <strong>Store Management</strong>

                                <span>Manage marketplace identity</span>

                            </div>



                            <span class="premium-quick-arrow">↗</span>

                        </div>





                        <div class="premium-quick-item">

                            <div class="premium-quick-icon violet">

                                <span>▦</span>

                            </div>



                            <div class="premium-quick-copy">

                                <strong>Template Library</strong>

                                <span>Manage visual presets</span>

                            </div>



                            <span class="premium-quick-arrow">↗</span>

                        </div>



                    </div>



                </div>





                

                <div class="premium-content-card activity-card">



                    <div class="premium-card-heading">

                        <div>

                            <span class="section-kicker">ACTIVITY</span>

                            <h3>Recent generations</h3>

                            <p>Visual terbaru yang dibuat dari creative engine.</p>

                        </div>



                        <button type="button" class="premium-view-all">

                            View all

                            <span>→</span>

                        </button>

                    </div>





                    <div class="premium-activity-empty">



                        <div class="activity-visual">



                            <div class="activity-visual-ring ring-one"></div>

                            <div class="activity-visual-ring ring-two"></div>



                            <div class="activity-visual-core">

                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">

                                    <rect x="3" y="3" width="18" height="18" rx="3"/>

                                    <path d="m7 16 4-4 3 3 3-5"/>

                                </svg>

                            </div>



                            <span class="activity-node activity-node-one">IMG</span>

                            <span class="activity-node activity-node-two">AI</span>

                            <span class="activity-node activity-node-three">SHOP</span>



                        </div>



                        <div class="premium-empty-copy">

                            <strong>Your creative space is ready.</strong>

                            <span>

                                Generated visuals will appear here once the

                                AI generator starts creating results.

                            </span>

                        </div>



                        <div class="premium-empty-status">

                            <i></i>

                            Waiting for first generation

                        </div>



                    </div>



                </div>



            </section>





            

            <section class="premium-system-strip reveal reveal-5">



                <div class="premium-system-brand">



                    <div class="mini-logo">

                        <img

                            src="<?php echo e(asset('images/logo-rizky-moto-shop.png')); ?>"

                            alt="Rizky Moto Shop"

                        >

                    </div>



                    <div>

                        <strong>Rizky Moto Shop AI Tools</strong>

                        <span>Internal creative workspace</span>

                    </div>



                </div>





                <div class="premium-system-info">



                    <span class="premium-system-online">

                        <i></i>

                        System online

                    </span>



                    <span class="premium-system-divider"></span>



                    <span class="premium-system-version">

                        OPENAI: <?php echo e($this->openAiStatus['label']); ?>


                    </span>



                </div>





            </section>

            </div>



            <?php elseif($activeSection === 'stores'): ?>

                <div

                    class="workspace-section-shell rms-content-enter"

                    <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'workspace-stores'; ?>wire:key="workspace-stores"

                >

                    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('stores', []);

$__keyOuter = $__key ?? null;

$__key = null;
$__componentSlots = [];

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-1040938067-0', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key, $__componentSlots);

echo $__html;

unset($__html);
unset($__key);
$__key = $__keyOuter;
unset($__keyOuter);
unset($__name);
unset($__params);
unset($__componentSlots);
unset($__split);
?>

                </div>



            <?php elseif($activeSection === 'templates'): ?>

                <div

                    class="workspace-section-shell rms-content-enter"

                    <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'workspace-templates'; ?>wire:key="workspace-templates"

                >

                    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('dashboard.templates.index', []);

$__keyOuter = $__key ?? null;

$__key = null;
$__componentSlots = [];

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-1040938067-1', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key, $__componentSlots);

echo $__html;

unset($__html);
unset($__key);
$__key = $__keyOuter;
unset($__keyOuter);
unset($__name);
unset($__params);
unset($__componentSlots);
unset($__split);
?>

                </div>



            <?php elseif($activeSection === 'generator'): ?>

                <div

                    class="workspace-section-shell rms-content-enter"

                    <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'workspace-generator'; ?>wire:key="workspace-generator"

                >

                    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('dashboard.generator.index', []);

$__keyOuter = $__key ?? null;

$__key = null;
$__componentSlots = [];

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-1040938067-2', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key, $__componentSlots);

echo $__html;

unset($__html);
unset($__key);
$__key = $__keyOuter;
unset($__keyOuter);
unset($__name);
unset($__params);
unset($__componentSlots);
unset($__split);
?>

                </div>



            <?php elseif(in_array($activeSection, ['settings-general', 'settings-openai'], true)): ?>

                <div

                    class="workspace-section-shell rms-content-enter rms-settings-section-shell"

                    <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'workspace-settings'; ?>wire:key="workspace-settings"

                >

                    <?php echo $__env->make('livewire.dashboard.settings.index', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                </div>

            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>



        </main>

    </div>



    

    <template x-teleport="<?php echo e('body'); ?>">

        <div

            x-data="{

                visible: false,

                type: 'success',

                title: '',

                message: '',

                duration: 4500,

                progress: 100,

                timer: null,

                progressTimer: null,

                showToast(detail = {}) {

                    clearTimeout(this.timer);

                    clearInterval(this.progressTimer);

                    this.type = detail.type || 'success';

                    this.title = detail.title || 'Berhasil';

                    this.message = detail.message || '';

                    this.duration = Number(detail.duration ?? 4500);

                    this.progress = 100;

                    this.visible = true;

                    if (this.duration <= 0) return;

                    const started = Date.now();

                    this.progressTimer = setInterval(() => {

                        this.progress = Math.max(0, 100 - ((Date.now() - started) / this.duration) * 100);

                    }, 40);

                    this.timer = setTimeout(() => this.hideToast(), this.duration);

                },

                hideToast() {

                    clearTimeout(this.timer);

                    clearInterval(this.progressTimer);

                    this.visible = false;

                    this.progress = 100;

                },

                icon() {

                    return { success: '✓', error: '!', warning: '!', info: 'i', loading: '↻' }[this.type] || '✓';

                },

                tone() {

                    return {

                        success: 'rms-toast-success', error: 'rms-toast-error', warning: 'rms-toast-warning',

                        info: 'rms-toast-info', loading: 'rms-toast-loading'

                    }[this.type] || 'rms-toast-success';

                }

            }"

            x-on:toast.window="showToast($event.detail || {})"

            x-on:toast-close.window="hideToast()"

            x-show="visible"

            x-cloak

            class="rms-universal-toast"

            :class="tone()"

            role="status"

            aria-live="polite"

        >

            <div class="rms-toast-icon" x-text="icon()"></div>

            <div class="rms-toast-copy">

                <strong x-text="title"></strong>

                <span x-text="message"></span>

            </div>

            <button type="button" class="rms-toast-close" @click="hideToast()" aria-label="Close notification">×</button>

            <div class="rms-toast-progress"><i :style="`width:${progress}%`"></i></div>

        </div>

    </template>



    <style>

        .rms-universal-toast{position:fixed;right:22px;top:22px;z-index:100000;width:min(390px,calc(100vw - 28px));display:flex;align-items:center;gap:12px;padding:13px 14px 15px;border:1px solid rgba(228,228,231,.95);border-radius:17px;background:rgba(255,255,255,.97);box-shadow:0 22px 70px rgba(24,24,27,.18);backdrop-filter:blur(16px);font-family:Inter,system-ui,sans-serif;animation:rmsToastIn .28s cubic-bezier(.22,.8,.2,1)}

        .rms-toast-icon{display:grid;width:38px;height:38px;flex:0 0 38px;place-items:center;border-radius:12px;color:#fff;font-size:15px;font-weight:950;box-shadow:0 8px 20px rgba(0,0,0,.12)}

        .rms-toast-copy{min-width:0;flex:1;display:grid;gap:3px}.rms-toast-copy strong{font-size:11px;color:#18181b}.rms-toast-copy span{font-size:9px;line-height:1.45;color:#71717a}.rms-toast-close{width:28px;height:28px;flex:0 0 28px;border:0;border-radius:8px;background:#f4f4f5;color:#71717a;font-size:17px;cursor:pointer}.rms-toast-close:hover{background:#e4e4e7;color:#18181b}.rms-toast-progress{position:absolute;left:12px;right:12px;bottom:6px;height:2px;overflow:hidden;border-radius:999px;background:#f4f4f5}.rms-toast-progress i{display:block;height:100%;border-radius:999px;background:#18181b;transition:width .04s linear}.rms-toast-success .rms-toast-icon{background:#16a34a}.rms-toast-success .rms-toast-progress i{background:#16a34a}.rms-toast-error .rms-toast-icon{background:#dc2626}.rms-toast-error .rms-toast-progress i{background:#dc2626}.rms-toast-warning .rms-toast-icon{background:#d97706}.rms-toast-warning .rms-toast-progress i{background:#d97706}.rms-toast-info .rms-toast-icon{background:#2563eb}.rms-toast-info .rms-toast-progress i{background:#2563eb}.rms-toast-loading .rms-toast-icon{background:#18181b;animation:rmsToastSpin 1s linear infinite}.rms-toast-loading .rms-toast-progress i{background:#18181b}

        @keyframes rmsToastIn{from{opacity:0;transform:translate3d(18px,-8px,0) scale(.97)}to{opacity:1;transform:none}}@keyframes rmsToastSpin{to{transform:rotate(360deg)}}

        @media(max-width:640px){.rms-universal-toast{left:12px;right:12px;top:12px;width:auto;padding:12px}.rms-toast-icon{width:35px;height:35px;flex-basis:35px}.rms-toast-copy strong{font-size:10px}.rms-toast-copy span{font-size:9px}}

        @media(prefers-reduced-motion:reduce){.rms-universal-toast{animation:none}.rms-toast-icon{animation:none}}

        .api-status-dot.is-connected{background:#16a34a;box-shadow:0 0 0 4px rgba(22,163,74,.10)}

        .api-status-dot.is-error{background:#dc2626;box-shadow:0 0 0 4px rgba(220,38,38,.10)}
        /* Sidebar AI Engine / Queue Worker */
.worker-refresh{margin-left:auto;width:24px;height:24px;border:0;border-radius:7px;background:rgba(255,255,255,.06);color:#a1a1aa;display:inline-flex;align-items:center;justify-content:center;cursor:pointer}.worker-refresh:hover{background:rgba(255,255,255,.1);color:#fff}.worker-refresh:disabled{opacity:.55;cursor:wait}
        .worker-status-card{position:relative;overflow:hidden}
        .worker-status-card:before{content:"";position:absolute;inset:auto -35px -55px auto;width:150px;height:150px;border-radius:50%;background:radial-gradient(circle,rgba(239,68,68,.12),transparent 68%);pointer-events:none}
        .api-live-dot.worker-live{background:#22c55e;box-shadow:0 0 0 4px rgba(34,197,94,.10),0 0 14px rgba(34,197,94,.38)}
        .api-live-dot.worker-offline{background:#ef4444;box-shadow:0 0 0 4px rgba(239,68,68,.08)}
        .worker-mini-divider{height:1px;background:rgba(255,255,255,.08);margin:12px 0 10px}
        .worker-status-line{display:flex;align-items:center;gap:9px;min-width:0}
        .worker-status-copy{display:grid;gap:2px;min-width:0;flex:1}
        .worker-status-copy strong{font-size:10px;line-height:1.2;color:#f4f4f5;font-weight:850}
        .worker-status-copy span{font-size:8px;line-height:1.35;color:#71717a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .worker-meta-grid{display:grid;grid-template-columns:1fr 1fr;gap:7px;margin-top:10px}
        .worker-meta-grid>div{min-width:0;padding:7px 8px;border:1px solid rgba(255,255,255,.06);border-radius:9px;background:rgba(255,255,255,.025)}
        .worker-meta-grid small{display:block;font-size:6.5px;line-height:1;color:#52525b;letter-spacing:.14em;font-weight:900;margin-bottom:4px}
        .worker-meta-grid strong{display:block;font-size:8px;line-height:1.15;color:#a1a1aa;font-weight:750;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .worker-actions{display:flex;gap:6px;margin-top:9px}
        .worker-action{height:28px;display:inline-flex;align-items:center;justify-content:center;gap:5px;border:1px solid rgba(255,255,255,.08);border-radius:8px;padding:0 9px;background:rgba(255,255,255,.035);color:#a1a1aa;font-family:inherit;font-size:7.5px;font-weight:850;cursor:pointer;transition:transform .18s ease,border-color .18s ease,background .18s ease,color .18s ease}
        .worker-action:hover{transform:translateY(-1px);background:rgba(255,255,255,.065);color:#f4f4f5;border-color:rgba(255,255,255,.14)}
        .worker-action:disabled{opacity:.55;cursor:wait;transform:none}
        .worker-action-start{flex:1;background:linear-gradient(135deg,#dc2626,#b91c1c);border-color:rgba(248,113,113,.25);color:#fff;box-shadow:0 8px 18px rgba(220,38,38,.18)}
        .worker-action-start:hover{background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff}
        .worker-action-restart{flex:1}
        .worker-action-stop{color:#fca5a5;border-color:rgba(239,68,68,.16)}
        .worker-action-stop:hover{color:#fecaca;border-color:rgba(239,68,68,.3);background:rgba(239,68,68,.07)}
        .worker-spinner{display:inline-block;animation:workerSpin .8s linear infinite}
        .worker-started-at{margin-top:7px;font-size:6.8px;color:#52525b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .worker-api-footer{display:flex;align-items:center;justify-content:space-between;gap:7px;margin-top:10px;padding-top:9px;border-top:1px solid rgba(255,255,255,.06);font-size:7px;color:#71717a}
        .worker-api-footer>span:first-child{display:inline-flex;align-items:center;gap:5px;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .worker-footer-dot{width:5px;height:5px;border-radius:50%;display:inline-block;background:#d97706;box-shadow:0 0 0 3px rgba(217,119,6,.08)}
        .worker-footer-dot.is-online{background:#22c55e;box-shadow:0 0 0 3px rgba(34,197,94,.08)}
        .worker-step{font-size:6px;letter-spacing:.08em;color:#52525b;font-weight:900;white-space:nowrap}
        @keyframes workerSpin{to{transform:rotate(360deg)}}


    </style>

</div><?php /**PATH D:\Website\Tools Generating Image Rizky Motoshop\rizky-moto-ai\storage\framework\views/livewire/views/f04a7103.blade.php ENDPATH**/ ?>