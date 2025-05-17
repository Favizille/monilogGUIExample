<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function __construct(private Book $book, private User $user)
    {
        
    }

    public function adminLogin(){
        return view('admin.login');
    }

    public function loginAdmin(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->route('admin.dashboard');
        } else {
            dd('Login failed', $credentials, bcrypt($credentials['password']));
        }

        return redirect()->back()->with("error", "Invalid credentials" );
        
    }


    public function showDashboard(){
        return view ('admin/dashboard');
    }

    public function showBooks(){
        $books = $this->book->with(['chapters' => function ($query) {
                    $query->orderBy('id');
                }])->get();
        return view('admin.comic_book', ['books' => $books]);
    }

    public function showUsers(){
        $users = $this->user->all();
        return view('admin.users', compact('users'));
    }

    public function createBookForm(){
        return view('admin.create_book');
    }

    public function registerUser(){
        return view('admin.register_user');
    }

    public function adminUserRegister(Request $request)
    {
        $validated = $request->validate([
            "name" => "required",
            "email" => "required",
            "password" => "required|confirmed"
        ]);

        
        $validated['password'] = bcrypt($request->password);
        
        
        $user = $this->user->create( $validated);
        // dd($user);

        if(!$user){
            return redirect()->back()->with('error', 'User registration failed');
        }

        return redirect()->route('user.show')->with('success', 'User Registered sucessfully');
    }

    public function deleteUser($email)
    {
        $user = $this->user->where('email', $email);

        if(!$user->delete()){
            return redirect()->back()->with('error', 'Deletion Failed');
        }

        return redirect()->route('user.show')->with('success', 'User Removed successfully!');
    }


    public function deleteSelectedUsers(Request $request)
    {
        $selectedUsers = explode(',', $request->input('selected_users', ''));

        if (empty($selectedUsers) || count($selectedUsers) === 0) {
            return redirect()->back()->with('error', 'No User selected for removal.');
        }

        if(!$this->user->whereIn('email', $selectedUsers)->delete()){
            return redirect()->back()->with('error', 'Deletion Failed');
        }

        return redirect()->route('user.show')->with('success', 'Selected Users deleted successfully!');
    }



}
