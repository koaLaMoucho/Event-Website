<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Report;
use Illuminate\Database\QueryException;

class AdminController extends Controller
{
    public function showAdminPage()
    {
        $user = Auth::user();
        $notifications = $user->notifications;
        $this->authorize('verifyAdmin', Admin::class);

        $userCount = null;
        $eventCount = null;
        $activeEventCount = null;
        $inactiveEventCount = null;
        $eventCountByMonth = Event::countEventsByMonth(date('n')); 
        $eventCountByDay = Event::countEventsByDay(date('j')); 
        $eventCountByYear = Event::countEventsByYear(date('Y'));
        $activeUsers = [];
        $inactiveUsers = [];

        $userCount = User::countUsers();
        $eventCount = Event::countEvents();
        $activeEventCount = Event::countActiveEvents();
        $inactiveEventCount = Event::countInactiveEvents();
        $activeUsers = User::where('active', true)->get();
        $inactiveUsers = User::where('active', false)->get();

        $reportedComments = DB::table('comment_')
            ->join('report', 'comment_.comment_id', '=', 'report.comment_id')
            ->join('event_', 'comment_.event_id', '=', 'event_.event_id')
            ->select('comment_.*', 'event_.name as event_name', 'report.type as type', 'report.report_id')
            ->distinct()
            ->get();

        return view('pages.admin', compact('notifications', 'userCount', 'eventCount', 'reportedComments', 'activeEventCount', 'inactiveEventCount', 'eventCountByMonth', 'eventCountByDay', 'eventCountByYear', 'activeUsers', 'inactiveUsers'));
    }

    public function getActiveUsersAjax(Request $request)
    {
        $activeUsers = User::where('active', true)->paginate(10);

        if ($request->ajax()) {
            return view('partials.active_table', compact('activeUsers'))->render();
        }

        return view('pages.admin', compact('activeUsers'));
    }

    public function getInactiveUsersAjax(Request $request)
    {
        $inactiveUsers = User::where('active', false)->paginate(10);

        if ($request->ajax()) {
            return view('partials.inactive_table', compact('inactiveUsers'))->render();
        }

        return view('pages.admin', compact('inactiveUsers'));
    }




    public function deactivateUser($id)
    {

        $user = User::findOrFail($id);

        $this->authorize('verifyAdmin', Admin::class);

        $user->active = false;
        $user->save();

        $user->own_events()->update(['private' => true]);

        return response()->json(['user_id' => $user->user_id]);
    }


    public function activateUser($id)
    {
        $user = User::findOrFail($id);

        $this->authorize('verifyAdmin', Admin::class);

        $user->active = true;
        $user->save();

        $user->own_events()->update(['private' => false]);

        return response()->json(['user_id' => $user->user_id]);
    }

    public function showUserCount()
    {
        if (Auth::check() && Auth::user()->is_admin) {
            $userCount = User::countUsers();
            return view('pages.admin', compact('userCount'));
        } else {
            return redirect()->route('login');
        }
    }

    public function getActiveUserCount()
    {
        $activeUserCount = User::where('active', true)->count();
        return response()->json(['count' => $activeUserCount]);
    }

    public function getInactiveUserCount()
    {
        $inactiveUserCount = User::where('active', false)->count();
        return response()->json(['count' => $inactiveUserCount]);
    }

    public function getActiveEventCount()
    {
        $activeEventCount = Event::countActiveEvents();
        return response()->json(['count' => $activeEventCount]);
    }

    public function getInactiveEventCount()
    {
        $inactiveEventCount = Event::countInactiveEvents();
        return response()->json(['count' => $inactiveEventCount]);
    }

    public function getEventCountByMonth($month)
    {
        $eventCount = Event::countEventsByMonth($month);
        return response()->json(['count' => $eventCount]);
    }

    public function getEventCountByDay($day)
    {
        $eventCount = Event::countEventsByDay($day);
        return response()->json(['count' => $eventCount]);
    }

    public function getEventCountByYear($year)
    {
        $eventCount = Event::countEventsByYear($year);
        return response()->json(['count' => $eventCount]);
    }

    
    public function deleteReport($reportId)
    {
        try {
            $report = Report::findOrFail($reportId);

            $report->notifications()->delete(); 

            $report->delete();

            return response()->json(['message' => 'Report deleted successfully', 'report_id' => $reportId]);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error deleting report'], 500);
        }
    }
}