<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Models\MentorshipSession;
use App\Models\Message;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Muestra el dashboard principal del mentor
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $mentor = Auth::user();
        $upcomingSessions = MentorshipSession::where('mentor_id', $mentor->id)
            ->where('status', 'confirmed')
            ->where('start_time', '>', now())
            ->orderBy('start_time', 'asc')
            ->take(5)
            ->get();
            
        $pendingRequests = MentorshipSession::where('mentor_id', $mentor->id)
            ->where('status', 'requested') // Changed from 'pending' to 'requested' to match the enum in migration
            ->orderBy('requested_at', 'desc')
            ->take(5)
            ->get();
            
        $totalStudents = User::whereHas('mentorshipSessions', function($query) use ($mentor) {
            $query->where('mentor_id', $mentor->id);
        })->count();
        
        $totalCourses = Course::where('creator_id', $mentor->id)->count();
        
        $totalSessions = MentorshipSession::where('mentor_id', $mentor->id)
            ->where('status', 'completed')
            ->count();
            
        $unreadMessages = Message::where('recipient_id', $mentor->id)
            ->where('read', false)
            ->count();
            
        // Obtener eventos para el calendario
        $upcomingEvents = Event::where(function($query) use ($mentor) {
                $query->where('user_id', $mentor->id)
                      ->orWhere('mentor_id', $mentor->id);
            })
            ->where('start_date', '>=', now())
            ->orderBy('start_date', 'asc')
            ->take(10)
            ->get();
            
        // Formatear eventos para FullCalendar
        $formattedEvents = [];
        foreach ($upcomingEvents as $event) {
            $formattedEvents[] = [
                'id' => $event->id,
                'title' => $event->title,
                'start' => $event->start_date,
                'end' => $event->end_date ?? $event->start_date->addHour(),
                'url' => route('mentor.events.show', $event->id),
                'backgroundColor' => $event->type === 'session' ? '#4F46E5' : '#10B981',
                'borderColor' => $event->type === 'session' ? '#4338CA' : '#059669',
            ];
        }
        
        return view('mentor.dashboard', compact(
            'upcomingSessions', 
            'pendingRequests', 
            'totalStudents', 
            'totalCourses', 
            'totalSessions',
            'unreadMessages',
            'upcomingEvents',
            'formattedEvents'
        ));
    }
    
    /**
     * Muestra los cursos creados por el mentor
     *
     * @return \Illuminate\View\View
     */
    public function myCourses()
    {
        $mentor = Auth::user();
        $courses = Course::where('creator_id', $mentor->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return view('mentor.my_courses', compact('courses'));
    }
    
    /**
     * Muestra los estudiantes asignados al mentor
     *
     * @return \Illuminate\View\View
     */
    public function students()
    {
        $mentor = Auth::user();
        $students = User::whereHas('mentorshipSessions', function($query) use ($mentor) {
            $query->where('mentor_id', $mentor->id);
        })->paginate(10);
        
        return view('mentor.students', compact('students'));
    }
    
    /**
     * Muestra los mensajes del mentor
     *
     * @return \Illuminate\View\View
     */
    public function messages()
    {
        $mentor = Auth::user();
        $messages = Message::where('recipient_id', $mentor->id)
            ->orWhere('sender_id', $mentor->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        // Marcar mensajes como leídos
        Message::where('recipient_id', $mentor->id)
            ->where('read', false)
            ->update(['read' => true]);
            
        return view('mentor.messages.index', compact('messages'));
    }
    
    /**
     * Muestra el perfil del mentor
     *
     * @return \Illuminate\View\View
     */
    public function profile()
    {
        $mentor = Auth::user();
        return view('mentor.profile.index', compact('mentor'));
    }
    
    /**
     * Muestra los recursos del mentor
     *
     * @return \Illuminate\View\View
     */
    public function resources()
    {
        $mentor = Auth::user();
        $resources = $mentor->resources()->paginate(10);
        
        return view('mentor.resources', compact('resources'));
    }
    
    /**
     * Muestra las mentorías del mentor
     *
     * @return \Illuminate\View\View
     */
    public function mentorias()
    {
        $mentor = Auth::user();
        
        $upcomingSessions = MentorshipSession::where('mentor_id', $mentor->id)
            ->where('status', 'confirmed')
            ->where('start_time', '>', now())
            ->orderBy('start_time', 'asc')
            ->paginate(5, ['*'], 'upcoming');
            
        $pendingRequests = MentorshipSession::where('mentor_id', $mentor->id)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate(5, ['*'], 'pending');
            
        $pastSessions = MentorshipSession::where('mentor_id', $mentor->id)
            ->where('status', 'completed')
            ->orderBy('start_time', 'desc')
            ->paginate(5, ['*'], 'past');
            
        return view('mentor.mentorias', compact(
            'upcomingSessions', 
            'pendingRequests', 
            'pastSessions'
        ));
    }
}