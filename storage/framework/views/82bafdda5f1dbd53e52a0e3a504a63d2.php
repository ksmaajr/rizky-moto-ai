<?php # [BlazeFolded]:{flux::heading}:{F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/heading.blade.php}:{1789427708} ?>
<?php # [BlazeFolded]:{flux::radio}:{F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/radio/index.blade.php}:{1789427708} ?>
<?php # [BlazeFolded]:{flux::radio}:{F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/radio/index.blade.php}:{1789427708} ?>
<?php # [BlazeFolded]:{flux::radio}:{F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/radio/index.blade.php}:{1789427708} ?>
<?php # [BlazeFolded]:{flux::radio.group}:{F:\Website\rizky-tools-ai\vendor\livewire\flux\src/../stubs/resources/views/flux/radio/group/index.blade.php}:{1789427708} ?>
<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Appearance settings')] class extends Component {
    //
}; ?>

<section class="w-full">
    <?php echo $__env->make('partials.settings-heading', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php ob_start(); ?><h2 class="font-medium [:where(&amp;)]:text-zinc-800 [:where(&amp;)]:dark:text-white text-sm [&amp;:has(+[data-flux-subheading])]:mb-2 [[data-flux-subheading]+&amp;]:mt-2 sr-only" data-flux-heading><?php ob_start(); ?><?php echo e(__('Appearance settings')); ?><?php echo trim(ob_get_clean()); ?></h2>

        <?php echo ltrim(ob_get_clean()); ?>

    <?php if (isset($component)) { $__componentOriginal47c6e5d793050babb6edb764210472f1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal47c6e5d793050babb6edb764210472f1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'af6a29d55d306249cfe5b80ece79872b::settings.layout','data' => ['heading' => __('Appearance'),'subheading' => __('Update the appearance settings for your account')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pages::settings.layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['heading' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Appearance')),'subheading' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Update the appearance settings for your account'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

        <?php ob_start(); ?><?php $__blaze->pushData(['xData' => true, 'variant' => 'segmented', 'xModel' => '$flux.appearance']); $__env->pushConsumableComponentData(['xData' => true, 'variant' => 'segmented', 'xModel' => '$flux.appearance']); ?><ui-radio-group class="block flex p-1 rounded-lg bg-zinc-800/5 dark:bg-white/10 h-10 p-1" x-data="" x-model="$flux.appearance"  data-flux-radio-group-segmented>
        <?php ob_start(); ?>
            <?php ob_start(); ?><ui-radio class="flex whitespace-nowrap flex-1 justify-center items-center gap-2 rounded-md data-checked:shadow-xs text-sm font-medium text-zinc-600 hover:text-zinc-800 dark:hover:text-white dark:text-white/70 data-checked:text-zinc-800 dark:data-checked:text-white data-checked:bg-white dark:data-checked:bg-white/20 [&amp;[disabled]]:opacity-50 dark:[&amp;[disabled]]:opacity-75 [&amp;[disabled]]:cursor-default [&amp;[disabled]]:pointer-events-none px-4" value="light" data-flux-control data-flux-radio-segmented tabindex="-1">
            <svg class="shrink-0 [:where(&amp;)]:size-5 text-zinc-500 dark:text-zinc-400 [ui-radio[data-checked]_&]:text-zinc-800 dark:[ui-radio[data-checked]_&]:text-white" data-flux-icon xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
  <path d="M10 2a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 2ZM10 15a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 15ZM10 7a3 3 0 1 0 0 6 3 3 0 0 0 0-6ZM15.657 5.404a.75.75 0 1 0-1.06-1.06l-1.061 1.06a.75.75 0 0 0 1.06 1.06l1.06-1.06ZM6.464 14.596a.75.75 0 1 0-1.06-1.06l-1.06 1.06a.75.75 0 0 0 1.06 1.06l1.06-1.06ZM18 10a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5h1.5A.75.75 0 0 1 18 10ZM5 10a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5h1.5A.75.75 0 0 1 5 10ZM14.596 15.657a.75.75 0 0 0 1.06-1.06l-1.06-1.061a.75.75 0 1 0-1.06 1.06l1.06 1.06ZM5.404 6.464a.75.75 0 0 0 1.06-1.06l-1.06-1.06a.75.75 0 1 0-1.061 1.06l1.06 1.06Z"/>
</svg>

            
    <?php ob_start(); ?><?php echo e(__('Light')); ?><?php echo trim(ob_get_clean()); ?>


    </ui-radio>
<?php echo ltrim(ob_get_clean()); ?>
            <?php ob_start(); ?><ui-radio class="flex whitespace-nowrap flex-1 justify-center items-center gap-2 rounded-md data-checked:shadow-xs text-sm font-medium text-zinc-600 hover:text-zinc-800 dark:hover:text-white dark:text-white/70 data-checked:text-zinc-800 dark:data-checked:text-white data-checked:bg-white dark:data-checked:bg-white/20 [&amp;[disabled]]:opacity-50 dark:[&amp;[disabled]]:opacity-75 [&amp;[disabled]]:cursor-default [&amp;[disabled]]:pointer-events-none px-4" value="dark" data-flux-control data-flux-radio-segmented tabindex="-1">
            <svg class="shrink-0 [:where(&amp;)]:size-5 text-zinc-500 dark:text-zinc-400 [ui-radio[data-checked]_&]:text-zinc-800 dark:[ui-radio[data-checked]_&]:text-white" data-flux-icon xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
  <path fill-rule="evenodd" d="M7.455 2.004a.75.75 0 0 1 .26.77 7 7 0 0 0 9.958 7.967.75.75 0 0 1 1.067.853A8.5 8.5 0 1 1 6.647 1.921a.75.75 0 0 1 .808.083Z" clip-rule="evenodd"/>
</svg>

            
    <?php ob_start(); ?><?php echo e(__('Dark')); ?><?php echo trim(ob_get_clean()); ?>


    </ui-radio>
<?php echo ltrim(ob_get_clean()); ?>
            <?php ob_start(); ?><ui-radio class="flex whitespace-nowrap flex-1 justify-center items-center gap-2 rounded-md data-checked:shadow-xs text-sm font-medium text-zinc-600 hover:text-zinc-800 dark:hover:text-white dark:text-white/70 data-checked:text-zinc-800 dark:data-checked:text-white data-checked:bg-white dark:data-checked:bg-white/20 [&amp;[disabled]]:opacity-50 dark:[&amp;[disabled]]:opacity-75 [&amp;[disabled]]:cursor-default [&amp;[disabled]]:pointer-events-none px-4" value="system" data-flux-control data-flux-radio-segmented tabindex="-1">
            <svg class="shrink-0 [:where(&amp;)]:size-5 text-zinc-500 dark:text-zinc-400 [ui-radio[data-checked]_&]:text-zinc-800 dark:[ui-radio[data-checked]_&]:text-white" data-flux-icon xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
  <path fill-rule="evenodd" d="M2 4.25A2.25 2.25 0 0 1 4.25 2h11.5A2.25 2.25 0 0 1 18 4.25v8.5A2.25 2.25 0 0 1 15.75 15h-3.105a3.501 3.501 0 0 0 1.1 1.677A.75.75 0 0 1 13.26 18H6.74a.75.75 0 0 1-.484-1.323A3.501 3.501 0 0 0 7.355 15H4.25A2.25 2.25 0 0 1 2 12.75v-8.5Zm1.5 0a.75.75 0 0 1 .75-.75h11.5a.75.75 0 0 1 .75.75v7.5a.75.75 0 0 1-.75.75H4.25a.75.75 0 0 1-.75-.75v-7.5Z" clip-rule="evenodd"/>
</svg>

            
    <?php ob_start(); ?><?php echo e(__('System')); ?><?php echo trim(ob_get_clean()); ?>


    </ui-radio>
<?php echo ltrim(ob_get_clean()); ?>
        <?php echo trim(ob_get_clean()); ?>

    </ui-radio-group>
<?php $__blaze->popData(); $__env->popConsumableComponentData(); ?><?php echo ltrim(ob_get_clean()); ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal47c6e5d793050babb6edb764210472f1)): ?>
<?php $attributes = $__attributesOriginal47c6e5d793050babb6edb764210472f1; ?>
<?php unset($__attributesOriginal47c6e5d793050babb6edb764210472f1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal47c6e5d793050babb6edb764210472f1)): ?>
<?php $component = $__componentOriginal47c6e5d793050babb6edb764210472f1; ?>
<?php unset($__componentOriginal47c6e5d793050babb6edb764210472f1); ?>
<?php endif; ?>
</section>
<?php /**PATH F:\Website\rizky-tools-ai\resources\views\pages\settings\appearance.blade.php ENDPATH**/ ?>