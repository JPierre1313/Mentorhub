<?php

namespace App\Models;

use App\Models\Profile;
use App\Models\Speciality;
use App\Models\Experience;
use App\Models\Education;
use App\Models\MentorAvailability;
use App\Models\MentorshipSession;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Event;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, LogsActivity, HasRoles;
    
    /**
     * Nombre personalizado para los registros de actividad.
     *
     * @var string
     */
    protected static $activityLogName = 'Usuario';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // admin, mentor, student
        'is_active',
        'profile_photo_path',
    ];
    
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url',
    ];
    
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];
    
    /**
     * Get the URL to the user's profile photo.
     *
     * @return string
     */
    /**
     * Get the URL to the user's profile photo.
     *
     * @return string
     */
    public function getProfilePhotoUrlAttribute()
    {
        if ($this->profile_photo_path) {
            return asset('storage/'.$this->profile_photo_path);
        }
        
        // Default avatar if no photo is set
        $name = trim(collect(explode(' ', $this->name))->map(function ($segment) {
            return mb_substr($segment, 0, 1);
        })->join(' '));
        
        return 'https://ui-avatars.com/api/?name='.urlencode($name).'&color=7F9CF5&background=EBF4FF';
    }
    
    /**
     * Alias for getProfilePhotoUrlAttribute()
     * 
     * @return string
     */
    public function getAvatarUrl()
    {
        return $this->profile_photo_url;
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole($role)
    {
        return $this->role === $role;
    }

    /**
     * Get the user's profile.
     */
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    /**
     * Get the user's specialities.
     */
    public function specialities()
    {
        return $this->belongsToMany(Speciality::class, 'mentor_speciality', 'user_id', 'speciality_id')
            ->withTimestamps();
    }

    /**
     * Get the user's work experiences.
     */
    public function experiences()
    {
        return $this->hasMany(Experience::class);
    }

    /**
     * Get the user's education history.
     */
    public function education()
    {
        return $this->hasMany(Education::class);
    }

    /**
     * Get the user's availability.
     */
    public function availabilities()
    {
        return $this->hasMany(MentorAvailability::class, 'user_id');
    }

    /**
     * Get the user's students (for mentors).
     */
    public function students()
    {
        return $this->belongsToMany(User::class, 'mentorship_sessions', 'mentor_id', 'student_id')
            ->withPivot('status')
            ->withTimestamps()
            ->distinct();
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is a mentor
     */
    public function isMentor()
    {
        return $this->role === 'mentor';
    }

    /**
     * Check if user is a student
     */
    public function isStudent()
    {
        return $this->role === 'student';
    }
    
    /**
     * Obtiene las sesiones donde el usuario es mentor.
     */
    public function mentorSessions()
    {
        return $this->hasMany(MentorshipSession::class, 'mentor_id');
    }
    
    /**
     * Alias for mentorSessions for backward compatibility.
     */
    public function mentorshipSessions()
    {
        return $this->mentorSessions();
    }

    /**
     * Obtiene las sesiones donde el usuario es estudiante.
     */
    public function menteeSessions()
    {
        return $this->hasMany(MentorshipSession::class, 'student_id');
    }
    
    /**
     * Alias para menteeSessions para mantener compatibilidad.
     */
    public function studentSessions()
    {
        return $this->menteeSessions();
    }
    
    /**
     * Obtiene los cursos creados por el usuario (si es mentor).
     */
    public function courses()
    {
        return $this->hasMany(Course::class, 'creator_id');
    }
    
    /**
     * Obtiene los cursos en los que el usuario está inscrito (si es estudiante).
     */
    public function enrolledCourses()
    {
        return $this->belongsToMany(Course::class, 'enrollments', 'user_id', 'course_id')
            ->withTimestamps()
            ->withPivot(['enrolled_at', 'completed_at']);
    }

    /**
     * Obtiene las solicitudes de mentoría pendientes para el mentor.
     */
    public function mentorRequests()
    {
        return $this->mentorSessions()->where('status', 'pending');
    }

    /**
     * Obtiene los estudiantes asociados con el mentor a través de sesiones.
     */
    public function menteeStudents()
    {
        return $this->hasManyThrough(
            User::class,
            MentorshipSession::class,
            'mentor_id',
            'id',
            'id',
            'student_id'
        )->distinct();
    }

    /**
     * Alias for menteeStudents() for backward compatibility.
     */
    public function mentorStudents()
    {
        return $this->menteeStudents();
    }

    /**
     * Obtiene los mentores asociados con el estudiante a través de sesiones.
     */
    /**
     * Obtiene los mentores asociados con el estudiante a través de sesiones.
     */
    public function mentors()
    {
        return $this->hasManyThrough(
            User::class,
            MentorshipSession::class,
            'student_id', // Cambiado de 'mentee_id' a 'student_id'
            'id',
            'id',
            'mentor_id'
        )->distinct();
    }

    /**
     * Obtiene las reseñas recibidas por el usuario.
     */
    public function receivedReviews()
    {
        return $this->hasMany(MentorshipReview::class, 'target_id');
    }

    /**
     * Obtiene las reseñas escritas por el usuario.
     */
    public function givenReviews()
    {
        return $this->hasMany(MentorshipReview::class, 'author_id');
    }

    /**
     * Obtiene la calificación promedio del usuario como mentor.
     */
    public function getAverageMentorRatingAttribute()
    {
        $reviews = $this->receivedReviews()->where('is_mentor_review', false)->get();
        if ($reviews->isEmpty()) {
            return 0;
        }
        return $reviews->avg('rating');
    }

    /**
     * Obtiene la calificación promedio del usuario como estudiante.
     */
    public function getAverageStudentRatingAttribute()
    {
        $reviews = $this->receivedReviews()->where('is_mentor_review', true)->get();
        if ($reviews->isEmpty()) {
            return 0;
        }
        return $reviews->avg('rating');
    }


    /**
     * Obtiene los mensajes enviados por el usuario.
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }
    
    /**
     * Obtiene los mensajes recibidos por el usuario.
     */
    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }
    
    /**
     * Obtiene los mensajes no leídos del usuario.
     */
    public function unreadMessages()
    {
        return $this->receivedMessages()
            ->where('read', false)
            ->where('deleted_by_recipient', false);
    }

    /**
     * Obtiene las inscripciones del usuario a cursos.
     */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'user_id');
    }

    /**
     * Obtiene las conversaciones del usuario.
     */
    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user', 'user_id', 'conversation_id')
            ->withTimestamps();
    }
    
    /**
     * Get the events that the user is attending.
     */
    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_user', 'user_id', 'event_id')
            ->withPivot('status', 'attended')
            ->withTimestamps();
    }
}

