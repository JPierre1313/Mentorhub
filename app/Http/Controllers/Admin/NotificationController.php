<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Notification;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Constructor del controlador
     */
    public function __construct()
    {
        $this->middleware('auth');
        // El middleware de roles ha sido eliminado temporalmente
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $notifications = Notification::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return view('admin.notifications.index', compact('notifications'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = User::orderBy('name')->get();
        $roles = Role::all();
        
        return view('admin.notifications.create', compact('users', 'roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:info,success,warning,error',
            'recipient_type' => 'required|in:all,role,specific',
            'role_id' => 'required_if:recipient_type,role|exists:roles,id',
            'user_ids' => 'required_if:recipient_type,specific|array',
            'user_ids.*' => 'exists:users,id',
            'send_now' => 'boolean',
        ]);
        
        $notification = new Notification([
            'title' => $validated['title'],
            'message' => $validated['message'],
            'type' => $validated['type'],
            'created_by' => Auth::id(),
        ]);
        
        $notification->save();
        
        // Procesar destinatarios según el tipo seleccionado
        if ($validated['recipient_type'] === 'all') {
            // Notificación para todos los usuarios
            $users = User::all();
            $notification->users()->attach($users->pluck('id'));
        } elseif ($validated['recipient_type'] === 'role') {
            // Notificación para usuarios con un rol específico
            $role = Role::findById($validated['role_id']);
            $users = User::role($role->name)->get();
            $notification->users()->attach($users->pluck('id'));
        } elseif ($validated['recipient_type'] === 'specific') {
            // Notificación para usuarios específicos
            $notification->users()->attach($validated['user_ids']);
        }
        
        // Enviar notificación inmediatamente si se solicita
        if ($request->has('send_now') && $request->send_now) {
            $notification->is_sent = true;
            $notification->sent_at = now();
            $notification->save();
            
            // Aquí iría la lógica para enviar notificaciones a los usuarios
            // Por ejemplo, mediante eventos, correos electrónicos, etc.
        }
        
        return redirect()->route('admin.notifications.index')
            ->with('success', 'Notificación creada exitosamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $notification = Notification::with('users')->findOrFail($id);
        return view('admin.notifications.show', compact('notification'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $notification = Notification::findOrFail($id);
        $users = User::orderBy('name')->get();
        $roles = Role::all();
        
        return view('admin.notifications.edit', compact('notification', 'users', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $notification = Notification::findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:info,success,warning,error',
            'send_now' => 'boolean',
        ]);
        
        $notification->update([
            'title' => $validated['title'],
            'message' => $validated['message'],
            'type' => $validated['type'],
        ]);
        
        // Enviar notificación si se solicita y aún no ha sido enviada
        if ($request->has('send_now') && $request->send_now && !$notification->is_sent) {
            $notification->is_sent = true;
            $notification->sent_at = now();
            $notification->save();
            
            // Aquí iría la lógica para enviar notificaciones a los usuarios
        }
        
        return redirect()->route('admin.notifications.index')
            ->with('success', 'Notificación actualizada exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $notification = Notification::findOrFail($id);
        $notification->delete();
        
        return redirect()->route('admin.notifications.index')
            ->with('success', 'Notificación eliminada exitosamente');
    }
    
    /**
     * Export notifications to the specified format.
     *
     * @param  string  $format
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response
     */
    public function export($format = 'excel')
    {
        $notifications = Notification::with('user')
            ->orderBy('created_at', 'desc')
            ->get();
            
        $fileName = 'notifications-' . now()->format('Y-m-d-His');
        
        if ($format === 'csv') {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '.csv"',
            ];
            
            $callback = function() use ($notifications) {
                $file = fopen('php://output', 'w');
                
                // Add CSV headers
                fputcsv($file, [
                    'ID', 'Título', 'Mensaje', 'Tipo', 'Pública', 'Creada por', 'Creada el', 'Enviada', 'Enviada el'
                ]);
                
                // Add data rows
                foreach ($notifications as $notification) {
                    fputcsv($file, [
                        $notification->id,
                        $notification->title,
                        $notification->message,
                        $notification->type,
                        $notification->is_public ? 'Sí' : 'No',
                        $notification->user ? $notification->user->name : 'Sistema',
                        $notification->created_at->format('d/m/Y H:i'),
                        $notification->is_sent ? 'Sí' : 'No',
                        $notification->sent_at ? $notification->sent_at->format('d/m/Y H:i') : 'No enviada',
                    ]);
                }
                
                fclose($file);
            };
            
            return response()->stream($callback, 200, $headers);
        } else {
            // Default to Excel (xlsx) using Fast Excel
            $export = new \App\Exports\NotificationsExport($notifications);
            $filePath = $export->export();
            
            return response()->download($filePath, $fileName . '.xlsx')
                ->deleteFileAfterSend(true);
        }
    }
}
