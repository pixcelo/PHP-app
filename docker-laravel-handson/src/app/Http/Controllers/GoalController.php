<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GoalController extends Controller
{
    public function index()
    {
        $goals = Goal::orderBy('id', 'asc')->get();
        
        return view('goal_list', [
            'goals' => $goals
        ]);
    }

    public function store(Request $request)
    {
        $validationData = $request->validate([
            'name' => 'required',
            'category' => 'required',
        ]);

        $goal = Goal::crate($validateData);

        return redirect()->route('goals.show', $goal)->with('success', '目標が作成されました。');
    }    
}
