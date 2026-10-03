<?php
if (!function_exists('_dd6f15c5b0658c22652e5cdb3e10893b')):
function _dd6f15c5b0658c22652e5cdb3e10893b($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
];
$name ??= $attributes['name'] ?? $__defaults['name']; unset($attributes['name']);
unset($__defaults);
?>

<?php
// We only want to show the name attribute on the checkbox if it has been set
// manually, but not if it has been set from the wire:model attribute...
$showName = isset($name);

if (! isset($name)) {
    $name = $attributes->whereStartsWith('wire:model')->first();
}

$classes = Flux::classes()
    ->add('flex size-[1.125rem] rounded-[.3rem] mt-px outline-offset-2')
    ;
?>

<?php if (!function_exists('_431ea3bdbd0be54d76bb8382dde7157d')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/with-inline-field.blade.php', $__blaze->compiledPath.'/431ea3bdbd0be54d76bb8382dde7157d.php'); require $__blaze->compiledPath.'/431ea3bdbd0be54d76bb8382dde7157d.php'; } ?>
<?php if (isset($__slots431ea3bdbd0be54d76bb8382dde7157d)) { $__slotsStack431ea3bdbd0be54d76bb8382dde7157d[] = $__slots431ea3bdbd0be54d76bb8382dde7157d; } ?>
<?php if (isset($__attrs431ea3bdbd0be54d76bb8382dde7157d)) { $__attrsStack431ea3bdbd0be54d76bb8382dde7157d[] = $__attrs431ea3bdbd0be54d76bb8382dde7157d; } ?>
<?php $__attrs431ea3bdbd0be54d76bb8382dde7157d = ['attributes' => $attributes]; ?>
<?php $__slots431ea3bdbd0be54d76bb8382dde7157d = []; ?>
<?php $__blaze->pushData($__attrs431ea3bdbd0be54d76bb8382dde7157d); ?>
<?php ob_start(); ?>
    <ui-checkbox <?php echo e($attributes->class($classes)); ?> <?php if($showName): ?> name="<?php echo e($name); ?>" <?php endif; ?> data-flux-control data-flux-checkbox>
        <?php $blaze_memoized_key = \Livewire\Blaze\Memoizer\Memo::key("flux::checkbox.indicator", []); ?><?php if ($blaze_memoized_key !== null && \Livewire\Blaze\Memoizer\Memo::has($blaze_memoized_key)) : ?><?php echo \Livewire\Blaze\Memoizer\Memo::get($blaze_memoized_key); ?><?php else : ?><?php ob_start(); ?><?php if (!function_exists('_527f5dd677dbb9b8c30d23307d73d943')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/checkbox/indicator.blade.php', $__blaze->compiledPath.'/527f5dd677dbb9b8c30d23307d73d943.php'); require $__blaze->compiledPath.'/527f5dd677dbb9b8c30d23307d73d943.php'; } ?>
<?php $__blaze->pushData([]); ?>
<?php _527f5dd677dbb9b8c30d23307d73d943($__blaze, [], [], [], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?><?php $blaze_memoized_html = ob_get_clean(); ?><?php if ($blaze_memoized_key !== null) { \Livewire\Blaze\Memoizer\Memo::put($blaze_memoized_key, $blaze_memoized_html); } ?><?php echo $blaze_memoized_html; ?><?php endif; ?>
    </ui-checkbox>
<?php $__slots431ea3bdbd0be54d76bb8382dde7157d['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots431ea3bdbd0be54d76bb8382dde7157d); ?>
<?php _431ea3bdbd0be54d76bb8382dde7157d($__blaze, $__attrs431ea3bdbd0be54d76bb8382dde7157d, $__slots431ea3bdbd0be54d76bb8382dde7157d, ['attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack431ea3bdbd0be54d76bb8382dde7157d)) { $__slots431ea3bdbd0be54d76bb8382dde7157d = array_pop($__slotsStack431ea3bdbd0be54d76bb8382dde7157d); } ?>
<?php if (! empty($__attrsStack431ea3bdbd0be54d76bb8382dde7157d)) { $__attrs431ea3bdbd0be54d76bb8382dde7157d = array_pop($__attrsStack431ea3bdbd0be54d76bb8382dde7157d); } ?>
<?php $__blaze->popData(); ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\checkbox\variants\default.blade.php ENDPATH**/ ?>