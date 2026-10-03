<?php # [BlazeFolded]:{flux::icon.chevron-down}:{F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/chevron-down.blade.php}:{1789427708} ?>
<?php # [BlazeFolded]:{flux::icon.chevron-right}:{F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/chevron-right.blade.php}:{1789427708} ?>
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'expandable' => false,
    'expanded' => true,
    'heading' => null,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'expandable' => false,
    'expanded' => true,
    'heading' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php if ($expandable && $heading): ?>

<ui-disclosure
    <?php echo e($attributes->class('group/disclosure')); ?>

    <?php if($expanded === true): ?> open <?php endif; ?>
    data-flux-navlist-group
>
    <button
        type="button"
        class="group/disclosure-button mb-[2px] flex h-10 w-full items-center rounded-lg text-zinc-500 hover:bg-zinc-800/5 hover:text-zinc-800 lg:h-8 dark:text-white/80 dark:hover:bg-white/[7%] dark:hover:text-white"
    >
        <div class="ps-3 pe-4">
            <?php ob_start(); ?><svg class="shrink-0 [:where(&amp;)]:size-6 hidden size-3! group-data-open/disclosure-button:block" data-flux-icon xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
  <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
</svg>

        <?php echo ltrim(ob_get_clean()); ?>
            <?php ob_start(); ?><svg class="shrink-0 [:where(&amp;)]:size-6 block size-3! group-data-open/disclosure-button:hidden" data-flux-icon xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
  <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
</svg>

        <?php echo ltrim(ob_get_clean()); ?>
        </div>

        <span class="text-sm font-medium leading-none"><?php echo e($heading); ?></span>
    </button>

    <div class="relative hidden space-y-[2px] ps-7 data-open:block" <?php if($expanded === true): ?> data-open <?php endif; ?>>
        <div class="absolute inset-y-[3px] start-0 ms-4 w-px bg-zinc-200 dark:bg-white/30"></div>

        <?php echo e($slot); ?>

    </div>
</ui-disclosure>

<?php elseif ($heading): ?>

<div <?php echo e($attributes->class('block space-y-[2px]')); ?>>
    <div class="px-1 py-2">
        <div class="text-xs leading-none text-zinc-400"><?php echo e($heading); ?></div>
    </div>

    <div>
        <?php echo e($slot); ?>

    </div>
</div>

<?php else: ?>

<div <?php echo e($attributes->class('block space-y-[2px]')); ?>>
    <?php echo e($slot); ?>

</div>

<?php endif; ?>
<?php /**PATH F:\Website\rizky-tools-ai\resources\views\flux\navlist\group.blade.php ENDPATH**/ ?>