<?php
if (!function_exists('_002cd8c7bc480f548482b6303ae9306a')):
function _002cd8c7bc480f548482b6303ae9306a($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'align' => 'start',
    'variant' => null,
    'sticky' => false,
];
$align ??= $attributes['align'] ?? $__defaults['align']; unset($attributes['align']);
$variant ??= $attributes['variant'] ?? $__defaults['variant']; unset($attributes['variant']);
$sticky ??= $attributes['sticky'] ?? $__defaults['sticky']; unset($attributes['sticky']);
unset($__defaults);
?>

<?php
$classes = Flux::classes()
    ->add('[:where(&)]:py-3 [:where(&)]:px-3 first:ps-0 last:pe-0 text-sm')
    ->add(match($align) {
        'center' => 'text-center [&>*]:mx-auto',
        'end' => 'text-end [&>*]:ms-auto',
        // Right is @deprecated but needed for backwards compatibility...
        'right' => 'text-end [&>*]:ms-auto',
        default => '',
    })
    ->add(match ($variant) {
        'strong' => 'font-medium text-zinc-800 dark:text-white',
        default => 'text-zinc-500 dark:text-zinc-300',
    })
    ->add($sticky ? [
        'z-10',
        'first:sticky first:left-0',
        'last:sticky last:right-0',
        'first:after:w-8 first:after:absolute first:after:inset-y-0 first:after:right-0 first:after:translate-x-full first:after:pointer-events-none',
        'last:after:w-8 last:after:absolute last:after:inset-y-0 last:after:left-0 last:after:-translate-x-full last:after:pointer-events-none',
        'in-data-scrolled-right:first:after:inset-shadow-[8px_0px_8px_-8px_rgba(0,0,0,0.05)]',
        'in-data-scrolled-left:last:after:inset-shadow-[-8px_0px_8px_-8px_rgba(0,0,0,0.05)]',
    ]: '')
    ->add('not-in-[tr:first-child]:border-t border-zinc-800/10 dark:border-white/20')
    ;
?>

<td <?php echo e($attributes->class($classes)); ?> data-flux-cell>
    <?php echo e($slot); ?>

</td>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\table\cell.blade.php ENDPATH**/ ?>