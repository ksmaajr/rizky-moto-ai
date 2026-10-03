<?php
if (!function_exists('_e39abc8d73207df1c59eb6fa11ebc1c9')):
function _e39abc8d73207df1c59eb6fa11ebc1c9($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
$__env = $__blaze->env;
$__slots['slot'] ??= new \Illuminate\View\ComponentSlot('');
if (($__data['attributes'] ?? null) instanceof \Illuminate\View\ComponentAttributeBag) { $__data = $__data + $__data['attributes']->all(); unset($__data['attributes']); }
extract($__slots, EXTR_SKIP); unset($__slots);
extract($__data, EXTR_SKIP);
$attributes = \Livewire\Blaze\Runtime\BlazeAttributeBag::make($__data, $__bound, $__keys);
unset($__data, $__bound, $__keys);
ob_start();
?>


<?php
$__defaults = [
    'accent' => true,
    'size' => 'base',
    'label' => null,
    'icon' => null,
];
$accent ??= $attributes['accent'] ?? $__defaults['accent']; unset($attributes['accent']);
$size ??= $attributes['size'] ?? $__defaults['size']; unset($attributes['size']);
$label ??= $attributes['label'] ?? $__defaults['label']; unset($attributes['label']);
$icon ??= $attributes['icon'] ?? $__defaults['icon']; unset($attributes['icon']);
unset($__defaults);
?>

<?php
$classes = Flux::classes()
    ->add('flex relative items-center font-medium justify-center gap-2 whitespace-nowrap')
    ->add(match ($size) {
        'base' => 'h-10 text-sm rounded-lg px-4 [&:has(>:not(span):first-child)]:ps-3 [&:has(>:not(span):last-child)]:pe-3',
        'sm' => 'h-8 text-sm rounded-md px-3',
        'xs' => 'h-6 text-xs rounded-md px-2',
    })
    ->add(match ($size) {
        'base' => 'shadow-xs',
        'sm' => 'shadow-xs',
        'xs' => 'shadow-none',
    })
    ->add('text-zinc-800 dark:text-white')
    ->add('bg-white dark:bg-zinc-700')
    ->add('after:absolute after:-inset-px after:rounded-lg')
    ->add('border border-zinc-200 border-b-zinc-300/80 dark:border-zinc-600')
    ->add([
        '[--haze:color-mix(in_oklab,_var(--color-accent-content),_transparent_97.5%)]',
        '[--haze-border:color-mix(in_oklab,_var(--color-accent-content),_transparent_80%)]',
        '[--haze-light:color-mix(in_oklab,_var(--color-accent),_transparent_98%)]',
        'dark:[--haze:color-mix(in_oklab,_var(--color-accent-content),_transparent_90%)]',
    ])
    ->add(match ($accent) {
        true => [
            'hover:border-[var(--haze-border)] dark:hover:border-zinc-500',
            'dark:data-checked:bg-white/15 data-checked:border-(--color-accent) hover:data-checked:border-(--color-accent)',
            'hover:after:bg-[var(--haze-light)] dark:hover:after:bg-white/[4%] data-checked:after:bg-(--haze) hover:data-checked:after:bg-(--haze)',
        ],
        false => [
            'hover:border-zinc-200 dark:hover:border-zinc-500',
            'data-checked:bg-zinc-50 dark:data-checked:bg-white/15 data-checked:border-zinc-800 dark:data-checked:border-white',
            'hover:bg-zinc-50 dark:hover:bg-zinc-600/75',
        ],
    })
    ->add('disabled:opacity-75 dark:disabled:opacity-75 disabled:cursor-default disabled:pointer-events-none')
    ;

$iconAttributes = Flux::attributesAfter('icon:', $attributes, [
    'class' => 'text-zinc-300 dark:text-zinc-400 in-data-checked:text-zinc-800 dark:in-data-checked:text-white',
    'variant' => 'micro',
]);
?>




<ui-checkbox <?php echo e($attributes->class($classes)); ?> data-flux-control data-flux-checkbox-buttons tabindex="-1" data-flux-field>
    <?php if (is_string($icon) && $icon !== ''): ?>
        <?php $blaze_memoized_key = \Livewire\Blaze\Memoizer\Memo::key("flux::icon", ['icon' => $icon, 'attributes' => $iconAttributes]); ?><?php if ($blaze_memoized_key !== null && \Livewire\Blaze\Memoizer\Memo::has($blaze_memoized_key)) : ?><?php echo \Livewire\Blaze\Memoizer\Memo::get($blaze_memoized_key); ?><?php else : ?><?php ob_start(); ?><?php if (!function_exists('_5a41e952545281559923d327e8a7de08')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/index.blade.php', $__blaze->compiledPath.'/5a41e952545281559923d327e8a7de08.php'); require $__blaze->compiledPath.'/5a41e952545281559923d327e8a7de08.php'; } ?>
<?php $__blaze->pushData(['icon' => $icon,'attributes' => $iconAttributes]); ?>
<?php _5a41e952545281559923d327e8a7de08($__blaze, ['icon' => $icon,'attributes' => $iconAttributes], [], ['icon', 'attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?><?php $blaze_memoized_html = ob_get_clean(); ?><?php if ($blaze_memoized_key !== null) { \Livewire\Blaze\Memoizer\Memo::put($blaze_memoized_key, $blaze_memoized_html); } ?><?php echo $blaze_memoized_html; ?><?php endif; ?>
    <?php elseif ($icon): ?>
        <?php echo e($icon); ?>

    <?php endif; ?>

    <?php if ($slot->isNotEmpty() || isset($label)): ?>
        <span><?php echo e($slot->isNotEmpty() ? $slot : $label); ?></span>
    <?php endif; ?>
</ui-checkbox>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\checkbox\variants\buttons.blade.php ENDPATH**/ ?>