<?php

namespace App\Models\Iam\Personnel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Traits\HasPermissions;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Iam\Personnel\EmergencyContact;
use Illuminate\Support\Str;
// Helpers
use App\Helpers\ModelHelper;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasRoles, HasPermissions, HasFactory, Notifiable, HasApiTokens;

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
        'status', // 'Active' or 'Inactive'
        'password',


        'shift_start_time',
        'shift_end_time',
        'vacation_eligible',
        'vacation_allotment_hour_id',
        'vacation_start_day_id',

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


}
