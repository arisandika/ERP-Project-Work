<?php
    use App\Models\HR\Leave;
    use App\Models\HR\LeaveRequest;

    $employee = auth()->user()?->employee;
    $rows = [];

    if ($employee) {

        // Filter leave berdasarkan gender
        $leaves = Leave::query()
            ->when($employee, function ($query) use ($employee) {

                if ($employee->gender === 'male') {
                    // Sembunyikan hanya cuti khusus wanita
                    $query->whereNot(function ($q) {
                        $q->where('is_female_only', true)
                            ->where('is_male_only', false);
                    });
                }

                if ($employee->gender === 'female') {
                    // Sembunyikan hanya cuti khusus laki-laki
                    $query->whereNot(function ($q) {
                        $q->where('is_female_only', false)
                            ->where('is_male_only', true);
                    });
                }
            })
            ->get();

        foreach ($leaves as $leave) {

            $used = LeaveRequest::query()
                ->where('employee_id', $employee->id)
                ->where('leave_id', $leave->id)
                ->where('status', 'approved')
                ->sum('total_days');

            $remaining = max($leave->days_count - $used, 0);

            $percentage = $leave->days_count > 0
                ? round(($used / $leave->days_count) * 100)
                : 0;

            $badgeColor = match (true) {
                $percentage < 40 => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-700 dark:text-emerald-100',
                $percentage < 70 => 'bg-amber-100 text-amber-700 dark:bg-amber-700 dark:text-amber-100',
                default => 'bg-red-100 text-red-700 dark:bg-red-700 dark:text-red-100'
            };

            $icons = [
                'Cuti Tahunan' => 'heroicon-o-sun',
                'Cuti Sakit' => 'heroicon-o-heart',
                'Cuti Melahirkan' => 'heroicon-o-gift',
                'Cuti Keguguran' => 'heroicon-o-heart',
                'Cuti Menikah' => 'heroicon-o-sparkles',
                'Cuti Istri Melahirkan/Keguguran' => 'heroicon-o-clipboard-document-list',
            ];

            $rows[] = [
                'type' => $leave->leave_type,
                'quota' => $leave->days_count,
                'used' => $used,
                'remaining' => $remaining,
                'percentage' => $percentage,
                'badge' => $badgeColor,
                'icon' => $icons[$leave->leave_type] ?? 'heroicon-o-calendar',
            ];
        }
    }
?>

<?php if (isset($component)) { $__componentOriginald489e48d6214ecaf87e4b6a8ce684ad1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald489e48d6214ecaf87e4b6a8ce684ad1 = $attributes; } ?>
<?php $component = Filament\View\LegacyComponents\Widget::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::widget'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Filament\View\LegacyComponents\Widget::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <?php if (isset($component)) { $__componentOriginalee08b1367eba38734199cf7829b1d1e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee08b1367eba38734199cf7829b1d1e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.section.index','data' => ['heading' => 'Sisa Cuti per Jenis','description' => 'Berikut adalah rincian sisa cuti Anda berdasarkan jenis cuti.','collapsible' => 'true','collapsed' => 'true']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['heading' => 'Sisa Cuti per Jenis','description' => 'Berikut adalah rincian sisa cuti Anda berdasarkan jenis cuti.','collapsible' => 'true','collapsed' => 'true']); ?>

        <div class="grid w-full grid-cols-1 gap-6 md:grid-cols-3 lg:grid-cols-3">

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div
                    class="w-full p-6 rounded-2xl bg-secondary-light dark:bg-secondary-dark ring-1 ring-border-light dark:ring-border-dark">

                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <?php if (isset($component)) { $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.icon','data' => ['icon' => $row['icon'],'class' => 'w-6 h-6 text-main-primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($row['icon']),'class' => 'w-6 h-6 text-main-primary']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $attributes = $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $component = $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?>
                            <h3 class="text-lg font-semibold"><?php echo e($row['type']); ?></h3>
                        </div>
                    </div>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span>Kuota</span>
                            <span class="font-medium"><?php echo e($row['quota']); ?> hari</span>
                        </div>

                        <div class="flex justify-between">
                            <span>Dipakai</span>
                            <span class="font-medium"><?php echo e($row['used']); ?> hari</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="font-semibold">Sisa</span>
                            <span class="font-bold text-main-primary"><?php echo e($row['remaining']); ?>

                                hari</span>
                        </div>
                    </div>

                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        </div>

     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalee08b1367eba38734199cf7829b1d1e9)): ?>
<?php $attributes = $__attributesOriginalee08b1367eba38734199cf7829b1d1e9; ?>
<?php unset($__attributesOriginalee08b1367eba38734199cf7829b1d1e9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalee08b1367eba38734199cf7829b1d1e9)): ?>
<?php $component = $__componentOriginalee08b1367eba38734199cf7829b1d1e9; ?>
<?php unset($__componentOriginalee08b1367eba38734199cf7829b1d1e9); ?>
<?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald489e48d6214ecaf87e4b6a8ce684ad1)): ?>
<?php $attributes = $__attributesOriginald489e48d6214ecaf87e4b6a8ce684ad1; ?>
<?php unset($__attributesOriginald489e48d6214ecaf87e4b6a8ce684ad1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald489e48d6214ecaf87e4b6a8ce684ad1)): ?>
<?php $component = $__componentOriginald489e48d6214ecaf87e4b6a8ce684ad1; ?>
<?php unset($__componentOriginald489e48d6214ecaf87e4b6a8ce684ad1); ?>
<?php endif; ?><?php /**PATH /var/www/erp-app-main/resources/views/filament/widgets/hr/leave-balance-per-type.blade.php ENDPATH**/ ?>