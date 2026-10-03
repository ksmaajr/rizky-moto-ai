<div
    class="rms-activity-v44"
    x-data="activityLogStreamV44()"
    x-init="init()"
    data-feed-url="<?php echo e(route('dashboard.activity-logs.feed')); ?>"
    data-clear-url="<?php echo e(route('dashboard.activity-logs.destroy')); ?>"
    @rms-filter-change.window="if ($event.detail.key === 'category') category = $event.detail.value; if ($event.detail.key === 'status') status = $event.detail.value; if ($event.detail.key === 'timeframe') timeframe = $event.detail.value"
>
    <style>
        .rms-activity-v44{font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#171717}
        .rms-activity-v44 *{box-sizing:border-box}
        .rms-activity-head{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:20px 18px 16px;border-bottom:1px solid #ececef}
        .rms-activity-brand{display:flex;align-items:center;gap:12px;min-width:0}
        .rms-activity-terminal{width:38px;height:38px;border-radius:11px;background:#151515;color:#fff;display:grid;place-items:center;font:700 14px/1 ui-monospace,SFMono-Regular,Menlo,monospace;box-shadow:0 8px 20px rgba(0,0,0,.12)}
        .rms-activity-title{font-size:15px;font-weight:800;letter-spacing:-.02em;display:flex;align-items:center;gap:8px}
        .rms-activity-sub{margin-top:4px;font-size:11px;color:#92929a}
        .rms-live-pill{display:inline-flex;align-items:center;gap:5px;padding:4px 8px;border-radius:999px;background:#effcf4;color:#149447;font-size:9px;font-weight:800;letter-spacing:.04em;text-transform:uppercase}
        .rms-live-dot{width:6px;height:6px;border-radius:50%;background:#18b957;box-shadow:0 0 0 3px rgba(24,185,87,.10)}
        .rms-live-dot.error{background:#ef4444;box-shadow:0 0 0 3px rgba(239,68,68,.10)}
        .rms-activity-actions{display:flex;align-items:center;gap:8px;flex-shrink:0}
        .rms-event-count{display:inline-flex;align-items:center;gap:6px;font-size:9px;font-weight:800;color:#73737b;text-transform:uppercase;letter-spacing:.05em}
        .rms-event-count i{width:7px;height:7px;border-radius:50%;background:#19bb59}
        .rms-clear-btn{height:32px;padding:0 11px;border:1px solid #dedee4;background:#fff;border-radius:9px;color:#6f7078;font-size:10px;font-weight:700;cursor:pointer;transition:.18s ease}
        .rms-clear-btn:hover{border-color:#c9c9d0;color:#222;background:#fafafa}
        .rms-clear-btn:disabled{opacity:.55;cursor:wait}
        .rms-activity-tools{display:grid;grid-template-columns:minmax(240px,1.6fr) repeat(3,minmax(135px,.55fr));gap:8px;padding:12px 12px 10px;border-bottom:1px solid #ededf0;background:#fff}
        .rms-search{position:relative}
        .rms-search span{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#1fae57;font:700 12px/1 ui-monospace,SFMono-Regular,Menlo,monospace;pointer-events:none}
        .rms-search input,.rms-filter{width:100%;height:38px;border:1px solid #dedee5;border-radius:9px;background:#fff;outline:none;color:#333;font-size:11px;transition:.18s ease}
        .rms-search input{padding:0 34px 0 28px}
        .rms-filter{padding:0 10px;cursor:pointer}
        .rms-search input:focus,.rms-filter:focus{border-color:#b9bac3;box-shadow:0 0 0 3px rgba(0,0,0,.035)}
        .rms-search-clear{position:absolute;right:8px;top:50%;transform:translateY(-50%);width:24px;height:24px;border:0;background:transparent;color:#9b9ba3;cursor:pointer;border-radius:6px}
        .rms-search-clear:hover{background:#f2f2f4;color:#333}
        .rms-streambar{height:31px;background:#171719;color:#fff;display:flex;align-items:center;justify-content:space-between;padding:0 11px;font-size:8px;font-weight:800;letter-spacing:.06em;text-transform:uppercase}
        .rms-streambar-left{display:flex;align-items:center;gap:6px;color:#74e39d}
        .rms-streambar-dot{width:6px;height:6px;border-radius:50%;background:#20c768}
        .rms-streambar-dot.error{background:#ef4444}
        .rms-streambar-right{color:#8c8c95}
        .rms-log-viewport{height:390px;overflow:auto;background:#fff;scroll-behavior:smooth}
        .rms-log-viewport::-webkit-scrollbar{width:8px}.rms-log-viewport::-webkit-scrollbar-track{background:#fafafa}.rms-log-viewport::-webkit-scrollbar-thumb{background:#d7d7dc;border-radius:20px;border:2px solid #fafafa}
        .rms-log-head,.rms-log-row{display:grid;grid-template-columns:92px 118px minmax(190px,1fr) minmax(170px,1.2fr);gap:12px;align-items:start}
        .rms-log-head{height:34px;align-items:center;padding:0 12px;border-bottom:1px solid #ededf0;background:#fafafa;color:#9b9ba2;font-size:8px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;position:sticky;top:0;z-index:3}
        .rms-log-row{position:relative;padding:13px 12px;border-bottom:1px solid #f0f0f2;min-height:76px;transition:background .2s ease}
        .rms-log-row:hover{background:#fcfcfd}
        .rms-log-row.new{animation:rmsNewRow .65s ease both}
        .rms-log-row::before{content:"";position:absolute;left:0;top:0;bottom:0;width:3px;background:#d7d7dc}
        .rms-log-row.success::before{background:#1cbe61}.rms-log-row.error::before{background:#ef4444}.rms-log-row.warning::before{background:#f59e0b}.rms-log-row.info::before{background:#3b82f6}.rms-log-row.processing::before{background:#8b5cf6}
        @keyframes rmsNewRow{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
        .rms-time{font:600 9px/1.45 ui-monospace,SFMono-Regular,Menlo,monospace;color:#8a8a92}.rms-date{display:block;color:#b1b1b8;margin-top:2px;font-family:inherit;font-size:8px}
        .rms-badges{display:flex;align-items:center;gap:5px;flex-wrap:wrap}.rms-badge{display:inline-flex;align-items:center;height:20px;padding:0 7px;border-radius:7px;font-size:8px;font-weight:800;letter-spacing:.04em;text-transform:uppercase}.rms-status-success{background:#ecfbf2;color:#0c9b49}.rms-status-error{background:#fff0f1;color:#db303a}.rms-status-warning{background:#fff7e8;color:#bd7400}.rms-status-info{background:#eef5ff;color:#2d6ec7}.rms-status-processing{background:#f3efff;color:#7250c9}.rms-cat{background:#f7f4ff;color:#7453ba;border:1px solid #ece5ff}
        .rms-activity-main{min-width:0}.rms-activity-title2{font-size:11px;font-weight:800;color:#45454b;line-height:1.35}.rms-action{font:500 8px/1.4 ui-monospace,SFMono-Regular,Menlo,monospace;color:#aaaab1;margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .rms-detail{font-size:9px;color:#9a9aa2;line-height:1.45;word-break:break-word}.rms-meta{display:flex;flex-wrap:wrap;gap:5px;margin-top:6px}.rms-meta span{padding:3px 6px;border-radius:5px;background:#f6f6f8;color:#97979f;font-size:7px;font-weight:700}
        .rms-empty{height:100%;display:grid;place-items:center;text-align:center;padding:40px;color:#9a9aa2}.rms-empty-icon{width:38px;height:38px;border-radius:11px;background:#161618;color:#fff;display:grid;place-items:center;margin:0 auto 10px;font:700 13px/1 ui-monospace,SFMono-Regular,Menlo,monospace}.rms-empty strong{display:block;font-size:12px;color:#55555d}.rms-empty small{display:block;max-width:360px;margin-top:5px;font-size:9px;line-height:1.5}
        .rms-error{margin:12px;border:1px solid #ffd6d9;background:#fff6f6;border-radius:9px;padding:10px 12px;color:#bd2733;font-size:9px;line-height:1.45}.rms-error strong{display:block;font-size:9px;margin-bottom:3px}
        .rms-new-banner{position:absolute;left:50%;top:12px;transform:translateX(-50%);z-index:8;border:0;border-radius:999px;background:#171719;color:#fff;padding:8px 13px;font-size:8px;font-weight:800;box-shadow:0 10px 24px rgba(0,0,0,.16);cursor:pointer}
        .rms-activity-foot{height:38px;display:flex;align-items:center;justify-content:space-between;padding:0 12px;border-top:1px solid #ededf0;color:#9a9aa2;font-size:8px}.rms-foot-live{display:flex;align-items:center;gap:6px}.rms-foot-live i{width:6px;height:6px;border-radius:50%;background:#1cbe61}.rms-foot-error i{background:#ef4444}.rms-foot-right{text-transform:uppercase;letter-spacing:.04em}
        @media(max-width:760px){.rms-activity-tools{grid-template-columns:1fr 1fr}.rms-search{grid-column:1/-1}.rms-log-head,.rms-log-row{grid-template-columns:78px 100px minmax(160px,1fr)}.rms-log-head div:last-child,.rms-log-row .rms-detail-col{display:none}.rms-log-viewport{height:340px}.rms-activity-head{padding:16px 12px}.rms-event-count{display:none}}
        /* =========================================================
        RMS ACTIVITY FILTER DROPDOWN
        ========================================================= */

        .rms-filter-dropdown {
            position: relative;
            min-width: 170px;
            z-index: 20;
        }

        .rms-filter-dropdown.is-open {
            z-index: 999;
        }

        .rms-filter-trigger {
            width: 100%;
            min-height: 42px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;

            padding: 0 13px;

            border: 1px solid #e2e4e8;
            border-radius: 11px;

            background: rgba(255, 255, 255, .96);

            color: #18181b;

            cursor: pointer;

            transition:
                border-color .18s ease,
                box-shadow .18s ease,
                transform .18s ease,
                background .18s ease;
        }

        .rms-filter-trigger:hover {
            border-color: #cfd2d8;
            background: #fff;
        }

        .rms-filter-dropdown.is-open .rms-filter-trigger {
            border-color: #111;
            box-shadow:
                0 0 0 3px rgba(17, 17, 17, .06),
                0 10px 30px rgba(17, 17, 17, .08);
        }

        .rms-filter-selected {
            min-width: 0;
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .rms-filter-selected-icon {
            width: 26px;
            height: 26px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 8px;

            background: #f5f5f5;

            color: #444;

            font-size: 11px;
            font-weight: 800;
        }

        .rms-filter-selected-text {
            min-width: 0;

            font-size: 12px;
            font-weight: 650;

            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .rms-filter-chevron {
            width: 16px;
            height: 16px;

            flex: 0 0 auto;

            color: #8b8d93;

            transition:
                transform .2s ease,
                color .2s ease;
        }

        .rms-filter-dropdown.is-open .rms-filter-chevron {
            transform: rotate(180deg);
            color: #111;
        }


        /* ---------------------------------------------------------
        MENU
        --------------------------------------------------------- */

        .rms-filter-menu {
            position: absolute;

            top: calc(100% + 8px);
            left: 0;

            width: 100%;
            min-width: 230px;

            padding: 6px;

            border: 1px solid rgba(226, 228, 232, .95);
            border-radius: 14px;

            background: rgba(255, 255, 255, .98);

            box-shadow:
                0 24px 60px rgba(0, 0, 0, .13),
                0 6px 20px rgba(0, 0, 0, .07);

            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);

            transform-origin: top center;

            z-index: 99999;

            overflow: hidden;

            animation: rmsDropdownIn .16s ease-out;
        }

        @keyframes rmsDropdownIn {
            from {
                opacity: 0;
                transform: translateY(-5px) scale(.985);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }


        /* ---------------------------------------------------------
        MENU ITEM
        --------------------------------------------------------- */

        .rms-filter-option {
            width: 100%;

            display: flex;
            align-items: center;

            gap: 10px;

            padding: 9px 10px;

            border: 0;
            border-radius: 9px;

            background: transparent;

            text-align: left;

            cursor: pointer;

            transition:
                background .15s ease,
                transform .15s ease;
        }

        .rms-filter-option:hover {
            background: #f7f7f8;
        }

        .rms-filter-option:active {
            transform: scale(.985);
        }

        .rms-filter-option.is-selected {
            background: #f4f4f5;
        }


        /* ---------------------------------------------------------
        ICON
        --------------------------------------------------------- */

        .rms-filter-option-icon {
            width: 30px;
            height: 30px;

            flex: 0 0 auto;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 9px;

            font-size: 11px;
            font-weight: 800;

            background: #f5f5f5;
            color: #555;
        }


        /* Category accents */

        .rms-filter-option-icon.api {
            color: #7c3aed;
            background: #f3e8ff;
        }

        .rms-filter-option-icon.generator {
            color: #dc2626;
            background: #fee2e2;
        }

        .rms-filter-option-icon.template {
            color: #2563eb;
            background: #dbeafe;
        }

        .rms-filter-option-icon.store {
            color: #d97706;
            background: #fef3c7;
        }

        .rms-filter-option-icon.account {
            color: #059669;
            background: #d1fae5;
        }

        .rms-filter-option-icon.system {
            color: #52525b;
            background: #e4e4e7;
        }


        /* ---------------------------------------------------------
        TEXT
        --------------------------------------------------------- */

        .rms-filter-option-content {
            min-width: 0;
            flex: 1;
        }

        .rms-filter-option-title {
            display: block;

            font-size: 12px;
            line-height: 1.25;

            font-weight: 650;

            color: #18181b;
        }

        .rms-filter-option-description {
            display: block;

            margin-top: 2px;

            font-size: 9px;
            line-height: 1.25;

            color: #a1a1aa;

            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }


        /* ---------------------------------------------------------
        CHECK
        --------------------------------------------------------- */

        .rms-filter-check {
            width: 20px;
            height: 20px;

            flex: 0 0 auto;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 6px;

            background: #18181b;

            color: white;

            font-size: 10px;
            font-weight: 900;

            animation: rmsCheckIn .16s ease-out;
        }

        @keyframes rmsCheckIn {
            from {
                opacity: 0;
                transform: scale(.7);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }


        /* ---------------------------------------------------------
        DIVIDER
        --------------------------------------------------------- */

        .rms-filter-divider {
            height: 1px;

            margin: 5px 7px;

            background: #eeeeef;
        }


        /* ---------------------------------------------------------
        MOBILE
        --------------------------------------------------------- */

        @media (max-width: 768px) {

            .rms-filter-dropdown {
                min-width: 145px;
                flex: 1;
            }

            .rms-filter-menu {
                min-width: 210px;
            }

            .rms-filter-option-description {
                display: none;
            }

            .rms-filter-selected-text {
                font-size: 11px;
            }
        }

        /* =========================================================
           RMS FILTER DROPDOWN V2 — PREMIUM COMMAND UI
           ========================================================= */
        .rms-activity-tools{
            position:relative;
            z-index:20;
            overflow:visible;
        }
        .rms-filter-dropdown{
            position:relative;
            min-width:0;
            z-index:30;
        }
        .rms-filter-dropdown.is-open{
            z-index:1000;
        }
        .rms-filter-trigger{
            position:relative;
            min-height:38px;
            height:38px;
            width:100%;
            padding:0 11px;
            border:1px solid #dedee5;
            border-radius:10px;
            background:linear-gradient(180deg,#fff 0%,#fbfbfc 100%);
            color:#27272a;
            box-shadow:0 1px 1px rgba(0,0,0,.02);
        }
        .rms-filter-trigger:hover{
            border-color:#c9cad1;
            background:#fff;
            box-shadow:0 5px 16px rgba(0,0,0,.045);
        }
        .rms-filter-dropdown.is-open .rms-filter-trigger{
            border-color:#18181b;
            box-shadow:0 0 0 3px rgba(24,24,27,.055),0 8px 22px rgba(0,0,0,.07);
        }
        .rms-filter-selected{
            min-width:0;
            display:flex;
            align-items:center;
            gap:8px;
        }
        .rms-filter-selected-icon{
            width:24px;
            height:24px;
            border-radius:7px;
            display:grid;
            place-items:center;
            flex:0 0 24px;
            background:#f4f4f5;
            color:#52525b;
            font:800 9px/1 Inter,ui-sans-serif,system-ui,sans-serif;
        }
        .rms-filter-selected-icon.api{background:#f3e8ff;color:#7c3aed}
        .rms-filter-selected-icon.generator{background:#fee2e2;color:#dc2626}
        .rms-filter-selected-icon.template{background:#dbeafe;color:#2563eb}
        .rms-filter-selected-icon.store{background:#fef3c7;color:#b45309}
        .rms-filter-selected-icon.account{background:#d1fae5;color:#047857}
        .rms-filter-selected-icon.system{background:#e4e4e7;color:#52525b}
        .rms-filter-selected-text{
            min-width:0;
            overflow:hidden;
            text-overflow:ellipsis;
            white-space:nowrap;
            font-size:10px;
            font-weight:750;
            color:#3f3f46;
        }
        .rms-filter-chevron{
            width:14px;
            height:14px;
            margin-left:auto;
            flex:0 0 14px;
            transition:transform .18s ease,color .18s ease;
        }
        .rms-filter-dropdown.is-open .rms-filter-chevron{
            transform:rotate(180deg);
            color:#18181b;
        }
        .rms-filter-menu{
            position:absolute;
            top:calc(100% + 7px);
            left:0;
            right:0;
            width:auto;
            min-width:225px;
            padding:6px;
            border:1px solid #e4e4e7;
            border-radius:13px;
            background:rgba(255,255,255,.985);
            box-shadow:0 24px 55px rgba(0,0,0,.13),0 5px 15px rgba(0,0,0,.06);
            backdrop-filter:blur(18px);
            -webkit-backdrop-filter:blur(18px);
            z-index:100000;
            overflow:hidden;
        }
        .rms-filter-menu::before{
            content:"";
            position:absolute;
            top:-4px;
            left:22px;
            width:8px;
            height:8px;
            background:#fff;
            border-left:1px solid #e4e4e7;
            border-top:1px solid #e4e4e7;
            transform:rotate(45deg);
        }
        .rms-filter-option{
            position:relative;
            min-height:42px;
            padding:7px 8px;
            border-radius:9px;
            gap:9px;
            transition:background .14s ease,transform .14s ease;
        }
        .rms-filter-option:hover{
            background:#f7f7f8;
        }
        .rms-filter-option.is-selected{
            background:#f2f2f3;
        }
        .rms-filter-option-icon{
            width:27px;
            height:27px;
            border-radius:8px;
            font-size:9px;
        }
        .rms-filter-option-content{
            padding-right:4px;
        }
        .rms-filter-option-title{
            font-size:10px;
            font-weight:750;
            color:#27272a;
        }
        .rms-filter-option-description{
            margin-top:2px;
            font-size:8px;
            color:#a1a1aa;
        }
        .rms-filter-check{
            width:19px;
            height:19px;
            border-radius:6px;
            background:#171719;
            font-size:9px;
        }
        .rms-filter-menu .rms-filter-option + .rms-filter-option{
            margin-top:1px;
        }
        @media(max-width:760px){
            .rms-filter-menu{
                min-width:210px;
            }
            .rms-filter-option-description{
                display:block;
            }
        }

    </style>

    <div class="rms-activity-head">
        <div class="rms-activity-brand">
            <div class="rms-activity-terminal">&gt;_</div>
            <div>
                <div class="rms-activity-title">Activity Logs <span class="rms-live-pill"><i class="rms-live-dot" :class="{error: syncError}"></i> LIVE</span></div>
                <div class="rms-activity-sub">Real-time workspace activity stream</div>
            </div>
        </div>
        <div class="rms-activity-actions">
            <span class="rms-event-count"><i></i><span x-text="filteredLogs.length + ' EVENTS'"></span></span>
            <button type="button" class="rms-clear-btn" @click="clearLogs()" :disabled="clearing || logs.length === 0" x-text="clearing ? 'Clearing...' : 'Clear'"></button>
        </div>
    </div>

    <div class="rms-activity-tools">
        <div class="rms-search">
            <span>$</span>
            <input type="search" x-model.debounce.250ms="search" placeholder="search activities, store, template, model...">
            <button type="button" class="rms-search-clear" x-show="search" x-cloak @click="search = ''">×</button>
        </div>
        <div class="rms-filter-dropdown" x-data="{ open: false }" :class="{ 'is-open': open }" @click.outside="open = false">
            <button type="button" class="rms-filter-trigger" @click="open = !open" :aria-expanded="open.toString()">
                <span class="rms-filter-selected">
                    <span class="rms-filter-selected-icon" x-show="category === 'all'">ALL</span>
                    <span class="rms-filter-selected-icon api" x-show="category === 'api'">AI</span>
                    <span class="rms-filter-selected-icon generator" x-show="category === 'generator'">✦</span>
                    <span class="rms-filter-selected-icon template" x-show="category === 'template'">T</span>
                    <span class="rms-filter-selected-icon store" x-show="category === 'store'">S</span>
                    <span class="rms-filter-selected-icon account" x-show="category === 'account'">A</span>
                    <span class="rms-filter-selected-icon system" x-show="category === 'system'">•</span>

                    <span class="rms-filter-selected-text" x-show="category === 'all'">All Activities</span>
                    <span class="rms-filter-selected-text" x-show="category === 'api'">API</span>
                    <span class="rms-filter-selected-text" x-show="category === 'generator'">Generator</span>
                    <span class="rms-filter-selected-text" x-show="category === 'template'">Template</span>
                    <span class="rms-filter-selected-text" x-show="category === 'store'">Store</span>
                    <span class="rms-filter-selected-text" x-show="category === 'account'">Account</span>
                    <span class="rms-filter-selected-text" x-show="category === 'system'">System</span>
                </span>
                <svg class="rms-filter-chevron" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path d="M6 8L10 12L14 8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1 scale-[.985]" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 -translate-y-1" class="rms-filter-menu" @click.stop>
                <button type="button" class="rms-filter-option" :class="{ 'is-selected': category === 'all' }" @click="$dispatch('rms-filter-change',{key:'category',value:'all'}); open=false">
                    <span class="rms-filter-option-icon">ALL</span><span class="rms-filter-option-content"><span class="rms-filter-option-title">All Activities</span><span class="rms-filter-option-description">Semua aktivitas workspace</span></span><span class="rms-filter-check" x-show="category === 'all'">✓</span>
                </button>
                <button type="button" class="rms-filter-option" :class="{ 'is-selected': category === 'api' }" @click="$dispatch('rms-filter-change',{key:'category',value:'api'}); open=false">
                    <span class="rms-filter-option-icon api">AI</span><span class="rms-filter-option-content"><span class="rms-filter-option-title">API</span><span class="rms-filter-option-description">OpenAI &amp; API activity</span></span><span class="rms-filter-check" x-show="category === 'api'">✓</span>
                </button>
                <button type="button" class="rms-filter-option" :class="{ 'is-selected': category === 'generator' }" @click="$dispatch('rms-filter-change',{key:'category',value:'generator'}); open=false">
                    <span class="rms-filter-option-icon generator">✦</span><span class="rms-filter-option-content"><span class="rms-filter-option-title">Generator</span><span class="rms-filter-option-description">Image generation activity</span></span><span class="rms-filter-check" x-show="category === 'generator'">✓</span>
                </button>
                <button type="button" class="rms-filter-option" :class="{ 'is-selected': category === 'template' }" @click="$dispatch('rms-filter-change',{key:'category',value:'template'}); open=false">
                    <span class="rms-filter-option-icon template">T</span><span class="rms-filter-option-content"><span class="rms-filter-option-title">Template</span><span class="rms-filter-option-description">Template management</span></span><span class="rms-filter-check" x-show="category === 'template'">✓</span>
                </button>
                <button type="button" class="rms-filter-option" :class="{ 'is-selected': category === 'store' }" @click="$dispatch('rms-filter-change',{key:'category',value:'store'}); open=false">
                    <span class="rms-filter-option-icon store">S</span><span class="rms-filter-option-content"><span class="rms-filter-option-title">Store</span><span class="rms-filter-option-description">Store management</span></span><span class="rms-filter-check" x-show="category === 'store'">✓</span>
                </button>
                <button type="button" class="rms-filter-option" :class="{ 'is-selected': category === 'account' }" @click="$dispatch('rms-filter-change',{key:'category',value:'account'}); open=false">
                    <span class="rms-filter-option-icon account">A</span><span class="rms-filter-option-content"><span class="rms-filter-option-title">Account</span><span class="rms-filter-option-description">Account activity</span></span><span class="rms-filter-check" x-show="category === 'account'">✓</span>
                </button>
                <button type="button" class="rms-filter-option" :class="{ 'is-selected': category === 'system' }" @click="$dispatch('rms-filter-change',{key:'category',value:'system'}); open=false">
                    <span class="rms-filter-option-icon system">•</span><span class="rms-filter-option-content"><span class="rms-filter-option-title">System</span><span class="rms-filter-option-description">System activity</span></span><span class="rms-filter-check" x-show="category === 'system'">✓</span>
                </button>
            </div>
        </div>
        <div class="rms-filter-dropdown" x-data="{ open: false }" :class="{ 'is-open': open }" @click.outside="open = false">
            <button type="button" class="rms-filter-trigger" @click="open = !open" :aria-expanded="open.toString()">
                <span class="rms-filter-selected">
                    <span class="rms-filter-selected-icon" x-show="status === 'all'">ALL</span>
                    <span class="rms-filter-selected-icon account" x-show="status === 'success'">✓</span>
                    <span class="rms-filter-selected-icon api" x-show="status === 'processing'">↻</span>
                    <span class="rms-filter-selected-icon template" x-show="status === 'info'">i</span>
                    <span class="rms-filter-selected-icon store" x-show="status === 'warning'">!</span>
                    <span class="rms-filter-selected-icon generator" x-show="status === 'error'">×</span>

                    <span class="rms-filter-selected-text" x-show="status === 'all'">All Status</span>
                    <span class="rms-filter-selected-text" x-show="status === 'success'">Success</span>
                    <span class="rms-filter-selected-text" x-show="status === 'processing'">Processing</span>
                    <span class="rms-filter-selected-text" x-show="status === 'info'">Info</span>
                    <span class="rms-filter-selected-text" x-show="status === 'warning'">Warning</span>
                    <span class="rms-filter-selected-text" x-show="status === 'error'">Error</span>
                </span>
                <svg class="rms-filter-chevron" viewBox="0 0 20 20" fill="none"><path d="M6 8L10 12L14 8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>

            <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1 scale-[.985]" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 -translate-y-1" class="rms-filter-menu" @click.stop>
                <button type="button" class="rms-filter-option" :class="{ 'is-selected': status === 'all' }" @click="$dispatch('rms-filter-change',{key:'status',value:'all'}); open=false"><span class="rms-filter-option-icon">ALL</span><span class="rms-filter-option-content"><span class="rms-filter-option-title">All Status</span><span class="rms-filter-option-description">Semua status aktivitas</span></span><span class="rms-filter-check" x-show="status === 'all'">✓</span></button>
                <button type="button" class="rms-filter-option" :class="{ 'is-selected': status === 'success' }" @click="$dispatch('rms-filter-change',{key:'status',value:'success'}); open=false"><span class="rms-filter-option-icon account">✓</span><span class="rms-filter-option-content"><span class="rms-filter-option-title">Success</span><span class="rms-filter-option-description">Aktivitas berhasil</span></span><span class="rms-filter-check" x-show="status === 'success'">✓</span></button>
                <button type="button" class="rms-filter-option" :class="{ 'is-selected': status === 'processing' }" @click="$dispatch('rms-filter-change',{key:'status',value:'processing'}); open=false"><span class="rms-filter-option-icon api">↻</span><span class="rms-filter-option-content"><span class="rms-filter-option-title">Processing</span><span class="rms-filter-option-description">Aktivitas sedang berjalan</span></span><span class="rms-filter-check" x-show="status === 'processing'">✓</span></button>
                <button type="button" class="rms-filter-option" :class="{ 'is-selected': status === 'info' }" @click="$dispatch('rms-filter-change',{key:'status',value:'info'}); open=false"><span class="rms-filter-option-icon template">i</span><span class="rms-filter-option-content"><span class="rms-filter-option-title">Info</span><span class="rms-filter-option-description">Informasi aktivitas</span></span><span class="rms-filter-check" x-show="status === 'info'">✓</span></button>
                <button type="button" class="rms-filter-option" :class="{ 'is-selected': status === 'warning' }" @click="$dispatch('rms-filter-change',{key:'status',value:'warning'}); open=false"><span class="rms-filter-option-icon store">!</span><span class="rms-filter-option-content"><span class="rms-filter-option-title">Warning</span><span class="rms-filter-option-description">Peringatan sistem</span></span><span class="rms-filter-check" x-show="status === 'warning'">✓</span></button>
                <button type="button" class="rms-filter-option" :class="{ 'is-selected': status === 'error' }" @click="$dispatch('rms-filter-change',{key:'status',value:'error'}); open=false"><span class="rms-filter-option-icon generator">×</span><span class="rms-filter-option-content"><span class="rms-filter-option-title">Error</span><span class="rms-filter-option-description">Aktivitas mengalami error</span></span><span class="rms-filter-check" x-show="status === 'error'">✓</span></button>
            </div>
        </div>
        <div class="rms-filter-dropdown" x-data="{ open: false }" :class="{ 'is-open': open }" @click.outside="open = false">
            <button type="button" class="rms-filter-trigger" @click="open = !open" :aria-expanded="open.toString()">
                <span class="rms-filter-selected">
                    <span class="rms-filter-selected-icon" x-show="timeframe === 'all'">∞</span>
                    <span class="rms-filter-selected-icon api" x-show="timeframe === 'today'">D</span>
                    <span class="rms-filter-selected-icon template" x-show="timeframe === 'yesterday'">Y</span>
                    <span class="rms-filter-selected-icon store" x-show="timeframe === '7days'">7</span>
                    <span class="rms-filter-selected-icon account" x-show="timeframe === '30days'">30</span>

                    <span class="rms-filter-selected-text" x-show="timeframe === 'all'">All Time</span>
                    <span class="rms-filter-selected-text" x-show="timeframe === 'today'">Today</span>
                    <span class="rms-filter-selected-text" x-show="timeframe === 'yesterday'">Yesterday</span>
                    <span class="rms-filter-selected-text" x-show="timeframe === '7days'">Last 7 Days</span>
                    <span class="rms-filter-selected-text" x-show="timeframe === '30days'">Last 30 Days</span>
                </span>
                <svg class="rms-filter-chevron" viewBox="0 0 20 20" fill="none"><path d="M6 8L10 12L14 8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>

            <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1 scale-[.985]" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 -translate-y-1" class="rms-filter-menu" @click.stop>
                <button type="button" class="rms-filter-option" :class="{ 'is-selected': timeframe === 'all' }" @click="$dispatch('rms-filter-change',{key:'timeframe',value:'all'}); open=false"><span class="rms-filter-option-icon">∞</span><span class="rms-filter-option-content"><span class="rms-filter-option-title">All Time</span><span class="rms-filter-option-description">Seluruh riwayat aktivitas</span></span><span class="rms-filter-check" x-show="timeframe === 'all'">✓</span></button>
                <button type="button" class="rms-filter-option" :class="{ 'is-selected': timeframe === 'today' }" @click="$dispatch('rms-filter-change',{key:'timeframe',value:'today'}); open=false"><span class="rms-filter-option-icon api">D</span><span class="rms-filter-option-content"><span class="rms-filter-option-title">Today</span><span class="rms-filter-option-description">Aktivitas hari ini</span></span><span class="rms-filter-check" x-show="timeframe === 'today'">✓</span></button>
                <button type="button" class="rms-filter-option" :class="{ 'is-selected': timeframe === 'yesterday' }" @click="$dispatch('rms-filter-change',{key:'timeframe',value:'yesterday'}); open=false"><span class="rms-filter-option-icon template">Y</span><span class="rms-filter-option-content"><span class="rms-filter-option-title">Yesterday</span><span class="rms-filter-option-description">Aktivitas kemarin</span></span><span class="rms-filter-check" x-show="timeframe === 'yesterday'">✓</span></button>
                <button type="button" class="rms-filter-option" :class="{ 'is-selected': timeframe === '7days' }" @click="$dispatch('rms-filter-change',{key:'timeframe',value:'7days'}); open=false"><span class="rms-filter-option-icon store">7</span><span class="rms-filter-option-content"><span class="rms-filter-option-title">Last 7 Days</span><span class="rms-filter-option-description">7 hari terakhir</span></span><span class="rms-filter-check" x-show="timeframe === '7days'">✓</span></button>
                <button type="button" class="rms-filter-option" :class="{ 'is-selected': timeframe === '30days' }" @click="$dispatch('rms-filter-change',{key:'timeframe',value:'30days'}); open=false"><span class="rms-filter-option-icon account">30</span><span class="rms-filter-option-content"><span class="rms-filter-option-title">Last 30 Days</span><span class="rms-filter-option-description">30 hari terakhir</span></span><span class="rms-filter-check" x-show="timeframe === '30days'">✓</span></button>
            </div>
        </div>
    </div>

    <div class="rms-streambar">
        <div class="rms-streambar-left"><i class="rms-streambar-dot" :class="{error: syncError}"></i><span x-text="syncError ? 'STREAM ERROR' : 'LIVE ACTIVITY STREAM'"></span></div>
        <div class="rms-streambar-right"><span x-text="filteredLogs.length + ' shown' + (loading ? ' · syncing' : ' · synced')"></span></div>
    </div>

    <div class="rms-log-viewport" x-ref="viewport" @scroll.passive="onScroll()">
        <template x-if="syncError">
            <div class="rms-error">
                <strong>Activity feed gagal disinkronkan.</strong>
                <span x-text="errorMessage"></span>
            </div>
        </template>

        <template x-if="!syncError && filteredLogs.length === 0 && !loading">
            <div class="rms-empty">
                <div>
                    <div class="rms-empty-icon">&gt;_</div>
                    <strong x-text="logs.length ? 'No matching activities.' : 'No activity yet.'"></strong>
                    <small x-text="logs.length ? 'Coba ubah kata pencarian atau filter.' : 'Jalankan Test Connection atau gunakan fitur workspace untuk membuat activity baru.'"></small>
                </div>
            </div>
        </template>

        <template x-if="filteredLogs.length > 0">
            <div>
                <div class="rms-log-head"><div>Time</div><div>Status</div><div>Activity</div><div>Detail</div></div>
                <template x-for="log in filteredLogs" :key="log.id">
                    <div class="rms-log-row" :class="[statusClass(log.status), newIds.includes(Number(log.id)) ? 'new' : '']">
                        <div class="rms-time"><span x-text="formatTime(log.created_at)"></span><span class="rms-date" x-text="formatDate(log.created_at)"></span></div>
                        <div class="rms-badges"><span class="rms-badge" :class="statusBadgeClass(log.status)" x-text="String(log.status || 'info').toUpperCase()"></span><span class="rms-badge rms-cat" x-text="String(log.category || 'system').toUpperCase()"></span></div>
                        <div class="rms-activity-main"><div class="rms-activity-title2" x-text="log.title || log.action || 'Activity'"></div><div class="rms-action" x-text="log.action || ''"></div></div>
                        <div class="rms-detail-col"><div class="rms-detail" x-text="log.description || ''"></div><div class="rms-meta"><template x-for="meta in metaItems(log.metadata)" :key="meta.key"><span x-text="meta.label + ' ' + meta.value"></span></template></div></div>
                    </div>
                </template>
            </div>
        </template>

        <template x-if="loading && logs.length === 0">
            <div class="rms-empty"><div><div class="rms-empty-icon">...</div><strong>Connecting to activity stream...</strong><small>Mengambil activity terbaru dari server.</small></div></div>
        </template>

        <template x-if="newCount > 0 && !atTop">
            <button type="button" class="rms-new-banner" @click="jumpToLatest()" x-text="newCount + ' new activit' + (newCount === 1 ? 'y' : 'ies') + ' · SHOW'"></button>
        </template>
    </div>

    <div class="rms-activity-foot">
        <div class="rms-foot-live" :class="{'rms-foot-error': syncError}"><i></i><span x-text="syncError ? 'Feed error' : 'Stream connected'"></span></div>
        <div class="rms-foot-right" x-text="syncError ? 'Retrying automatically' : 'No page refresh · silent sync'"></div>
    </div>

    
</div>
<?php /**PATH F:\Website\rizky-tools-ai\resources\views/livewire/dashboard/activity-log-stream.blade.php ENDPATH**/ ?>