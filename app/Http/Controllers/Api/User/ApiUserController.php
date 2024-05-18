<?php

namespace App\Http\Controllers\Api\User;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\ExtController;

class ApiUserController extends ExtController
{
    public function index(Request $request){
        
        $user_list = User::query()
        ->get()
        ->toArray();

        return $this->send_success($user_list);
    }

    public function show(Request $request, $id){
        
        $user_list = User::query()
        ->where("remember_token", "=", $id)
        ->get();

        return $this->send_success($user_list[0]);
    }
    
    public function login(Request $request){

        if(isset($request->email)){

            // Authorization
            $request->validate([
                'name' => 'required',
                'email' => 'required',
                'password' => 'required'
            ]);
            $user_password = User::query()
            ->where("name", "=", $request->name)
            ->where("email", "=", $request->email)
            ->get()
            ->pluck("password")
            ->toArray();

            // If password exists
            if(count($user_password) >= 1){
                $user_password = $user_password[0];

                // If password isn't null & password is correct
                if(isset($user_password) && Hash::check($request->password, $user_password)){
                    $user = User::query()
                    ->where("name", "=", $request->name)
                    ->where("email", "=", $request->email)
                    ->get()
                    ->pluck("remember_token")
                    ->toArray()[0];
                    $request->session()->put("token", $user);
                    return redirect("/");
                    // return $this->send_success("You have logged in.");
                }
            }

        }

        return $this->send_unauthorized("Incorrect email or password");

        
    }

    public function register(Request $request){

        
        $fields = $request->validate([
            'name' => 'required|string',
            'telephone' => 'required|integer',
            'image' => ['image', 'mimes:jpg', 'dimensions:max_width=1000,max_height=`1000'],
            'password' => 'required',
            'school_name' => 'required',
        ]);

        $name = $fields["name"];
        $password = bcrypt($fields["password"]);
        //$token = sha1($password);
        $school_name = $fields["school_name"];
        $telephone = $fields["telephone"];
            
        $img = $request->file('image');
        /*
        $name = filter_input(INPUT_POST, $request->name, FILTER_SANITIZE_SPECIAL_CHARS);
        $password = Hash::make(filter_input(INPUT_POST, $request->password, FILTER_SANITIZE_SPECIAL_CHARS));
        $token = sha1($password);
        */
        // $email = filter_input(INPUT_POST, $request->email, FILTER_SANITIZE_EMAIL);
        
        echo 'File Name: '.$img->getClientOriginalName();
        echo '<br>';
        
        // ekstensi file
        echo 'File Extension: '.$img->getClientOriginalExtension();
        echo '<br>';
        
        // real path
        echo 'File Real Path: '.$img->getRealPath();
        echo '<br>';
        
        // ukuran file
        echo 'File Size: '.$img->getSize();
        echo '<br>';
        
        // tipe mime
        echo 'File Mime Type: '.$img->getMimeType();

        $destination = "uploads/profile_pic";

        $user = User::create([
            "name" => $name,
            "password" => $password,
            //"remember_token" => $token,
            //"image" => "uploads/profile_pic/$name.jpg",
            "school_name" => $school_name,
            "telephone" => $telephone,
        ]);

        // Login
        auth()->login($user);

        if ($img->move($destination, $name.".jpg")){
            return "File Upload Success";
        }
        else{
            return "Failed to upload file";
        }
        
    }

    public function edit(Request $request){
        $name = $request->name;
        $password = Hash::make($request->password);
        $token = sha1($password);
        $email = $request->email;

        User::query()
        ->where("id", "=", User::query()
            ->where("remember_token", "=", $request->token)
            ->get()
            ->pluck("id")
            ->toArray()[0]
        )
        ->take(1)
        ->update([
            "name" => $name,
            "password" => $password,
            "remember_token" => $token,
            "email" => $email,
        ]);
        return $this->send_success("Edit complete.");
    
    }

    public function logout(Request $request){
        $request->session()->forget("token");

        return $this->send_success("You have logged out.");
    }

    public function delete(Request $request){
        $request->session()->forget("token");
        $product = User::query()
        ->where("remember_token", "=", $request->token)
        ->take(1)
        ->delete();
        return $this->send_success("Your account has been deleted");
    }
}
