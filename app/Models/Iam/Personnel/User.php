<?php

namespace App\Models\Iam\Personnel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Traits\HasPermissions;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Iam\Personnel\EmergencyContact;
use Illuminate\Support\Str;
// Helpers
use App\Helpers\ModelHelper;
use App\Models\Stores\Store;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasRoles, HasPermissions, HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'unique_id',
        'first_name',
        'middle_name',
        'employee_code',
        'last_name',
        'email',
        'mobile_phone',
        'phone_number',

        'store_id',

        'street_address',
        'city',
        'state',
        'zip_code',
        'country',
        'start_date',
        'end_date',
        'pay_type',
        'limit_start_time',
        'limit_end_time',
        'lunch_override',
        'status', // 'Active' or 'Inactive'
        'password',


        'shift_start_time',
        'shift_end_time',
        'auto_clockout_penalty',
        'vacation_eligible',
        'vacation_allotment_hour_id',
        'vacation_start_day_id',

        'bonus_vacation_hours',
        'bonus_vacation_hours_start_date',
        'bonus_vacation_hours_end_date',

        'social_security',
        'is_driver',
    ];

    protected $appends = ['full_name', 'role_short_names'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_driver' => 'boolean',
        ];
    }

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'PER');

            // Generate unique employee_code
            $model->employee_code = self::generateEmployeeCode();
        });
    }

    private static function generateEmployeeCode()
    {
        do {
            $code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('employee_code', $code)->exists());

        return $code;
    }

    /**
     * Scope a query to only include active users.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    /**
     * Include active users and optionally specific assigned user IDs.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param iterable<int|string>|null $ids
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActiveOrIds($query, $ids = null)
    {
        $ids = collect($ids)
            ->filter(fn($id) => !is_null($id) && $id !== '')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $query->where(function ($q) use ($ids) {
            $q->active();

            if (!empty($ids)) {
                $q->orWhereIn('id', $ids);
            }
        });
    }

    /**
     * Get the user's full name.
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     *
     * Get the emergency contacts for the user.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<EmergencyContact>
     * */
    public function emergencyContacts()
    {
        return $this->hasMany(EmergencyContact::class);
    }

    public function emergencyContactOne()
    {
        return $this->hasOne(EmergencyContact::class)->where('contact_index', 1);
    }

    public function emergencyContactTwo()
    {
        return $this->hasOne(EmergencyContact::class)->where('contact_index', 2);
    }
    public function getRoleShortNamesAttribute(): array
    {
        return $this->roles->pluck('short_name')->toArray();
    }

    public function stateRelation()
    {
        return $this->belongsTo(\App\Models\Locations\State::class, 'state', 'id');
    }

    public function devices()
    {
        return $this->hasMany(UserDevice::class, 'user_id', 'id');
    }

    public function vacationStartDay()
    {
        return $this->belongsTo(
            VacationDay::class,
            'vacation_start_day_id'
        );
    }

    public function vacationAllotmentHour()
    {
        return $this->belongsTo(
            VacationHour::class,
            'vacation_allotment_hour_id'
        );
    }



    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class, 'employee_id');
    }

    public function activeTimeEntry()
    {
        return $this->hasOne(TimeEntry::class, 'employee_id')
            ->whereNull('clock_out')
            ->where('status', 'active');
    }


    public function vacationRequests()
    {
        return $this->hasMany(
            VacationRequest::class,
            'employee_id'
        );
    }


    public function approvedVacationRequests()
    {
        return $this->hasMany(
            VacationRequest::class,
            'employee_id'
        )->where('status', 'approved');
    }


    public function approvedVacationRequestsForYear(int $year)
    {
        return $this->hasMany(
            VacationRequest::class,
            'employee_id'
        )
        ->where('status', 'approved')
        ->whereYear('start_date', $year);
    }

public function store()
{
    return $this->belongsTo(Store::class);
}



public function getVacationAccruedHours(int $year): float
{
    $entries = $this->timeEntries()
        ->whereYear('clock_in', $year)
        ->whereNotNull('clock_out')
        ->get();

    $weeks = [];

    foreach ($entries as $entry) {
        $weekStart = \Carbon\Carbon::parse($entry->clock_in)
            ->startOfWeek()
            ->toDateString();

        $weeks[$weekStart][] = $entry;
    }

    $totalEligibleHours = 0;

    foreach ($weeks as $weekEntries) {
        $weekHours = collect($weekEntries)->sum('total_hours');
        $totalEligibleHours += min($weekHours, 40);
    }

    $allottedHours = optional($this->vacationAllotmentHour)->hours ?? 0;

    if ($allottedHours == 0) {
        return 0;
    }

    $rate = $allottedHours / 2080;

    $accrued = $totalEligibleHours * $rate;

    //  BONUS LOGIC START
    $bonus = 0;

    if (
        $this->vacation_eligible &&
        $this->bonus_vacation_hours &&
        $this->bonus_vacation_hours_start_date &&
        $this->bonus_vacation_hours_end_date
    ) {
        $now = now();

        if (
            $now->between(
                $this->bonus_vacation_hours_start_date,
                $this->bonus_vacation_hours_end_date
            )
        ) {
            $bonus = $this->bonus_vacation_hours;
        }
    }
    // BONUS LOGIC END

    return round($accrued + $bonus, 2);
}


public function getVacationUsedHours(int $year): float
{
    return $this->approvedVacationRequestsForYear($year)
        ->with('requestHour')
        ->get()
        ->sum(fn ($req) => $req->requestHour?->hours ?? 0);
}


public function getEligibleWorkedHours(int $year): float
{
    $entries = $this->timeEntries()
        ->whereYear('clock_in', $year)
        ->whereNotNull('clock_out')
        ->get();

    $weeks = [];

    foreach ($entries as $entry) {
        $weekStart = \Carbon\Carbon::parse($entry->clock_in)
            ->startOfWeek()
            ->toDateString();

        $weeks[$weekStart][] = $entry;
    }

    $total = 0;

    foreach ($weeks as $weekEntries) {
        $total += min(collect($weekEntries)->sum('total_hours'), 40);
    }

    return round($total, 2);
}


public function isMasterAdmin(): bool
{
    return $this->hasRole('master_admin');
}

}
