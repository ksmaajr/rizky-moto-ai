<?php
if (!function_exists('_f5154a65d18448bb2a790b41af6f24a1')):
function _f5154a65d18448bb2a790b41af6f24a1($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'name' => null,
    'variant' => null,
];
$name ??= $attributes['name'] ?? $__defaults['name']; unset($attributes['name']);
$variant ??= $attributes['variant'] ?? $__defaults['variant']; unset($attributes['variant']);
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
    // Adjust spacing between fields...
    ->add('*:data-flux-field:mb-3')
    ->add('[&>[data-flux-field]:has(>[data-flux-description])]:mb-4')
    ->add('[&>[data-flux-field]:last-child]:mb-0!')
    ;
?>

<?php if (!function_exists('_b82a103a681f6493b96d99831ecd901f')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/with-field.blade.php', $__blaze->compiledPath.'/b82a103a681f6493b96d99831ecd901f.php'); require $__blaze->compiledPath.'/b82a103a681f6493b96d99831ecd901f.php'; } ?>
<?php if (isset($__slotsb82a103a681f6493b96d99831ecd901f)) { $__slotsStackb82a103a681f6493b96d99831ecd901f[] = $__slotsb82a103a681f6493b96d99831ecd901f; } ?>
<?php if (isset($__attrsb82a103a681f6493b96d99831ecd901f)) { $__attrsStackb82a103a681f6493b96d99831ecd901f[] = $__attrsb82a103a681f6493b96d99831ecd901f; } ?>
<?php $__attrsb82a103a681f6493b96d99831ecd901f = ['attributes' => $attributes]; ?>
<?php $__slotsb82a103a681f6493b96d99831ecd901f = []; ?>
<?php $__blaze->pushData($__attrsb82a103a681f6493b96d99831ecd901f); ?>
<?php ob_start(); ?>
    <ui-radio-group <?php echo e($attributes->class($classes)); ?> <?php if($showName): ?> name="<?php echo e($name); ?>" <?php endif; ?> data-flux-radio-group>
        <?php echo e($slot); ?>

    </ui-radio-group>
<?php $__slotsb82a103a681f6493b96d99831ecd901f['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slotsb82a103a681f6493b96d99831ecd901f); ?>
<?php _b82a103a681f6493b96d99831ecd901f($__blaze, $__attrsb82a103a681f6493b96d99831ecd901f, $__slotsb82a103a681f6493b96d99831ecd901f, ['attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStackb82a103a681f6493b96d99831ecd901f)) { $__slotsb82a103a681f6493b96d99831ecd901f = array_pop($__slotsStackb82a103a681f6493b96d99831ecd901f); } ?>
<?php if (! empty($__attrsStackb82a103a681f6493b96d99831ecd901f)) { $__attrsb82a103a681f6493b96d99831ecd901f = array_pop($__attrsStackb82a103a681f6493b96d99831ecd901f); } ?>
<?php $__blaze->popData(); ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\radio\group\variants\default.blade.php ENDPATH**/ ?>