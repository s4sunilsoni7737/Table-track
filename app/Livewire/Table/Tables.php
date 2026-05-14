<?php

namespace App\Livewire\Table;

use App\Models\Area;
use App\Models\Table;
use App\Models\Order;
use App\Models\User;
use App\Scopes\BranchScope;
use Livewire\Component;
use App\Models\Reservation;
use Livewire\Attributes\On;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Facades\DB;

class Tables extends Component
{

    use LivewireAlert;

    public $activeTable;
    public $areaID = null;
    public $showAddTableModal = false;
    public $showEditTableModal = false;
    public $confirmDeleteTableModal = false;
    public $showAssignWaiterModal = false;
    public $showUpdateWaiterModal = false;
    public $showUpdateConfirmationModal = false;
    public $selectedTableId = null;
    public $selectedTable = null;
    public $currentWaiter = null;
    public $filterAvailable = null;
    public $viewType = 'list';
    public $reservations;
    public $reservedTables;
    public $timeSlotDifference;
    public $existingAssignment = false;

    protected $listeners = [
        'tableLockUpdated' => 'handleTableLockUpdate',
        'refreshTables' => '$refresh'
    ];

    public function mount()
    {
        // Get the saved view type from session, default to 'list' if not set
        $this->viewType = session('table_view_type', 'list');
        $this->reservations = Reservation::where('table_id', '!=', null)->get();
        // dd($this->reservations);
        $this->reservedTables = $this->reservations->pluck('table_id', 'reservation_date_time', 'reservation_status');
        // dd($this->reservedTables);

        $this->refreshDataWithCleanup();
    }

    /**
     * Refresh reservation collections without cleanup.
     */
    public function refreshData()
    {
        $this->reservations = Reservation::where('table_id', '!=', null)->get();
        $this->reservedTables = $this->reservations->pluck('table_id', 'reservation_date_time', 'reservation_status');
    }

    /**
     * React to table lock events by refreshing data with cleanup.
     */
    public function handleTableLockUpdate()
    {
        $this->refreshDataWithCleanup();
    }

    public function updatedViewType($value)
    {
        $this->refreshDataWithCleanup();

        // Save the view type preference to session whenever it changes
        session(['table_view_type' => $value]);
    }

    #[On('refreshTables')]
    public function refreshTables()
    {
        $this->render();
    }

    #[On('hideAddTable')]
    public function hideAddTable()
    {
        $this->showAddTableModal = false;
    }

    #[On('hideEditTable')]
    public function hideEditTable()
    {
        $this->showEditTableModal = false;
    }

    public function showEditTable($id)
    {
        $this->activeTable = Table::findOrFail($id);
        $this->showEditTableModal = true;
    }

    public function showTableOrder($id)
    {
        // Check if table is locked before allowing access
        $table = Table::find($id);

        if ($table && !$table->canBeAccessedByUser(user()->id)) {
            $session = $table->tableSession;
            $lockedByUser = $session?->lockedByUser;
            $lockedUserName = $lockedByUser?->name ?? 'Admin';

            $this->alert('error', __('messages.tableLockedByUser', ['user' => $lockedUserName]), [
                'toast' => true,
                'position' => 'top-end'
            ]);
            return;
        }

        // Full navigation so POS @push('scripts') runs and window.posState / AJAX handlers exist.
        return $this->redirect(route('pos.show', $id));
    }

    public function showTableOrderDetail($id)
    {
        return $this->redirect(route('pos.order', [$id]));
    }

    public function forceUnlockTable($tableId)
    {
        $table = Table::find($tableId);

        if (!$table) {
            $this->alert('error', __('messages.tableNotFound'), [
                'toast' => true,
                'position' => 'top-end'
            ]);
            return;
        }

        // Check permissions in one condition
        $hasPermission = user()->hasRole('Admin_' . user()->restaurant_id) ||
                        ($table->tableSession && $table->tableSession->locked_by_user_id == user()->id);

        if (!$hasPermission) {
            $this->alert('error', __('messages.tableUnlockFailed'), [
                'toast' => true,
                'position' => 'top-end'
            ]);
            return;
        }

        // Force unlock and handle result
        $result = $table->unlock(null, true);

        $this->alert(
            $result['success'] ? 'success' : 'error',
            $result['success']
                ? __('messages.tableUnlockedSuccess', ['table' => $table->table_code])
                : __('messages.tableUnlockFailed'),
            ['toast' => true, 'position' => 'top-end']
        );

        $this->dispatch('refreshTables');
    }

    public function showWaiterSelect($tableId)
    {
        $this->selectedTableId = $tableId;
        $this->selectedTable = Table::find($tableId);
        $this->currentWaiter = $this->selectedTable?->activeOrder?->waiter_id;

        // Check for existing active assignment
        $existingAssignment = DB::table('assign_waiter_to_tables')
            ->where('table_id', $tableId)
            ->orderBy('created_at', 'desc')
            ->first();

        // Show appropriate modal based on existing assignment
        if ($existingAssignment) {
            $this->existingAssignment = true;
            $this->showUpdateWaiterModal = true;
        } else {
            $this->existingAssignment = false;
            $this->showAssignWaiterModal = true;
        }
    }


