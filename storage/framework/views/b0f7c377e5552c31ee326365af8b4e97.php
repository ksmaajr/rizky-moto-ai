<?php # [BlazeFolded]:{flux::icon.globe-alt}:{F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/icon/globe-alt.blade.php}:{1789427708} ?>
<?php
if (!function_exists('_b0f7c377e5552c31ee326365af8b4e97')):
function _b0f7c377e5552c31ee326365af8b4e97($__blaze, $__data = [], $__slots = [], $__bound = [], $__keys = [], $__this = null) {
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
    'country' => null,
    'src' => null,
    'alt' => '',
    'size' => 'sm',
    'circle' => false,
];
$country ??= $attributes['country'] ?? $__defaults['country']; unset($attributes['country']);
$src ??= $attributes['src'] ?? $__defaults['src']; unset($attributes['src']);
$alt ??= $attributes['alt'] ?? $__defaults['alt']; unset($attributes['alt']);
$size ??= $attributes['size'] ?? $__defaults['size']; unset($attributes['size']);
$circle ??= $attributes['circle'] ?? $__defaults['circle']; unset($attributes['circle']);
unset($__defaults);
?>

<?php
$country = is_string($country) ? strtoupper(trim($country)) : null;
$src ??= $country ? Flux::flagUrl($country) : null;

$classes = Flux::classes()
    ->add(match ($size) {
        'xl' => '[:where(&)]:w-12',
        'lg' => '[:where(&)]:w-10',
        'md' => '[:where(&)]:w-8',
        default => '[:where(&)]:w-6',
        'xs' => '[:where(&)]:w-5',
    })
    ->add($circle ? 'aspect-square rounded-full' : 'aspect-[3/2] rounded-[2px]')
    ->add('relative isolate block flex-none overflow-hidden bg-zinc-100 dark:bg-zinc-800')
    ->add([
        'after:absolute after:inset-0 after:inset-ring-[1px] after:inset-ring-black/7 dark:after:inset-ring-white/10',
        $circle ? 'after:rounded-full' : 'after:rounded-[2px]',
    ]);
?>

<?php if ($src): ?>
    <span
        <?php echo e($attributes->class($classes)->merge([
            'data-flux-flag' => '',
            'data-country' => $country,
        ])); ?>

    >
        <img
            src="<?php echo e($src); ?>"
            alt="<?php echo e($alt); ?>"
            loading="lazy"
            decoding="async"
            class="size-full object-cover"
        >
    </span>
<?php else: ?>
    <span
        role="img"
        <?php if($alt): ?> aria-label="<?php echo e($alt); ?>" <?php else: ?> aria-hidden="true" <?php endif; ?>
        <?php echo e($attributes->class($classes)->merge([
            'data-flux-flag' => '',
            'data-country' => $country,
        ])); ?>

    >
        <span class="flex size-full items-center justify-center">
            <?php ob_start(); ?><svg class="shrink-0 [:where(&amp;)]:size-4 size-2/3 text-zinc-400 dark:text-zinc-500" data-flux-icon xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" data-slot="icon">
  <path fill-rule="evenodd" d="M3.757 4.5c.18.217.376.42.586.608.153-.61.354-1.175.596-1.678A5.53 5.53 0 0 0 3.757 4.5ZM8 1a6.994 6.994 0 0 0-7 7 7 7 0 1 0 7-7Zm0 1.5c-.476 0-1.091.386-1.633 1.427-.293.564-.531 1.267-.683 2.063A5.48 5.48 0 0 0 8 6.5a5.48 5.48 0 0 0 2.316-.51c-.152-.796-.39-1.499-.683-2.063C9.09 2.886 8.476 2.5 8 2.5Zm3.657 2.608a8.823 8.823 0 0 0-.596-1.678c.444.298.842.659 1.182 1.07-.18.217-.376.42-.586.608Zm-1.166 2.436A6.983 6.983 0 0 1 8 8a6.983 6.983 0 0 1-2.49-.456 10.703 10.703 0 0 0 .202 2.6c.72.231 1.49.356 2.288.356.798 0 1.568-.125 2.29-.356a10.705 10.705 0 0 0 .2-2.6Zm1.433 1.85a12.652 12.652 0 0 0 .018-2.609c.405-.276.78-.594 1.117-.947a5.48 5.48 0 0 1 .44 2.262 7.536 7.536 0 0 1-1.575 1.293Zm-2.172 2.435a9.046 9.046 0 0 1-3.504 0c.039.084.078.166.12.244C6.907 13.114 7.523 13.5 8 13.5s1.091-.386 1.633-1.427c.04-.078.08-.16.12-.244Zm1.31.74a8.5 8.5 0 0 0 .492-1.298c.457-.197.893-.43 1.307-.696a5.526 5.526 0 0 1-1.8 1.995Zm-6.123 0a8.507 8.507 0 0 1-.493-1.298 8.985 8.985 0 0 1-1.307-.696 5.526 5.526 0 0 0 1.8 1.995ZM2.5 8.1c.463.5.993.935 1.575 1.293a12.652 12.652 0 0 1-.018-2.608 7.037 7.037 0 0 1-1.117-.947 5.48 5.48 0 0 0-.44 2.262Z" clip-rule="evenodd"/>
</svg>

        <?php echo ltrim(ob_get_clean()); ?>
        </span>
    </span>
<?php endif; ?>
<?php
echo ltrim(ob_get_clean());
} endif; ?><?php /**PATH F:\Website\rizky-tools-ai\vendor\livewire\flux\stubs\resources\views\flux\flag.blade.php ENDPATH**/ ?>