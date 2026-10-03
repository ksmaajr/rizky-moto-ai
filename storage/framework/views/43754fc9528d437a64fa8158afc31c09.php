<?php
if (!function_exists('_43754fc9528d437a64fa8158afc31c09')):
function _43754fc9528d437a64fa8158afc31c09($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
$__env = $__blaze->env;
$__slots['slot'] ??= new \Illuminate\View\ComponentSlot('');
if (($__data['attributes'] ?? null) instanceof \Illuminate\View\ComponentAttributeBag) { $__data = $__data + $__data['attributes']->all(); unset($__data['attributes']); }
extract($__slots, EXTR_SKIP); unset($__slots);
extract($__data, EXTR_SKIP);
$attributes = \Livewire\Blaze\Runtime\BlazeAttributeBag::make($__data, $__bound, $__keys);
unset($__data, $__bound, $__keys);
ob_start();
?>


<?php $logoDark ??= $attributes->pluck('logo:dark'); ?>
<?php $iconTrailing ??= $attributes->pluck('icon:trailing'); ?>
<?php $iconVariant ??= $attributes->pluck('icon:variant'); ?>

<?php
$__defaults = [
    'iconTrailing' => null,
    'iconVariant' => 'micro',
    'name' => null,
    'logo' => null,
    'logoDark' => null,
    'alt' => null,
    'href' => '/',
    'as' => null,
];
$iconTrailing ??= $attributes['icon-trailing'] ?? $attributes['iconTrailing'] ?? $__defaults['iconTrailing']; unset($attributes['iconTrailing'], $attributes['icon-trailing']);
$iconVariant ??= $attributes['icon-variant'] ?? $attributes['iconVariant'] ?? $__defaults['iconVariant']; unset($attributes['iconVariant'], $attributes['icon-variant']);
$name ??= $attributes['name'] ?? $__defaults['name']; unset($attributes['name']);
$logo ??= $attributes['logo'] ?? $__defaults['logo']; unset($attributes['logo']);
$logoDark ??= $attributes['logo-dark'] ?? $attributes['logoDark'] ?? $__defaults['logoDark']; unset($attributes['logoDark'], $attributes['logo-dark']);
$alt ??= $attributes['alt'] ?? $__defaults['alt']; unset($attributes['alt']);
$href ??= $attributes['href'] ?? $__defaults['href']; unset($attributes['href']);
$as ??= $attributes['as'] ?? $__defaults['as']; unset($attributes['as']);
unset($__defaults);
?>

<?php
$href = $as === 'button' ? null : $href;

$classes = Flux::classes()
    ->add('h-10 min-w-0 flex items-center px-2 in-data-flux-sidebar-collapsed-desktop:w-10 in-data-flux-sidebar-collapsed-desktop:px-0 in-data-flux-sidebar-collapsed-desktop:justify-center')
    ->add('in-data-flux-sidebar-collapsed-desktop:in-data-flux-sidebar-active:absolute')
    ->add('in-data-flux-sidebar-collapsed-desktop:in-data-flux-sidebar-active:opacity-0')
    ->add($as === 'button' ? 'group select-none rounded-lg hover:bg-zinc-800/5 in-data-open:bg-zinc-800/5 dark:hover:bg-white/15 dark:in-data-open:bg-white/15' : '')
    ;

$textClasses = Flux::classes()
    ->add('min-w-0 text-sm font-medium truncate [:where(&)]:text-zinc-800 dark:[:where(&)]:text-zinc-100')
    ;

$iconClasses = Flux::classes()
    ->add('shrink-0 text-zinc-400 group-hover:text-zinc-800 in-data-open:text-zinc-800 dark:text-white/80 dark:group-hover:text-white dark:in-data-open:text-white')
    ->add('in-data-flux-sidebar-collapsed-desktop:hidden')
    ->add($iconVariant === 'outline' ? 'size-4' : '')
    ;
?>

<?php if ($name): ?>
    <?php if (!function_exists('_d45f006b852d1f524dd60e6bb178ebd3')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/button-or-link-pure.blade.php', $__blaze->compiledPath.'/d45f006b852d1f524dd60e6bb178ebd3.php'); require $__blaze->compiledPath.'/d45f006b852d1f524dd60e6bb178ebd3.php'; } ?>
<?php if (isset($__slotsd45f006b852d1f524dd60e6bb178ebd3)) { $__slotsStackd45f006b852d1f524dd60e6bb178ebd3[] = $__slotsd45f006b852d1f524dd60e6bb178ebd3; } ?>
<?php if (isset($__attrsd45f006b852d1f524dd60e6bb178ebd3)) { $__attrsStackd45f006b852d1f524dd60e6bb178ebd3[] = $__attrsd45f006b852d1f524dd60e6bb178ebd3; } ?>
<?php $__attrsd45f006b852d1f524dd60e6bb178ebd3 = ['as' => $as,'href' => $href,'attributes' => $attributes->class([ $classes, 'gap-2' ]),'dataFluxSidebarBrand' => true]; ?>
<?php $__slotsd45f006b852d1f524dd60e6bb178ebd3 = []; ?>
<?php $__blaze->pushData($__attrsd45f006b852d1f524dd60e6bb178ebd3); ?>
<?php ob_start(); ?>
        <?php if ($logo instanceof \Illuminate\View\ComponentSlot): ?>
            <div <?php echo e($logo->attributes->class('flex items-center justify-center [:where(&)]:h-6 [:where(&)]:min-w-6 [:where(&)]:rounded-sm overflow-hidden shrink-0')); ?>>
                <?php echo e($logo); ?>

            </div>
        <?php else: ?>
            <div class="flex items-center justify-center h-6 min-w-6 rounded-sm overflow-hidden shrink-0">
                <?php if ($logoDark): ?>
                    <img src="<?php echo e($logo); ?>" alt="<?php echo e($alt); ?>" class="h-6 min-w-6 dark:hidden" />
                    <img src="<?php echo e($logoDark); ?>" alt="<?php echo e($alt); ?>" class="h-6 min-w-6 hidden dark:block" />
                <?php elseif ($logo): ?>
                    <img src="<?php echo e($logo); ?>" alt="<?php echo e($alt); ?>" class="h-6 min-w-6" />
                <?php else: ?>
                    <?php echo e($slot); ?>

                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="<?php echo e($textClasses); ?> in-data-flux-sidebar-collapsed-desktop:hidden"><?php echo e($name); ?></div>

        <?php if (is_string($iconTrailing) && $iconTrailing !== ''): ?>
            <?php $blaze_memoized_key = \Livewire\Blaze\Memoizer\Memo::key("flux::icon", ['icon' => $iconTrailing, 'variant' => $iconVariant, 'class' => $iconClasses]); ?><?php if ($blaze_memoized_key !== null && \Livewire\Blaze\Memoizer\Memo::has($blaze_memoized_key)) : ?><?php echo \Livewire\Blaze\Memoizer\Memo::get($blaze_memoized_key); ?><?php else : ?><?php ob_start(); ?><?php if (!function_exists('_5a41e952545281559923d327e8a7de08')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/index.blade.php', $__blaze->compiledPath.'/5a41e952545281559923d327e8a7de08.php'); require $__blaze->compiledPath.'/5a41e952545281559923d327e8a7de08.php'; } ?>
<?php $__blaze->pushData(['icon' => $iconTrailing,'variant' => $iconVariant,'class' => $iconClasses]); ?>
<?php _5a41e952545281559923d327e8a7de08($__blaze, ['icon' => $iconTrailing,'variant' => $iconVariant,'class' => $iconClasses], [], ['icon', 'variant', 'class'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?><?php $blaze_memoized_html = ob_get_clean(); ?><?php if ($blaze_memoized_key !== null) { \Livewire\Blaze\Memoizer\Memo::put($blaze_memoized_key, $blaze_memoized_html); } ?><?php echo $blaze_memoized_html; ?><?php endif; ?>
        <?php elseif ($iconTrailing): ?>
            <?php echo e($iconTrailing); ?>

        <?php endif; ?>
    <?php $__slotsd45f006b852d1f524dd60e6bb178ebd3['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slotsd45f006b852d1f524dd60e6bb178ebd3); ?>
<?php _d45f006b852d1f524dd60e6bb178ebd3($__blaze, $__attrsd45f006b852d1f524dd60e6bb178ebd3, $__slotsd45f006b852d1f524dd60e6bb178ebd3, ['as', 'href', 'attributes', 'dataFluxSidebarBrand'], ['dataFluxSidebarBrand' => 'data-flux-sidebar-brand'], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStackd45f006b852d1f524dd60e6bb178ebd3)) { $__slotsd45f006b852d1f524dd60e6bb178ebd3 = array_pop($__slotsStackd45f006b852d1f524dd60e6bb178ebd3); } ?>
<?php if (! empty($__attrsStackd45f006b852d1f524dd60e6bb178ebd3)) { $__attrsd45f006b852d1f524dd60e6bb178ebd3 = array_pop($__attrsStackd45f006b852d1f524dd60e6bb178ebd3); } ?>
<?php $__blaze->popData(); ?>
<?php else: ?>
    <?php if (!function_exists('_d45f006b852d1f524dd60e6bb178ebd3')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/button-or-link-pure.blade.php', $__blaze->compiledPath.'/d45f006b852d1f524dd60e6bb178ebd3.php'); require $__blaze->compiledPath.'/d45f006b852d1f524dd60e6bb178ebd3.php'; } ?>
<?php if (isset($__slotsd45f006b852d1f524dd60e6bb178ebd3)) { $__slotsStackd45f006b852d1f524dd60e6bb178ebd3[] = $__slotsd45f006b852d1f524dd60e6bb178ebd3; } ?>
<?php if (isset($__attrsd45f006b852d1f524dd60e6bb178ebd3)) { $__attrsStackd45f006b852d1f524dd60e6bb178ebd3[] = $__attrsd45f006b852d1f524dd60e6bb178ebd3; } ?>
<?php $__attrsd45f006b852d1f524dd60e6bb178ebd3 = ['as' => $as,'href' => $href,'attributes' => $attributes->class($classes),'dataFluxSidebarBrand' => true]; ?>
<?php $__slotsd45f006b852d1f524dd60e6bb178ebd3 = []; ?>
<?php $__blaze->pushData($__attrsd45f006b852d1f524dd60e6bb178ebd3); ?>
<?php ob_start(); ?>
        <?php if ($logo instanceof \Illuminate\View\ComponentSlot): ?>
            <div <?php echo e($logo->attributes->class('flex items-center justify-center [:where(&)]:h-6 [:where(&)]:min-w-6 [:where(&)]:rounded-sm overflow-hidden shrink-0')); ?>>
                <?php echo e($logo); ?>

            </div>
        <?php else: ?>
            <div class="flex items-center justify-center h-6 rounded-sm overflow-hidden shrink-0">
                <?php if ($logoDark): ?>
                    <img src="<?php echo e($logo); ?>" alt="<?php echo e($alt); ?>" class="h-6 dark:hidden" />
                    <img src="<?php echo e($logoDark); ?>" alt="<?php echo e($alt); ?>" class="h-6 hidden dark:block" />
                <?php elseif ($logo): ?>
                    <img src="<?php echo e($logo); ?>" alt="<?php echo e($alt); ?>" class="h-6" />
                <?php else: ?>
                    <?php echo e($slot); ?>

                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (is_string($iconTrailing) && $iconTrailing !== ''): ?>
            <?php $blaze_memoized_key = \Livewire\Blaze\Memoizer\Memo::key("flux::icon", ['icon' => $iconTrailing, 'variant' => $iconVariant, 'class' => $iconClasses]); ?><?php if ($blaze_memoized_key !== null && \Livewire\Blaze\Memoizer\Memo::has($blaze_memoized_key)) : ?><?php echo \Livewire\Blaze\Memoizer\Memo::get($blaze_memoized_key); ?><?php else : ?><?php ob_start(); ?><?php if (!function_exists('_5a41e952545281559923d327e8a7de08')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/index.blade.php', $__blaze->compiledPath.'/5a41e952545281559923d327e8a7de08.php'); require $__blaze->compiledPath.'/5a41e952545281559923d327e8a7de08.php'; } ?>
<?php $__blaze->pushData(['icon' => $iconTrailing,'variant' => $iconVariant,'class' => $iconClasses]); ?>
<?php _5a41e952545281559923d327e8a7de08($__blaze, ['icon' => $iconTrailing,'variant' => $iconVariant,'class' => $iconClasses], [], ['icon', 'variant', 'class'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?><?php $blaze_memoized_html = ob_get_clean(); ?><?php if ($blaze_memoized_key !== null) { \Livewire\Blaze\Memoizer\Memo::put($blaze_memoized_key, $blaze_memoized_html); } ?><?php echo $blaze_memoized_html; ?><?php endif; ?>
        <?php elseif ($iconTrailing): ?>
            <?php echo e($iconTrailing); ?>

        <?php endif; ?>
    <?php $__slotsd45f006b852d1f524dd60e6bb178ebd3['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slotsd45f006b852d1f524dd60e6bb178ebd3); ?>
<?php _d45f006b852d1f524dd60e6bb178ebd3($__blaze, $__attrsd45f006b852d1f524dd60e6bb178ebd3, $__slotsd45f006b852d1f524dd60e6bb178ebd3, ['as', 'href', 'attributes', 'dataFluxSidebarBrand'], ['dataFluxSidebarBrand' => 'data-flux-sidebar-brand'], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStackd45f006b852d1f524dd60e6bb178ebd3)) { $__slotsd45f006b852d1f524dd60e6bb178ebd3 = array_pop($__slotsStackd45f006b852d1f524dd60e6bb178ebd3); } ?>
<?php if (! empty($__attrsStackd45f006b852d1f524dd60e6bb178ebd3)) { $__attrsd45f006b852d1f524dd60e6bb178ebd3 = array_pop($__attrsStackd45f006b852d1f524dd60e6bb178ebd3); } ?>
<?php $__blaze->popData(); ?>
<?php endif; ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\sidebar\brand.blade.php ENDPATH**/ ?>