<?php
if (!function_exists('_9deff279cbc6352f5d2ef92a39d88a01')):
function _9deff279cbc6352f5d2ef92a39d88a01($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
$__env = $__blaze->env;

if (($__data['attributes'] ?? null) instanceof \Illuminate\View\ComponentAttributeBag) { $__data = $__data + $__data['attributes']->all(); unset($__data['attributes']); }
extract($__slots, EXTR_SKIP); unset($__slots);
extract($__data, EXTR_SKIP);
$attributes = \Livewire\Blaze\Runtime\BlazeAttributeBag::make($__data, $__bound, $__keys);
unset($__data, $__bound, $__keys);
ob_start();
?>


<?php
$__defaults = [
    'name' => null,
    'align' => 'right',
    'checked' => null
];
$name ??= $attributes['name'] ?? $__defaults['name']; unset($attributes['name']);
$align ??= $attributes['align'] ?? $__defaults['align']; unset($attributes['align']);
$checked ??= $attributes['checked'] ?? $__defaults['checked']; unset($attributes['checked']);
unset($__defaults);
?>

<?php
// We only want to show the name attribute it has been set manually
// but not if it has been set from the `wire:model` attribute...
$showName = isset($name);
if (! isset($name)) {
    $name = $attributes->whereStartsWith('wire:model')->first();
}

$classes = Flux::classes()
    ->add('group h-5 w-8 min-w-8 relative inline-flex items-center outline-offset-2')
    ->add('rounded-full')
    ->add('transition')
    ->add('bg-zinc-800/15 [&[disabled]]:opacity-50 dark:bg-transparent dark:border dark:border-white/20 dark:[&[disabled]]:border-white/10')
    ->add('data-loading:opacity-50 data-loading:pointer-events-none dark:data-loading:border-white/10')
    ->add('[print-color-adjust:exact]')
    ->add([
        'data-checked:bg-(--color-accent)',
        'data-checked:border-0',
    ])
    ;

$indicatorClasses = Flux::classes()
    ->add('size-3.5')
    ->add('rounded-full')
    ->add('transition translate-x-[0.1875rem] dark:translate-x-[0.125rem] rtl:-translate-x-[0.1875rem] dark:rtl:-translate-x-[0.125rem]')
    ->add('bg-white')
    ->add([
        'group-data-checked:translate-x-[0.9375rem] rtl:group-data-checked:-translate-x-[0.9375rem]',
        // We have to add the dark variant of the `translate-x-[0.9375rem]` to ensure that if `.dark` is added to an element mid way
        // down the DOM instead of on the root HTML element, that the above `dark:translate-x-[0.125rem]` doesn't over ride it...
        'dark:group-data-checked:translate-x-[0.9375rem] dark:rtl:group-data-checked:-translate-x-[0.9375rem]',
        'group-data-checked:bg-(--color-accent-foreground)',
    ]);
?>

<?php if ($align === 'left' || $align === 'start'): ?>
    <?php if (!function_exists('_431ea3bdbd0be54d76bb8382dde7157d')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/with-inline-field.blade.php', $__blaze->compiledPath.'/431ea3bdbd0be54d76bb8382dde7157d.php'); require $__blaze->compiledPath.'/431ea3bdbd0be54d76bb8382dde7157d.php'; } ?>
<?php if (isset($__slots431ea3bdbd0be54d76bb8382dde7157d)) { $__slotsStack431ea3bdbd0be54d76bb8382dde7157d[] = $__slots431ea3bdbd0be54d76bb8382dde7157d; } ?>
<?php if (isset($__attrs431ea3bdbd0be54d76bb8382dde7157d)) { $__attrsStack431ea3bdbd0be54d76bb8382dde7157d[] = $__attrs431ea3bdbd0be54d76bb8382dde7157d; } ?>
<?php $__attrs431ea3bdbd0be54d76bb8382dde7157d = ['attributes' => $attributes]; ?>
<?php $__slots431ea3bdbd0be54d76bb8382dde7157d = []; ?>
<?php $__blaze->pushData($__attrs431ea3bdbd0be54d76bb8382dde7157d); ?>
<?php ob_start(); ?>
        <ui-switch <?php echo e($attributes->class($classes)); ?> <?php if($showName): ?> name="<?php echo e($name); ?>" <?php endif; ?> <?php if($checked): ?> checked data-checked <?php endif; ?> data-flux-control data-flux-switch>
            <span class="<?php echo e(\Illuminate\Support\Arr::toCssClasses($indicatorClasses)); ?>"></span>
        </ui-switch>
    <?php $__slots431ea3bdbd0be54d76bb8382dde7157d['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots431ea3bdbd0be54d76bb8382dde7157d); ?>
<?php _431ea3bdbd0be54d76bb8382dde7157d($__blaze, $__attrs431ea3bdbd0be54d76bb8382dde7157d, $__slots431ea3bdbd0be54d76bb8382dde7157d, ['attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack431ea3bdbd0be54d76bb8382dde7157d)) { $__slots431ea3bdbd0be54d76bb8382dde7157d = array_pop($__slotsStack431ea3bdbd0be54d76bb8382dde7157d); } ?>
<?php if (! empty($__attrsStack431ea3bdbd0be54d76bb8382dde7157d)) { $__attrs431ea3bdbd0be54d76bb8382dde7157d = array_pop($__attrsStack431ea3bdbd0be54d76bb8382dde7157d); } ?>
<?php $__blaze->popData(); ?>
<?php else: ?>
    <?php if (!function_exists('_682b084d9a3a87b3c3d6c019d9459eca')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/with-reversed-inline-field.blade.php', $__blaze->compiledPath.'/682b084d9a3a87b3c3d6c019d9459eca.php'); require $__blaze->compiledPath.'/682b084d9a3a87b3c3d6c019d9459eca.php'; } ?>
<?php if (isset($__slots682b084d9a3a87b3c3d6c019d9459eca)) { $__slotsStack682b084d9a3a87b3c3d6c019d9459eca[] = $__slots682b084d9a3a87b3c3d6c019d9459eca; } ?>
<?php if (isset($__attrs682b084d9a3a87b3c3d6c019d9459eca)) { $__attrsStack682b084d9a3a87b3c3d6c019d9459eca[] = $__attrs682b084d9a3a87b3c3d6c019d9459eca; } ?>
<?php $__attrs682b084d9a3a87b3c3d6c019d9459eca = ['attributes' => $attributes]; ?>
<?php $__slots682b084d9a3a87b3c3d6c019d9459eca = []; ?>
<?php $__blaze->pushData($__attrs682b084d9a3a87b3c3d6c019d9459eca); ?>
<?php ob_start(); ?>
        <ui-switch <?php echo e($attributes->class($classes)); ?> <?php if($showName): ?> name="<?php echo e($name); ?>" <?php endif; ?> <?php if($checked): ?> checked data-checked <?php endif; ?> data-flux-control data-flux-switch>
            <span class="<?php echo e(\Illuminate\Support\Arr::toCssClasses($indicatorClasses)); ?>"></span>
        </ui-switch>
    <?php $__slots682b084d9a3a87b3c3d6c019d9459eca['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots682b084d9a3a87b3c3d6c019d9459eca); ?>
<?php _682b084d9a3a87b3c3d6c019d9459eca($__blaze, $__attrs682b084d9a3a87b3c3d6c019d9459eca, $__slots682b084d9a3a87b3c3d6c019d9459eca, ['attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack682b084d9a3a87b3c3d6c019d9459eca)) { $__slots682b084d9a3a87b3c3d6c019d9459eca = array_pop($__slotsStack682b084d9a3a87b3c3d6c019d9459eca); } ?>
<?php if (! empty($__attrsStack682b084d9a3a87b3c3d6c019d9459eca)) { $__attrs682b084d9a3a87b3c3d6c019d9459eca = array_pop($__attrsStack682b084d9a3a87b3c3d6c019d9459eca); } ?>
<?php $__blaze->popData(); ?>
<?php endif; ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\switch.blade.php ENDPATH**/ ?>