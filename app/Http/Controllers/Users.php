<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;
use App\User;

class Users extends Controller
{
    public function index(Request $request){
        //get users from users table
        // $users = User::orderBy('created_at', 'desc')->paginate(10);

        // //return json
        // return response()->json(['status' => 'success', 'data' => $users]);

        $users = User::orderBy('created_at', 'desc')->when($request->q, function($users) use($request) {
            $users = $users->where('name', 'LIKE', '%' . $request->q . '%');
        })->paginate(10);
        return response()->json(['status' => 'success', 'data' => $users]);
    }

    public function store(Request $request)
    {
        //Validation
        $this->validate($request, [
            'name' => 'required|string|max:50',
            'identity_id' => 'required|string|unique:users', //UNIQUE BERARTI DATA INI TIDAK BOLEH SAMA DI DALAM TABLE USERS
            'gender' => 'required',
            'address' => 'required|string',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'phone_number' => 'required|string',
            'role' => 'required',
            'status' => 'required',
        ]);

        //User will be able to empty photo, except drivers
        $filename = null;
        //Check if theres file
        if ($request->hasFile('photo')) {
            //Then generate name for the file, using random string + email
            $filename = Str::random(5) . $request->email . '.jpg';
            $file = $request->file('photo');
            $file->move(base_path('public/images'), $filename); //Save to folder public/images
        }

        //Saving user data to users table using User Model
        User::create([
            'name' => $request->name,
            'identity_id' => $request->identity_id,
            'gender' => $request->gender,
            'address' => $request->address,
            'photo' => $filename,
            'email' => $request->email,
            'password' => app('hash')->make($request->password), //Encrypt password
            'phone_number' => $request->phone_number,
            // 'api_token' => 'test', //Should be empty, when users login, its automation filled
            'role' => $request->role,
            'status' => $request->status
        ]);
        return response()->json(['status' => 'success']);
    }

    public function edit($id)
    {
        //Get data by ID
        $user = User::find($id);
        //return json format
        return response()->json(['status' => 'success', 'data' => $user]);
    }

    public function update(Request $request, $id)
    {
        //Validation
        $this->validate($request, [
            'name' => 'required|string|max:50',
            'identity_id' => 'required|string|unique:users,identity_id,' . $id,
            'gender' => 'required',
            'address' => 'required|string',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|min:6',
            'phone_number' => 'required|string',
            'role' => 'required',
            'status' => 'required',
        ]);

        $user = User::find($id); //Get user data

        //If empty keep current password, else encrypt new password
        $password = $request->password != '' ? app('hash')->make($request->password):$user->password;

        //Get filename from DB
        $filename = $user->photo;
        //if theres file
        if ($request->hasFile('photo')) {
            //Generate name and save new file
            $filename = Str::random(5) . $user->email . '.jpg';
            $file = $request->file('photo');
            $file->move(base_path('public/images'), $filename); //
            //Delete old file
            unlink(base_path('public/images/' . $user->photo));
        }

        //Then update user data
        $user->update([
            'name' => $request->name,
            'identity_id' => $request->identity_id,
            'gender' => $request->gender,
            'address' => $request->address,
            'photo' => $filename,
            'password' => $password,
            'phone_number' => $request->phone_number,
            'role' => $request->role,
            'status' => $request->status
        ]);
        return response()->json(['status' => 'success']);
    }

    public function destroy($id)
    {
        $user = User::find($id);
        if ($user->photo) {
            unlink(base_path('public/images/' . $user->photo));
        }
        $user->delete();
        return response()->json(['status' => 'success']);
    }

    public function login(Request $request)
    {
        //User Input validation
        //Email must be exist in DB & Password min. 6 char
        $this->validate($request, [
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:6'
        ]);

        //Find user by email
        $user = User::where('email', $request->email)->first();
        //If theres, then check password
        //Comparing encrypted pass] with plain text, using facede check
        if ($user && Hash::check($request->password, $user->password)) {
            $token = Str::random(40); //GENERATE TOKEN BARU
            $user->update(['api_token' => $token]); //UPDATE USER TERKAIT
            //Return token to client
            return response()->json(['status' => 'success', 'data' => $token]);
        }
        //If false, return error
        return response()->json(['status' => 'error']);
    }

    public function sendResetToken(Request $request)
    {
        //Validate existing email
        $this->validate($request, [
            'email' => 'required|email|exists:users'
        ]);

        //Get user data by email
        $user = User::where('email', $request->email)->first();
        //Then generate token
        $user->update(['reset_token' => Str::random(40)]);

        //Send token to email for authentication
        Mail::to($user->email)->send(new ResetPasswordMail($user));

        return response()->json(['status' => 'success', 'data' => $user->reset_token]);
    }

    public function verifyResetPassword(Request $request, $token)
    {
        //Password Validation
        $this->validate($request, [
            'password' => 'required|string|min:6'
        ]);

        //Find user by accepted token
        $user = User::where('reset_token', $token)->first();
        //If theres data
        if ($user) {
            //Update password
            $user->update(['password' => app('hash')->make($request->password)]);
            return response()->json(['status' => 'success']);
        }
        return response()->json(['status' => 'error']);
    }

    public function getUserLogin(Request $request)
    {
        return response()->json(['status' => 'success', 'data' => $request->user()]);
    }

    public function logout(Request $request)
    {
        $user = $request->user(); //Get  logged in user
        $user->update(['api_token' => null]); //Update value to null
        return response()->json(['status' => 'success']);
    }
}
