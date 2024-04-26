<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ActionController extends Controller
{
    public function store(Request $request)
    {
        $validateData = $request->validate([
            'name' => 'required',
            'goal_id' => 'required|exists:goals,id',
        ]);

        $action = Action::create($validateData);

        return redirect()->route('actions.show', $action)->with('success', 'タスクが作成されました。');
    }
}
