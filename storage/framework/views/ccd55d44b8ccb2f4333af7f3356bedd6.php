<?php
if (!function_exists('_ccd55d44b8ccb2f4333af7f3356bedd6')):
function _ccd55d44b8ccb2f4333af7f3356bedd6($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'color' => null,
];
$color ??= $attributes['color'] ?? $__defaults['color']; unset($attributes['color']);
unset($__defaults);
?>

<?php
$trackClasses = Flux::classes()
    ->add('[:where(&)]:h-1.5 relative w-full overflow-hidden [:where(&)]:bg-zinc-200 dark:[:where(&)]:bg-white/10')
    ->add('[print-color-adjust:exact]')
    ->add('rounded-full')
    ->add(match ($color) {
        'red'     => '[--flux-progress-color:var(--color-red-600)] dark:[--flux-progress-color:var(--color-red-400)]',
        'orange'  => '[--flux-progress-color:var(--color-orange-600)] dark:[--flux-progress-color:var(--color-orange-400)]',
        'amber'   => '[--flux-progress-color:var(--color-amber-600)] dark:[--flux-progress-color:var(--color-amber-400)]',
        'yellow'  => '[--flux-progress-color:var(--color-yellow-600)] dark:[--flux-progress-color:var(--color-yellow-400)]',
        'lime'    => '[--flux-progress-color:var(--color-lime-600)] dark:[--flux-progress-color:var(--color-lime-400)]',
        'green'   => '[--flux-progress-color:var(--color-green-600)] dark:[--flux-progress-color:var(--color-green-400)]',
        'emerald' => '[--flux-progress-color:var(--color-emerald-600)] dark:[--flux-progress-color:var(--color-emerald-400)]',
        'teal'    => '[--flux-progress-color:var(--color-teal-600)] dark:[--flux-progress-color:var(--color-teal-400)]',
        'cyan'    => '[--flux-progress-color:var(--color-cyan-600)] dark:[--flux-progress-color:var(--color-cyan-400)]',
        'sky'     => '[--flux-progress-color:var(--color-sky-600)] dark:[--flux-progress-color:var(--color-sky-400)]',
        'blue'    => '[--flux-progress-color:var(--color-blue-600)] dark:[--flux-progress-color:var(--color-blue-400)]',
        'indigo'  => '[--flux-progress-color:var(--color-indigo-600)] dark:[--flux-progress-color:var(--color-indigo-400)]',
        'violet'  => '[--flux-progress-color:var(--color-violet-600)] dark:[--flux-progress-color:var(--color-violet-400)]',
        'purple'  => '[--flux-progress-color:var(--color-purple-600)] dark:[--flux-progress-color:var(--color-purple-400)]',
        'fuchsia' => '[--flux-progress-color:var(--color-fuchsia-600)] dark:[--flux-progress-color:var(--color-fuchsia-400)]',
        'pink'    => '[--flux-progress-color:var(--color-pink-600)] dark:[--flux-progress-color:var(--color-pink-400)]',
        'rose'    => '[--flux-progress-color:var(--color-rose-600)] dark:[--flux-progress-color:var(--color-rose-400)]',
        default   => '[--flux-progress-color:var(--color-accent)]',
    })
    ;

$barClasses = Flux::classes()
    ->add('h-full rounded-full transition-[width] duration-300 ease-out')
    ->add('bg-[var(--flux-progress-color)]')
    ;
?>

<ui-progress <?php echo e($attributes->class($trackClasses)); ?> data-flux-progress>
    <div class="<?php echo e($barClasses); ?>" style="width: var(--flux-progress-percentage)"></div>
</ui-progress>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\progress.blade.php ENDPATH**/ ?>