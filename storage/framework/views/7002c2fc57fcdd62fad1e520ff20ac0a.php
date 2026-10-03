<?php
if (!function_exists('_7002c2fc57fcdd62fad1e520ff20ac0a')):
function _7002c2fc57fcdd62fad1e520ff20ac0a($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
$classes = Flux::classes()
    ->add('*:data-flux-field:mb-3')
    ->add('[&>[data-flux-field]:has(>[data-flux-description])]:mb-4')
    ->add('[&>[data-flux-field]:last-child]:mb-0!')
    ;

// Support adding the .self modifier to the wire:model directive...
if (($wireModel = $attributes->wire('model')) && $wireModel->directive && ! $wireModel->hasModifier('self')) {
    unset($attributes[$wireModel->directive]);

    $wireModel->directive .= '.self';

    $attributes = $attributes->merge([$wireModel->directive => $wireModel->value]);
}
?>

<?php if (!function_exists('_b82a103a681f6493b96d99831ecd901f')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/with-field.blade.php', $__blaze->compiledPath.'/b82a103a681f6493b96d99831ecd901f.php'); require $__blaze->compiledPath.'/b82a103a681f6493b96d99831ecd901f.php'; } ?>
<?php if (isset($__slotsb82a103a681f6493b96d99831ecd901f)) { $__slotsStackb82a103a681f6493b96d99831ecd901f[] = $__slotsb82a103a681f6493b96d99831ecd901f; } ?>
<?php if (isset($__attrsb82a103a681f6493b96d99831ecd901f)) { $__attrsStackb82a103a681f6493b96d99831ecd901f[] = $__attrsb82a103a681f6493b96d99831ecd901f; } ?>
<?php $__attrsb82a103a681f6493b96d99831ecd901f = ['attributes' => $attributes]; ?>
<?php $__slotsb82a103a681f6493b96d99831ecd901f = []; ?>
<?php $__blaze->pushData($__attrsb82a103a681f6493b96d99831ecd901f); ?>
<?php ob_start(); ?>
    <ui-checkbox-group <?php echo e($attributes->class($classes)); ?> data-flux-checkbox-group>
        <?php echo e($slot); ?>

    </ui-checkbox-group>
<?php $__slotsb82a103a681f6493b96d99831ecd901f['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slotsb82a103a681f6493b96d99831ecd901f); ?>
<?php _b82a103a681f6493b96d99831ecd901f($__blaze, $__attrsb82a103a681f6493b96d99831ecd901f, $__slotsb82a103a681f6493b96d99831ecd901f, ['attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStackb82a103a681f6493b96d99831ecd901f)) { $__slotsb82a103a681f6493b96d99831ecd901f = array_pop($__slotsStackb82a103a681f6493b96d99831ecd901f); } ?>
<?php if (! empty($__attrsStackb82a103a681f6493b96d99831ecd901f)) { $__attrsb82a103a681f6493b96d99831ecd901f = array_pop($__attrsStackb82a103a681f6493b96d99831ecd901f); } ?>
<?php $__blaze->popData(); ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\checkbox\group\variants\default.blade.php ENDPATH**/ ?>