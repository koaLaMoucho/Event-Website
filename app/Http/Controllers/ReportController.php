<?php

namespace App\Http\Controllers;
use App\Models\Report;
use Illuminate\Http\Request;
use App\Models\Notification;

class ReportController extends Controller
{

    public function submitReport(Request $request)
    {
        // Validate the report data
        $request->validate([
            'reportReason' => 'required',
        ]);

      
        $report = new Report();
        $report->type = $request->input('reportReason');
        $report->comment_id = $request->input('comment_id');
        $report->author_id = auth()->user()->user_id; 
        $report->save();

       
        return redirect()->back()->with('success', 'Report successfull.');
    }
}
