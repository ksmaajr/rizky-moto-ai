<?php
if (!function_exists('_014051182467a9d75b61ee5510b29573')):
function _014051182467a9d75b61ee5510b29573($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'variant' => 'outline',
    'size' => null,
];
$variant ??= $attributes['variant'] ?? $__defaults['variant']; unset($attributes['variant']);
$size ??= $attributes['size'] ?? $__defaults['size']; unset($attributes['size']);
unset($__defaults);
?>

<?php
$classes = Flux::classes()
    ->add('border')
    ->add(match ($variant) {
        'soft' => '[:where(&)]:bg-black/[0.02] [:where(&)]:border-black/[0.025] dark:[:where(&)]:bg-white/[0.06] dark:[:where(&)]:border-white/[0.065]',
        default => '[:where(&)]:bg-white [:where(&)]:border-zinc-200 dark:[:where(&)]:bg-white/10 dark:[:where(&)]:border-white/10',
    })
    ->add(match ($size) {
        default => '[:where(&)]:p-6 [:where(&)]:rounded-xl [--flux-bleed:1.5rem]',
        'sm' => '[:where(&)]:p-4 [:where(&)]:rounded-lg [--flux-bleed:1rem]',
    })
    ;
?>

<div <?php echo e($attributes->class($classes)); ?> data-flux-card>
    <?php echo e($slot); ?>

</div>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\card\index.blade.php ENDPATH**/ ?>