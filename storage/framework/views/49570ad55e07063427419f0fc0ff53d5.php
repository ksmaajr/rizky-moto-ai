<?php # [BlazeFolded]:{flux::sidebar.toggle}:{F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/sidebar/toggle.blade.php}:{1789427708} ?>
<?php # [BlazeFolded]:{flux::navbar}:{F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/navbar/index.blade.php}:{1789427708} ?>
<?php # [BlazeFolded]:{flux::spacer}:{F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/spacer.blade.php}:{1789427708} ?>
<?php # [BlazeFolded]:{flux::navbar}:{F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/navbar/index.blade.php}:{1789427708} ?>
<?php # [BlazeFolded]:{flux::header}:{F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/header.blade.php}:{1789427708} ?>
<?php # [BlazeFolded]:{flux::sidebar.collapse}:{F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/sidebar/collapse.blade.php}:{1789427708} ?>
<?php # [BlazeFolded]:{flux::sidebar.header}:{F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/sidebar/header.blade.php}:{1789427708} ?>
<?php # [BlazeFolded]:{flux::sidebar.nav}:{F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/sidebar/nav.blade.php}:{1789427708} ?>
<?php # [BlazeFolded]:{flux::spacer}:{F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/spacer.blade.php}:{1789427708} ?>
<?php # [BlazeFolded]:{flux::sidebar.nav}:{F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/sidebar/nav.blade.php}:{1789427708} ?>
<?php # [BlazeFolded]:{flux::sidebar}:{F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/sidebar/index.blade.php}:{1789427708} ?>
<?php # [BlazeFolded]:{flux::toast}:{F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/toast/index.blade.php}:{1789427708} ?>
<?php # [BlazeFolded]:{flux::toast.group}:{F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/toast/group.blade.php}:{1789427708} ?>
<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" class="dark">
    <head>
        <?php echo $__env->make('partials.head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <?php ob_start(); ?><?php $__blaze->pushData(['container' => true, 'class' => 'border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900']); $__env->pushConsumableComponentData(['container' => true, 'class' => 'border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900']); ?><header class="[grid-area:header] z-10 min-h-14  border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900" data-flux-header>
            <div class="mx-auto w-full h-full [:where(&)]:max-w-7xl px-6 lg:px-8 flex items-center">
            <?php ob_start(); ?>
            <?php ob_start(); ?><button type="button" class="relative items-center font-medium justify-center whitespace-nowrap disabled:opacity-50 dark:disabled:opacity-50 disabled:cursor-default disabled:pointer-events-none disabled:shadow-none justify-center h-10 text-sm rounded-lg gap-2 w-10 inline-flex -ms-2.5 bg-transparent hover:bg-zinc-800/5 dark:hover:bg-white/15 text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-white       shrink-0 lg:hidden mr-2" data-flux-button="data-flux-button" x-data="" x-on:click="$dispatch('flux-sidebar-toggle')" aria-label="Toggle sidebar" data-flux-sidebar-toggle="data-flux-sidebar-toggle">
        <svg class="shrink-0 [:where(&amp;)]:size-5" data-flux-icon xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
  <path fill-rule="evenodd" d="M2 6.75A.75.75 0 0 1 2.75 6h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 6.75Zm0 6.5a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H2.75a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd"/>
</svg>
    </button>
<?php echo ltrim(ob_get_clean()); ?>

            <?php if (isset($component)) { $__componentOriginal7b17d80ff7900603fe9e5f0b453cc7c3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7b17d80ff7900603fe9e5f0b453cc7c3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.app-logo','data' => ['href' => ''.e(route('dashboard')).'','wire:navigate' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e(route('dashboard')).'','wire:navigate' => true]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7b17d80ff7900603fe9e5f0b453cc7c3)): ?>
<?php $attributes = $__attributesOriginal7b17d80ff7900603fe9e5f0b453cc7c3; ?>
<?php unset($__attributesOriginal7b17d80ff7900603fe9e5f0b453cc7c3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7b17d80ff7900603fe9e5f0b453cc7c3)): ?>
<?php $component = $__componentOriginal7b17d80ff7900603fe9e5f0b453cc7c3; ?>
<?php unset($__componentOriginal7b17d80ff7900603fe9e5f0b453cc7c3); ?>
<?php endif; ?>

            <?php ob_start(); ?><?php $__blaze->pushData(['class' => '-mb-px max-lg:hidden']); $__env->pushConsumableComponentData(['class' => '-mb-px max-lg:hidden']); ?><nav class="flex items-center gap-0.5 py-3  -mb-px max-lg:hidden" data-flux-navbar>
    <?php ob_start(); ?>
                <?php if (!function_exists('_0f4901f0bb492be280a534ff837631ac')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/navbar/item.blade.php', $__blaze->compiledPath.'/0f4901f0bb492be280a534ff837631ac.php'); require $__blaze->compiledPath.'/0f4901f0bb492be280a534ff837631ac.php'; } ?>
<?php if (isset($__slots0f4901f0bb492be280a534ff837631ac)) { $__slotsStack0f4901f0bb492be280a534ff837631ac[] = $__slots0f4901f0bb492be280a534ff837631ac; } ?>
<?php if (isset($__attrs0f4901f0bb492be280a534ff837631ac)) { $__attrsStack0f4901f0bb492be280a534ff837631ac[] = $__attrs0f4901f0bb492be280a534ff837631ac; } ?>
<?php $__attrs0f4901f0bb492be280a534ff837631ac = ['icon' => 'layout-grid','href' => route('dashboard'),'current' => request()->routeIs('dashboard'),'wire:navigate' => true]; ?>
<?php $__slots0f4901f0bb492be280a534ff837631ac = []; ?>
<?php $__blaze->pushData($__attrs0f4901f0bb492be280a534ff837631ac); ?>
<?php ob_start(); ?>
                    <?php echo e(__('Dashboard')); ?>

                <?php $__slots0f4901f0bb492be280a534ff837631ac['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots0f4901f0bb492be280a534ff837631ac); ?>
<?php _0f4901f0bb492be280a534ff837631ac($__blaze, $__attrs0f4901f0bb492be280a534ff837631ac, $__slots0f4901f0bb492be280a534ff837631ac, ['href', 'current', 'wire:navigate'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack0f4901f0bb492be280a534ff837631ac)) { $__slots0f4901f0bb492be280a534ff837631ac = array_pop($__slotsStack0f4901f0bb492be280a534ff837631ac); } ?>
<?php if (! empty($__attrsStack0f4901f0bb492be280a534ff837631ac)) { $__attrs0f4901f0bb492be280a534ff837631ac = array_pop($__attrsStack0f4901f0bb492be280a534ff837631ac); } ?>
<?php $__blaze->popData(); ?>
            <?php echo trim(ob_get_clean()); ?>

</nav>
<?php $__blaze->popData(); $__env->popConsumableComponentData(); ?><?php echo ltrim(ob_get_clean()); ?>

            <?php ob_start(); ?><div class="flex-1" data-flux-spacer></div>
<?php echo ltrim(ob_get_clean()); ?>

            <?php ob_start(); ?><?php $__blaze->pushData(['class' => 'me-1.5 space-x-0.5 rtl:space-x-reverse py-0!']); $__env->pushConsumableComponentData(['class' => 'me-1.5 space-x-0.5 rtl:space-x-reverse py-0!']); ?><nav class="flex items-center gap-0.5 py-3  me-1.5 space-x-0.5 rtl:space-x-reverse py-0!" data-flux-navbar>
    <?php ob_start(); ?>
                <?php if (!function_exists('_dfd2184989fa5e11a0c987d1c0b86139')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/tooltip/index.blade.php', $__blaze->compiledPath.'/dfd2184989fa5e11a0c987d1c0b86139.php'); require $__blaze->compiledPath.'/dfd2184989fa5e11a0c987d1c0b86139.php'; } ?>
<?php if (isset($__slotsdfd2184989fa5e11a0c987d1c0b86139)) { $__slotsStackdfd2184989fa5e11a0c987d1c0b86139[] = $__slotsdfd2184989fa5e11a0c987d1c0b86139; } ?>
<?php if (isset($__attrsdfd2184989fa5e11a0c987d1c0b86139)) { $__attrsStackdfd2184989fa5e11a0c987d1c0b86139[] = $__attrsdfd2184989fa5e11a0c987d1c0b86139; } ?>
<?php $__attrsdfd2184989fa5e11a0c987d1c0b86139 = ['content' => __('Search'),'position' => 'bottom']; ?>
<?php $__slotsdfd2184989fa5e11a0c987d1c0b86139 = []; ?>
<?php $__blaze->pushData($__attrsdfd2184989fa5e11a0c987d1c0b86139); ?>
<?php ob_start(); ?>
                    <?php if (!function_exists('_0f4901f0bb492be280a534ff837631ac')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/navbar/item.blade.php', $__blaze->compiledPath.'/0f4901f0bb492be280a534ff837631ac.php'); require $__blaze->compiledPath.'/0f4901f0bb492be280a534ff837631ac.php'; } ?>
<?php $__blaze->pushData(['class' => '!h-10 [&>div>svg]:size-5','icon' => 'magnifying-glass','href' => '#','label' => __('Search')]); ?>
<?php _0f4901f0bb492be280a534ff837631ac($__blaze, ['class' => '!h-10 [&>div>svg]:size-5','icon' => 'magnifying-glass','href' => '#','label' => __('Search')], [], ['label'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?>
                <?php $__slotsdfd2184989fa5e11a0c987d1c0b86139['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slotsdfd2184989fa5e11a0c987d1c0b86139); ?>
<?php _dfd2184989fa5e11a0c987d1c0b86139($__blaze, $__attrsdfd2184989fa5e11a0c987d1c0b86139, $__slotsdfd2184989fa5e11a0c987d1c0b86139, ['content'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStackdfd2184989fa5e11a0c987d1c0b86139)) { $__slotsdfd2184989fa5e11a0c987d1c0b86139 = array_pop($__slotsStackdfd2184989fa5e11a0c987d1c0b86139); } ?>
<?php if (! empty($__attrsStackdfd2184989fa5e11a0c987d1c0b86139)) { $__attrsdfd2184989fa5e11a0c987d1c0b86139 = array_pop($__attrsStackdfd2184989fa5e11a0c987d1c0b86139); } ?>
<?php $__blaze->popData(); ?>
                <?php if (!function_exists('_dfd2184989fa5e11a0c987d1c0b86139')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/tooltip/index.blade.php', $__blaze->compiledPath.'/dfd2184989fa5e11a0c987d1c0b86139.php'); require $__blaze->compiledPath.'/dfd2184989fa5e11a0c987d1c0b86139.php'; } ?>
<?php if (isset($__slotsdfd2184989fa5e11a0c987d1c0b86139)) { $__slotsStackdfd2184989fa5e11a0c987d1c0b86139[] = $__slotsdfd2184989fa5e11a0c987d1c0b86139; } ?>
<?php if (isset($__attrsdfd2184989fa5e11a0c987d1c0b86139)) { $__attrsStackdfd2184989fa5e11a0c987d1c0b86139[] = $__attrsdfd2184989fa5e11a0c987d1c0b86139; } ?>
<?php $__attrsdfd2184989fa5e11a0c987d1c0b86139 = ['content' => __('Repository'),'position' => 'bottom']; ?>
<?php $__slotsdfd2184989fa5e11a0c987d1c0b86139 = []; ?>
<?php $__blaze->pushData($__attrsdfd2184989fa5e11a0c987d1c0b86139); ?>
<?php ob_start(); ?>
                    <?php if (!function_exists('_0f4901f0bb492be280a534ff837631ac')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/navbar/item.blade.php', $__blaze->compiledPath.'/0f4901f0bb492be280a534ff837631ac.php'); require $__blaze->compiledPath.'/0f4901f0bb492be280a534ff837631ac.php'; } ?>
<?php $__blaze->pushData(['class' => 'h-10 max-lg:hidden [&>div>svg]:size-5','icon' => 'folder-git-2','href' => 'https://github.com/laravel/livewire-starter-kit','target' => '_blank','label' => __('Repository')]); ?>
<?php _0f4901f0bb492be280a534ff837631ac($__blaze, ['class' => 'h-10 max-lg:hidden [&>div>svg]:size-5','icon' => 'folder-git-2','href' => 'https://github.com/laravel/livewire-starter-kit','target' => '_blank','label' => __('Repository')], [], ['label'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?>
                <?php $__slotsdfd2184989fa5e11a0c987d1c0b86139['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slotsdfd2184989fa5e11a0c987d1c0b86139); ?>
<?php _dfd2184989fa5e11a0c987d1c0b86139($__blaze, $__attrsdfd2184989fa5e11a0c987d1c0b86139, $__slotsdfd2184989fa5e11a0c987d1c0b86139, ['content'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStackdfd2184989fa5e11a0c987d1c0b86139)) { $__slotsdfd2184989fa5e11a0c987d1c0b86139 = array_pop($__slotsStackdfd2184989fa5e11a0c987d1c0b86139); } ?>
<?php if (! empty($__attrsStackdfd2184989fa5e11a0c987d1c0b86139)) { $__attrsdfd2184989fa5e11a0c987d1c0b86139 = array_pop($__attrsStackdfd2184989fa5e11a0c987d1c0b86139); } ?>
<?php $__blaze->popData(); ?>
                <?php if (!function_exists('_dfd2184989fa5e11a0c987d1c0b86139')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/tooltip/index.blade.php', $__blaze->compiledPath.'/dfd2184989fa5e11a0c987d1c0b86139.php'); require $__blaze->compiledPath.'/dfd2184989fa5e11a0c987d1c0b86139.php'; } ?>
<?php if (isset($__slotsdfd2184989fa5e11a0c987d1c0b86139)) { $__slotsStackdfd2184989fa5e11a0c987d1c0b86139[] = $__slotsdfd2184989fa5e11a0c987d1c0b86139; } ?>
<?php if (isset($__attrsdfd2184989fa5e11a0c987d1c0b86139)) { $__attrsStackdfd2184989fa5e11a0c987d1c0b86139[] = $__attrsdfd2184989fa5e11a0c987d1c0b86139; } ?>
<?php $__attrsdfd2184989fa5e11a0c987d1c0b86139 = ['content' => __('Documentation'),'position' => 'bottom']; ?>
<?php $__slotsdfd2184989fa5e11a0c987d1c0b86139 = []; ?>
<?php $__blaze->pushData($__attrsdfd2184989fa5e11a0c987d1c0b86139); ?>
<?php ob_start(); ?>
                    <?php if (!function_exists('_0f4901f0bb492be280a534ff837631ac')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/navbar/item.blade.php', $__blaze->compiledPath.'/0f4901f0bb492be280a534ff837631ac.php'); require $__blaze->compiledPath.'/0f4901f0bb492be280a534ff837631ac.php'; } ?>
<?php $__blaze->pushData(['class' => 'h-10 max-lg:hidden [&>div>svg]:size-5','icon' => 'book-open-text','href' => 'https://laravel.com/docs/starter-kits#livewire','target' => '_blank','label' => __('Documentation')]); ?>
<?php _0f4901f0bb492be280a534ff837631ac($__blaze, ['class' => 'h-10 max-lg:hidden [&>div>svg]:size-5','icon' => 'book-open-text','href' => 'https://laravel.com/docs/starter-kits#livewire','target' => '_blank','label' => __('Documentation')], [], ['label'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?>
                <?php $__slotsdfd2184989fa5e11a0c987d1c0b86139['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slotsdfd2184989fa5e11a0c987d1c0b86139); ?>
<?php _dfd2184989fa5e11a0c987d1c0b86139($__blaze, $__attrsdfd2184989fa5e11a0c987d1c0b86139, $__slotsdfd2184989fa5e11a0c987d1c0b86139, ['content'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStackdfd2184989fa5e11a0c987d1c0b86139)) { $__slotsdfd2184989fa5e11a0c987d1c0b86139 = array_pop($__slotsStackdfd2184989fa5e11a0c987d1c0b86139); } ?>
<?php if (! empty($__attrsStackdfd2184989fa5e11a0c987d1c0b86139)) { $__attrsdfd2184989fa5e11a0c987d1c0b86139 = array_pop($__attrsStackdfd2184989fa5e11a0c987d1c0b86139); } ?>
<?php $__blaze->popData(); ?>
            <?php echo trim(ob_get_clean()); ?>

</nav>
<?php $__blaze->popData(); $__env->popConsumableComponentData(); ?><?php echo ltrim(ob_get_clean()); ?>

            <?php if (isset($component)) { $__componentOriginalca54afb14f8d43d7f1acc5dbe6164a0a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalca54afb14f8d43d7f1acc5dbe6164a0a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.desktop-user-menu','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('desktop-user-menu'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalca54afb14f8d43d7f1acc5dbe6164a0a)): ?>
<?php $attributes = $__attributesOriginalca54afb14f8d43d7f1acc5dbe6164a0a; ?>
<?php unset($__attributesOriginalca54afb14f8d43d7f1acc5dbe6164a0a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalca54afb14f8d43d7f1acc5dbe6164a0a)): ?>
<?php $component = $__componentOriginalca54afb14f8d43d7f1acc5dbe6164a0a; ?>
<?php unset($__componentOriginalca54afb14f8d43d7f1acc5dbe6164a0a); ?>
<?php endif; ?>
        <?php echo trim(ob_get_clean()); ?>

        </div>
    </header>
<?php $__blaze->popData(); $__env->popConsumableComponentData(); ?><?php echo ltrim(ob_get_clean()); ?>

        <!-- Mobile Menu -->
        <?php ob_start(); ?><ui-sidebar-toggle class="z-20 fixed inset-0 bg-black/10 hidden data-flux-sidebar-on-mobile:not-data-flux-sidebar-collapsed-mobile:block" data-flux-sidebar-backdrop></ui-sidebar-toggle>

<ui-sidebar
    class="[grid-area:sidebar] z-1 flex flex-col gap-4 [:where(&amp;)]:w-64 p-4 data-flux-sidebar-collapsed-desktop:w-14 data-flux-sidebar-collapsed-desktop:px-2 data-flux-sidebar-collapsed-desktop:cursor-e-resize rtl:data-flux-sidebar-collapsed-desktop:cursor-w-resize max-lg:data-flux-sidebar-cloak:hidden data-flux-sidebar-on-mobile:data-flux-sidebar-collapsed-mobile:-translate-x-full data-flux-sidebar-on-mobile:data-flux-sidebar-collapsed-mobile:rtl:translate-x-full z-20! data-flux-sidebar-on-mobile:start-0! data-flux-sidebar-on-mobile:fixed! data-flux-sidebar-on-mobile:top-0! data-flux-sidebar-on-mobile:min-h-dvh! data-flux-sidebar-on-mobile:max-h-dvh! max-h-dvh overflow-y-auto overscroll-contain lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900" x-init="$el.classList.add(&#039;transition-transform&#039;)"
     collapsible="mobile"          sticky     x-data
    data-flux-sidebar-cloak
    data-flux-sidebar
>
    <?php ob_start(); ?>
            <?php ob_start(); ?><div class="flex items-center justify-between gap-2 min-h-10" data-flux-sidebar-header>
    <?php ob_start(); ?>
                <?php if (isset($component)) { $__componentOriginal7b17d80ff7900603fe9e5f0b453cc7c3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7b17d80ff7900603fe9e5f0b453cc7c3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.app-logo','data' => ['sidebar' => true,'href' => ''.e(route('dashboard')).'','wire:navigate' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['sidebar' => true,'href' => ''.e(route('dashboard')).'','wire:navigate' => true]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7b17d80ff7900603fe9e5f0b453cc7c3)): ?>
<?php $attributes = $__attributesOriginal7b17d80ff7900603fe9e5f0b453cc7c3; ?>
<?php unset($__attributesOriginal7b17d80ff7900603fe9e5f0b453cc7c3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7b17d80ff7900603fe9e5f0b453cc7c3)): ?>
<?php $component = $__componentOriginal7b17d80ff7900603fe9e5f0b453cc7c3; ?>
<?php unset($__componentOriginal7b17d80ff7900603fe9e5f0b453cc7c3); ?>
<?php endif; ?>
                <?php ob_start(); ?><ui-sidebar-toggle class="w-10 h-8 shrink-0 flex items-center justify-center in-data-flux-sidebar-collapsed-desktop:opacity-0 in-data-flux-sidebar-collapsed-desktop:absolute in-data-flux-sidebar-collapsed-desktop:in-data-flux-sidebar-active:opacity-100  in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" data-flux-sidebar-collapse>
    <ui-tooltip position="right center"  data-flux-tooltip >
        <button type="button" class="size-10 relative items-center font-medium justify-center gap-2 whitespace-nowrap disabled:opacity-75 dark:disabled:opacity-75 disabled:cursor-default disabled:pointer-events-none text-sm rounded-lg inline-flex  bg-transparent hover:bg-zinc-800/5 dark:hover:bg-white/15 text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-white in-data-flux-sidebar-collapsed-desktop:cursor-e-resize rtl:in-data-flux-sidebar-collapsed-desktop:cursor-w-resize [&amp;[collapsible=&quot;mobile&quot;]]:in-data-flux-sidebar-on-desktop:hidden rtl:rotate-180">
            <svg class="text-zinc-500 dark:text-zinc-400" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M7.5 3.75V16.25M3.4375 16.25H16.5625C17.08 16.25 17.5 15.83 17.5 15.3125V4.6875C17.5 4.17 17.08 3.75 16.5625 3.75H3.4375C2.92 3.75 2.5 4.17 2.5 4.6875V15.3125C2.5 15.83 2.92 16.25 3.4375 16.25Z" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
        </button>

                    <div popover="manual" class="relative py-2 px-2.5 rounded-md text-xs text-white font-medium bg-zinc-800 dark:bg-zinc-700 dark:border dark:border-white/10 p-0 overflow-visible" data-flux-tooltip-content>
    Toggle sidebar

    </div>
            </ui-tooltip>
</ui-sidebar-toggle>
<?php echo ltrim(ob_get_clean()); ?>
            <?php echo trim(ob_get_clean()); ?>

</div><?php echo ltrim(ob_get_clean()); ?>

            <?php ob_start(); ?><nav class="flex flex-col overflow-visible min-h-auto" data-flux-sidebar-nav>
    <?php ob_start(); ?>
                <?php if (!function_exists('_8b789c46737bdaaa51d91d901305ef0b')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/sidebar/group.blade.php', $__blaze->compiledPath.'/8b789c46737bdaaa51d91d901305ef0b.php'); require $__blaze->compiledPath.'/8b789c46737bdaaa51d91d901305ef0b.php'; } ?>
<?php if (isset($__slots8b789c46737bdaaa51d91d901305ef0b)) { $__slotsStack8b789c46737bdaaa51d91d901305ef0b[] = $__slots8b789c46737bdaaa51d91d901305ef0b; } ?>
<?php if (isset($__attrs8b789c46737bdaaa51d91d901305ef0b)) { $__attrsStack8b789c46737bdaaa51d91d901305ef0b[] = $__attrs8b789c46737bdaaa51d91d901305ef0b; } ?>
<?php $__attrs8b789c46737bdaaa51d91d901305ef0b = ['heading' => __('Platform')]; ?>
<?php $__slots8b789c46737bdaaa51d91d901305ef0b = []; ?>
<?php $__blaze->pushData($__attrs8b789c46737bdaaa51d91d901305ef0b); ?>
<?php ob_start(); ?>
                    <?php if (!function_exists('_39d3fbfd452fb5b395fbe5cc2adee7d4')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/sidebar/item.blade.php', $__blaze->compiledPath.'/39d3fbfd452fb5b395fbe5cc2adee7d4.php'); require $__blaze->compiledPath.'/39d3fbfd452fb5b395fbe5cc2adee7d4.php'; } ?>
<?php if (isset($__slots39d3fbfd452fb5b395fbe5cc2adee7d4)) { $__slotsStack39d3fbfd452fb5b395fbe5cc2adee7d4[] = $__slots39d3fbfd452fb5b395fbe5cc2adee7d4; } ?>
<?php if (isset($__attrs39d3fbfd452fb5b395fbe5cc2adee7d4)) { $__attrsStack39d3fbfd452fb5b395fbe5cc2adee7d4[] = $__attrs39d3fbfd452fb5b395fbe5cc2adee7d4; } ?>
<?php $__attrs39d3fbfd452fb5b395fbe5cc2adee7d4 = ['icon' => 'layout-grid','href' => route('dashboard'),'current' => request()->routeIs('dashboard'),'wire:navigate' => true]; ?>
<?php $__slots39d3fbfd452fb5b395fbe5cc2adee7d4 = []; ?>
<?php $__blaze->pushData($__attrs39d3fbfd452fb5b395fbe5cc2adee7d4); ?>
<?php ob_start(); ?>
                        <?php echo e(__('Dashboard')); ?>

                    <?php $__slots39d3fbfd452fb5b395fbe5cc2adee7d4['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots39d3fbfd452fb5b395fbe5cc2adee7d4); ?>
<?php _39d3fbfd452fb5b395fbe5cc2adee7d4($__blaze, $__attrs39d3fbfd452fb5b395fbe5cc2adee7d4, $__slots39d3fbfd452fb5b395fbe5cc2adee7d4, ['href', 'current', 'wire:navigate'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack39d3fbfd452fb5b395fbe5cc2adee7d4)) { $__slots39d3fbfd452fb5b395fbe5cc2adee7d4 = array_pop($__slotsStack39d3fbfd452fb5b395fbe5cc2adee7d4); } ?>
<?php if (! empty($__attrsStack39d3fbfd452fb5b395fbe5cc2adee7d4)) { $__attrs39d3fbfd452fb5b395fbe5cc2adee7d4 = array_pop($__attrsStack39d3fbfd452fb5b395fbe5cc2adee7d4); } ?>
<?php $__blaze->popData(); ?>
                <?php $__slots8b789c46737bdaaa51d91d901305ef0b['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots8b789c46737bdaaa51d91d901305ef0b); ?>
<?php _8b789c46737bdaaa51d91d901305ef0b($__blaze, $__attrs8b789c46737bdaaa51d91d901305ef0b, $__slots8b789c46737bdaaa51d91d901305ef0b, ['heading'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack8b789c46737bdaaa51d91d901305ef0b)) { $__slots8b789c46737bdaaa51d91d901305ef0b = array_pop($__slotsStack8b789c46737bdaaa51d91d901305ef0b); } ?>
<?php if (! empty($__attrsStack8b789c46737bdaaa51d91d901305ef0b)) { $__attrs8b789c46737bdaaa51d91d901305ef0b = array_pop($__attrsStack8b789c46737bdaaa51d91d901305ef0b); } ?>
<?php $__blaze->popData(); ?>
            <?php echo trim(ob_get_clean()); ?>

</nav>
<?php echo ltrim(ob_get_clean()); ?>

            <?php ob_start(); ?><div class="flex-1" data-flux-spacer></div>
<?php echo ltrim(ob_get_clean()); ?>

            <?php ob_start(); ?><nav class="flex flex-col overflow-visible min-h-auto" data-flux-sidebar-nav>
    <?php ob_start(); ?>
                <?php if (!function_exists('_39d3fbfd452fb5b395fbe5cc2adee7d4')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/sidebar/item.blade.php', $__blaze->compiledPath.'/39d3fbfd452fb5b395fbe5cc2adee7d4.php'); require $__blaze->compiledPath.'/39d3fbfd452fb5b395fbe5cc2adee7d4.php'; } ?>
<?php if (isset($__slots39d3fbfd452fb5b395fbe5cc2adee7d4)) { $__slotsStack39d3fbfd452fb5b395fbe5cc2adee7d4[] = $__slots39d3fbfd452fb5b395fbe5cc2adee7d4; } ?>
<?php if (isset($__attrs39d3fbfd452fb5b395fbe5cc2adee7d4)) { $__attrsStack39d3fbfd452fb5b395fbe5cc2adee7d4[] = $__attrs39d3fbfd452fb5b395fbe5cc2adee7d4; } ?>
<?php $__attrs39d3fbfd452fb5b395fbe5cc2adee7d4 = ['icon' => 'folder-git-2','href' => 'https://github.com/laravel/livewire-starter-kit','target' => '_blank']; ?>
<?php $__slots39d3fbfd452fb5b395fbe5cc2adee7d4 = []; ?>
<?php $__blaze->pushData($__attrs39d3fbfd452fb5b395fbe5cc2adee7d4); ?>
<?php ob_start(); ?>
                    <?php echo e(__('Repository')); ?>

                <?php $__slots39d3fbfd452fb5b395fbe5cc2adee7d4['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots39d3fbfd452fb5b395fbe5cc2adee7d4); ?>
<?php _39d3fbfd452fb5b395fbe5cc2adee7d4($__blaze, $__attrs39d3fbfd452fb5b395fbe5cc2adee7d4, $__slots39d3fbfd452fb5b395fbe5cc2adee7d4, [], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack39d3fbfd452fb5b395fbe5cc2adee7d4)) { $__slots39d3fbfd452fb5b395fbe5cc2adee7d4 = array_pop($__slotsStack39d3fbfd452fb5b395fbe5cc2adee7d4); } ?>
<?php if (! empty($__attrsStack39d3fbfd452fb5b395fbe5cc2adee7d4)) { $__attrs39d3fbfd452fb5b395fbe5cc2adee7d4 = array_pop($__attrsStack39d3fbfd452fb5b395fbe5cc2adee7d4); } ?>
<?php $__blaze->popData(); ?>
                <?php if (!function_exists('_39d3fbfd452fb5b395fbe5cc2adee7d4')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/sidebar/item.blade.php', $__blaze->compiledPath.'/39d3fbfd452fb5b395fbe5cc2adee7d4.php'); require $__blaze->compiledPath.'/39d3fbfd452fb5b395fbe5cc2adee7d4.php'; } ?>
<?php if (isset($__slots39d3fbfd452fb5b395fbe5cc2adee7d4)) { $__slotsStack39d3fbfd452fb5b395fbe5cc2adee7d4[] = $__slots39d3fbfd452fb5b395fbe5cc2adee7d4; } ?>
<?php if (isset($__attrs39d3fbfd452fb5b395fbe5cc2adee7d4)) { $__attrsStack39d3fbfd452fb5b395fbe5cc2adee7d4[] = $__attrs39d3fbfd452fb5b395fbe5cc2adee7d4; } ?>
<?php $__attrs39d3fbfd452fb5b395fbe5cc2adee7d4 = ['icon' => 'book-open-text','href' => 'https://laravel.com/docs/starter-kits#livewire','target' => '_blank']; ?>
<?php $__slots39d3fbfd452fb5b395fbe5cc2adee7d4 = []; ?>
<?php $__blaze->pushData($__attrs39d3fbfd452fb5b395fbe5cc2adee7d4); ?>
<?php ob_start(); ?>
                    <?php echo e(__('Documentation')); ?>

                <?php $__slots39d3fbfd452fb5b395fbe5cc2adee7d4['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots39d3fbfd452fb5b395fbe5cc2adee7d4); ?>
<?php _39d3fbfd452fb5b395fbe5cc2adee7d4($__blaze, $__attrs39d3fbfd452fb5b395fbe5cc2adee7d4, $__slots39d3fbfd452fb5b395fbe5cc2adee7d4, [], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack39d3fbfd452fb5b395fbe5cc2adee7d4)) { $__slots39d3fbfd452fb5b395fbe5cc2adee7d4 = array_pop($__slotsStack39d3fbfd452fb5b395fbe5cc2adee7d4); } ?>
<?php if (! empty($__attrsStack39d3fbfd452fb5b395fbe5cc2adee7d4)) { $__attrs39d3fbfd452fb5b395fbe5cc2adee7d4 = array_pop($__attrsStack39d3fbfd452fb5b395fbe5cc2adee7d4); } ?>
<?php $__blaze->popData(); ?>
            <?php echo trim(ob_get_clean()); ?>

</nav>
<?php echo ltrim(ob_get_clean()); ?>
        <?php echo trim(ob_get_clean()); ?>

</ui-sidebar>
<?php echo ltrim(ob_get_clean()); ?>

        <?php echo e($slot); ?>


        <?php app("livewire")->forceAssetInjection(); ?><div x-persist="<?php echo e('toast'); ?>">
            <?php ob_start(); ?><ui-toast-group x-data x-on:toast-show.document="$el.showToast($event.detail)" popover="manual" position="bottom end"  wire:ignore>
    <?php ob_start(); ?>
                <?php ob_start(); ?><ui-toast x-data x-on:toast-show.document="! $el.closest('ui-toast-group') && $el.showToast($event.detail)" popover="manual" position="bottom end" wire:ignore>
    <template>
        <div class="group/toast max-w-sm in-[ui-toast-group]:max-w-auto in-[ui-toast-group]:w-xs sm:in-[ui-toast-group]:w-sm" data-variant=""  data-flux-toast-dialog>
            <div class="p-2 flex rounded-xl shadow-lg bg-white border border-zinc-200 border-b-zinc-300/80 dark:bg-zinc-700 dark:border-zinc-600 group-data-invert/toast:bg-zinc-800 group-data-invert/toast:border-zinc-700 dark:group-data-invert/toast:bg-white dark:group-data-invert/toast:border-zinc-200 dark:group-data-invert/toast:border-b-zinc-300/80">
                <div class="flex-1 flex items-start gap-4 has-data-flux-toast-action:gap-2 overflow-hidden">
                    <div class="flex-1 py-1.5 ps-2.5 flex gap-2">
                        
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="hidden [[data-flux-toast-dialog][data-variant=success]_&]:block shrink-0 mt-0.5 size-4 text-lime-600 dark:text-lime-400">
                            <path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14Zm3.844-8.791a.75.75 0 0 0-1.188-.918l-3.7 4.79-1.649-1.833a.75.75 0 1 0-1.114 1.004l2.25 2.5a.75.75 0 0 0 1.15-.043l4.25-5.5Z" clip-rule="evenodd" />
                        </svg>

                        
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="hidden [[data-flux-toast-dialog][data-variant=warning]_&]:block shrink-0 mt-0.5 size-4 text-amber-500 dark:text-amber-400">
                            <path fill-rule="evenodd" d="M6.701 2.25c.577-1 2.02-1 2.598 0l5.196 9a1.5 1.5 0 0 1-1.299 2.25H2.804a1.5 1.5 0 0 1-1.3-2.25l5.197-9ZM8 4a.75.75 0 0 1 .75.75v3a.75.75 0 1 1-1.5 0v-3A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                        </svg>

                        
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="hidden [[data-flux-toast-dialog][data-variant=info]_&]:block shrink-0 mt-0.5 size-4 text-cyan-500 dark:text-cyan-400">
                            <path fill-rule="evenodd" d="M15 8A7 7 0 1 1 1 8a7 7 0 0 1 14 0ZM9 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM6.75 8a.75.75 0 0 0 0 1.5h.75v1.75a.75.75 0 0 0 1.5 0v-2.5A.75.75 0 0 0 8.25 8h-1.5Z" clip-rule="evenodd" />
                        </svg>

                        
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="hidden [[data-flux-toast-dialog][data-variant=danger]_&]:block shrink-0 mt-0.5 size-4 text-rose-500 dark:text-rose-400">
                            <path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14ZM8 4a.75.75 0 0 1 .75.75v3a.75.75 0 0 1-1.5 0v-3A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                        </svg>

                        <div>
                            
                            <div class="font-medium text-sm text-zinc-800 dark:text-white group-data-invert/toast:text-white dark:group-data-invert/toast:text-zinc-800 [&:not(:empty)+div]:font-normal [&:not(:empty)+div]:text-zinc-500 [&:not(:empty)+div]:dark:text-zinc-300 group-data-invert/toast:[&:not(:empty)+div]:text-zinc-300 dark:group-data-invert/toast:[&:not(:empty)+div]:text-zinc-500 [&:not(:empty)]:pb-2"><slot name="heading"></slot></div>

                            
                            <div class="font-medium text-sm text-zinc-800 dark:text-white group-data-invert/toast:text-white dark:group-data-invert/toast:text-zinc-800"><slot name="text"></slot></div>

                            
                            <template name="link">
                                <a class="block mt-2 font-medium text-sm text-[var(--color-accent-content)] decoration-[color-mix(in_oklab,var(--color-accent-content),transparent_80%)] underline underline-offset-[6px] hover:decoration-current group-data-invert/toast:text-white group-data-invert/toast:decoration-white/20 dark:group-data-invert/toast:text-zinc-800 dark:group-data-invert/toast:decoration-zinc-800/20"><slot name="text"></slot></a>
                            </template>
                        </div>
                    </div>

                    <div class="flex items-center gap-1">
                        
                        <template name="action">
                            <div class="flex items-center" data-flux-toast-action>
                                <button type="button" data-flux-toast-action-button class="relative inline-flex h-8 items-center justify-center gap-2 whitespace-nowrap rounded-md bg-zinc-800/5 px-3 text-sm font-medium text-zinc-800 hover:bg-zinc-800/10 data-loading:pointer-events-none dark:bg-white/10 dark:text-white dark:hover:bg-white/20 group-data-invert/toast:bg-white/10 group-data-invert/toast:text-white group-data-invert/toast:hover:bg-white/20 dark:group-data-invert/toast:bg-zinc-800/5 dark:group-data-invert/toast:text-zinc-800 dark:group-data-invert/toast:hover:bg-zinc-800/10 [&[data-loading]_[data-flux-toast-action-label]]:opacity-0 [&[data-loading]_[data-flux-loading-indicator]]:opacity-100">
                                    <span class="transition-opacity" data-flux-toast-action-label></span>
                                    <span class="absolute inset-0 flex items-center justify-center opacity-0 transition-opacity" data-flux-loading-indicator>
                                        <svg class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z"></path>
                                        </svg>
                                    </span>
                                </button>

                                <a data-flux-toast-action-link class="inline-flex h-8 items-center justify-center gap-2 whitespace-nowrap rounded-md bg-zinc-800/5 px-3 text-sm font-medium text-zinc-800 hover:bg-zinc-800/10 dark:bg-white/10 dark:text-white dark:hover:bg-white/20 group-data-invert/toast:bg-white/10 group-data-invert/toast:text-white group-data-invert/toast:hover:bg-white/20 dark:group-data-invert/toast:bg-zinc-800/5 dark:group-data-invert/toast:text-zinc-800 dark:group-data-invert/toast:hover:bg-zinc-800/10">
                                    <span data-flux-toast-action-label></span>
                                </a>
                            </div>
                        </template>

                        
                        <ui-close class="flex items-center">
                            <button type="button" class="inline-flex items-center font-medium justify-center gap-2 truncate disabled:opacity-50 dark:disabled:opacity-75 disabled:cursor-default h-8 text-sm rounded-md w-8 bg-transparent hover:bg-zinc-800/5 dark:hover:bg-white/15 text-zinc-400 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-white group-data-invert/toast:hover:bg-white/15 group-data-invert/toast:hover:text-white dark:group-data-invert/toast:hover:bg-zinc-800/5 dark:group-data-invert/toast:hover:text-zinc-800" as="button">
                                <div>
                                    <svg class="[:where(&)]:size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"></path>
                                    </svg>
                                </div>
                            </button>
                        </ui-close>
                    </div>
                </div>
            </div>
        </div>
    </template>
</ui-toast>
<?php echo ltrim(ob_get_clean()); ?>
            <?php echo trim(ob_get_clean()); ?>

</ui-toast-group>
<?php echo ltrim(ob_get_clean()); ?>
        </div>

        <?php app('livewire')->forceAssetInjection(); ?>
<?php echo app('flux')->scripts(); ?>

    </body>
</html>
<?php /**PATH F:\Website\rizky-tools-ai\resources\views\layouts\app\header.blade.php ENDPATH**/ ?>