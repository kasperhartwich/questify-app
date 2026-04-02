<?php
use App\Auth\QuestifyApiGuard;
use App\Exceptions\Api\ApiAuthenticationException;
use App\Exceptions\Api\ApiException;
use App\Exceptions\Api\ApiValidationException;
use App\Livewire\Concerns\HandlesApiErrors;
use App\Livewire\Concerns\WithApiClient;
use App\Services\AppInfoService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
?>

<div class="flex min-h-screen flex-col bg-cream px-5 pb-6 pt-2">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($step === 'login'): ?>
        
        <h1 class="mb-1 mt-4 font-heading text-[24px] font-[800] leading-tight text-bark"><?php echo nl2br(e(__('auth.welcome_back'))); ?></h1>
        <p class="mb-[22px] text-[13px] text-muted"><?php echo e(__('auth.login_subtitle')); ?></p>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($socialProviders) > 0): ?>
            <div class="mb-[4px] grid grid-cols-2 gap-[8px]">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $socialProviders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $provider): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                    <a href="/auth/<?php echo e($provider); ?>/redirect" class="flex w-full items-center justify-center gap-[8px] rounded-[14px] border-[1.5px] border-cream-border bg-transparent py-[13px] text-[14px] font-semibold text-bark">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($provider === 'google'): ?>
                            <svg width="18" height="18" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                        <?php elseif($provider === 'facebook'): ?>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        <?php elseif($provider === 'apple'): ?>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="#1D1D1F"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
                        <?php elseif($provider === 'microsoft'): ?>
                            <svg width="18" height="18" viewBox="0 0 24 24"><rect x="1" y="1" width="10.5" height="10.5" fill="#F25022"/><rect x="12.5" y="1" width="10.5" height="10.5" fill="#7FBA00"/><rect x="1" y="12.5" width="10.5" height="10.5" fill="#00A4EF"/><rect x="12.5" y="12.5" width="10.5" height="10.5" fill="#FFB900"/></svg>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php echo e(ucfirst($provider)); ?>

                    </a>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($emailEnabled): ?>
            <div class="flex items-center gap-3 py-[10px]">
                <div class="h-px flex-1 bg-cream-border"></div>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-muted"><?php echo e(__('general.or')); ?></span>
                <div class="h-px flex-1 bg-cream-border"></div>
            </div>

            
            <form wire:submit="login">
                <div>
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-muted"><?php echo e(__('general.email')); ?></label>
                    <div class="relative">
                        <div class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="text-muted"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg>
                        </div>
                        <input type="email" wire:model.live="email" placeholder="<?php echo e(__('auth.email_placeholder')); ?>" class="w-full rounded-[14px] border-[1.5px] border-cream-border bg-white py-[13px] pl-9 pr-3.5 text-[13px] text-bark placeholder:text-forest-300 focus:border-forest-600 focus:outline-none" required />
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[10px] text-coral"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(strlen($email) > 0): ?>
                    <div class="mt-2.5">
                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-muted"><?php echo e(__('general.password')); ?></label>
                        <div class="relative">
                            <div class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="text-muted"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                            </div>
                            <input type="password" wire:model="password" placeholder="<?php echo e(__('general.password')); ?>" class="w-full rounded-[14px] border-[1.5px] border-cream-border bg-white py-[13px] pl-9 pr-3.5 text-[13px] text-bark placeholder:text-forest-300 focus:border-forest-600 focus:outline-none" required />
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[10px] text-coral"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <div class="mt-2 mb-3 text-right">
                        <a href="/forgot-password" class="text-[11px] font-semibold text-forest-400" wire:navigate><?php echo e(__('auth.forgot_password')); ?></a>
                    </div>

                    <button type="submit" class="w-full rounded-[14px] bg-amber-400 px-4 py-[13px] text-center font-heading text-sm font-bold text-bark">
                        <?php echo e(__('general.login')); ?>

                    </button>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </form>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($phoneEnabled): ?>
            <div class="flex items-center gap-3 py-[10px]">
                <div class="h-px flex-1 bg-cream-border"></div>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-muted"><?php echo e(__('general.or')); ?></span>
                <div class="h-px flex-1 bg-cream-border"></div>
            </div>

            
            <div>
                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-muted"><?php echo e(__('auth.phone_number')); ?></label>
                <form wire:submit="sendPhoneOtpFromMain" class="flex gap-2">
                    
                    <div class="relative">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[14px]">🇩🇰</span>
                        <select
                            wire:model="country_code"
                            class="h-full appearance-none rounded-[14px] border-[1.5px] border-cream-border bg-white py-[13px] pl-8 pr-7 text-[13px] font-semibold text-bark focus:border-forest-600 focus:outline-none"
                        >
                            <option value="+45">+45</option>
                            <option value="+1">+1</option>
                            <option value="+44">+44</option>
                            <option value="+46">+46</option>
                            <option value="+47">+47</option>
                            <option value="+49">+49</option>
                            <option value="+33">+33</option>
                            <option value="+34">+34</option>
                            <option value="+39">+39</option>
                            <option value="+31">+31</option>
                            <option value="+48">+48</option>
                            <option value="+351">+351</option>
                            <option value="+91">+91</option>
                            <option value="+61">+61</option>
                            <option value="+81">+81</option>
                        </select>
                        <div class="pointer-events-none absolute right-1.5 top-1/2 -translate-y-1/2">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#7A7470" stroke-width="2.5" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
                        </div>
                    </div>

                    
                    <div class="relative flex-1">
                        <input
                            type="tel"
                            wire:model="phone_local"
                            placeholder="20 12 34 56"
                            class="w-full rounded-[14px] border-[1.5px] border-cream-border bg-white px-3.5 py-[13px] pr-[110px] text-[13px] font-semibold text-bark placeholder:font-normal placeholder:text-forest-300 focus:border-forest-600 focus:outline-none"
                            inputmode="tel"
                        />
                        <button type="submit" class="absolute right-[5px] top-1/2 -translate-y-1/2 rounded-[10px] bg-forest-600 px-3 py-[8px] text-[12px] font-bold text-white">
                            <?php echo e(__('auth.send_sms')); ?>

                        </button>
                    </div>
                </form>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['phone_local'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[10px] text-coral"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        
        <p class="mt-auto pt-6 text-center text-[13px] text-muted">
            <?php echo e(__('general.dont_have_account')); ?>

            <a href="/register" class="font-semibold text-forest-400" wire:navigate><?php echo e(__('general.register')); ?></a>
        </p>

    
    <?php elseif($step === 'otp'): ?>
        
        <div class="flex items-center gap-2.5 pb-6 pt-1">
            <button wire:click="backToLogin" class="flex h-[36px] w-[36px] items-center justify-center rounded-[11px] bg-cream-dark">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-bark"><path d="M15 18l-6-6 6-6"/></svg>
            </button>
        </div>

        
        <div class="relative mb-4 flex h-16 w-16 items-center justify-center rounded-[18px] bg-amber-100">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#C8811A" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
            <div class="absolute -right-1 -top-1 flex h-3.5 w-3.5 items-center justify-center rounded-full border-2 border-cream bg-forest-600">
                <div class="h-1.5 w-1.5 rounded-full bg-white"></div>
            </div>
        </div>

        
        <h1 class="mb-1.5 font-heading text-[22px] font-extrabold leading-tight text-bark"><?php echo e(__('auth.verify_login')); ?></h1>
        <p class="mb-1.5 text-xs leading-relaxed text-muted"><?php echo e(__('auth.otp_sent_to_phone')); ?></p>
        <p class="mb-6 text-[13px] font-bold text-bark"><?php echo e($phone_number ?: $email); ?></p>

        
        <form wire:submit="verifyOtp" class="flex flex-1 flex-col">
            <label class="mb-2.5 block text-center text-[9px] font-bold uppercase tracking-wider text-muted"><?php echo e(__('auth.enter_6_digit_code')); ?></label>
            <?php if (isset($component)) { $__componentOriginalb6fa76219478b9db417029194e2a99c5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb6fa76219478b9db417029194e2a99c5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.code-boxes','data' => ['wireModel' => 'otp_code','inputmode' => 'numeric']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('code-boxes'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire-model' => 'otp_code','inputmode' => 'numeric']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb6fa76219478b9db417029194e2a99c5)): ?>
<?php $attributes = $__attributesOriginalb6fa76219478b9db417029194e2a99c5; ?>
<?php unset($__attributesOriginalb6fa76219478b9db417029194e2a99c5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb6fa76219478b9db417029194e2a99c5)): ?>
<?php $component = $__componentOriginalb6fa76219478b9db417029194e2a99c5; ?>
<?php unset($__componentOriginalb6fa76219478b9db417029194e2a99c5); ?>
<?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['otp_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-center text-[10px] text-coral"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <div class="mt-auto pt-6">
                <button type="submit" class="w-full rounded-[14px] bg-forest-600 px-4 py-3.5 text-center font-heading text-sm font-bold text-white transition-opacity" wire:loading.attr="disabled" wire:loading.class="opacity-50">
                    <?php echo e(__('auth.verify')); ?>

                </button>
            </div>
        </form>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div><?php /**PATH /Users/kasper/Projects/questify-app/storage/framework/views/livewire/views/9460c64d.blade.php ENDPATH**/ ?>