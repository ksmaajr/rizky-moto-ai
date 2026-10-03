<?php
if (!function_exists('_44826ca8e14b5376ed895d48289b8515')):
function _44826ca8e14b5376ed895d48289b8515($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
$__env = $__blaze->env;
$__slots['slot'] ??= new \Illuminate\View\ComponentSlot('');
if (($__data['attributes'] ?? null) instanceof \Illuminate\View\ComponentAttributeBag) { $__data = $__data + $__data['attributes']->all(); unset($__data['attributes']); }
extract($__slots, EXTR_SKIP); unset($__slots);
extract($__data, EXTR_SKIP);
$attributes = \Livewire\Blaze\Runtime\BlazeAttributeBag::make($__data, $__bound, $__keys);
unset($__data, $__bound, $__keys);
ob_start();
?>


<?php $onLabel ??= $attributes->pluck('on:label'); ?>
<?php $offLabel ??= $attributes->pluck('off:label'); ?>
<?php $onIcon ??= $attributes->pluck('on:icon'); ?>
<?php $offIcon ??= $attributes->pluck('off:icon'); ?>

<?php
$__defaults = [
    'variant' => 'outline',
    'checked' => null,
    'size' => 'base',
    'name' => null,
    'icon' => null,
    'label' => null,
    'color' => null,
    'inset' => null,
    'onLabel' => null,
    'offLabel' => null,
    'onIcon' => null,
    'offIcon' => null,
];
$variant ??= $attributes['variant'] ?? $__defaults['variant']; unset($attributes['variant']);
$checked ??= $attributes['checked'] ?? $__defaults['checked']; unset($attributes['checked']);
$size ??= $attributes['size'] ?? $__defaults['size']; unset($attributes['size']);
$name ??= $attributes['name'] ?? $__defaults['name']; unset($attributes['name']);
$icon ??= $attributes['icon'] ?? $__defaults['icon']; unset($attributes['icon']);
$label ??= $attributes['label'] ?? $__defaults['label']; unset($attributes['label']);
$color ??= $attributes['color'] ?? $__defaults['color']; unset($attributes['color']);
$inset ??= $attributes['inset'] ?? $__defaults['inset']; unset($attributes['inset']);
$onLabel ??= $attributes['on-label'] ?? $attributes['onLabel'] ?? $__defaults['onLabel']; unset($attributes['onLabel'], $attributes['on-label']);
$offLabel ??= $attributes['off-label'] ?? $attributes['offLabel'] ?? $__defaults['offLabel']; unset($attributes['offLabel'], $attributes['off-label']);
$onIcon ??= $attributes['on-icon'] ?? $attributes['onIcon'] ?? $__defaults['onIcon']; unset($attributes['onIcon'], $attributes['on-icon']);
$offIcon ??= $attributes['off-icon'] ?? $attributes['offIcon'] ?? $__defaults['offIcon']; unset($attributes['offIcon'], $attributes['off-icon']);
unset($__defaults);
?>

<?php
// We only want to show the name attribute if it has been set manually,
// but not if it has been inferred from the wire:model attribute...
$showName = isset($name);

if (! isset($name)) {
    $name = $attributes->whereStartsWith('wire:model')->first();
}

$onIcon = is_string($onIcon) && $onIcon !== '' ? $onIcon : null;
$offIcon = is_string($offIcon) && $offIcon !== '' ? $offIcon : null;

$square = $slot->isEmpty() && ! $onLabel && ! $label;
$hasIcon = $icon || $onIcon;

$iconClasses = Flux::classes()
    ->add(match ($variant) {
        'outline' => 'text-zinc-500/85 dark:text-zinc-300/80 in-data-checked:text-(--color-accent-content) dark:in-data-checked:text-(--color-accent-content)',
        'filled' => 'text-zinc-500/85 dark:text-zinc-300/80 in-data-checked:text-(--color-accent-content) dark:in-data-checked:text-(--color-accent-content)',
        'ghost' => 'text-zinc-500/85 dark:text-zinc-300/80 in-data-checked:text-(--color-accent-content) dark:in-data-checked:text-(--color-accent-content)',
        'subtle' => join(' ', [
            'text-zinc-400/90 group-hover:text-zinc-500 in-data-checked:text-zinc-500 in-data-checked:group-hover:text-zinc-800',
            'dark:text-zinc-500/90 dark:group-hover:text-zinc-400 dark:in-data-checked:text-zinc-400 dark:in-data-checked:group-hover:text-white',
        ])
    })
    ->add($square && $size !== 'xs' ? 'size-5' : 'size-4')
    ->add($attributes->pluck('icon:class'))
    ;

$classes = Flux::classes()
    ->add('group relative inline-flex items-center font-medium justify-center whitespace-nowrap outline-offset-2')
    ->add('transition touch-manipulation')
    ->add([
        '*:transition-opacity',
        '[&[data-loading]>:not([data-flux-loading-indicator])]:opacity-0',
        '[&[data-loading]>[data-flux-loading-indicator]]:opacity-100',
        'data-loading:pointer-events-none',
    ])
    ->add('[&[disabled]]:opacity-50 dark:[&[disabled]]:opacity-50 [&[disabled]]:shadow-none [&[disabled]]:cursor-default [&[disabled]]:pointer-events-none')
    ->add(match ($size) {
        'base' => 'h-10 text-sm rounded-lg gap-2' . ' ' . ($square ? 'w-10' : ($hasIcon ? 'ps-3 pe-4' : 'px-4')),
        'sm' => 'h-8 text-sm rounded-md gap-2' . ' ' . ($square ? 'w-8' : ($hasIcon ? 'ps-2 pe-3' : 'px-3')),
        'xs' => 'h-6 text-xs rounded-md gap-1' . ' ' . ($square ? 'w-6' : ($hasIcon ? 'ps-1 pe-2' : 'px-2')),
    })
    ->add($inset ? match ($size) {
        'base' => $square
            ? Flux::applyInset($inset, top: '-mt-2.5', right: '-me-2.5', bottom: '-mb-2.5', left: '-ms-2.5')
            : Flux::applyInset($inset, top: '-mt-2.5', right: '-me-4', bottom: '-mb-3', left: ($hasIcon ? '-ms-3' : '-ms-4')),
        'sm' => $square
            ? Flux::applyInset($inset, top: '-mt-1.5', right: '-me-1.5', bottom: '-mb-1.5', left: '-ms-1.5')
            : Flux::applyInset($inset, top: '-mt-1.5', right: '-me-3', bottom: '-mb-1.5', left: ($hasIcon ? '-ms-2' : '-ms-3')),
        'xs' => $square
            ? Flux::applyInset($inset, top: '-mt-1', right: '-me-1', bottom: '-mb-1', left: '-ms-1')
            : Flux::applyInset($inset, top: '-mt-1', right: '-me-2', bottom: '-mb-1', left: ($hasIcon ? '-ms-1' : '-ms-2')),
    } : '')
    ->add(match ($variant) {
        'outline' => 'bg-white hover:bg-zinc-50 dark:bg-zinc-700 dark:hover:bg-zinc-600/75',
        'filled' => 'bg-zinc-800/5 hover:bg-zinc-800/10 dark:bg-white/10 dark:hover:bg-white/20',
        'ghost' => 'bg-transparent hover:bg-zinc-800/5 dark:hover:bg-white/15',
        'subtle' => 'bg-transparent hover:bg-zinc-800/5 dark:hover:bg-white/15',
    })
    ->add(match ($variant) { // Text color...
        'outline' => 'text-zinc-600/85 data-checked:text-zinc-800 dark:text-zinc-300/95 dark:data-checked:text-white',
        'filled' => 'text-zinc-600/85 data-checked:text-zinc-800 dark:text-zinc-300/95 dark:data-checked:text-white',
        'ghost' => 'text-zinc-600/85 data-checked:text-zinc-800 dark:text-zinc-300/95 dark:data-checked:text-white',
        'subtle' => join(' ', [
            'text-zinc-500/85 hover:text-zinc-500 data-checked:text-zinc-500 data-checked:hover:text-zinc-800',
            'dark:text-zinc-400/80 dark:hover:text-zinc-300 dark:data-checked:text-zinc-400 dark:data-checked:hover:text-white',
        ])
    })
    ->add(match ($variant) {
        'outline' => 'border border-zinc-200 hover:border-zinc-200 border-b-zinc-300/80 dark:border-zinc-600 dark:hover:border-zinc-600',
        default => '',
    })
    ->add(match ($variant) {
        'outline' => match ($size) {
            'base', 'sm' => 'shadow-xs',
            'xs' => 'shadow-none',
        },
        default => '',
    })
    ;
?>

<?php if (!function_exists('_2ed90dc8f75ccd7d63505773a2bd6a32')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/accent.blade.php', $__blaze->compiledPath.'/2ed90dc8f75ccd7d63505773a2bd6a32.php'); require $__blaze->compiledPath.'/2ed90dc8f75ccd7d63505773a2bd6a32.php'; } ?>
<?php if (isset($__slots2ed90dc8f75ccd7d63505773a2bd6a32)) { $__slotsStack2ed90dc8f75ccd7d63505773a2bd6a32[] = $__slots2ed90dc8f75ccd7d63505773a2bd6a32; } ?>
<?php if (isset($__attrs2ed90dc8f75ccd7d63505773a2bd6a32)) { $__attrsStack2ed90dc8f75ccd7d63505773a2bd6a32[] = $__attrs2ed90dc8f75ccd7d63505773a2bd6a32; } ?>
<?php $__attrs2ed90dc8f75ccd7d63505773a2bd6a32 = ['color' => $color,'class' => 'contents']; ?>
<?php $__slots2ed90dc8f75ccd7d63505773a2bd6a32 = []; ?>
<?php $__blaze->pushData($__attrs2ed90dc8f75ccd7d63505773a2bd6a32); ?>
<?php ob_start(); ?>
    <?php if (!function_exists('_a08d1a89f104f74d8bf8c4fbf195c8fb')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/with-tooltip.blade.php', $__blaze->compiledPath.'/a08d1a89f104f74d8bf8c4fbf195c8fb.php'); require $__blaze->compiledPath.'/a08d1a89f104f74d8bf8c4fbf195c8fb.php'; } ?>
<?php if (isset($__slotsa08d1a89f104f74d8bf8c4fbf195c8fb)) { $__slotsStacka08d1a89f104f74d8bf8c4fbf195c8fb[] = $__slotsa08d1a89f104f74d8bf8c4fbf195c8fb; } ?>
<?php if (isset($__attrsa08d1a89f104f74d8bf8c4fbf195c8fb)) { $__attrsStacka08d1a89f104f74d8bf8c4fbf195c8fb[] = $__attrsa08d1a89f104f74d8bf8c4fbf195c8fb; } ?>
<?php $__attrsa08d1a89f104f74d8bf8c4fbf195c8fb = ['attributes' => $attributes]; ?>
<?php $__slotsa08d1a89f104f74d8bf8c4fbf195c8fb = []; ?>
<?php $__blaze->pushData($__attrsa08d1a89f104f74d8bf8c4fbf195c8fb); ?>
<?php ob_start(); ?>
        <ui-switch <?php echo e($attributes->class($classes)); ?> <?php if($showName): ?> name="<?php echo e($name); ?>" <?php endif; ?> <?php if($checked): ?> checked data-checked <?php endif; ?> data-flux-control data-flux-toggle>
            <div class="absolute inset-0 flex items-center justify-center opacity-0" data-flux-loading-indicator aria-hidden="true">
                <?php $blaze_memoized_key = \Livewire\Blaze\Memoizer\Memo::key("flux::icon", ['icon' => 'loading', 'variant' => 'micro', 'class' => $square && $size !== 'xs' ? 'size-5' : 'size-4']); ?><?php if ($blaze_memoized_key !== null && \Livewire\Blaze\Memoizer\Memo::has($blaze_memoized_key)) : ?><?php echo \Livewire\Blaze\Memoizer\Memo::get($blaze_memoized_key); ?><?php else : ?><?php ob_start(); ?><?php if (!function_exists('_5a41e952545281559923d327e8a7de08')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/index.blade.php', $__blaze->compiledPath.'/5a41e952545281559923d327e8a7de08.php'); require $__blaze->compiledPath.'/5a41e952545281559923d327e8a7de08.php'; } ?>
<?php $__blaze->pushData(['icon' => 'loading','variant' => 'micro','class' => $square && $size !== 'xs' ? 'size-5' : 'size-4']); ?>
<?php _5a41e952545281559923d327e8a7de08($__blaze, ['icon' => 'loading','variant' => 'micro','class' => $square && $size !== 'xs' ? 'size-5' : 'size-4'], [], ['class'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?><?php $blaze_memoized_html = ob_get_clean(); ?><?php if ($blaze_memoized_key !== null) { \Livewire\Blaze\Memoizer\Memo::put($blaze_memoized_key, $blaze_memoized_html); } ?><?php echo $blaze_memoized_html; ?><?php endif; ?>
            </div>

            <?php if ((is_string($icon) && $icon !== '') || $onIcon): ?>
                <?php $blaze_memoized_key = \Livewire\Blaze\Memoizer\Memo::key("flux::icon", ['icon' => $onIcon ?? $icon, 'variant' => 'solid', 'class' => $iconClasses->add('hidden group-data-checked:block')]); ?><?php if ($blaze_memoized_key !== null && \Livewire\Blaze\Memoizer\Memo::has($blaze_memoized_key)) : ?><?php echo \Livewire\Blaze\Memoizer\Memo::get($blaze_memoized_key); ?><?php else : ?><?php ob_start(); ?><?php if (!function_exists('_5a41e952545281559923d327e8a7de08')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/index.blade.php', $__blaze->compiledPath.'/5a41e952545281559923d327e8a7de08.php'); require $__blaze->compiledPath.'/5a41e952545281559923d327e8a7de08.php'; } ?>
<?php $__blaze->pushData(['icon' => $onIcon ?? $icon,'variant' => 'solid','class' => $iconClasses->add('hidden group-data-checked:block')]); ?>
<?php _5a41e952545281559923d327e8a7de08($__blaze, ['icon' => $onIcon ?? $icon,'variant' => 'solid','class' => $iconClasses->add('hidden group-data-checked:block')], [], ['icon', 'class'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?><?php $blaze_memoized_html = ob_get_clean(); ?><?php if ($blaze_memoized_key !== null) { \Livewire\Blaze\Memoizer\Memo::put($blaze_memoized_key, $blaze_memoized_html); } ?><?php echo $blaze_memoized_html; ?><?php endif; ?>
                <?php $blaze_memoized_key = \Livewire\Blaze\Memoizer\Memo::key("flux::icon", ['icon' => $offIcon ?? $onIcon ?? $icon, 'variant' => 'outline', 'class' => $iconClasses->add('group-data-checked:hidden')]); ?><?php if ($blaze_memoized_key !== null && \Livewire\Blaze\Memoizer\Memo::has($blaze_memoized_key)) : ?><?php echo \Livewire\Blaze\Memoizer\Memo::get($blaze_memoized_key); ?><?php else : ?><?php ob_start(); ?><?php if (!function_exists('_5a41e952545281559923d327e8a7de08')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/index.blade.php', $__blaze->compiledPath.'/5a41e952545281559923d327e8a7de08.php'); require $__blaze->compiledPath.'/5a41e952545281559923d327e8a7de08.php'; } ?>
<?php $__blaze->pushData(['icon' => $offIcon ?? $onIcon ?? $icon,'variant' => 'outline','class' => $iconClasses->add('group-data-checked:hidden')]); ?>
<?php _5a41e952545281559923d327e8a7de08($__blaze, ['icon' => $offIcon ?? $onIcon ?? $icon,'variant' => 'outline','class' => $iconClasses->add('group-data-checked:hidden')], [], ['icon', 'class'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?><?php $blaze_memoized_html = ob_get_clean(); ?><?php if ($blaze_memoized_key !== null) { \Livewire\Blaze\Memoizer\Memo::put($blaze_memoized_key, $blaze_memoized_html); } ?><?php echo $blaze_memoized_html; ?><?php endif; ?>
            <?php elseif ($icon): ?>
                <?php echo e($icon); ?>

            <?php endif; ?>

            <?php if ($slot->isNotEmpty() || $onLabel || $label): ?>
                <?php $onLabel = $slot->isNotEmpty() ? $slot : ($onLabel ?? $label); ?>

                <span class="group-data-checked:hidden"><?php echo e($offLabel ?? $onLabel); ?></span>
                <span class="hidden group-data-checked:block"><?php echo e($onLabel); ?></span>
            <?php endif; ?>
        </ui-switch>
    <?php $__slotsa08d1a89f104f74d8bf8c4fbf195c8fb['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slotsa08d1a89f104f74d8bf8c4fbf195c8fb); ?>
<?php _a08d1a89f104f74d8bf8c4fbf195c8fb($__blaze, $__attrsa08d1a89f104f74d8bf8c4fbf195c8fb, $__slotsa08d1a89f104f74d8bf8c4fbf195c8fb, ['attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStacka08d1a89f104f74d8bf8c4fbf195c8fb)) { $__slotsa08d1a89f104f74d8bf8c4fbf195c8fb = array_pop($__slotsStacka08d1a89f104f74d8bf8c4fbf195c8fb); } ?>
<?php if (! empty($__attrsStacka08d1a89f104f74d8bf8c4fbf195c8fb)) { $__attrsa08d1a89f104f74d8bf8c4fbf195c8fb = array_pop($__attrsStacka08d1a89f104f74d8bf8c4fbf195c8fb); } ?>
<?php $__blaze->popData(); ?>
<?php $__slots2ed90dc8f75ccd7d63505773a2bd6a32['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots2ed90dc8f75ccd7d63505773a2bd6a32); ?>
<?php _2ed90dc8f75ccd7d63505773a2bd6a32($__blaze, $__attrs2ed90dc8f75ccd7d63505773a2bd6a32, $__slots2ed90dc8f75ccd7d63505773a2bd6a32, ['color'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack2ed90dc8f75ccd7d63505773a2bd6a32)) { $__slots2ed90dc8f75ccd7d63505773a2bd6a32 = array_pop($__slotsStack2ed90dc8f75ccd7d63505773a2bd6a32); } ?>
<?php if (! empty($__attrsStack2ed90dc8f75ccd7d63505773a2bd6a32)) { $__attrs2ed90dc8f75ccd7d63505773a2bd6a32 = array_pop($__attrsStack2ed90dc8f75ccd7d63505773a2bd6a32); } ?>
<?php $__blaze->popData(); ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\toggle.blade.php ENDPATH**/ ?>