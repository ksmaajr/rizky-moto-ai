<?php
if (!function_exists('_2bc701847f3288cd566662d03ebd6967')):
function _2bc701847f3288cd566662d03ebd6967($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'value' => null,
];
$value ??= $attributes['value'] ?? $__defaults['value']; unset($attributes['value']);
unset($__defaults);
?>

<option
    <?php echo e($attributes); ?>

    <?php if(isset($value)): ?> value="<?php echo e($value); ?>" <?php endif; ?>
    <?php if(isset($value)): ?> <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = ''.e($value).''; ?>wire:key="<?php echo e($value); ?>" <?php endif; ?>
><?php echo e($slot); ?></option><?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\select\option\variants\default.blade.php ENDPATH**/ ?>