<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompany;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserManagementController extends Controller
{
    use ResolvesCompany;

    public function index() { return view('users.index', ['company'=>$this->company(), 'users'=>User::orderBy('name')->get()]); }

    public function store(Request $request)
    {
        $data=$request->validate(['name'=>'required|string|max:255','username'=>'required|string|lowercase|alpha_dash|min:3|max:50|unique:users,username','email'=>'required|string|lowercase|email|max:255|unique:users,email','role'=>'required|in:admin,accountant,sales,inventory,staff','password'=>['required','confirmed',Rules\Password::defaults()]]);
        User::create($data+['password'=>Hash::make($data['password']),'is_active'=>true]);
        return back()->with('success','Team member created. Share the login details securely.');
    }

    public function update(Request $request, User $user)
    {
        $data=$request->validate(['role'=>'required|in:admin,accountant,sales,inventory,staff','is_active'=>'nullable|boolean']);
        if($user->id===auth()->id() && (($data['is_active'] ?? false)===false || $data['role']!=='admin')) return back()->withErrors(['role'=>'You cannot remove your own administrator access.']);
        $user->update(['role'=>$data['role'],'is_active'=>$request->boolean('is_active')]);
        return back()->with('success','Team member access updated.');
    }
}
