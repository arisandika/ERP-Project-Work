# Employee Performance Feature - Implementation Plan

## Current State Findings

### Employee (`nx_employees` table, `app/Models/HR/Employee.php`)
- No supervisor relationship exists — needs `supervisor_id` FK added to same table.
- Has `projects()` belongsToMany via `nx_project_members` (already tracks role-less membership).
- Has `assignedTickets()` belongsToMany via `nx_ticket_users`.
- `position` is an enum string (Staf/Junior/Senior/Magang/Pimpinan/Mantan Karyawan).

### Project (`nx_projects` table, `app/Models/Project/Project.php`)
- Has `project_manager_id` FK to Employee — **column is defined in model but NOT in migration** (existing bug, not in scope).
- Has `tickets()` hasMany, `ticketStatuses()`, `members()` belongsToMany Employee.

### Ticket (`nx_tickets` table, `app/Models/Project/Ticket.php`)
- Fields: `project_id`, `ticket_status_id`, `due_date`, `start_date`, `created_by`, `priority_id`, `epic_id`.
- `assignees()` belongsToMany Employee via `nx_ticket_users`.
- `creator()` belongsTo Employee.
- Status has `is_completed` boolean on `TicketStatus`.

### Permission System
- Shield + Spatie. `super_admin` bypasses via `Gate::before`.
- Permissions auto-derived from Policies (e.g. `view_h::r::employee`).
- Module access via `module.access.{key}` permissions in `config/erp-modules.php` + `config/module-role-permissions.php`.
- `BelongsToModule` trait gates navigation visibility by `module.access.*`.

### Overdue Definition (no existing logic)
- A ticket is **overdue** if `due_date < today` AND its `TicketStatus.is_completed = false`.

---

## Implementation Steps

### Step 1: Migration — add `supervisor_id` to employees
**File:** `database/migrations/2026_08_30_000000_add_supervisor_id_to_nx_employees_table.php`

```php
Schema::table('nx_employees', function (Blueprint $table) {
    $table->foreignId('supervisor_id')
        ->nullable()
        ->constrained('nx_employees')
        ->nullOnDelete();       // avoid cascade cycles
    $table->index('supervisor_id');
});
```

### Step 2: Employee model relationships
**File:** `app/Models/HR/Employee.php` — add:

```php
public function supervisor(): BelongsTo
{
    return $this->belongsTo(Employee::class, 'supervisor_id');
}

public function subordinates(): HasMany
{
    return $this->hasMany(Employee::class, 'supervisor_id');
}
```

### Step 3: PerformanceEvaluation model + migration + policy
**Migration** `2026_08_30_000001_create_nx_performance_evaluations_table.php`:

```php
Schema::create('nx_performance_evaluations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained('nx_employees')->cascadeOnDelete();
    $table->foreignId('evaluator_id')->constrained('nx_employees')->cascadeOnDelete();
    $table->string('period');                // e.g. "2025-Q4" / "2025-12"
    $table->unsignedTinyInteger('rating');   // 1–5 scale (enum kept flexible via int)
    $table->text('feedback')->nullable();
    $table->timestamp('evaluated_at')->nullable();
    $table->timestamps();

    $table->unique(['employee_id', 'period', 'evaluator_id']);
});
```

**Model** `app/Models/HR/PerformanceEvaluation.php`:
- Table `nx_performance_evaluations`.
- `employee()`, `evaluator()` belongsTo Employee.
- Validation: rating 1..5.

**Policy** `app/Policies/HR/PerformanceEvaluationPolicy.php`:
- Mirror existing EmployeePolicy pattern (Shield-generated).
- `viewAny`: `can('view_any_hr::performance_evaluation')`
- `view`: supervisor of employee OR evaluator OR super_admin.

### Step 4: ProjectMember pivot enrichment (Role/PIC)
**Decision:** `nx_project_members` pivot **currently has no role column**.

Options:
- A. Add `role` column to pivot (1 migration) — cleanest, lets each project store per-employee role/PIC.
- B. Derive PIC from `Project.project_manager_id` — only one PIC per project; does NOT satisfy per-project role display per employee.

→ **Choose A**: add `role` string column to `nx_project_members`. Default `null`. Supervisor/PIC = `role = 'PIC'` or matches `project_manager_id`.

**Migration** `2026_08_30_000002_add_role_to_nx_project_members_table.php`:

```php
Schema::table('nx_project_members', function (Blueprint $table) {
    $table->string('role')->nullable()->default(null)
        ->comment('e.g. PIC, Developer, Designer, QA, Support');
    $table->index('role');
});
```

Update `Employee::projects()` and `Project::members()/employees()` to `withPivot(['role'])`.

### Step 5: Computed metrics on Employee (scoped to a Project)
Add a convenience method on Employee (or a dedicated service) to compute:
- total tasks (tickets assigned OR created within project)
- completed tasks (status is_completed true)
- overdue tasks (due_date < today AND not completed)
- completion rate = completed/total * 100

