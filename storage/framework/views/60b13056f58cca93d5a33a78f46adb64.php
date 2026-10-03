<?php
if (!function_exists('_60b13056f58cca93d5a33a78f46adb64')):
function _60b13056f58cca93d5a33a78f46adb64($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'label',
];
$label ??= $attributes['label']; unset($attributes['label']);
unset($__defaults);
?>

<optgroup <?php echo e($attributes); ?> label="<?php echo e($label); ?>"><?php echo e($slot); ?></optgroup>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\select\group\variants\default.blade.php ENDPATH**/ ?>