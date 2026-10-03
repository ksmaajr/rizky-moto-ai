<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'sidebar' => false,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'sidebar' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sidebar): ?>
    <?php if (!function_exists('_e4f80671876ea77264ae9798ad6255b2')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/sidebar/brand.blade.php', $__blaze->compiledPath.'/e4f80671876ea77264ae9798ad6255b2.php'); require $__blaze->compiledPath.'/e4f80671876ea77264ae9798ad6255b2.php'; } ?>
<?php if (isset($__slotse4f80671876ea77264ae9798ad6255b2)) { $__slotsStacke4f80671876ea77264ae9798ad6255b2[] = $__slotse4f80671876ea77264ae9798ad6255b2; } ?>
<?php if (isset($__attrse4f80671876ea77264ae9798ad6255b2)) { $__attrsStacke4f80671876ea77264ae9798ad6255b2[] = $__attrse4f80671876ea77264ae9798ad6255b2; } ?>
<?php $__attrse4f80671876ea77264ae9798ad6255b2 = ['name' => config('app.name', 'Laravel'),'attributes' => $attributes]; ?>
<?php $__slotse4f80671876ea77264ae9798ad6255b2 = []; ?>
<?php $__blaze->pushData($__attrse4f80671876ea77264ae9798ad6255b2); ?>
<?php ob_start(); ?>
         <?php ob_start(); ?>
            <?php if (isset($component)) { $__componentOriginal159d6670770cb479b1921cea6416c26c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal159d6670770cb479b1921cea6416c26c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.app-logo-icon','data' => ['class' => 'size-5 fill-current text-white dark:text-black']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-logo-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-5 fill-current text-white dark:text-black']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal159d6670770cb479b1921cea6416c26c)): ?>
<?php $attributes = $__attributesOriginal159d6670770cb479b1921cea6416c26c; ?>
<?php unset($__attributesOriginal159d6670770cb479b1921cea6416c26c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal159d6670770cb479b1921cea6416c26c)): ?>
<?php $component = $__componentOriginal159d6670770cb479b1921cea6416c26c; ?>
<?php unset($__componentOriginal159d6670770cb479b1921cea6416c26c); ?>
<?php endif; ?>
        <?php $__slotse4f80671876ea77264ae9798ad6255b2['logo'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), ['class' => 'flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground']); ?>
    <?php $__slotse4f80671876ea77264ae9798ad6255b2['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slotse4f80671876ea77264ae9798ad6255b2); ?>
<?php _e4f80671876ea77264ae9798ad6255b2($__blaze, $__attrse4f80671876ea77264ae9798ad6255b2, $__slotse4f80671876ea77264ae9798ad6255b2, ['name', 'attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStacke4f80671876ea77264ae9798ad6255b2)) { $__slotse4f80671876ea77264ae9798ad6255b2 = array_pop($__slotsStacke4f80671876ea77264ae9798ad6255b2); } ?>
<?php if (! empty($__attrsStacke4f80671876ea77264ae9798ad6255b2)) { $__attrse4f80671876ea77264ae9798ad6255b2 = array_pop($__attrsStacke4f80671876ea77264ae9798ad6255b2); } ?>
<?php $__blaze->popData(); ?>
<?php else: ?>
    <?php if (!function_exists('_f503f1200b7119ace381528ce55f0124')) { $__blaze->compile('F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/brand.blade.php', $__blaze->compiledPath.'/f503f1200b7119ace381528ce55f0124.php'); require $__blaze->compiledPath.'/f503f1200b7119ace381528ce55f0124.php'; } ?>
<?php if (isset($__slotsf503f1200b7119ace381528ce55f0124)) { $__slotsStackf503f1200b7119ace381528ce55f0124[] = $__slotsf503f1200b7119ace381528ce55f0124; } ?>
<?php if (isset($__attrsf503f1200b7119ace381528ce55f0124)) { $__attrsStackf503f1200b7119ace381528ce55f0124[] = $__attrsf503f1200b7119ace381528ce55f0124; } ?>
<?php $__attrsf503f1200b7119ace381528ce55f0124 = ['name' => config('app.name', 'Laravel'),'attributes' => $attributes]; ?>
<?php $__slotsf503f1200b7119ace381528ce55f0124 = []; ?>
<?php $__blaze->pushData($__attrsf503f1200b7119ace381528ce55f0124); ?>
<?php ob_start(); ?>
         <?php ob_start(); ?>
            <?php if (isset($component)) { $__componentOriginal159d6670770cb479b1921cea6416c26c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal159d6670770cb479b1921cea6416c26c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.app-logo-icon','data' => ['class' => 'size-5 fill-current text-white dark:text-black']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-logo-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-5 fill-current text-white dark:text-black']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal159d6670770cb479b1921cea6416c26c)): ?>
<?php $attributes = $__attributesOriginal159d6670770cb479b1921cea6416c26c; ?>
<?php unset($__attributesOriginal159d6670770cb479b1921cea6416c26c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal159d6670770cb479b1921cea6416c26c)): ?>
<?php $component = $__componentOriginal159d6670770cb479b1921cea6416c26c; ?>
<?php unset($__componentOriginal159d6670770cb479b1921cea6416c26c); ?>
<?php endif; ?>
        <?php $__slotsf503f1200b7119ace381528ce55f0124['logo'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), ['class' => 'flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground']); ?>
    <?php $__slotsf503f1200b7119ace381528ce55f0124['slot'] = new \Illuminate\View\ComponentSlot(trim(ob_get_clean()), []); ?>
<?php $__blaze->pushSlots($__slotsf503f1200b7119ace381528ce55f0124); ?>
<?php _f503f1200b7119ace381528ce55f0124($__blaze, $__attrsf503f1200b7119ace381528ce55f0124, $__slotsf503f1200b7119ace381528ce55f0124, ['name', 'attributes'], [], $__this ?? (isset($this) ? $this : null)); ?>
<?php if (! empty($__slotsStackf503f1200b7119ace381528ce55f0124)) { $__slotsf503f1200b7119ace381528ce55f0124 = array_pop($__slotsStackf503f1200b7119ace381528ce55f0124); } ?>
<?php if (! empty($__attrsStackf503f1200b7119ace381528ce55f0124)) { $__attrsf503f1200b7119ace381528ce55f0124 = array_pop($__attrsStackf503f1200b7119ace381528ce55f0124); } ?>
<?php $__blaze->popData(); ?>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH F:\Website\rizky-tools-ai\resources\views\components\app-logo.blade.php ENDPATH**/ ?>