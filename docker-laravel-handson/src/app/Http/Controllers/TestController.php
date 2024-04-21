<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Test;

class TestController extends Controller
{
    public function index()
    {
        $models = Test::all(); // 全件取得
        
        // dd($models); // die + var_dump 処理を止めて内容を確認できる
        
        // return view('tests.test');
        return view('tests.test', compact('models'));
    }
}
