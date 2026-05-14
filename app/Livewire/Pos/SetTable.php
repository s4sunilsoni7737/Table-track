<?php

namespace App\Livewire\Pos;

use App\Models\Area;
use Livewire\Component;
use App\Models\Reservation;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Facades\DB;

class SetTable extends Component
{
    use LivewireAlert;

    public $tables;
    public $reservations;
    public $targetEvent = 'setTable';

    protected $listeners = [
        'posOrderSuccess' => 'refreshData',
        'refreshSetTableComponent' => 'refreshDataWithCleanup',
    ];

    /**
     * Get table IDs assigned to the current waiter
     * Returns null if user is not a waiter (shows all tables)
     */
    private function getAssignedTableIds()
    {
        $user = user();

        // Check if current user is a waiter
        if (!$user || !$user->hasRole('Waiter_' . $user->restaurant_id)) {
            return null; // Not a waiter, return null to show all tables
        }

        $today = now()->format('Y-m-d');

        // Get table IDs assigned to this waiter
        $assignedTableIds = DB::table('assign_waiter_to_tables')
            ->where('waiter_id', $user->id)
            ->where('is_active', true)
            ->where('effective_from', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $today);
            })
            ->pluck('table_id')
            ->toArray();

        return $assignedTableIds;
    }

    /**
     * Get table IDs that are assigned to any waiter (active, current period).
     * Used to show only unassigned tables to waiters who have no tables assigned.
     */
    private function getTableIdsAssignedToAnyWaiter(): array
    {
        $today = now()->format('Y-m-d');

        return DB::table('assign_waiter_to_tables')
            ->where('is_active', true)
            ->where('effective_from', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $today);
            })
            ->pluck('table_id')
            ->unique()
            ->values()
            ->toArray();
    }

    private function loadTables()
    {
        $assignedTableIds = $this->getAssignedTableIds();
        $assignedToAnyWaiterTableIds = ($assignedTableIds !== null && empty($assignedTableIds))
            ? $this->getTableIdsAssignedToAnyWaiter()
            : [];

        return Area::with(['tables' => function ($query) use ($assignedTableIds, $assignedToAnyWaiterTableIds) {
            $query->where('available_status', '<>', 'running')
                ->where('status', 'active');

            // Not a waiter: show all tables
            if ($assignedTableIds === null) {
                return;
            }
            // Waiter with assigned tables: show only their tables
            if (!empty($assignedTableIds)) {
                $query->whereIn('id', $assignedTableIds);
                return;
            }
            // Waiter with no tables assigned: show only tables not assigned to any waiter
            if (!empty($assignedToAnyWaiterTableIds)) {
                $query->whereNotIn('id', $assignedToAnyWaiterTableIds);
            }
        }, 'tables.tableSession.lockedByUser'])->get();
    }

    private function loadReservations()
    {
        return Reservation::whereDate('reservation_date_time', now(timezone())->toDateString())
            ->whereNotNull('table_id')
            ->with('table')
            ->get();
    }

    public function mount()
    {
        $this->refreshDataWithCleanup();
    }

    /**
     * Refresh data with expired lock cleanup - called by refreshSetTableComponent
     */
    public function refreshDataWithCleanup()
    {
        try {
            // First, clean up expired locks and get the result
            \App\Models\Table::cleanupExpiredLocks();
            // Then refresh the data
            $this->refreshData();

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('SetTable: Error in refreshDataWithCleanup', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Refresh data without cleanup - for normal updates
     */
    public function refreshData()
    {

        $this->tables = $this->loadTables();
        $this->reservations = $this->loadReservations();
    }

    public function setOrderTable($table)
    {
        // Check if table is locked before allowing selection
        $tableModel = \App\Models\Table::find($table['id']);

        if ($tableModel && !$tableModel->canBeAccessedByUser(user()->id)) {
            $session = $tableModel->tableSession;
            $lockedByUser = $session?->lockedByUser;
            $lockedUserName = $lockedByUser?->name ?? 'Admin';

            $this->alert('error', __('messages.tableLockedByUser', ['user' => $lockedUserName]), [
                'toast' => true,
                'position' => 'top-end'
            ]);
            return;
        }

        $this->dispatch($this->targetEvent, table: $table);
    }

    /**
     * Force unlock any table - giving admin power to current user
     */
    public function forceUnlockTable($tableId)
    {
        $table = \App\Models\Table::find($tableId);

        if (!$table) {
            $this->alert('error', __('messages.tableNotFound'), [
                'toast' => true,
                'position' => 'top-end'
            ]);
            return;
        }


        $isAdmin = user()->hasRole('Admin_' . user()->restaurant_id);
        $isLockedByCurrentUser = $table->tableSession && $table->tableSession->locked_by_user_id == user()->id;

        if (!($isAdmin || $isLockedByCurrentUser)) {
            $this->alert('error', __('messages.tableUnlockFailed'), [
            'toast' => true,
            'position' => 'top-end'
            ]);
            return;
        }

        $result = $table->unlock(null, true);

        if ($result['success']) {
            $this->alert('success', __('messages.tableUnlockedSuccess', ['table' => $table->table_code]), [
                'toast' => true,
                'position' => 'top-end',
            ]);
            $this->dispatch('tableLockUpdated');
            $this->refreshData();
        } else {
            $this->alert('error', __('messages.tableUnlockFailed'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
        }
    }


    public function render()
    {
        return view('livewire.pos.set-table');
    }

}
