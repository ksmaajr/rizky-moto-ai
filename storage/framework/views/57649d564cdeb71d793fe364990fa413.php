<?php
if (!function_exists('_57649d564cdeb71d793fe364990fa413')):
function _57649d564cdeb71d793fe364990fa413($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'interactive' => null,
    'position' => 'top',
    'align' => 'center',
    'content' => null,
    'kbd' => null,
    'toggleable' => null,
];
$interactive ??= $attributes['interactive'] ?? $__defaults['interactive']; unset($attributes['interactive']);
$position ??= $attributes['position'] ?? $__defaults['position']; unset($attributes['position']);
$align ??= $attributes['align'] ?? $__defaults['align']; unset($attributes['align']);
$content ??= $attributes['content'] ?? $__defaults['content']; unset($attributes['content']);
$kbd ??= $attributes['kbd'] ?? $__defaults['kbd']; unset($attributes['kbd']);
$toggleable ??= $attributes['toggleable'] ?? $__defaults['toggleable']; unset($attributes['toggleable']);
unset($__defaults);
?>

<?php
// Support adding the .self modifier to the wire:model directive...
if (($wireModel = $attributes->wire('model')) && $wireModel->directive && ! $wireModel->hasModifier('self')) {
    unset($attributes[$wireModel->directive]);

    $wireModel->directive .= '.self';

    $attributes = $attributes->merge([$wireModel->directive => $wireModel->value]);
}
?>

<?php if ($toggleable): ?>
    <ui-dropdown position="<?php echo e($position); ?> <?php echo e($align); ?>" <?php echo e($attributes); ?> data-flux-tooltip>
        <?php echo e($slot); ?>


        <?php if ($content !== null): ?>
            <?php if (!function_exists('_3f2666fb7f87cdbef97ed140f283fa79')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/tooltip/content.blade.php', $__blaze->compiledPath.'/3f2666fb7f87cdbef97ed140f283fa79.php'); require $__blaze->compiledPath.'/3f2666fb7f87cdbef97ed140f283fa79.php'; } ?>
<?php if (isset($__slots3f2666fb7f87cdbef97ed140f283fa79)) { $__slotsStack3f2666fb7f87cdbef97ed140f283fa79[] = $__slots3f2666fb7f87cdbef97ed140f283fa79; } ?>
<?php if (isset($__attrs3f2666fb7f87cdbef97ed140f283fa79)) { $__attrsStack3f2666fb7f87cdbef97ed140f283fa79[] = $__attrs3f2666fb7f87cdbef97ed140f283fa79; } ?>
<?php $__attrs3f2666fb7f87cdbef97ed140f283fa79 = ['kbd' => $kbd]; ?>
<?php $__slots3f2666fb7f87cdbef97ed140f283fa79 = []; ?>
<?php $__blaze->pushData($__attrs3f2666fb7f87cdbef97ed140f283fa79); ?>
<?php ob_start(); ?><?php echo e($content); ?><?php $__slots3f2666fb7f87cdbef97ed140f283fa79['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots3f2666fb7f87cdbef97ed140f283fa79); ?>
<?php _3f2666fb7f87cdbef97ed140f283fa79($__blaze, $__attrs3f2666fb7f87cdbef97ed140f283fa79, $__slots3f2666fb7f87cdbef97ed140f283fa79, ['kbd'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack3f2666fb7f87cdbef97ed140f283fa79)) { $__slots3f2666fb7f87cdbef97ed140f283fa79 = array_pop($__slotsStack3f2666fb7f87cdbef97ed140f283fa79); } ?>
<?php if (! empty($__attrsStack3f2666fb7f87cdbef97ed140f283fa79)) { $__attrs3f2666fb7f87cdbef97ed140f283fa79 = array_pop($__attrsStack3f2666fb7f87cdbef97ed140f283fa79); } ?>
<?php $__blaze->popData(); ?>
        <?php endif; ?>
    </ui-dropdown>
<?php else: ?>
    <ui-tooltip position="<?php echo e($position); ?> <?php echo e($align); ?>" <?php echo e($attributes); ?> data-flux-tooltip <?php if($interactive): ?> interactive <?php endif; ?>>
        <?php echo e($slot); ?>


        <?php if ($content !== null): ?>
            <?php if (!function_exists('_3f2666fb7f87cdbef97ed140f283fa79')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/tooltip/content.blade.php', $__blaze->compiledPath.'/3f2666fb7f87cdbef97ed140f283fa79.php'); require $__blaze->compiledPath.'/3f2666fb7f87cdbef97ed140f283fa79.php'; } ?>
<?php if (isset($__slots3f2666fb7f87cdbef97ed140f283fa79)) { $__slotsStack3f2666fb7f87cdbef97ed140f283fa79[] = $__slots3f2666fb7f87cdbef97ed140f283fa79; } ?>
<?php if (isset($__attrs3f2666fb7f87cdbef97ed140f283fa79)) { $__attrsStack3f2666fb7f87cdbef97ed140f283fa79[] = $__attrs3f2666fb7f87cdbef97ed140f283fa79; } ?>
<?php $__attrs3f2666fb7f87cdbef97ed140f283fa79 = ['kbd' => $kbd]; ?>
<?php $__slots3f2666fb7f87cdbef97ed140f283fa79 = []; ?>
<?php $__blaze->pushData($__attrs3f2666fb7f87cdbef97ed140f283fa79); ?>
<?php ob_start(); ?><?php echo e($content); ?><?php $__slots3f2666fb7f87cdbef97ed140f283fa79['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slots3f2666fb7f87cdbef97ed140f283fa79); ?>
<?php _3f2666fb7f87cdbef97ed140f283fa79($__blaze, $__attrs3f2666fb7f87cdbef97ed140f283fa79, $__slots3f2666fb7f87cdbef97ed140f283fa79, ['kbd'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStack3f2666fb7f87cdbef97ed140f283fa79)) { $__slots3f2666fb7f87cdbef97ed140f283fa79 = array_pop($__slotsStack3f2666fb7f87cdbef97ed140f283fa79); } ?>
<?php if (! empty($__attrsStack3f2666fb7f87cdbef97ed140f283fa79)) { $__attrs3f2666fb7f87cdbef97ed140f283fa79 = array_pop($__attrsStack3f2666fb7f87cdbef97ed140f283fa79); } ?>
<?php $__blaze->popData(); ?>
        <?php endif; ?>
    </ui-tooltip>
<?php endif; ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\tooltip\index.blade.php ENDPATH**/ ?>