Implementation: a query scope on Ticket filtered by `project_id` and assignee/creator.

```php
// In Employee model — tasks scoped to a project
public function tasksInProject(Project $project): BelongsToMany
{
    return $this->belongsToMany(Ticket::class, 'nx_ticket_users')
        ->where('project_id', $project->id);
}
```
Completion-rate computed in controller/page, not stored.

### Step 6: Filament Resource — EmployeePerformanceResource
**Navigation:** under "Manajemen HR" group, icon `heroicon-o-chart-bar`, navigationSort 6 (after Employee at 5).

**Slug:** `hr/employees/performance`

Pages:
1. **ListEmployeePerformance** (Table)
   - Shows `subordinates()` of the logged-in supervisor (filter: `supervisor_id = auth employee id`).
   - Columns: `full_name`, `employee_id`, `department.name`, `position`, `projects_count`, `action` (view).
   - Super-admin: shows all employees (no supervisor filter) — consistent with other HR resources.

2. **ViewEmployeePerformance** (custom page / entity view)
   - Header: employee profile summary (reuse infolist from EmployeeResource via `infolist` + custom section).
   - **Project History table** (Filament Table):
     - Relationship: `projects()` with pivot role.
     - Columns: `name` (Project), `pivot.role` (Role/PIC), `tickets_count` (Total tasks), computed `completed_tasks`, computed `overdue_tasks`, computed `completion_rate`.
     - Row action → "Detail" navigates to ViewEmployeeProjectDetail.

3. **ViewEmployeeProjectDetail** (custom page, per project-per-employee)
   - Route: `/hr/employees/performance/{employee}/project/{project}`.
   - Shows:
     - Project header (name, dates, status, project manager).
     - Role/PIC badge.
     - Metrics: Total tasks, Completed, Overdue, Completion rate (percent bar).
     - **Task list table**: tickets assigned to employee in that project — columns: name, status, priority, due_date (with overdue highlight), epic.
   - Header action: "Evaluate" → opens PerformanceEvaluation modal OR navigates to evaluation list.

   - **PerformanceEvaluation RelationManager** (table at bottom of detail page):
     - Columns: `period`, `evaluator.full_name`, `rating`, `evaluated_at`, `feedback` (truncated).
     - Actions: View / Edit (if own or super admin) / Delete.
     - Header action: "Add Evaluation" opens form modal (rating 1-5, period picker, feedback textarea).

### Step 7: Policy wiring + Shield permissions
- Create `PerformanceEvaluationPolicy`.
- Run `shield:generate --all` or rely on Shield discovery (resource auto-discovered on first access).
- Register resource in `config/filament-shield.php` custom_permissions OR let Shield auto-create.

### Step 8: Register navigation
- Resource auto-registers via Filament `discoverResources`.
- `BelongsToModule` trait + `module.access.hr` permission gates visibility.

---

## Files to Create/Modify

### New files
| File | Purpose |
|------|---------|
| `database/migrations/2026_08_30_000000_add_supervisor_id_to_nx_employees_table.php` | Supervisor FK |
| `database/migrations/2026_08_30_000001_create_nx_performance_evaluations_table.php` | Evaluation table |
| `database/migrations/2026_08_30_000002_add_role_to_nx_project_members_table.php` | Pivot role column |
| `app/Models/HR/PerformanceEvaluation.php` | Evaluation model |
| `app/Policies/HR/PerformanceEvaluationPolicy.php` | Shield policy |
| `app/Filament/Resources/HR/EmployeePerformanceResource.php` | Main resource |
| `app/Filament/Resources/HR/EmployeePerformanceResource/Pages/ListEmployeePerformance.php` | Employee list (subordinates) |
| `app/Filament/Resources/HR/EmployeePerformanceResource/Pages/ViewEmployeePerformance.php` | Employee detail + project history |
| `app/Filament/Resources/HR/EmployeePerformanceResource/Pages/ViewEmployeeProjectDetail.php` | Project detail + evaluations |
| `app/Filament/Resources/HR/EmployeePerformanceResource/RelationManagers/PerformanceEvaluationsRelationManager.php` | Evaluation CRUD |

### Modified files
| File | Change |
|------|--------|
| `app/Models/HR/Employee.php` | Add `supervisor()` / `subordinates()` relations; update `projects()` pivot |
| `app/Models/Project/Project.php` | `members()` pivot `withPivot('role')` |
| `app/Models/Project/Ticket.php` | Optional: add helper scope for overdue / completed |
| `database/seeders/ShieldSeeder.php` | (auto-handled by Shield discovery) |

## Key Design Decisions
- **No duplicate project/task data.** PerformanceEvaluation references `employee_id` + period; project/task reads from existing models.
- **Supervisor hierarchy** added as `supervisor_id` self-reference — the only schema addition to Employee (minimal, single column).
- **Role/PIC per project** via pivot `role` column — minimal pivot enrichment.
- **Metrics computed at query time** — no stored aggregates, always fresh.
