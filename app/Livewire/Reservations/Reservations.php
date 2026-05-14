<?php

namespace App\Livewire\Reservations;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\Reservation;
use App\Services\RestaurantAvailabilityService;

class Reservations extends Component
{

    protected $listeners = ['refreshKots' => '$refresh'];
    public $dateRangeType;
    public $startDate;
    public $endDate;
    public $showAddReservation = false;
    public $search = '';
    public $isRestaurantOpenForReservations = true;
    public $restaurantClosedMessage = '';

    public function mount()
    {
        $tz = timezone();
        $dateFormat = restaurant()->date_format ?? 'd-m-Y';

        // Load date range type from cookie
        $this->dateRangeType = request()->cookie('reservations_date_range_type', 'currentWeek');
        $this->startDate = Carbon::now($tz)->startOfWeek()->format($dateFormat);
        $this->endDate = Carbon::now($tz)->endOfWeek()->format($dateFormat);

        $this->setDateRange();
    }

    public function setDateRange()
    {
        $tz = timezone();
        $dateFormat = restaurant()->date_format ?? 'd-m-Y';

        $ranges = [
            'today' => [Carbon::now($tz)->startOfDay(), Carbon::now($tz)->startOfDay()],
            'lastWeek' => [Carbon::now($tz)->subWeek()->startOfWeek(), Carbon::now($tz)->subWeek()->endOfWeek()],
            'nextWeek' => [Carbon::now($tz)->addWeek()->startOfWeek(), Carbon::now($tz)->addWeek()->endOfWeek()],
            'last7Days' => [Carbon::now($tz)->subDays(7), Carbon::now($tz)->startOfDay()],
            'currentMonth' => [Carbon::now($tz)->startOfMonth(), Carbon::now($tz)->endOfMonth()],
            'lastMonth' => [Carbon::now($tz)->subMonth()->startOfMonth(), Carbon::now($tz)->subMonth()->endOfMonth()],
            'currentYear' => [Carbon::now($tz)->startOfYear(), Carbon::now($tz)->startOfDay()],
            'lastYear' => [Carbon::now($tz)->subYear()->startOfYear(), Carbon::now($tz)->subYear()->endOfYear()],
            'default' => [Carbon::now($tz)->startOfWeek(), Carbon::now($tz)->endOfWeek()],
        ];

        [$start, $end] = $ranges[$this->dateRangeType] ?? $ranges['default'];

        $this->startDate = $start->format($dateFormat);
        $this->endDate = $end->format($dateFormat);
    }

    public function updatedDateRangeType($value)
    {
        cookie()->queue(cookie('reservations_date_range_type', $value, 60 * 24 * 30)); // 30 days
    }

    public function render()
    {

        if (!in_array('Table Reservation', restaurant_modules())) {
            return view('livewire.license-expire');
        }

        $tz = timezone();
        $dateFormat = restaurant()->date_format ?? 'd-m-Y';

        $start = Carbon::createFromFormat($dateFormat, $this->startDate, $tz)->startOfDay()->setTimezone('UTC')->toDateTimeString();
        $end = Carbon::createFromFormat($dateFormat, $this->endDate, $tz)->endOfDay()->setTimezone('UTC')->toDateTimeString();

        $reservations = Reservation::with('customer', 'table')
            ->orderBy('reservation_date_time', 'asc')
            ->whereDate('reservation_date_time', '>=', $start)
            ->whereDate('reservation_date_time', '<=', $end)
            ->where(function ($query) {
                $query->whereHas('customer', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('phone', 'like', '%' . $this->search . '%');
                });
            })
            ->get();

        $availability = RestaurantAvailabilityService::getAvailability(restaurant(), branch(), null, 'reservation');
        $this->isRestaurantOpenForReservations = (bool) ($availability['is_open'] ?? true);
        $this->restaurantClosedMessage = RestaurantAvailabilityService::getMessage($availability, restaurant());

        return view('livewire.reservations.reservations', [
            'reservations' => $reservations
        ]);
    }
}