    #[On('waiterAssigned')]
    public function handleWaiterAssigned()
    {
        $this->showAssignWaiterModal = false;
        $this->selectedTableId = null;
        $this->selectedTable = null;
        $this->currentWaiter = null;
    }

    #[On('waiterUpdated')]
    public function handleWaiterUpdated()
    {
        $this->showUpdateWaiterModal = false;
        $this->showUpdateConfirmationModal = false;
        $this->selectedTableId = null;
        $this->selectedTable = null;
        $this->currentWaiter = null;
    }

    public function showUpdateConfirmation()
    {
        // Close update modal and show confirmation modal
        $this->showUpdateWaiterModal = false;
        $this->showUpdateConfirmationModal = true;
    }

    public function confirmUpdateWaiter()
    {
        // Close confirmation modal - the component will handle the update
        $this->showUpdateConfirmationModal = false;
        $this->showUpdateWaiterModal = true;
        // Dispatch event to trigger update in the component
        $this->dispatch('confirmUpdate');
    }

    public function getCurrentWaiterUserProperty()
    {
        if ($this->currentWaiter) {
            return User::find($this->currentWaiter);
        }
        return null;
    }

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

    public function render()
    {
        // Get assigned table IDs if logged in user is a waiter
        $assignedTableIds = null;
        $user = user();
        if ($user && $user->hasRole('Waiter_' . $user->restaurant_id)) {
            $today = now()->format('Y-m-d');
            $assignedTableIds = DB::table('assign_waiter_to_tables')
                ->where(function ($q) use ($user) {
                    $q->where('waiter_id', $user->id)
                        ->orWhere('backup_waiter_id', $user->id);
                })
                ->where('is_active', true)
                ->where('effective_from', '<=', $today)
                ->where(function ($q) use ($today) {
                    $q->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', $today);
                })
                ->pluck('table_id')
                ->toArray();

            // If waiter has no assigned tables, show all tables
            if (empty($assignedTableIds)) {
                $assignedTableIds = null;
            }
        }

        $query = Area::with(['tables' => function ($query) use ($assignedTableIds) {
            if (!is_null($this->filterAvailable)) {
                $query->where('available_status', $this->filterAvailable);
            }

            // If user is a waiter, only show assigned tables
            if (!is_null($assignedTableIds)) {
                if (empty($assignedTableIds)) {
                    // Waiter has no assigned tables, return empty result
                    $query->whereRaw('1 = 0');
                } else {
                    $query->whereIn('id', $assignedTableIds);
                }
            }
        }, 'tables.activeOrder', 'tables.tableSession.lockedByUser']);

        if (!is_null($this->areaID)) {
            $query = $query->where('id', $this->areaID);
        }

        $query = $query->get();

        // Filter out areas that have no tables after waiter filtering
        if (!is_null($assignedTableIds)) {
            $query = $query->map(function($area) use ($assignedTableIds) {
                $area->setRelation('tables', $area->tables->filter(function($table) use ($assignedTableIds) {
                    return in_array($table->id, $assignedTableIds);
                }));
                return $area;
            })->filter(function($area) {
                return $area->tables->count() > 0;
            });
        }

        // Get all table IDs to check for reservations
        $tableIds = $query->flatMap(function($area) {
            return $area->tables->pluck('id');
        });

        // Get reservations for these tables
        $tableReservations = $this->reservations->whereIn('table_id', $tableIds)
            ->keyBy('table_id')
            ->map(function($reservation) {
                // Get the time slot difference for this reservation's slot type
                $timeSlotDifference = \App\Models\ReservationSetting::where('slot_type', $reservation->reservation_slot_type)->first();

                $dateFormat = restaurant()->date_format ?? dateFormat();
                $timeFormat = restaurant()->time_format ?? timeFormat();

                return [
                    'date' => $reservation->reservation_date_time->translatedFormat($dateFormat),
                    'time' => $reservation->reservation_date_time->translatedFormat($timeFormat),
                    'datetime' => $reservation->reservation_date_time->translatedFormat($dateFormat . ' ' . $timeFormat),
                    'status' => $reservation->reservation_status,
                    'reservation_slot_type' => $reservation->reservation_slot_type,
                    'timeSlotDifference' => $timeSlotDifference ? $timeSlotDifference->time_slot_difference : null
                ];
            });

        // Load waiters for the restaurant
        $waiters = cache()->remember('waiters_' . restaurant()->id, 60 * 60 * 24, function () {
            return User::withoutGlobalScope(BranchScope::class)
                ->where(function ($q) {
                    return $q->where('branch_id', branch()->id)
                        ->orWhereNull('branch_id');
                })
                ->role('waiter_' . restaurant()->id)
                ->where('restaurant_id', restaurant()->id)
                ->get();
        });

        return view('livewire.table.tables', [
            'tables' => $query,
            'areas' => Area::get(),
            'tableReservations' => $tableReservations,
            'waiters' => $waiters
        ]);
    }

}
