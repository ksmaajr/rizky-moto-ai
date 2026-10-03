<?php
if (!function_exists('__5b6aceb913c3ec052b314329ba60a651')):
function __5b6aceb913c3ec052b314329ba60a651($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'length' => null,
    'private' => false,
];
$length ??= $attributes['length'] ?? $__defaults['length']; unset($attributes['length']);
$private ??= $attributes['private'] ?? $__defaults['private']; unset($attributes['private']);
unset($__defaults);
?>

<?php
    $classes = Flux::classes()
        ->add('flex items-center gap-2 isolate w-fit')
        ->add('[&_[data-flux-input-group]]:w-auto')
?>

<?php if (!function_exists('__b82a103a681f6493b96d99831ecd901f')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/with-field.blade.php', $__blaze->compiledPath.'/b82a103a681f6493b96d99831ecd901f.php'); require $__blaze->compiledPath.'/b82a103a681f6493b96d99831ecd901f.php'; } ?>
<?php if (isset($__slotsb82a103a681f6493b96d99831ecd901f)) { $__slotsStackb82a103a681f6493b96d99831ecd901f[] = $__slotsb82a103a681f6493b96d99831ecd901f; } ?>
<?php if (isset($__attrsb82a103a681f6493b96d99831ecd901f)) { $__attrsStackb82a103a681f6493b96d99831ecd901f[] = $__attrsb82a103a681f6493b96d99831ecd901f; } ?>
<?php $__attrsb82a103a681f6493b96d99831ecd901f = ['attributes' => $attributes]; ?>
<?php $__slotsb82a103a681f6493b96d99831ecd901f = []; ?>
<?php $__blaze->pushData($__attrsb82a103a681f6493b96d99831ecd901f); ?>
<?php ob_start(); ?>
    <ui-otp
        <?php echo e($attributes->class($classes)); ?>

        data-flux-otp
        data-flux-control
        role="group"
        data-flux-input-aria-label="<?php echo e(__('Character {current} of {total}')); ?>"
    >
        <?php if($slot->isEmpty() && $length): ?>
            <?php for($i = 0; $i < $length; $i++): ?>
                <?php if (!function_exists('__980c93f6ac76556a3c19816f198e8862')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/otp/input.blade.php', $__blaze->compiledPath.'/980c93f6ac76556a3c19816f198e8862.php'); require $__blaze->compiledPath.'/980c93f6ac76556a3c19816f198e8862.php'; } ?>
<?php $__blaze->pushData([]); ?>
<?php __980c93f6ac76556a3c19816f198e8862($__blaze, [], [], [], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php $__blaze->popData(); ?>
            <?php endfor; ?>
        <?php else: ?>
            <?php echo e($slot); ?>

        <?php endif; ?>
    </ui-otp>
<?php $__slotsb82a103a681f6493b96d99831ecd901f['slot'] = new \Illuminate\View\ComponentSlot($__blaze->processPassthroughContent('trim', trim(ob_get_clean())), []); ?>
<?php $__blaze->pushSlots($__slotsb82a103a681f6493b96d99831ecd901f); ?>
<?php __b82a103a681f6493b96d99831ecd901f($__blaze, $__attrsb82a103a681f6493b96d99831ecd901f, $__slotsb82a103a681f6493b96d99831ecd901f, ['attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStackb82a103a681f6493b96d99831ecd901f)) { $__slotsb82a103a681f6493b96d99831ecd901f = array_pop($__slotsStackb82a103a681f6493b96d99831ecd901f); } ?>
<?php if (! empty($__attrsStackb82a103a681f6493b96d99831ecd901f)) { $__attrsb82a103a681f6493b96d99831ecd901f = array_pop($__attrsStackb82a103a681f6493b96d99831ecd901f); } ?>
<?php $__blaze->popData(); ?><?php
echo $__blaze->processPassthroughContent('ltrim', ltrim(ob_get_clean()));
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/otp/index.blade.php ENDPATH**/ ?>